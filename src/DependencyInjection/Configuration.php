<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\DependencyInjection;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('couchbase_migrations');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('host')->isRequired()->end()
            ->scalarNode('user')->isRequired()->end()
            ->scalarNode('password')->isRequired()->end()
            ->scalarNode('port')->defaultNull()->end()
            ->scalarNode('bucket_default')->isRequired()->end()
            ->scalarNode('bucket_migrations')->defaultValue(BucketFactory::BUCKET_MIGRATIONS)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
