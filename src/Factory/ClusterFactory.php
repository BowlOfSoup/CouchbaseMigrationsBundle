<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Factory;

use BowlOfSoup\CouchbaseMigrationsBundle\Model\ConnectionSettings;
use Couchbase\Cluster;
use Couchbase\PasswordAuthenticator;

class ClusterFactory
{
    /** @var \BowlOfSoup\CouchbaseMigrationsBundle\Model\ConnectionSettings */
    private $connectionSettings;

    /** @var string */
    private $defaultBucketName;

    /** @var \Couchbase\Cluster */
    private $cluster;

    /**
     * @param \BowlOfSoup\CouchbaseMigrationsBundle\Model\ConnectionSettings $connectionSettings
     * @param string $defaultBucketName
     */
    public function __construct(
        ConnectionSettings $connectionSettings,
        string $defaultBucketName
    ) {
        $this->connectionSettings = $connectionSettings;
        $this->defaultBucketName = $defaultBucketName;
    }

    /**
     * @return \Couchbase\Cluster
     */
    public function getCluster(): Cluster
    {
        if (null !== $this->cluster) {
            return $this->cluster;
        }

        $host = (null !== $this->connectionSettings->getPort())
            ? $this->connectionSettings->getHost() . ':' . $this->connectionSettings->getPort()
            : $this->connectionSettings->getHost();

        $this->cluster = new Cluster('couchbase://' . $host);

        $this->authenticateForCluster();

        return $this->cluster;
    }

    /**
     * @return string
     */
    public function getDefaultBucketName()
    {
        return $this->defaultBucketName;
    }

    private function authenticateForCluster()
    {
        $authenticator = new PasswordAuthenticator();
        $authenticator->username($this->connectionSettings->getUser())->password($this->connectionSettings->getPassword());

        $this->cluster->authenticate($authenticator);
    }
}
