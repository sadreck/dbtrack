<?php
namespace DBtrack\Base;

use DBtrack\System\System;

class Config
{
    /** DBtrack version */
    const VERSION = '0.2';

    /** @var string The directory where all config information is be stored. */
    public $dbtDirectory = '';

    /** @var System */
    protected $system = null;

    /**
     * @param string $userDirectory Override $dbtDirectory.
     * @throws \Exception
     */
    public function __construct($userDirectory = '')
    {
        $this->system = Container::getClassInstance('system');

        $this->dbtDirectory = empty($userDirectory)
            ? $this->system->getcwd() . '/.dbtrack'
            : $userDirectory;

        // If the directory exists check if it's writable. If not, we don't care
        // as we 'll try and create it later on.
        if ($this->system->is_dir($this->dbtDirectory)
            && !$this->system->is_writable($this->dbtDirectory)) {

            throw new \Exception(
                'Permission denied to read/write to: ' . $this->dbtDirectory
            );
        }
    }

    /**
     * Check if DBtrack has already been initialised - but don't validate.
     * @return bool
     */
    public function isInitialised()
    {
        return $this->system->file_exists($this->getConfigFile());
    }

    /**
     * Save config.
     * @param \stdClass $config
     * @return bool
     */
    public function saveConfig(\stdClass $config)
    {
        $configFile = $this->getConfigFile();

        // Try to delete file if it exists.
        if ($this->system->file_exists($configFile)) {
            if (!$this->system->unlink($configFile)) {
                return false;
            }
        } // @codeCoverageIgnore

        // Create directory.
        if (!$this->createDirectory(dirname($configFile))) {
            return false;
        }

        // Save new config.
        $this->system->file_put_contents($configFile, json_encode($config));
        if (!$this->system->file_exists($configFile)) {
            // Could not create config file.
            return false;
        }

        return true;
    }

    /**
     * Load config file.
     * @return bool|mixed
     */
    public function loadConfig()
    {
        if (!$this->isInitialised()) {
            return false;
        }

        $data = $this->system->file_get_contents($this->getConfigFile());
        if (empty($data)) {
            return false;
        }

        $config = json_decode($data);
        if (null === $config) {
            return false;
        } elseif (!$this->validateConfig($config)) {
            return false;
        }

        return $config;
    }

    /**
     * @return string
     */
    protected function getConfigFile()
    {
        return $this->dbtDirectory . '/config';
    }

    /**
     * @param \stdClass $config
     * @return bool
     */
    protected function validateConfig(\stdClass $config)
    {
        return isset(
            $config->datatype,
            $config->hostname,
            $config->database,
            $config->username,
            $config->password);
    }

    /**
     * Set the running state of dbtrack.
     * @param $isRunning
     * @return bool
     * @throws \Exception
     */
    public function setRunning($isRunning)
    {
        if (!$this->configTableExists()) {
            return false;
        }

        /** @var $dbms Database */
        $dbms = Container::getClassInstance('dbms');

        $running = $isRunning ? 1 : 0;

        $sqlQueries = array(
            "DELETE FROM dbtrack_config
              WHERE name IN ('running', 'runtime')",

            "INSERT INTO dbtrack_config (name, value)
              VALUES('running', {$running})",

            "INSERT INTO dbtrack_config (name, value)
              VALUES('runtime', ". time() .")"
        );

        foreach ($sqlQueries as $sql) {
            $dbms->executeQuery($sql);
        }
    }

    /**
     * Check if dbtrack is already running.
     * @return bool
     * @throws \Exception
     */
    public function isRunning()
    {
        if (!$this->configTableExists()) {
            return false;
        }

        /** @var $dbms Database */
        $dbms = Container::getClassInstance('dbms');

        $sql = "SELECT *
                FROM dbtrack_config
                WHERE name = 'running' AND value = 1";
        $row = $dbms->getRow($sql);
        return (false !== $row);
    }

    /**
     * Check if dbtrack table exists.
     * @return bool
     * @throws \Exception
     */
    protected function configTableExists()
    {
        /** @var $dbms Database */
        $dbms = Container::getClassInstance('dbms');
        return ($dbms->tableExists('dbtrack_config'));
    }

    /**
     * Delete config directory.
     * @return bool
     */
    public function deleteConfigDirectory()
    {
        $this->deleteDirectory($this->dbtDirectory);

        return !$this->system->is_dir($this->dbtDirectory);
    }

    /**
     * Delete directory.
     * @param $directory
     * @return bool
     */
    protected function deleteDirectory($directory)
    {
        $directory = rtrim($directory, '/');
        if ($this->system->is_dir($directory)) {
            $list = $this->system->scandir($directory);
            foreach ($list as $item) {
                if ('.' == $item || '..' == $item) {
                    continue;
                }
                $path = $directory . '/' . $item;
                if ($this->system->is_dir($path)) {
                    $this->deleteDirectory($path);
                } else {
                    $this->system->unlink($path);
                }
            }
            $this->system->rmdir($directory);
        }
        return true;
    }

    /**
     * Create directory.
     * @param $directory
     * @param int $permissions
     * @return bool
     */
    protected function createDirectory($directory, $permissions = 0775)
    {
        if ($this->system->is_dir($directory)) {
            return true;
        }

        $umask = $this->system->umask(0);
        $this->system->mkdir($directory, $permissions, true);
        $this->system->umask($umask);

        return $this->system->is_dir($directory);
    }
}