<?php
/**
 * Created by PhpStorm.
 * User: danny
 * Date: 12/03/14
 * Time: 15:34
 */

namespace Swoopaholic\Bundle\ContentBundle;

use Swoopaholic\Bundle\ContentBundle\DependencyInjection\Compiler\SetRouterPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwpContentBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
//        $container->addCompilerPass(new RegisterRoutersPass());
//        $container->addCompilerPass(new RegisterRouteEnhancersPass());
        $container->addCompilerPass(new SetRouterPass());
    }

} 