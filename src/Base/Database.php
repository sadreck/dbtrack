<?php
namespace DBtrack\Base;

abstract class Database
{
    /** @var \stdClass */
    protected $config = null;

    /** @var string */
    protected $type = '';

    /** @var \PDO */
    protected $connection = null;

    /**
     * If true the PDO connection must be stored in $this->connection.
     * @return bool
     */
    abstract public function connect();

    /**
     * Initialise database handler.
     * @param $hostname
     * @param $database
     * @param $username
     * @param $password
     * @throws \Exception
     */
    public function __construct($hostname, $database, $username, $password)
    {
        $this->config = new \stdClass();
        $this->config->hostname = $hostname;
        $this->config->database = $database;
        $this->config->username = $username;
        $this->config->password = $password;

        if (empty($this->type)) {
            throw new \Exception('Database type not set.');
        }
    }
}