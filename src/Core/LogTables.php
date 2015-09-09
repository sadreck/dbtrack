<?php
namespace DBtrack\Core;

use DBtrack\Base\Config;
use DBtrack\Base\Container;
use DBtrack\Base\Database;
use DBtrack\Base\Events;
use Mockery\CountValidator\Exception;

class LogTables
{
    /** @var Database */
    protected $dbms = null;

    public function __construct()
    {
        $this->dbms = Container::getClassInstance('dbms');
    }

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

    /**
     * Prepare all required dbtrack tables.
     * @return bool
     * @throws \Exception
     */
    public function prepare()
    {
        if ($this->isPrepared()) {
            return true;
        }

        if (!$this->createTables()) {
            Events::triggerSimple(
                'eventDisplayMessage',
                'Could not create DBtrack tables.'
            );
            return false;
        }

        if (!$this->checkTableVersion(Config::VERSION)) {
            // TODO - Upgrade tables.
            throw new Exception('TODO: Upgrade tables');
        }

        return true;
    }

    /**
     * Check if all the right tables exist.
     * @return bool
     */
    protected function isPrepared()
    {
        // Check if all tables are present.
        $allThere = true;
        foreach ($this->dbtrackTables as $table) {
            if (!$this->dbms->tableExists($table)) {
                $allThere = false;
                break;
            }
        }

        if (!$allThere) {
            return false;
        }

        if (!$this->checkTableVersion(Config::VERSION)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the tables are on the right version.
     * @param $version
     * @return bool
     */
    protected function checkTableVersion($version)
    {
        $sql = "SELECT *
                FROM dbtrack_config
                WHERE name = 'version' AND value = :version";
        $params = array('version' => $version);

        $result = $this->dbms->getRow($sql, $params);
        // If a row is returned, the tables are on the right version.
        return (false !== $result);
    }

    /**
     * Create all required tables.
     * @return bool
     * @throws \Exception
     */
    protected function createTables()
    {
        foreach ($this->dbtrackTables as $table) {
            if (!$this->dbms->tableExists($table)) {
                $sqlScript = $this->loadSQLFile($table);
                if (false === $sqlScript) {
                    // Exception cause it's a coding error.
                    throw new \Exception('Could not load file: ' . $table);
                }

                $this->dbms->executeScript($sqlScript);
            }
        }
        return true;
    }

    /**
     * Load SQL script for the given database table (for dbtrack).
     * @param $table
     * @return bool|string
     */
    protected function loadSQLFile($table)
    {
        $sqlFile = implode(
            DIRECTORY_SEPARATOR,
            array(
                dirname(dirname(__FILE__)),
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

    /**
     * Delete tracking tables.
     * @return bool
     */
    public function deleteTrackTables()
    {
        foreach ($this->dbtrackTables as $table) {
            $this->dbms->deleteTable($table);
        }
        return true;
    }
}