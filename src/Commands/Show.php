<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;

class Show extends Command
{
    public function execute()
    {
        if (!$this->prepareCommand()) {
            $this->climate->out('Could not start dbtrack.');
            return false;
        }
    }
}