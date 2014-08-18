<?php

namespace Pirminis\GatewayBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pirminis_gateway');

        $rootNode
            ->children()
                ->arrayNode('swedbank')
                    ->isRequired()
                    ->children()
                        ->scalarNode('vtid')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->info('Account vTID/client ID supplied by Swedbank.')
                            ->example('01234567')
                            ->end()
                        ->scalarNode('password')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->info('Account password supplied by Swedbank.')
                            ->example('012345QWErty')
                            ->end()
                        ->end()
                    ->end()
                ->end()
        ;

        return $treeBuilder;
    }
}
