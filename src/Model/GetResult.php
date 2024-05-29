<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseMigrationsBundle\Model;

use Couchbase\GetResult as CouchbaseGetResult;

/**
 * This class is a small backwards compatibility layer to keep old migrations from breaking
 */
class GetResult
{
    private CouchbaseGetResult $getResult;

    public $value;

    public function __construct(CouchbaseGetResult $getResult)
    {
        $this->getResult = $getResult;
        $this->value = $getResult->content();
    }

    public function getResult(): CouchbaseGetResult
    {
        return $this->getResult;
    }

}