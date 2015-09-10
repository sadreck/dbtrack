<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;

class Config extends Command
{
    public function execute()
    {
        if (!$this->config->isInitialised()) {
            $this->climate->out('dbtrack is not initialised');
            return false;
        }

        $config = $this->config->loadConfig();

        $this->climate->out('Database Type: ' . $config->datatype);
        $this->climate->out('Database Hostname: ' . $config->hostname);
        $this->climate->out('Database Name: ' . $config->database);
        $this->climate->out('Database Username: ' . $config->username);

        if ($this->showAll()) {
            $this->climate->out('Database Password: ' . $config->password);
        }

        return true;
    }

    /**
     * Check if --all or -a have been set.
     * @return bool
     */
    protected function showAll()
    {
        $showAll = $this->getArguments($this->arguments, 'all', 'a');
        return (1 == count($showAll));
    }
}