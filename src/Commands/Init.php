<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Base\Config;
use DBtrack\Base\Container;
use DBtrack\Base\DBManager;
use DBtrack\Base\Terminal;

class Init extends Command
{
    public function execute()
    {
        // Check if it's already been initialised.
        if ($this->config->isInitialised()) {
            if (!$this->confirmReInitialise()) {
                return false;
            }
        }

        $data = $this->getData();
        if (false === $data) {
            return false;
        }

        $dbms = $this->connectToDatabase($data);
        if (false === $dbms) {
            $this->terminal->display(
                'Could not connect to database. Try running <dbt init> again.'
            );
            return false;
        }

        if (!$this->config->saveConfig($data)) {
            $this->terminal->display(
                'Could not save config (could be permissions error).'
            );
            return false;
        }

        $this->terminal->display('dbtrack has been initialised.');
        return true;
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
            $this->terminal->display('No supported databases found.');
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

            $this->terminal->display('You must enter all credentials.');
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
