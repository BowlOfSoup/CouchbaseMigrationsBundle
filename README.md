Couchbase Migrations Bundle
===========================

With this Symfony bundle you can generate and execute migrations a Couchbase database. 
It works kind of like [Doctrine Migrations](https://github.com/doctrine/migrations).
* Generate blank migrations and fill them to e.g. make new indexes on buckets or upsert/remove documents.
* It keeps in check which migrations have already been executed and which still need to be done.
* Usable via Symfony commands.

Prerequisites
-------------
* PHP 7.0 or higher
* Couchbase SDK for PHP installed ([How to install](https://developer.couchbase.com/documentation/server/current/sdk/php/start-using-sdk.html)).
* symfony/symfony 3.4.*

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

    $ mkdir app/CouchbaseMigrations
    
Add the correct parameters in `app/config/config.yml`. 

    couchbase_migrations:
        host:
        user:
        password:
        bucket_migrations:
        bucket_default:

* **`bucket_migrations`** must hold the bucket in which you want to store which migration has already been done.
Not mandatory, but then a bucket named `migrations` must be accessible.
* **`bucket_default`** must hold your default bucket.
If you do not have a default bucket, use `%couchbase_migrations.bucket_migrations%` as option.
        
Usage
-----

#### Generate migraton

    bin/console couchbase:migrations:generate

This will generate a blank migration file in `app/CouchbaseMigrations` for you to fill.

#### Migrate all

    bin/console couchbase:migrations:migrate

This will execute all migrations that still need to be done.

The configured bucket (`bucket_migrations` in the config file) will contain a document (`migrations::versions`) which contains the already migrated.

#### Execute a single migration

    bin/console couchbase:migrations:execute VERSION_NUMBER
    
This will execute the given version (file in `app/CouchbaseMigrations`).
Replace `VERSION_NUMBER` with the version (**date-time part** of the file) you want to execute.
You can execute a version indefinitely: will not be kept track of.

How to write a migration
------------------------
When you have generated a migration, open the file and use the `up` function. Example:

```
public function up()
{
    $this->selectBucket(); // If you do not pass a bucket name, the default bucket will be used.

    $this->bucketRepository->query(
        'CREATE INDEX `i_someindexname` ON `bucketname`(`propertyname`) WHERE (`someotherpropertyname` = "propertycontent")'
    );
}
```

If you want to use your default bucket in the above query, use:

```
$this->bucketRepository->query(
    CREATE INDEX `i_someindexname` ON `' . $this->clusterFactory->getDefaultBucketName() . '`(`propertyname`) WHERE (`someotherpropertyname` = "propertycontent")
);
```

* The `$this->bucketRepository` can be used to make it easier to do queries on a bucket (like named parameters).
* You can also directly do actions on the bucket by using the return value of `$this->selectBucket()`.


How to use as standalone application
------------------------------------
You can use this bundle as standalone application, so, not use it within a Symfony installation.
This is also perfect for development.

* Checkout this repository and create an `app` directory within the `src` directory.
* Create a `app/CouchbaseMigrations` directory.
* Create a `app/parameters.yml` which holds the config parameters.

Fill the `app/parameters.yml` file with:

    parameters:
      kernel.project_dir:

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
