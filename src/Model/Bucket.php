<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseMigrationsBundle\Model;

use Couchbase\Bucket as CouchbaseBucket;

/**
 * This class is a small backwards compatibility layer to keep old migrations from breaking
 */
class Bucket
{
    private CouchbaseBucket $bucket;

    public function __construct(CouchbaseBucket $bucket)
    {
        $this->bucket = $bucket;
    }

    public function get(string $id)
    {
        return $this->bucket->defaultCollection()->get($id);
    }

    public function getBucket(): CouchbaseBucket
    {
        return $this->bucket;
    }
}