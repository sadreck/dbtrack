<?php
namespace DBtrack\Core;

use DBtrack\Base\Container;
use DBtrack\Base\Database;
use DBtrack\Base\Events;

class Actions
{
    /** @var $dbms Database */
    protected $dbms = null;

    public function __construct()
    {
        $this->dbms = Container::getClassInstance('dbms');
    }

    /**
     * Get list of commits.
     * @return mixed
     */
    public function getCommitsList()
    {
        $sql = "SELECT
                  groupid AS id,
                  message,
                  MIN(timeadded) AS mintime,
                  MAX(timeadded) AS maxtime,
                  COUNT(id) AS actioncount
                FROM dbtrack_actions
                GROUP BY groupid, message
                ORDER BY MIN(timeadded)";
        return $this->dbms->getRows($sql);
    }

    /**
     * Check if the group id passed exists in the tracking table.
     * @param $groupId
     * @return bool
     */
    public function groupExists($groupId)
    {
        $sql = "SELECT COUNT(id) AS c
                FROM dbtrack_actions
                WHERE groupid = :groupid";
        $result = $this->dbms->getRow($sql, array('groupid' => $groupId));
        return (!empty($result) && (0 < (int)$result->c));
    }

    /**
     * Return all parsed actions for the specified group id.
     * @param $grouId
     * @return array
     */
    public function getActionsList($grouId)
    {
        if (!$this->groupExists($grouId)) {
            Events::triggerSimple(
                'eventDisplayMessage',
                'Group ID does not exist: ' . $grouId
            );
        }

        $actionParser = new ActionParser();

        $parsedActions = $actionParser->parseGroup($grouId);
        $fullParsedActions = $actionParser->getFullRows($parsedActions);

        return $fullParsedActions;
    }

    /**
     * Filter the parsed actions by action name/type.
     * @param array $allActions
     * @param $showActions
     * @param $ignoreActions
     * @return array
     */
    public function filterActions(
        array $allActions,
        $showActions,
        $ignoreActions
    ) {
        $actionParser = new ActionParser();

        $showActions = $this->convertParameterToArray($showActions);
        $ignoreActions = $this->convertParameterToArray($ignoreActions);

        $showActions = array_map('strtoupper', $showActions);
        $ignoreActions = array_map('strtoupper', $ignoreActions);

        $allActions = $actionParser->filterByActions(
            $allActions,
            $showActions,
            $ignoreActions
        );

        return $allActions;
    }

    /**
     * Filter the parsed actions by table name.
     * @param array $allActions
     * @param $showTables
     * @param $ignoreTables
     * @return array
     */
    public function filterTables(
        array $allActions,
        $showTables,
        $ignoreTables
    ) {
        $actionParser = new ActionParser();

        $showTables = $this->convertParameterToArray($showTables);
        $ignoreTables = $this->convertParameterToArray($ignoreTables);

        $showTables = array_map('strtolower', $showTables);
        $ignoreTables = array_map('strtolower', $ignoreTables);

        $allActions = $actionParser->filterByTables(
            $allActions,
            $showTables,
            $ignoreTables
        );

        return $allActions;
    }

    /**
     * Convert a single variable to an array.
     * @param $value
     * @return array
     */
    protected function convertParameterToArray($value)
    {
        return is_array($value) ? $value : array($value);
    }
}