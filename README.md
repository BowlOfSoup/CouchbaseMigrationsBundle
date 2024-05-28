Couchbase Migrations Bundle
===========================

With this Symfony bundle you can generate and execute migrations on a Couchbase database.
It works kind of like [Doctrine Migrations](https://github.com/doctrine/migrations).
* Generate blank migrations and fill them to e.g. make new indexes on buckets or upsert/remove documents.
* It keeps in check which migrations that have already been executed and which still need to be done.
* Usable via Symfony commands.

Prerequisites
-------------
* PHP 8.3 or higher with Couchbase extension

Installation and setup
----------------------
Install the bundle via composer.

    composer require bowlofsoup/couchbase-migrations-bundle

Add the bundle to your `AppKernel.php`.

    $bundles = [
        ...
        new \BowlOfSoup\CouchbaseMigrationsBundle\CouchbaseMigrationsBundle()
        ...
    ];
    
Create a `CouchbaseMigrations` directory in your `app` directory. This will contain all the generated blank migrations.

    $ mkdir CouchbaseMigrations
    
Add the `config/packages/couchbase_migrations_bundle.yaml`.

    couchbase_migrations:
        host: '%env(COUCHBASE_MIGRATIONS_HOST)%'
        user: '%env(COUCHBASE_MIGRATIONS_USER)%'
        password: '%env(COUCHBASE_MIGRATIONS_PASSWORD)%'
        bucket_migrations: '%env(COUCHBASE_MIGRATIONS_BUCKET_MIGRATIONS)%'
        bucket_default: '%env(COUCHBASE_MIGRATIONS_DEFAULT_BUCKET)%'

In your .env file, define the above values:

    COUCHBASE_MIGRATIONS_HOST="127.0.0.1"
    COUCHBASE_MIGRATIONS_USER="couchbase_user"
    COUCHBASE_MIGRATIONS_PASSWORD="couchbase_password"
    COUCHBASE_MIGRATIONS_BUCKET_MIGRATIONS="default"
    COUCHBASE_MIGRATIONS_DEFAULT_BUCKET="default"

* **`bucket_migrations`** must hold the bucket in which you want to store which migration has already been done.
Not mandatory, but then a bucket named `migrations` must be accessible.
* **`bucket_default`** must hold your default bucket.
If you do not have a default bucket, use `%couchbase_migrations.bucket_migrations%` as option.
        
Usage
-----

#### Generate migration

    bin/console couchbase:migrations:generate

This will generate a blank migration file in `CouchbaseMigrations/` for you to fill.

#### Migrate all

    bin/console couchbase:migrations:migrate

This will execute all migrations that still need to be done.

The configured bucket (`bucket_migrations` in the config file) will contain a document (`migrations::versions`) which contains the already migrated.

#### Execute a single migration

    bin/console couchbase:migrations:execute VERSION_NUMBER [--no-verbose] [--down]
    
This will execute the given version (file in `CouchbaseMigrations/`).
Replace `VERSION_NUMBER` with the version (**date-time part** of the file) you want to execute.
You can execute a version indefinitely: will not be kept track of.

#### Flush all data in a bucket, except the migrations

    bin/console couchbase:migrations:flush-data BUCKET_NAME

Flushes all the data in a bucket, if flushing is enabled for that bucket, except the migrations version document.
Replace `BUCKET_NAME` with the name of the bucket for which you want the data to be flushed. Defaults to the configured bucket.

Can be handy if you want to reset all data in a bucket, but do not want to lose your migrations.

#### Create a bucket

    bin/console couchbase:migrations:create-bucket [BUCKET_NAME] [--index]

Creates a bucket, optional name (takes default configured) and optionally creating a primary index.

#### Remove a bucket

    bin/console couchbase:migrations:remove-bucket [BUCKET_NAME]

Creates a bucket, optional name (takes default configured).


How to write a migration
------------------------
When you have generated a migration, open the file and use the `up` function. Example:

```
public function up()
{
    // Use only if you want to select a different bucket than the one configured.
    $this->selectBucket('some-other-bucket-name');

    $this->bucketRepository->query(
        'CREATE INDEX `i_someindexname` ON `bucketname`(`propertyname`) WHERE (`someotherpropertyname` = "propertycontent")'
    );
}
```

or

```
public function up()
{
    // Use only if you want to select a different bucket than the one configured.
    $bucket = $this->selectBucket('some-other-bucket-name');

    $result = $bucket->get('some-document-key');
    $documentContent = $result->value;

    $bucket->insert('some-other-document-key', $documentContent);
}
```

Downgrading is also supported for the **execute** command, just add a method `down()` to the migration.

```
public function down()
{
    $this->bucketRepository->query(
        'the opposite of the up() query'
    );
}
```

So:
* The `$this->bucketRepository` can be used to make it easier to do queries on a bucket (like named parameters).
* You can also directly do actions on the bucket by using the return value of `$this->selectBucket()`.

Note: The Bucket returned by `selectBucket()` and the result returned by `$bucket->get()` are both small backwards-
compatible classes to not break old migrations defined with the previous version of this bundle. If you need the actual
Couchbase Bucket, you can use `$bucket->getBucket()`, if you need the actual Couchbase result you can get that by calling
`$result->getResult()`.

How to use as standalone application
------------------------------------
You can use this bundle as standalone application, so, not use it within a Symfony installation.
This is also perfect for development.

* Checkout this repository and create an `app` directory within the `src` directory.
* Create a `src/CouchbaseMigrations` directory.
* Create a `src/app/parameters.yml` which holds the config parameters.

Fill the `src/app/parameters.yml` file with:

    parameters:
      kernel.project_dir:

      couchbase_bucket:

      couchbase_migrations.bucket_migrations:
      couchbase_migrations.host:
      couchbase_migrations.user:
      couchbase_migrations.password:

* The `kernel.project_dir` option must hold the full path the the `src` directory.
* See 'Installation and setup' for more info on the options.

Contributing
------------
You are more then welcome to fork this repository, make changes and create a pull request back.

* Create an issue and state the changes you want to make.
* In your commit messages, refer to this issue.
* Be sure to run `vendor/bin/php-cs-fixer fix` before you commit code changes. This will make the changed code adhere to the coding standards.
