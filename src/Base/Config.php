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
        return file_exists($this->dbtDirectory . '/config');
    }

    /**
     * Save config.
     * @param \stdClass $config
     * @return bool
     */
    public function saveConfig(\stdClass $config)
    {
        $configFile = $this->dbtDirectory . '/config';

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
}