<?php
namespace DBtrack\Base;

class Config
{
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
}