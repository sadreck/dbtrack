<?php
namespace DBtrack\Core;

use DBtrack\Base\Container;
use DBtrack\Base\Database;
use DBtrack\Base\Events;
use League\CLImate\CLImate;

class ActionParser
{
    /** @var Database */
    protected $dbms = null;

    public function __construct()
    {
        $this->dbms = Container::getClassInstance('dbms');
    }

    public function parseGroup($groupId)
    {
        /** @var $climate CLImate */
        $climate = Container::getClassInstance('climate');

        $actions = $this->getGroupActions($groupId);

        $parsedActions = array();
        $i = 0;
        $progressBar = $climate->progress(count($actions));
        foreach ($actions as $action) {
            $parsedAction = $this->parseAction($action, $parsedActions);
            if (false === $parsedAction) {
                continue;
            }

            // This is faster then array_unshift.
            $parsedActions[] = $parsedAction;

            $progressBar->current(++$i);
        }
        $progressBar->current(count($actions));

        // Reversing because using array_unshift is too slow.
        return array_reverse($parsedActions);
    }

    /**
     * Get all actions for the specified group.
     * @param $groupId
     * @return array
     */
    protected function getGroupActions($groupId)
    {
        $sql = "SELECT id, tablename, actiontype AS type
                FROM dbtrack_actions
                WHERE groupid = :groupid
                ORDER BY id DESC";
        return $this->dbms->getRows($sql, array('groupid' => $groupId));
    }

    /**
     * Parse a single individual tracked action.
     * @param \stdClass $action
     * @param array $parsedActions
     * @return bool|\stdClass
     */
    protected function parseAction(\stdClass $action, array $parsedActions)
    {
        // Check if all required properties are set.
        if (!isset($action->id, $action->tablename, $action->type)) {
            return false;
        }

        $primaryKeys = $this->getActionPrimaryKeys($action->id);
        $trackedColumns = $this->getActionColumns($action->id, $action->type);
        if (0 == count($trackedColumns)) {
            /*
             * This happens if there is an 'update' query but not values have
             * been updated (all new values match the old ones).
             */
            return false;
        }

        $data = new \stdClass();
        $previous = new \stdClass();
        foreach ($trackedColumns as $track) {
            if (Database::TRIGGER_ACTION_INSERT == $action->type) {
                $data->{$track->columnname} = $track->dataafter;
            } elseif (Database::TRIGGER_ACTION_DELETE == $action->type) {
                $data->{$track->columnname} = $track->databefore;
            } else {
                // Check if object is empty.
                if (new \stdClass() == $data) {
                    /*
                     * Check if this row has been already processed. This
                     * happens when a row has been modified multiple times.
                     */
                    $data = $this->getPreviousRecord(
                        $action->tablename,
                        $primaryKeys,
                        $parsedActions
                    );

                    // If object is still empty, we 've got a problem.
                    if (new \stdClass() == $data) {
                        Events::triggerSimple(
                            'eventDisplayMessage',
                            'Could not query the database row for the ' .
                            'previous state of record: ' . print_r($track, true)
                        );
                        return false;
                    }

                    // Cleanup data (remove columns that may have been assigned
                    // in previous tracking data).
                    $data = $this->cleanRecord($trackedColumns, $data);
                }

                $data->{$track->columnname} = $track->dataafter;
                $previous->{$track->columnname} = $track->databefore;
            }
        }

        $return = new \stdClass();
        $return->actionType = $action->type;
        $return->tableName = $action->tablename;
        $return->primaryKeys = $primaryKeys;
        $return->newData = $data;
        $return->oldData = $previous;

        return $return;
    }

    /**
     * Clean the $data class from any columns that are not present in $results.
     * A previously parsed row may contain columns that haven't been modified
     * in this action. As we copy the previous state of the row, this function
     * leaves only the affected columns in the object.
     * @param array $results
     * @param \stdClass $data
     * @return \stdClass
     */
    protected function cleanRecord(array $results, \stdClass $data)
    {
        // Gather all column names.
        $columns = array();
        foreach ($results as $result) {
            $columns[$result->columnname] = true;
        }

        // Cleanup current record.
        foreach ($data as $column => $value) {
            if (!isset($columns[$column])) {
                unset($data->{$column});
            }
        }

        return $data;
    }

    /**
     * Loop through the previously parsed rows and see if the specific row
     * (matching the primary keys) has already been processed. This way we get
     * the previous state of the current record (in case it's been updated
     * multiple times).
     * @param $tableName
     * @param array $primaryKeys
     * @param array $parsedActions
     * @return \stdClass
     */
    protected function getPreviousRecord(
        $tableName,
        array $primaryKeys,
        array $parsedActions
    ) {
        $data = new \stdClass();
        for ($i = count($parsedActions) - 1; $i >= 0; $i--) {
            $action = $parsedActions[$i];

            if ($action->tableName == $tableName) {
                $matchCount = 0;
                foreach ($primaryKeys as $key) {
                    if (isset($action->newData->{$key->name})
                        && $action->newData->{$key->name} == $key->value) {

                        $matchCount++;
                    }
                }

                if ($matchCount == count($primaryKeys)) {
                    $data = clone $action->newData;
                }
            }
        }

        /*
         * If a match wasn't found in the above loop it means that the record
         * hasn't been tracked within this session. This means that it's
         * previous state is the current database row.
         */
        if ($data == new \stdClass()) {
            $data = $this->queryPreviousRecord($tableName, $primaryKeys);
            if (false === $data) {
                $data = new \stdClass();
            }
        }

        return $data;
    }

    /**
     * Query the passed table and get the row we need using the primary keys.
     * @param $tableName
     * @param array $primaryKeys
     * @return array|mixed
     */
    protected function queryPreviousRecord($tableName, array $primaryKeys)
    {
        // Build the SQL query to get the row from the database.
        $where = array();
        $params = array();
        foreach ($primaryKeys as $key) {
            $where[] = $key->name . ' = :' . $key->name;
            $params[$key->name] = $key->value;
        }
        $where = implode(' AND ', $where);

        $sql = "SELECT * FROM {$tableName} WHERE {$where}";
        return $this->dbms->getRow($sql, $params);
    }

    /**
     * Get the primary key(s) for the given action.
     * @param $actionId
     * @return array
     */
    protected function getActionPrimaryKeys($actionId)
    {
        $sql = "SELECT name, value
                FROM dbtrack_keys
                WHERE actionid = :id";
        return $this->dbms->getRows($sql, array('id' => $actionId));
    }

    /**
     * Get all tracked columns/rows for the current action.
     * @param $actionId
     * @param $actionType
     * @return array
     */
    protected function getActionColumns($actionId, $actionType)
    {
        $sql = "SELECT dbd.columnname, dbd.databefore, dbd.dataafter
                FROM dbtrack_actions dba
                JOIN dbtrack_data dbd ON dbd.actionid = dba.id
                WHERE dba.id = :id AND dba.actiontype = :type
                ORDER BY dbd.id";
        return $this->dbms->getRows(
            $sql,
            array(
                'id' => $actionId,
                'type' => $actionType
            )
        );
    }

    /**
     * Fill missing columns for the parsed actions.
     * @param array $parsedActions
     * @return array
     */
    public function getFullRows(array $parsedActions)
    {
        $fullRows = array();

        foreach ($parsedActions as $action) {
            if (Database::TRIGGER_ACTION_INSERT == $action->actionType
                || Database::TRIGGER_ACTION_DELETE == $action->actionType) {

                $fullRows[] = $action;
            } else {
                $row = $this->getPreviousRecord(
                    $action->tableName,
                    $action->primaryKeys,
                    $parsedActions
                );

                $newAction = clone $action;
                $data = clone $action->newData;
                foreach ($row as $column => $value) {
                    if (!isset($data->{$column})) {
                        $data->{$column} = $value;
                    }
                    $newAction->newData = $data;
                }
                $fullRows[] = $newAction;
            }
        }

        return $this->sortRowColumns($fullRows);
    }

    /**
     * Sort row columns.
     * @param array $fullRows
     * @return array
     */
    protected function sortRowColumns(array $fullRows)
    {
        $tableCache = array();

        foreach ($fullRows as $index => $row) {
            if (!isset($tableCache[$row->tableName])) {
                $tableCache[$row->tableName] = $this->dbms->getTableColumns(
                    $row->tableName
                );
            }

            $new = new \stdClass();
            foreach ($tableCache[$row->tableName] as $column) {
                $new->{$column} = $row->newData->{$column};
            }
            $fullRows[$index]->newData = $new;
        }

        return $fullRows;
    }

    /**
     * Filter by actions.
     * @param array $allActions
     * @param array $showActions Which actions to display.
     * @param array $ignoreActions Which actions to ignore.
     * @return array
     */
    public function filterByActions(
        array $allActions,
        array $showActions,
        array $ignoreActions
    ) {
        // No need to run anything.
        if (0 == count($showActions) && 0 == count($ignoreActions)) {
            return $allActions;
        }

        // Flip so we can use isset() rather than in_array().
        if (!empty($showActions)) {
            $showActions = array_flip($this->convertActionTypes($showActions));
        } else {
            $showActions = array(
                Database::TRIGGER_ACTION_INSERT => 1,
                Database::TRIGGER_ACTION_UPDATE => 2,
                Database::TRIGGER_ACTION_DELETE => 3
            );
        }

        $ignoreActions = array_flip($this->convertActionTypes($ignoreActions));

        $filtered = array();
        foreach ($allActions as $action) {
            if (isset($showActions[$action->actionType])
                && !isset($ignoreActions[$action->actionType])) {
                $filtered[] = $action;
            }
        }

        return $filtered;
    }

    /**
     * Convert action names to action types (from INSERT to 1 etc).
     * @param array $actionList
     * @return array
     */
    protected function convertActionTypes(array $actionList)
    {
        foreach ($actionList as $key => $value) {
            if (!is_numeric($value)) {
                $value = $this->dbms->getActionDescriptionFromText($value);
                if (empty($value)) {
                    unset($actionList[$key]);
                } else {
                    $actionList[$key] = $value;
                }
            }
        }

        return $actionList;
    }

    /**
     * Filter actions by table name.
     * @param array $allActions
     * @param array $showTables
     * @param array $ignoreTables
     * @return array
     */
    public function filterByTables(
        array $allActions,
        array $showTables,
        array $ignoreTables
    ) {
        // No need to run anything.
        if (0 == count($showTables) && 0 == count($ignoreTables)) {
            return $allActions;
        }

        $showTables = array_flip($showTables);
        $ignoreTables = array_flip($ignoreTables);

        $filtered = array();
        foreach ($allActions as $action) {
            $table = strtolower($action->tableName);
            if (isset($showTables[$table]) && !isset($ignoreTables[$table])) {
                $filtered[] = $action;
            }
        }

        return $filtered;
    }
}