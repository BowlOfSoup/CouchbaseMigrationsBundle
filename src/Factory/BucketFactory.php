<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Factory;

use BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException;
use Couchbase\Bucket;
use Couchbase\Exception;

class BucketFactory
{
    const BUCKET_MIGRATIONS = 'migrations';

    /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory */
    private $clusterFactory;

    /** @var string */
    private $bucketName;

    /** @var \Couchbase\Bucket */
    private $bucket;

    /**
     * @param \BowlOfSoup\CouchbaseMigrationsBundle\Factory\ClusterFactory $clusterFactory
     * @param string $bucketName
     */
    public function __construct(ClusterFactory $clusterFactory, string $bucketName)
    {
        $this->clusterFactory = $clusterFactory;
        $this->bucketName = $bucketName;
    }

    /**
     * @return string
     */
    public function getBucketName()
    {
        return $this->bucketName;
    }

    /**
     * @return \Couchbase\Bucket
     *
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
     */
    public function getBucket(): Bucket
    {
        if (null !== $this->bucket) {
            return $this->bucket;
        }

        try {
            $this->bucket = $this->clusterFactory->getCluster()->openBucket($this->bucketName);
        } catch (Exception $e) {
            throw new BucketNoAccessException(sprintf(BucketNoAccessException::BUCKET_CANNOT_BE_ACCESSED, $this->bucketName));
        }

        return $this->bucket;
    }
}
