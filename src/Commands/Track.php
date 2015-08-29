<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Core\LogTables;

class Track extends Command
{
    public function execute()
    {
        if (!$this->prepareCommand()) {
            $this->terminal->display('Could not start dbtrack.');
            return false;
        }

        if ($this->config->isRunning()) {
            $this->terminal->display('dbtrack is already running.');
            return false;
        }

        $tables = $this->getTablesToTrack($this->arguments);
        if (0 == count($tables)) {
            $this->terminal->display('No tables to track.');
            return false;
        }

        // TODO - Implement Chain - check.
        $dbTrackTables = new LogTables();
        if (!$dbTrackTables->prepare()) {
            $this->terminal->display('Could not prepare dbtrack tables.');
            return false;
        }

        // Create triggers.
        if (!$this->dbManager->createTriggers($tables)) {
            // Clean up triggers.
            $this->terminal->display('Could not create triggers.');
            return false;
        }

        $this->config->setRunning(true);

        return true;
    }

    /**
     * Get a list of tables to track.
     * @param array $arguments
     * @return array
     */
    protected function getTablesToTrack(array $arguments)
    {
        $trackTables = $this->getArguments($arguments, 'track', 't');
        $ignoreTables = $this->getArguments($arguments, 'ignore', 'i');
        $allTables = $this->removeDBtrackTables($this->dbms->getTableList());

        // If no params have been passed, return all tables.
        $tables = (0 == count($trackTables) && 0 == count($ignoreTables))
            ? $allTables
            : $this->filterTables($allTables, $trackTables, $ignoreTables);

        return array_values($tables);
    }

    /**
     * Remove all dbtrack tables from the table list (can't track own tables).
     * @param array $tables
     * @return array
     */
    protected function removeDBtrackTables(array $tables)
    {
        $toRemove = array_filter(
            $tables,
            function ($table) {
                return ('dbtrack_' === substr($table, 0, 8));
            }
        );

        if (empty($toRemove)) {
            return $tables;
        }

        foreach ($toRemove as $key => $table) {
            unset($tables[$key]);
        }

        return $tables;
    }
}