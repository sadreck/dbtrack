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
        if ($this->config->isInitialised()
            && !$this->canOverwrite($this->arguments)) {

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

        $presets = $this->getPresetArguments($this->arguments);

        $datatype = isset($presets['datatype'])
            ? $presets['datatype']
            : $this->terminal->prompt('DBMS ('. $list .'): ');
        $hostname = isset($presets['hostname'])
            ? $presets['hostname']
            : $this->terminal->prompt('Database hostname: ');
        $database = isset($presets['database'])
            ? $presets['database']
            : $this->terminal->prompt('Database name: ');
        $username = isset($presets['username'])
            ? $presets['username']
            : $this->terminal->prompt('Database username: ');
        $password = isset($presets['password'])
            ? $presets['password']
            : $this->terminal->prompt('Database password: ');

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

    /**
     * Get preset arguments.
     * @param array $arguments
     * @return array
     */
    protected function getPresetArguments(array $arguments)
    {
        $presets = array(
            'datatype' => $this->getArguments($arguments, 'datatype', 'dt'),
            'database' => $this->getArguments($arguments, 'database', 'db'),
            'hostname' => $this->getArguments($arguments, 'hostname', 'h'),
            'username' => $this->getArguments($arguments, 'username', 'u'),
            'password' => $this->getArguments($arguments, 'password', 'p'),
        );

        foreach ($presets as $i => $preset) {
            if (0 == count($preset)) {
                unset($presets[$i]);
            } elseif (1 == count($preset)) {
                $presets[$i] = $preset[0];
            }
        }

        return $presets;
    }

    /**
     * Check if we can overwrite without prompting the user.
     * @param array $arguments
     * @return bool
     */
    protected function canOverwrite(array $arguments)
    {
        $overwrite = $this->getArguments($arguments, 'overwrite', 'o');
        return (0 < count($overwrite));
    }
}
