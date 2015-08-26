<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Base\Config;
use DBtrack\Base\DBManager;
use DBtrack\Base\Events;
use DBtrack\Base\Terminal;

class Init extends Command
{
    /** @var Terminal */
    protected $terminal = null;

    /** @var DBManager */
    protected $dbManager = null;

    public function execute()
    {
        /** @var $config Config */
        $config = $this->getClassInstance('config');
        if (false === $config) {
            return false;
        }

        $this->terminal = $this->getClassInstance('terminal');
        $this->dbManager = $this->getClassInstance('dbmanager');

        // Check if it's already been initialised.
        if ($config->isInitialised()) {
            if (!$this->confirmReInitialise()) {
                return false;
            }
        }

        $data = $this->getData();
        if (false === $data) {
            return false;
        }

        $dbms = $this->getDatabaseObject($data);
        if (false === $dbms) {
            Events::triggerSimple(
                'eventDisplayMessage',
                'Could not connect to database. Try running <dbt init> again.'
            );
            return false;
        }

        // Add the database connection to the class container.
        $this->addContainer('dbms', $dbms);

        if (!$config->saveConfig($data)) {
            Events::triggerSimple(
                'eventDisplayMessage',
                'Could not save config (could be permissions error).'
            );
            return false;
        }

        $this->terminal->display('dbtrack has been initialised.');
        return true;
    }

    /**
     * Get $dbms object.
     * @param \stdClass $data
     * @return bool|\DBtrack\Base\Database
     */
    protected function getDatabaseObject(\stdClass $data)
    {
        return $this->dbManager->connect(
            $data->datatype,
            $data->hostname,
            $data->database,
            $data->username,
            $data->password
        );
    }

    /**
     * Confirm if the user wants to re-initialise dbtrack.
     * @return bool
     */
    protected function confirmReInitialise()
    {
        $this->terminal->display(
            'This directory has already been initialised.' .
            ' Are you sure you want to re-initialise?'
        );
        $answer = $this->terminal->prompt('Y/N: ');
        return ('y' == trim(strtolower($answer)));
    }

    /**
     * Gather all required information for the database connection.
     * @return object
     * @throws \Exception
     */
    protected function getData()
    {
        $databases = $this->dbManager->getSupportedDatabases();
        if (0 == count($databases)) {
            Events::triggerSimple(
                'eventDisplayMessage',
                'No supported databases found.'
            );
            return false;
        }

        $list = implode('/', $databases);
        $datatype = $this->terminal->prompt('DBMS ('. $list .'): ');
        $hostname = $this->terminal->prompt('Database hostname: ');
        $database = $this->terminal->prompt('Database name: ');
        $username = $this->terminal->prompt('Database username: ');
        $password = $this->terminal->prompt('Database password: ');

        if (empty($datatype) ||
            empty($hostname) ||
            empty($database) ||
            empty($username) ||
            empty($password)) {

            Events::triggerSimple(
                'eventDisplayMessage',
                'You must enter all credentials.'
            );
            return false;
        }

        $data = array(
            'datatype' => $datatype,
            'hostname' => $hostname,
            'database' => $database,
            'username' => $username,
            'password' => $password
        );

        return (object)$data;
    }
}
