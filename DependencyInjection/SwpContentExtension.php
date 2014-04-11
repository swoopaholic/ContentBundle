<?php

namespace Swoopaholic\Bundle\ContentBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

class SwpContentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $this->setupDynamicRouter($config['dynamic'], $container, $loader);
        $loader->load('routing-chain.xml');

        $container->setParameter($this->getAlias() . '.replace_symfony_router', $config['chain']['replace_symfony_router']);

        // add the routers defined in the configuration mapping
        $router = $container->getDefinition($this->getAlias() . '.router');
        foreach ($config['chain']['routers_by_id'] as $id => $priority) {
            $router->addMethodCall('add', array(new Reference($id), trim($priority)));
        }
    }

    /**
     * Set up the DynamicRouter - only to be called if enabled is set to true
     *
     * @param array            $config    the compiled configuration for the dynamic router
     * @param ContainerBuilder $container the container builder
     * @param LoaderInterface  $loader    the configuration loader
     */
    private function setupDynamicRouter(array $config, ContainerBuilder $container, LoaderInterface $loader)
    {
        // strip whitespace (XML support)
        foreach (array('controllers_by_type', 'controllers_by_class', 'templates_by_class', 'route_filters_by_id') as $option) {
            $config[$option] = array_map(function ($value) {
                return trim($value);
            }, $config[$option]);
        }

        $defaultController = $config['default_controller'];
        if (null === $defaultController) {
            $defaultController = $config['generic_controller'];
        }
        $container->setParameter($this->getAlias() . '.default_controller', $defaultController);
        $container->setParameter($this->getAlias() . '.generic_controller', $config['generic_controller']);
        $container->setParameter($this->getAlias() . '.controllers_by_type', $config['controllers_by_type']);
        $container->setParameter($this->getAlias() . '.controllers_by_class', $config['controllers_by_class']);
        $container->setParameter($this->getAlias() . '.templates_by_class', $config['templates_by_class']);
        $container->setParameter($this->getAlias() . '.uri_filter_regexp', $config['uri_filter_regexp']);
        $container->setParameter($this->getAlias() . '.route_collection_limit', $config['route_collection_limit']);

        $locales = false;
        if (isset($config['locales'])) {
            $locales = $config['locales'];
            $container->setParameter($this->getAlias() . '.dynamic.locales', $locales);
        }

        $loader->load('routing-dynamic.xml');

        $hasProvider = false;
        $hasContentRepository = false;
        if ($config['persistence']['phpcr']['enabled'] && $config['persistence']['orm']['enabled']) {
            throw new InvalidConfigurationException('You can only enable either phpcr or orm, not both.');
        }

        if ($config['persistence']['phpcr']['enabled']) {
            $this->loadPhpcrProvider($config['persistence']['phpcr'], $loader, $container, $locales);
            $hasProvider = true;
            $hasContentRepository = true;
        }

        if ($config['persistence']['orm']['enabled']) {
            $this->loadOrmProvider($config['persistence']['orm'], $loader, $container);
            $hasProvider = true;
        }

        if (isset($config['route_provider_service_id'])) {
            $container->setAlias($this->getAlias() . '.route_provider', $config['route_provider_service_id']);
            $hasProvider = true;
        }
        if (!$hasProvider) {
            throw new InvalidConfigurationException('When the dynamic router is enabled, you need to either enable one of the persistence layers or set the cmf_routing.dynamic.route_provider_service_id option');
        }
        if (isset($config['content_repository_service_id'])) {
            $container->setAlias($this->getAlias() . '.content_repository', $config['content_repository_service_id']);
            $hasContentRepository = true;
        }
        // content repository is optional
        if ($hasContentRepository) {
            $generator = $container->getDefinition($this->getAlias() . '.generator');
            $generator->addMethodCall('setContentRepository', array(
                new Reference($this->getAlias() . '.content_repository'),
            ));
        }

        $dynamic = $container->getDefinition($this->getAlias() . '.dynamic_router');

        // if any mappings are defined, set the respective route enhancer
        if (!empty($config['controllers_by_type'])) {
            $dynamic->addMethodCall(
                'addRouteEnhancer',
                array(
                    new Reference($this->getAlias() . '.enhancer.controllers_by_type'),
                    60
                )
            );
        }
        if (!empty($config['controllers_by_class'])) {
            $dynamic->addMethodCall(
                'addRouteEnhancer',
                array(
                    new Reference($this->getAlias() . '.enhancer.controllers_by_class'),
                    50
                )
            );
        }
        if (!empty($config['templates_by_class'])) {
            $dynamic->addMethodCall(
                'addRouteEnhancer',
                array(
                    new Reference($this->getAlias() . '.enhancer.templates_by_class'),
                    40
                )
            );

            /*
             * The CoreBundle prepends the controller from ContentBundle if the
             * ContentBundle is present in the project.
             * If you are sure you do not need a generic controller, set the field
             * to false to disable this check explicitly. But you would need
             * something else like the default_controller to set the controller,
             * as no controller will be set here.
             */
            if (null === $config['generic_controller']) {
                throw new InvalidConfigurationException('If you want to configure templates_by_class, you need to configure the generic_controller option.');
            }

            if (is_string($config['generic_controller'])) {
                // if the content class defines the template, we also need to make sure we use the generic controller for those routes
                $controllerForTemplates = array();
                foreach ($config['templates_by_class'] as $key => $value) {
                    $controllerForTemplates[$key] = $config['generic_controller'];
                }

                $definition = $container->getDefinition($this->getAlias() . '.enhancer.controller_for_templates_by_class');
                $definition->replaceArgument(2, $controllerForTemplates);

                $dynamic->addMethodCall(
                    'addRouteEnhancer',
                    array(
                        new Reference($this->getAlias() . '.enhancer.controller_for_templates_by_class'),
                        30
                    )
                );
            }
        }
        if (!empty($config['generic_controller']) && $config['generic_controller'] !== $defaultController) {
            $dynamic->addMethodCall(
                'addRouteEnhancer',
                array(
                    new Reference($this->getAlias() . '.enhancer.explicit_template'),
                    10
                )
            );
        }
        if ($defaultController) {
            $dynamic->addMethodCall(
                'addRouteEnhancer',
                array(
                    new Reference($this->getAlias() . '.enhancer.default_controller'),
                    -100
                )
            );
        }

        if (!empty($config['route_filters_by_id'])) {
            $matcher = $container->getDefinition($this->getAlias() . '.nested_matcher');
            foreach ($config['route_filters_by_id'] as $id => $priority) {
                $matcher->addMethodCall('addRouteFilter', array(new Reference($id), $priority));
            }
        }
    }
}

