<?php
namespace DBtrack\Base;

class Config
{
    /** DBtrack version */
    const VERSION = '0.2';

    /** @var string The directory where all config information is be stored. */
    public $dbtDirectory = '';

    /**
     * @param string $userDirectory Override $dbtDirectory.
     * @throws \Exception
     */
    public function __construct($userDirectory = '')
    {
        $this->dbtDirectory = empty($userDirectory)
            ? getcwd() . '/.dbtrack'
            : $userDirectory;

        // If the directory exists check if it's writable. If not, we don't care
        // as we 'll try and create it later on.
        if (is_dir($this->dbtDirectory) && !is_writable($this->dbtDirectory)) {
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
        return file_exists($this->getConfigFile());
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
        if (file_exists($configFile)) {
            @unlink($configFile);
            if (file_exists($configFile)) {
                return false;
            }
        }

        // Save new config.
        file_put_contents($configFile, json_encode($config));
        if (!file_exists($configFile)) {
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

        $data = file_get_contents($this->getConfigFile());
        if (empty($data)) {
            return false;
        }

        $config = json_decode($data);
        if (false === $config) {
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
            $config->password
        );
    }

    /**
     * Set the running state of dbtrack.
     * @param $isRunning
     * @throws \Exception
     */
    public function setRunning($isRunning)
    {
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
        /** @var $dbms Database */
        $dbms = Container::getClassInstance('dbms');

        $sql = "SELECT *
                FROM dbtrack_config
                WHERE name = 'running' AND value = 1";
        $row = $dbms->getRow($sql);
        return (false !== $row);
    }
}