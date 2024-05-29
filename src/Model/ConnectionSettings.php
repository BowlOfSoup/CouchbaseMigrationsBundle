<?php

namespace BowlOfSoup\CouchbaseMigrationsBundle\Model;

class ConnectionSettings
{
    private string $host;

    private string $user;

    private string $password;

    private ?int $port;

    public function __construct(string $host, string $user, string $password, ?int $port = null)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->port = $port;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
