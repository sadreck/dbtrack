<?php
namespace DBtrack\Core;

use DBtrack\Base\Container;
use DBtrack\Base\Database;

class Commit
{
    /**
     * Commit all actions using the specified message.
     * @param $message
     * @return mixed
     * @throws \Exception
     */
    public function save($message)
    {
        /** @var $dbms Database */
        $dbms = Container::getClassInstance('dbms');

        $groupId = $this->getGroupID();

        $sql = "UPDATE dbtrack_actions
                SET groupid = :groupid, message = :message WHERE groupid = 0";
        $dbms->executeQuery(
            $sql,
            array(
                'groupid' => $groupId,
                'message' => $message
            )
        );

        // Count new actions.
        $sql = "SELECT COALESCE(COUNT(id), 0) AS actions
                FROM dbtrack_actions WHERE groupid = :groupid";
        $count = $dbms->getRow($sql, array('groupid' => $groupId));

        return $count->actions;
    }

    /**
     * Get next group id (used as a commit hash).
     * @return mixed
     * @throws \Exception
     */
    protected function getGroupID()
    {
        /** @var $dbms Database */
        $dbms = Container::getClassInstance('dbms');

        $sql = "SELECT COALESCE(MAX(groupid), 0) AS groupid
                FROM dbtrack_actions";
        $result = $dbms->getRow($sql);
        return ++$result->groupid;
    }
}