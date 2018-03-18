<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('couchbase_migration');

        $rootNode
            ->children()
            ->scalarNode('host')->isRequired()->end()
            ->scalarNode('user')->isRequired()->end()
            ->scalarNode('password')->isRequired()->end()
            ->scalarNode('bucket_migrations')->defaultValue('migrations')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
