<?php
namespace DBtrack\Core;

use DBtrack\Base\Container;
use DBtrack\Base\Database;

class ActionFormatting
{
    /** @var Database */
    protected $dbms = null;

    public function __construct()
    {
        $this->dbms = Container::getClassInstance('dbms');
    }

    /**
     * Format an action list (to display in terminal).
     * @param array $actions
     * @return array
     */
    public function formatList(array $actions)
    {
        $data = array();
        foreach ($actions as $action) {
            $data[] = $this->formatRow($action);
        }
        return $data;
    }

    /**
     * Format a specific action.
     * @param \stdClass $action
     * @return array
     */
    public function formatRow(\stdClass $action)
    {
        $row = $action->newData;
        $changedColumns = array_flip(array_keys((array)$action->oldData));

        foreach ($row as $property => $value) {
            if (strlen($value) >= 20) {
                $value = substr($value, 0, 20) . '...';
            }

            if (isset($changedColumns[$property])) {
                $value = '<light_yellow>' . $value . '</light_yellow>';
            }

            $row->{$property} = $value;
        }

        $row = array(
                $action->tableName => $this->dbms->getActionDescription(
                    $action->actionType
                )
            ) + (array)$row;

        return $row;
    }
}