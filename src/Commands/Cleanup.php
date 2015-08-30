<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Core\LogTables;

class Cleanup extends Command
{
    public function execute()
    {
        if (!$this->prepareCommand()) {
            $this->terminal->display('Could not start dbtrack.');
            return false;
        }

        if ($this->config->isRunning()) {
            $this->terminal->display('dbtrack is still running.');
            return false;
        }

        if (!$this->isConfirmed($this->arguments)) {
            // Prompt user for confirmation.
            $answer = $this->terminal->prompt(
                'Are you sure you want to drop all tracking tables? (Y/N): '
            );
            if ('y' != strtolower($answer)) {
                return false;
            }
        }

        if (!$this->dbManager->deleteTriggers()) {
            $this->terminal->display('Could not delete orphan triggers.');
            return false;
        }

        $dbTrackTables = new LogTables();
        if (!$dbTrackTables->deleteTrackTables()) {
            $this->terminal->display('Could not delete tracking tables.');
            return false;
        }

        $this->terminal->display('dbtrack has been removed from your database');
        return true;
    }

    /**
     * Check if the user has confirmed the cleanup via command line (--yes | -y)
     * @param array $arguments
     * @return bool
     */
    protected function isConfirmed(array $arguments)
    {
        $confirm = $this->getArguments($arguments, 'yes', 'y');
        return (0 < count($confirm));
    }
}