<?php
namespace DBtrack\Databases;

use DBtrack\Base\Database;
use DBtrack\Base\Events;

class MySQL extends Database
{
    /** @var string */
    protected $type = 'MySQL';

    public function connect()
    {
        $options = array(
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        );

        $dsn = array(
            "mysql:host={$this->config->hostname}",
            "dbname={$this->config->database}"
        );

        try {
            $this->connection = new \PDO(
                implode(';', $dsn),
                $this->config->username,
                $this->config->password,
                $options
            );
            $this->connection->setAttribute(
                \PDO::ATTR_ERRMODE,
                \PDO::ERRMODE_EXCEPTION
            );
        } catch (\Exception $e) {
            $this->connection = null;

            Events::triggerSimple(
                'eventDisplayMessage',
                $e->getMessage()
            );
            return false;
        }

        return true;
    }
}