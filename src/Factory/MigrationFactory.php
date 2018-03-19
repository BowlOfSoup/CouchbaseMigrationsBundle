<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Factory;

use BowlOfSoup\CouchbaseMigrationsBundle\Migration\AbstractMigration;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

class MigrationFactory
{
    /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory */
    private $clusterFactory;

    /**
     * @param \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory $clusterFactory
     */
    public function __construct(ClusterFactory $clusterFactory)
    {
        $this->clusterFactory = $clusterFactory;
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $file
     *
     * @return \BowlOfSoup\CouchbaseMigrationsBundle\Migration\AbstractMigration
     *
     * @throws \InvalidArgumentException
     */
    public function createByFile(SplFileInfo $file): AbstractMigration
    {
        if ($file->getExtension() !== 'php') {
            throw new \InvalidArgumentException('File ' . $file->getPath() . ' does not have a .php extension');
        }

        require_once $file->getRealPath();
        $className = str_replace('.php', '', $file->getFilename());
        $reflectionInstance = new ReflectionClass($className);

        if(!$reflectionInstance->isSubclassOf(AbstractMigration::class)) {
            throw new \InvalidArgumentException('Class '.$className.' does not extend '.AbstractMigration::class);
        }

        return new $className($this->clusterFactory);
    }
}
