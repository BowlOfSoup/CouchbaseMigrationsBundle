<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Exception;

use Couchbase\Exception\CouchbaseException;

class BucketNoAccessException extends CouchbaseException
{
    const BUCKET_MIGRATIONS_CANNOT_BE_ACCESSED = 'The bucket for keeping track of migrations (name: %s) does not exist, or your credentials are invalid.';
    const BUCKET_CANNOT_BE_ACCESSED = 'The requested bucket \'%s\' does not exist, or your credentials are invalid.';
}
