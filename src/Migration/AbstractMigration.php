<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Migration;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseMigrationsBundle\Model\Bucket;
use BowlOfSoup\CouchbaseMigrationsBundle\Repository\BucketRepository;

abstract class AbstractMigration
{
    protected ClusterFactory $clusterFactory;

    protected ?BucketRepository $bucketRepository;

    private ?BucketFactory $bucketFactory = null;

    public function __construct(
        ClusterFactory $clusterFactory
    ) {
        $this->clusterFactory = $clusterFactory;
    }

    /**
     * Up migration.
     */
    abstract public function up(): void;

    /**
     * Down migration.
     */
    public function down(): void
    {
    }

    public function selectBucket(string $bucketName = null): Bucket
    {
        if (null === $bucketName) {
            $bucketName = $this->clusterFactory->getDefaultBucketName();
        }

        if (null !== $this->bucketFactory && $this->bucketFactory->getBucketName() === $bucketName) {
            return new Bucket($this->bucketFactory->getBucket());
        }

        $this->bucketFactory = new BucketFactory($this->clusterFactory, $bucketName);
        $this->bucketRepository = new BucketRepository($this->bucketFactory, $this->clusterFactory->getCluster());

        return new Bucket($this->bucketFactory->getBucket());
    }
}
