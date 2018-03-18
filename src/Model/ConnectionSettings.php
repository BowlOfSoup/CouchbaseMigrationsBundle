<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Model;

class ConnectionSettings
{
    /** @var string */
    private $host;

    /** @var string */
    private $user;

    /** @var string */
    private $password;

    /** @var int */
    private $port;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param int $port
     */
    public function __construct(string $host, string $user, string $password, int $port = null)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }
}
