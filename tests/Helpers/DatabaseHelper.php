<?php
namespace DBtrack\Tests;

use DBtrack\Base\Config;
use DBtrack\Base\Database;

class DatabaseHelper
{
    /** @var Database */
    protected $dbms = null;

    /**
     * List of tables used by dbtrack.
     * @var array
     */
    private $dbtrackTables = array(
        'dbtrack_actions',
        'dbtrack_data',
        'dbtrack_keys',
        'dbtrack_config'
    );

    public function __construct(Database $dbms)
    {
        $this->dbms = $dbms;
    }

    /**
     * Load SQL script for the given database table (for dbtrack).
     * @param $table
     * @return bool|string
     */
    public function loadSQLFile($table)
    {
        $sqlFile = implode(
            DIRECTORY_SEPARATOR,
            array(
                dirname(dirname(dirname(__FILE__))),
                'src',
                'Databases',
                $this->dbms->getType(),
                'DBtrack',
                $table . '.sql'
            )
        );

        $sqlScript = $this->dbms->loadSQLFile($sqlFile);
        if (false === $sqlScript) {
            return false;
        }

        return $this->parseSQLScript($sqlScript);
    }

    /**
     * Replace constants in SQL scripts.
     * @param $sqlScript
     * @return mixed
     */
    protected function parseSQLScript($sqlScript)
    {
        return str_replace(
            '{%VERSION%}',
            Config::VERSION,
            $sqlScript
        );
    }

    public function createTable($table)
    {
        $data = $this->loadSQLFile($table);
        $this->dbms->executeScript($data);
    }

    public function deleteTable($table)
    {
        $this->dbms->deleteTable($table);
    }

    public function deleteAllTables()
    {
        $tables = $this->dbms->getTableList();
        if (empty($tables)) {
            $tables = array();
        }
        foreach ($tables as $table) {
            $this->deleteTable($table);
        }
    }

    public function createAllTables()
    {
        foreach ($this->dbtrackTables as $table) {
            $this->createTable($table);
        }
    }

    public function deleteAllTriggers()
    {
        $triggers = $this->dbms->getTriggerList();
        foreach ($triggers as $trigger) {
            $this->dbms->deleteTrigger($trigger);
        }
    }
}