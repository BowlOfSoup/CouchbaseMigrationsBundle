<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Factory;

use Couchbase\Bucket;

class BucketFactory
{
    const BUCKET_MIGRATIONS = 'migrations';

    private ClusterFactory $clusterFactory;

    private string $bucketName;

    private ?Bucket $bucket = null;

    public function __construct(ClusterFactory $clusterFactory, string $bucketName)
    {
        $this->clusterFactory = $clusterFactory;
        $this->bucketName = $bucketName;
    }

    public function getBucketName(): string
    {
        return $this->bucketName;
    }

    public function getBucket(): Bucket
    {
        if (null !== $this->bucket) {
            return $this->bucket;
        }

        $this->bucket = $this->clusterFactory->getCluster()->bucket($this->bucketName);

        return $this->bucket;
    }
}
