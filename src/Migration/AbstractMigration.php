<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Migration;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Repository\BucketRepository;

abstract class AbstractMigration
{
    /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory */
    protected $clusterFactory;

    /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Repository\BucketRepository */
    protected $bucketRepository;

    /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory */
    private $bucketFactory;

    /**
     * @param \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory $clusterFactory
     */
    public function __construct(
        ClusterFactory $clusterFactory
    ) {
        $this->clusterFactory = $clusterFactory;
    }

    /**
     * Up migration.
     */
    abstract public function up();

    /**
     * @param string $bucketName
     *
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
     *
     * @return \Couchbase\Bucket
     */
    protected function selectBucket(string $bucketName = null)
    {
        if (null === $bucketName) {
            $bucketName = $this->clusterFactory->getDefaultBucketName();
        }

        if (null !== $this->bucketFactory && $this->bucketFactory->getBucketName() === $bucketName) {
            return $this->bucketFactory->getBucket();
        }

        $this->bucketFactory = new BucketFactory($this->clusterFactory, $bucketName);
        $this->bucketRepository = new BucketRepository($this->bucketFactory);

        return $this->bucketFactory->getBucket();
    }
}
