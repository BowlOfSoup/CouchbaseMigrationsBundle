<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Repository;

use BowlOfSoup\CouchbaseMigrationsBundle\Factory\BucketFactory;
use Couchbase\Cluster;
use Couchbase\QueryOptions;

class BucketRepository
{
    private BucketFactory $bucketFactory;

    private Cluster $cluster;

    public function __construct(
        BucketFactory $bucketFactory,
        Cluster $cluster,
    ) {
        $this->bucketFactory = $bucketFactory;
        $this->cluster = $cluster;
    }

    /**
     * Select data from documents in a bucket.
     *
     * This method filters the results by name of the bucket.
     * Use query() method when querying cross-buckets.
     */
    public function select(string $query, array $params = []): array
    {
        $result = $this->query($query, $params);

        return array_map(
            function ($row) {
                if (1 === count($row)) {
                    return reset($row);
                }

                if (array_key_exists($this->bucketFactory->getBucketName(), $row)) {
                    return $row[$this->bucketFactory->getBucketName()];
                }

                return $row;
            },
            $result->rows
        );
    }

    /**
     * Execute a N1ql query with named parameter support.
     */
    public function query(string $query, array $params = []): array
    {
        $queryOptions = new QueryOptions();
        if (empty($params) === false) {
            $queryOptions->namedParameters($params);
        }

        return $this->cluster->query($query, $queryOptions)->rows();
    }
}
