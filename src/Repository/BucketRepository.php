<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Repository;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory;
use Couchbase\N1qlQuery;

class BucketRepository
{
    const RESULT_AS_ARRAY = true;

    /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory */
    private $bucketFactory;

    /** @var \Couchbase\Bucket */
    private $bucket;

    /**
     * @param \BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory $bucketFactory
     *
     * @throws \BowlOfSoup\CouchbaseMigrationsBundle\Exception\BucketNoAccessException
     */
    public function __construct(
        BucketFactory $bucketFactory
    ) {
        $this->bucketFactory = $bucketFactory;
        $this->bucket = $bucketFactory->getBucket();
    }

    /**
     * Select data from documents in a bucket.
     *
     * This method filters the results by name of the bucket.
     * Use query() method when querying cross-buckets.
     *
     * @param string $query
     * @param array $params
     *
     * @return array
     */
    public function select(string $query, array $params = [])
    {
        $result = $this->query($query, $params);

        return array_map(
            function ($row) {
                return $row[$this->bucketFactory->getBucketName()];
            },
            $result->rows
        );
    }

    /**
     * Execute a N1ql query with named parameter support.
     *
     * @param string $query
     * @param array $params
     *
     * @return object|array
     */
    public function query(string $query, array $params = [])
    {
        $query = N1qlQuery::fromString($query);
        $query->namedParams($params);

        return $this->bucket->query($query, static::RESULT_AS_ARRAY);
    }
}
