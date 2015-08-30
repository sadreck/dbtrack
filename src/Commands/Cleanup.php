<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Core\LogTables;

class Cleanup extends Command
{
    public function execute()
    {
        if (!$this->prepareCommand()) {
            $this->climate->out('Could not start dbtrack.');
            return false;
        }

        if ($this->config->isRunning()) {
            $this->climate->out('dbtrack is still running.');
            return false;
        }

        if (!$this->isConfirmed($this->arguments)) {
            // Prompt user for confirmation.
            $answer = $this->climate->out(
                'Are you sure you want to drop all tracking tables? (Y/N): '
            );
            if ('y' != strtolower($answer)) {
                return false;
            }
        }

        if (!$this->dbManager->deleteTriggers()) {
            $this->climate->out('Could not delete orphan triggers.');
            return false;
        }

        $dbTrackTables = new LogTables();
        if (!$dbTrackTables->deleteTrackTables()) {
            $this->climate->out('Could not delete tracking tables.');
            return false;
        }

        if ($this->removeConfig($this->arguments)) {
            if (!$this->config->deleteConfigDirectory()) {
                $this->climate->out('Could not delete config directory.');
                return false;
            }
        }

        $this->climate->out('dbtrack has been removed.');
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

    /**
     * Check if we need to remove the dbtrack directory.
     * @param array $arguments
     * @return bool
     */
    protected function removeConfig(array $arguments)
    {
        $remove = $this->getArguments($arguments, 'remove-config', 'r');
        return (0 < count($remove));
    }
}