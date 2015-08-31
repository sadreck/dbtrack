<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Core\RevertManager;

class Revert extends Command
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

        $groupId = $this->getGroupID($this->arguments);
        if (false === $groupId) {
            $this->climate->out(
                'No group id passed to revert. Run <dbt show> first.'
            );
            return false;
        }

        $revertManager = new RevertManager();
        $revertedActions = $revertManager->revert($groupId);
        if (false === $revertedActions) {
            $this->climate->out('Could not complete the revert.');
            return false;
        }

        $this->climate->out(
            $revertedActions . ' actions have been reverted successfully.'
        );
        return true;
    }

    /**
     * Get group id passed via the command line.
     * @param array $arguments
     * @return bool
     */
    protected function getGroupID(array $arguments)
    {
        if (3 <= count($arguments['raw-command'])
            && is_numeric($arguments['raw-command'][2])) {
            return $arguments['raw-command'][2];
        }
        return false;
    }
}