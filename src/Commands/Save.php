<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Core\Commit;

class Save extends Command
{
    public function execute()
    {
        if (!$this->prepareCommand()) {
            $this->climate->out('Could not start dbtrack.');
            return false;
        }

        if (!$this->config->isRunning()) {
            $this->climate->out('dbtrack is not running.');
            return false;
        }

        if (!$this->dbManager->deleteTriggers()) {
            $this->climate->out('Could not remove all dbtrack triggers');
            return false;
        }

        $commit = new Commit();
        $actions = $commit->save($this->getMessage());
        if (false === $actions) {
            $this->climate->out('Could not commit actions.');
            return false;
        }

        $this->config->setRunning(false);

        $this->climate->out(
            'Tracking stopped. '. $actions .' action(s) tracked.'
        );
        return true;
    }

    /**
     * Get commit message.
     * @return string
     */
    protected function getMessage()
    {
        $arguments = $this->getArguments($this->arguments, 'message', 'm');
        return (1 == count($arguments)) ? $arguments[0] : '';
    }
}