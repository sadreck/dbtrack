<?php
namespace DBtrack\Core;

use DBtrack\Base\Container;
use DBtrack\Base\Database;

class RevertManager
{
    /** @var Database */
    protected $dbms = null;

    public function __construct()
    {
        $this->dbms = Container::getClassInstance('dbms');
    }

    /**
     * @param $groupId
     * @return bool|int|void
     */
    public function revert($groupId)
    {
        $allGroups = $this->getAllGroups($groupId);
        if (empty($allGroups)) {
            return false;
        }

        $revertedActions = 0;
        $this->dbms->beginTransaction();
        try {

            foreach ($allGroups as $group) {
                $revertedCount = $this->revertGroup($group->id);
                if (false === $revertedCount) {
                    throw new \Exception(
                        'There was an error while reverting group ' . $group->id
                    );
                }

                $revertedActions += $revertedCount;
            }

            $this->dbms->commitTransaction();
        } catch (\Exception $e) {
            $this->dbms->rollbackTransaction();
            return false;
        }

        return $revertedActions;
    }

    /**
     * Revert all actions for a specific group id.
     * @param $groupId
     * @return bool|int
     */
    protected function revertGroup($groupId)
    {
        $actionParser = new ActionParser();

        $parsedActions = array_reverse($actionParser->parseGroup($groupId));
        if (!$this->revertActions($parsedActions)) {
            return false;
        }

        $this->cleanUpGroup($groupId);

        return count($parsedActions);
    }

    /**
     * Remove group id from all tables.
     * @param $groupId
     */
    protected function cleanUpGroup($groupId)
    {
        // Delete data.
        $sql = "DELETE FROM dbtrack_data
                WHERE actionid IN (
                  SELECT id
                  FROM dbtrack_actions
                  WHERE groupid = :groupid
                )";
        $this->dbms->executeQuery($sql, array('groupid' => $groupId));

        // Delete keys.
        $sql = "DELETE FROM dbtrack_keys
                WHERE actionid IN (
                  SELECT id
                  FROM dbtrack_actions
                  WHERE groupid = :groupid
                )";
        $this->dbms->executeQuery($sql, array('groupid' => $groupId));

        // Delete actions.
        $sql = "DELETE FROM dbtrack_actions WHERE groupid = :groupid";
        $this->dbms->executeQuery($sql, array('groupid' => $groupId));
    }

    /**
     * Revert an array of actions.
     * @param array $actions
     * @return bool
     */
    protected function revertActions(array $actions)
    {
        foreach ($actions as $action) {
            list($where, $params) = $this->buildWhere($action->primaryKeys);

            switch ($action->actionType) {
                case Database::TRIGGER_ACTION_INSERT:
                    $sql = "DELETE FROM {$action->tableName} WHERE {$where}";
                    $this->dbms->executeQuery($sql, $params);
                    break;
                case Database::TRIGGER_ACTION_DELETE:
                    $data = (array)$action->newData;
                    $columns = array_keys($data);
                    $values = $columns;
                    array_walk(
                        $values,
                        function (&$value, &$key) {
                            $value = ':' . $value;
                        }
                    );

                    $sql = "INSERT INTO {$action->tableName}
                            (". implode(', ', $columns) .")
                            VALUES
                            (". implode(', ', $values) .")";
                    $this->dbms->executeQuery($sql, $data);
                    break;
                case Database::TRIGGER_ACTION_UPDATE:
                    $set = array();
                    foreach ($action->oldData as $column => $value) {
                        $set[] = $column . ' = :' . $column;
                        $params[':' . $column] = $value;
                    }

                    $sql = "UPDATE {$action->tableName}
                            SET ". implode(', ', $set) ." WHERE {$where}";
                    $this->dbms->executeQuery($sql, $params);
                    break;
            }
        }

        return true;
    }

    /**
     * Build where statement.
     * @param array $primaryKeys
     * @param string $condition
     * @return string
     */
    protected function buildWhere(array $primaryKeys, $condition = 'AND')
    {
        $where = array();
        $params = array();
        foreach ($primaryKeys as $key) {
            $where[] = $key->name . ' = :' . $key->name;
            $params[$key->name] = $key->value;
        }
        return array(
            implode(' ' . $condition . ' ', $where),
            $params
        );
    }

    /**
     * Get all groups that need to be reverted.
     * @param $groupId
     * @return array
     */
    protected function getAllGroups($groupId)
    {
        $sql = "SELECT groupid AS id
                FROM dbtrack_actions
                WHERE groupid >= :groupid
                GROUP BY groupid
                ORDER BY groupid DESC";
        return $this->dbms->getRows($sql, array('groupid' => $groupId));
    }
}