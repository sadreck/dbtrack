<?php
namespace DBtrack\Core;

use DBtrack\Base\Container;
use DBtrack\Base\Database;

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

    public function getActionsList()
    {
        return array();
    }
}