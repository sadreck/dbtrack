<?php
namespace DBtrack\Base;

class DBManager
{
    /** @var Database */
    protected $dbms = null;

    /**
     * Return a list of all the supported databases.
     * @return array
     */
    public function getSupportedDatabases()
    {
        $baseDir = dirname(dirname(__FILE__)) . '/Databases';
        $list = array();
        $files = glob($baseDir . '/*.php');

        foreach ($files as $file) {
            $list[] = pathinfo($file, PATHINFO_FILENAME);
        }
        return $list;
    }

    /**
     * Connect to the database and store object.
     * @param $type
     * @param $hostname
     * @param $database
     * @param $username
     * @param $password
     * @return bool|Database
     */
    public function connect($type, $hostname, $database, $username, $password)
    {
        $className = "DBtrack\\Databases\\" . $this->getDatabaseClass($type);
        if (!class_exists($className)) {
            Events::triggerSimple(
                'eventDisplayMessage',
                'Database handler does not exist: ' . $type
            );
            return false;
        }

        $this->dbms = new $className(
            $hostname,
            $database,
            $username,
            $password
        );

        if (!$this->dbms->connect()) {
            $this->dbms = null;
            return false;
        }

        return $this->dbms;
    }

    /**
     * User input might be "mysql", however the class' name is "MySQL".
     * @param $name
     * @return string
     */
    public function getDatabaseClass($name)
    {
        $className = '';
        $databases = $this->getSupportedDatabases();

        foreach ($databases as $database) {
            if (0 == strcasecmp($database, $name)) {
                $className = $database;
                break;
            }
        }
        return $className;
    }

    public function createTriggers(array $tables)
    {
        /** @var $terminal Terminal */
        $terminal = Container::getClassInstance('terminal');
        $progressBar = $terminal->getProgressBar(count($tables));

        $i = 0;
        foreach ($tables as $table) {
            if (!$this->dbms->createTrackingTrigger($table)) {
                return false;
            }
            $progressBar->update(++$i);
        }
        return true;
    }

    public function deleteTriggers()
    {
        $allTriggers = $this->dbms->getTriggerList();
        $dbtrackTriggers = array_filter(
            $allTriggers,
            function ($trigger) {
                return ('dbtrack_' === substr($trigger, 0, 8));
            }
        );

        /** @var $terminal Terminal */
        $terminal = Container::getClassInstance('terminal');
        $progressBar = $terminal->getProgressBar(count($dbtrackTriggers));

        $i = 0;
        foreach ($dbtrackTriggers as $trigger) {
            $this->dbms->deleteTrigger($trigger);
            $progressBar->update(++$i);
        }

        return true;
    }
}