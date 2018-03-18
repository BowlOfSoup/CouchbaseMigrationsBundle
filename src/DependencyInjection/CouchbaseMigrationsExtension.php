<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CouchbaseMigrationsExtension extends Extension
{
    /**
     * @param array $configs
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('couchbase_migration.bucket_migrations', (string) $config['bucket_migrations']);
        $container->setParameter('couchbase_migration.host', (string) $config['host']);
        $container->setParameter('couchbase_migration.user', (string) $config['user']);
        $container->setParameter('couchbase_migration.password', (string) $config['password']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yml');
    }
}
