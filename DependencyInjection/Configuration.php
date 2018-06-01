<?php
/**
 * Created by PhpStorm.
 * User: SteveWinter
 * Date: 10/04/2017
 * Time: 15:30
 */

namespace MSDev\FileMakerDataAPIBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('file_maker_data_api');
        $rootNode
            ->children()
                ->scalarNode('valuelist_layout')
                    ->defaultValue(false)
                    ->end()
                ->scalarNode('javascript_translations')
                    ->defaultValue(false)
                    ->end()
            ->end();

        return $treeBuilder;
    }
}