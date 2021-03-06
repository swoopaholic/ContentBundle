<?php
/**
 * Created by PhpStorm.
 * User: danny
 * Date: 12/03/14
 * Time: 16:03
 */

namespace Swoopaholic\Bundle\ContentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * Returns the config tree builder.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('swp_content')
            ->children()
            ->arrayNode('chain')
            ->addDefaultsIfNotSet()
            ->fixXmlConfig('router_by_id', 'routers_by_id')
            ->children()
            ->arrayNode('routers_by_id')
            ->defaultValue(array('router.default' => 100))
            ->useAttributeAsKey('id')
            ->prototype('scalar')->end()
            ->end()
            ->booleanNode('replace_symfony_router')->defaultTrue()->end()
            ->end()
            ->end()
            ->arrayNode('dynamic')
            ->fixXmlConfig('controller_by_type', 'controllers_by_type')
            ->fixXmlConfig('controller_by_class', 'controllers_by_class')
            ->fixXmlConfig('template_by_class', 'templates_by_class')
            ->fixXmlConfig('route_filter_by_id', 'route_filters_by_id')
            ->fixXmlConfig('locale')
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
            ->children()
            ->scalarNode('route_collection_limit')->defaultNull()->end()
            ->scalarNode('generic_controller')->defaultNull()->end()
            ->scalarNode('default_controller')->defaultNull()->end()
            ->arrayNode('controllers_by_type')
            ->useAttributeAsKey('type')
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('controllers_by_class')
            ->useAttributeAsKey('class')
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('templates_by_class')
            ->useAttributeAsKey('class')
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('persistence')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('phpcr')
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
            ->children()
            ->scalarNode('manager_name')->defaultNull()->end()
            ->scalarNode('route_basepath')->defaultValue('/cms/routes')->end()
            ->scalarNode('content_basepath')->defaultValue('/cms/content')->end()
            ->enumNode('use_sonata_admin')
            ->beforeNormalization()
            ->ifString()
            ->then(function ($v) {
                switch ($v) {
                    case 'true':
                        return true;

                    case 'false':
                        return false;

                    default:
                        return $v;
                }
            })
            ->end()
            ->values(array(true, false, 'auto'))
            ->defaultValue('auto')
            ->end()
            ->end()
            ->end()
            ->arrayNode('orm')
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
            ->children()
            ->scalarNode('manager_name')->defaultNull()->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->scalarNode('uri_filter_regexp')->defaultValue('')->end()
            ->scalarNode('route_provider_service_id')->end()
            ->arrayNode('route_filters_by_id')
            ->canBeUnset()
            ->useAttributeAsKey('id')
            ->prototype('scalar')->end()
            ->end()
            ->scalarNode('content_repository_service_id')->end()
            ->arrayNode('locales')
            ->prototype('scalar')->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }

} 