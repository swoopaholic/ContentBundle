<?php
/**
 * Created by PhpStorm.
 * User: danny
 * Date: 11/03/14
 * Time: 20:07
 */

namespace Swoopaholic\Bundle\ContentBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SetRouterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->setAlias('router', 'swp_content.router');
    }
}