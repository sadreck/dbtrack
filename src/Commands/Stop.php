<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;

class Stop extends Command
{
    public function execute()
    {
        if (!$this->prepareCommand()) {
            $this->terminal->display('Could not start dbtrack.');
            return false;
        }

        if (!$this->dbManager->deleteTriggers()) {
            $this->terminal->display('Could not remove all dbtrack triggers');
            return false;
        }

        $this->config->setRunning(false);

        return true;
    }
}