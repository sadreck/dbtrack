<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Base\Database;
use DBtrack\Core\ActionFormatting;
use DBtrack\Core\Actions;

class Show extends Command
{
    public function execute()
    {
        if (!$this->prepareCommand()) {
            $this->climate->out('Could not start dbtrack.');
            return false;
        }

        $arguments = $this->getPresetArguments($this->arguments);

        $actionsManager = new Actions();
        $actionFormatting = new ActionFormatting();

        $groupId = $this->getGroupID($this->arguments);
        if (false === $groupId) {
            $commits = $actionsManager->getCommitsList();
            if (empty($commits)) {
                $this->climate->out('No commits found.');
                return true;
            }
            $this->showCommitList($commits);
        } else {
            $actionList = $actionsManager->getActionsList($groupId);
            if (empty($actionList)) {
                $this->climate->out('No actions found.');
                return true;
            }

            // Get results per page.
            $perPage = isset($arguments['perpage'])
            && is_numeric($arguments['perpage'])
                ? (int)$arguments['perpage']
                : 10;

            // Get max value length.
            $maxLength = isset($arguments['maxlength'])
            && is_numeric($arguments['maxlength'])
                ? (int)$arguments['maxlength']
                : 20;

            $actionList = $this->filter($actionList, $arguments);

            $formattedList = $actionFormatting->formatList(
                $actionList,
                $maxLength
            );
            $this->showTrackedData($formattedList, $perPage);
        }
    }

    /**
     * Filter which records to show.
     * @param array $parsedActions
     * @param array $arguments
     * @return array
     */
    protected function filter(array $parsedActions, array $arguments)
    {
        $actionManager = new Actions();

        $showActions = isset($arguments['actions'])
            ? $arguments['actions']
            : array();
        $ignoreActions = isset($arguments['ignore-actions'])
            ? $arguments['ignore-actions']
            : array();

        $parsedActions = $actionManager->filterActions(
            $parsedActions,
            $showActions,
            $ignoreActions
        );

        $showTables = isset($arguments['tables'])
            ? $arguments['tables']
            : array();
        $ignoreTables = isset($arguments['ignore-tables'])
            ? $arguments['ignore-tables']
            : array();

        $parsedActions = $actionManager->filterTables(
            $parsedActions,
            $showTables,
            $ignoreTables
        );

        return $parsedActions;
    }

    /**
     * Get preset arguments.
     * @param array $arguments
     * @return array
     */
    protected function getPresetArguments(array $arguments)
    {
        $presets = array(
            'maxlength' => $this->getArguments($arguments, 'maxlength', 'm'),
            'perpage' => $this->getArguments($arguments, 'per-page', 'p'),
            'actions' => $this->getArguments($arguments, 'actions', 'a'),
            'ignore-actions' => $this->getArguments(
                $arguments,
                'ignore-actions',
                'ia'
            ),
            'tables' => $this->getArguments($arguments, 'tables', 't'),
            'ignore-tables' => $this->getArguments(
                $arguments,
                'ignore-tables',
                'it'
            ),
        );

        foreach ($presets as $i => $preset) {
            if (0 == count($preset)) {
                unset($presets[$i]);
            } elseif (1 == count($preset)) {
                $presets[$i] = $preset[0];
            }
        }

        return $presets;
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

    /**
     * Display tracked data.
     * @param array $actions
     */
    protected function showTrackedData(array $actions, $perPage)
    {
        $rowCount = 0;
        foreach ($actions as $action) {
            ++$rowCount;

            $this->climate->table(array($action));
            $this->climate->out('');

            if (0 < $perPage && (0 == $rowCount % $perPage)) {
                $this->climate->input(
                    'Press enter to continue...' .
                    '('. $rowCount .'/'. count($actions) .')'
                )->prompt();
            }
        }
    }
}