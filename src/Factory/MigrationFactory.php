<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Factory;

use BowlOfSoup\CouchbaseMigrationsBundle\Migration\AbstractMigration;
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
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     *
     * @return \BowlOfSoup\CouchbaseMigrationsBundle\Migration\AbstractMigration
     */
    public function createByFile(SplFileInfo $file): AbstractMigration
    {
        if ('php' !== $file->getExtension()) {
            throw new \InvalidArgumentException('File ' . $file->getPath() . ' does not have a .php extension');
        }

        require_once $file->getRealPath();
        $className = str_replace('.php', '', $file->getFilename());
        $reflectionInstance = new \ReflectionClass($className);

        if (!$reflectionInstance->isSubclassOf(AbstractMigration::class)) {
            throw new \InvalidArgumentException('Class ' . $className . ' does not extend ' . AbstractMigration::class);
        }

        return new $className($this->clusterFactory);
    }
}
