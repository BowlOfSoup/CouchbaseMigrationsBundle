<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CouchbaseMigrationsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $port = is_numeric($config['port']) ? (int) $config['port'] : null;

        $container->setParameter('couchbase_migrations.bucket_default', (string) $config['bucket_default']);
        $container->setParameter('couchbase_migrations.bucket_migrations', (string) $config['bucket_migrations']);
        $container->setParameter('couchbase_migrations.host', (string) $config['host']);
        $container->setParameter('couchbase_migrations.user', (string) $config['user']);
        $container->setParameter('couchbase_migrations.password', (string) $config['password']);
        $container->setParameter('couchbase_migrations.port', $port);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yml');
    }
}
