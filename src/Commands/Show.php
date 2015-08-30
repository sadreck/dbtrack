<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Core\Actions;

class Show extends Command
{
    public function execute()
    {
        if (!$this->prepareCommand()) {
            $this->climate->out('Could not start dbtrack.');
            return false;
        }

        $actionsManager = new Actions();

        $groupId = $this->getGroupID($this->arguments);
        if (false === $groupId) {
            $commits = $actionsManager->getCommitsList();
            if (empty($commits)) {
                $this->climate->out('No commits found.');
                return true;
            }
            $this->showCommitList($commits);
        } else {
            $actionList = $actionsManager->getActionsList();
            if (empty($actionList)) {
                $this->climate->out('No actions found.');
                return true;
            }
        }
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

    /**
     * Show list of commits.
     * @param array $commits
     */
    protected function showCommitList(array $commits)
    {
        $data = array();
        foreach ($commits as $commit) {
            $data[] = array(
                'ID' => $commit->id,
                'From' => date('d/m/Y H:i:s', $commit->mintime),
                'To' => date('d/m/Y H:i:s', $commit->maxtime),
                'Actions' => $commit->actioncount,
                'Message' => $commit->message
            );
        }

        $this->climate->table($data);
    }
}