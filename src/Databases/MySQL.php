<?php
namespace DBtrack\Databases;

use DBtrack\Base\Database;
use DBtrack\Base\Events;

class MySQL extends Database
{
    /** @var string */
    protected $type = 'MySQL';

    public function connect()
    {
        $options = array(
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        );

        $dsn = array(
            "mysql:host={$this->config->hostname}",
            "dbname={$this->config->database}"
        );

        try {
            $this->connection = new \PDO(
                implode(';', $dsn),
                $this->config->username,
                $this->config->password,
                $options
            );
            $this->connection->setAttribute(
                \PDO::ATTR_ERRMODE,
                \PDO::ERRMODE_EXCEPTION
            );
        } catch (\Exception $e) {
            $this->connection = null;

            Events::triggerSimple(
                'eventDisplayMessage',
                $e->getMessage()
            );
            return false;
        }

        return true;
    }

    public function getTableList()
    {
        $statement = $this->connection->query('SHOW TABLES');
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function createTrackingTrigger($table)
    {
        $tableColumns = $this->getTableColumns($table);
        $tablePrimaryKeys = $this->getTablePrimaryKeys($table);

        $success = true;
        foreach ($this->actionDescriptions as $action => $description) {
            $triggerName = 'dbtrack_' . $table . '_' . strtolower($description);

            $success = $this->createTrigger(
                $triggerName,
                $table,
                $action,
                $tableColumns,
                $tablePrimaryKeys
            );

            if (!$success) {
                Events::triggerSimple(
                    'eventDisplayMessage',
                    "Could not create trigger {$description} for table {$table}"
                );
                break;
            }
        }

        return $success;
    }

    private function createTrigger(
        $triggerName,
        $table,
        $action,
        array $tableColumns,
        array $tablePrimaryKeys
    ) {
        $sqlFile = implode(
            DIRECTORY_SEPARATOR,
            array(
                dirname(__FILE__),
                $this->type,
                'Templates',
                'trigger.sql'
            )
        );
        $sqlTemplate = $this->loadSQLFile($sqlFile);
        if (false === $sqlTemplate) {
            return false;
        }

        $state = (Database::TRIGGER_ACTION_DELETE == $action) ? 'OLD' : 'NEW';

        // Create the INSERT query for the primary keys.
        $primaryKeys = array();
        foreach ($tablePrimaryKeys as $key) {
            $primaryKeys[] = "INSERT INTO dbtrack_keys(actionid, name, value)
                              VALUES(@lastid, '{$key}', {$state}.{$key});";
        }

        // Create the INSERT query for the actual data that changed.
        $inserts = array();
        foreach ($tableColumns as $column) {
            switch ($action) {
                case Database::TRIGGER_ACTION_INSERT:
                    $inserts[] = "INSERT INTO dbtrack_data
                                  (
                                    actionid,
                                    columnname,
                                    dataafter
                                  )
                                  VALUES
                                  (
                                    @lastid,
                                    '{$column}',
                                    {$state}.{$column}
                                  );";
                    break;
                case Database::TRIGGER_ACTION_UPDATE:
                    $inserts[] = "IF (OLD.{$column} <> NEW.{$column}) THEN
                                    INSERT INTO dbtrack_data
                                    (
                                        actionid,
                                        columnname,
                                        databefore,
                                        dataafter
                                    )
                                    VALUES
                                    (
                                        @lastid,
                                        '{$column}',
                                        OLD.{$column},
                                        NEW.{$column}
                                    );
                                  END IF;";
                    break;
                case Database::TRIGGER_ACTION_DELETE:
                    $inserts[] = "INSERT INTO dbtrack_data
                                  (
                                    actionid,
                                    columnname,
                                    databefore
                                  )
                                  VALUES
                                  (
                                    @lastid,
                                    '{$column}',
                                    {$state}.{$column}
                                  );";
                    break;
            }
        }

        // Replace variables.
        $sql = str_replace(
            array(
                '{%NAME%}',
                '{%TYPE%}',
                '{%TABLE%}',
                '{%ACTION%}',
                '{%PRIMARYKEYS%}',
                '{%INSERTS%}'
            ),
            array(
                $triggerName,
                $this->actionDescriptions[$action],
                $table,
                $action,
                implode(PHP_EOL, $primaryKeys),
                implode(PHP_EOL, $inserts)
            ),
            $sqlTemplate
        );

        $this->executeScript($sql);

        return true;
    }

    public function getTableColumns($table)
    {
        $sql = "SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_NAME = :table AND TABLE_SCHEMA = :schema
                ORDER BY ORDINAL_POSITION";

        return $this->getRows(
            $sql,
            array(
                'table' => $table,
                'schema' => $this->config->database
            ),
            \PDO::FETCH_COLUMN
        );
    }

    public function getTablePrimaryKeys($table)
    {
        $sql = "SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = :schema
                      AND TABLE_NAME = :table
                      AND COLUMN_KEY = 'PRI'";
        $results = $this->getRows(
            $sql,
            array(
                'schema' => $this->config->database,
                'table' => $table
            )
        );

        $columns = array();
        if (1 == count($results)) {
            $columns = array($results[0]->COLUMN_NAME);
        } else {
            foreach ($results as $result) {
                $columns[] = $result->COLUMN_NAME;
            }
        }

        return $columns;
    }

    public function getTriggerList()
    {
        $statement = $this->connection->query('SHOW TRIGGERS');
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function deleteTrigger($trigger)
    {
        $this->connection->query("DROP TRIGGER IF EXISTS {$trigger}");
    }

    public function deleteTable($table)
    {
        $this->connection->query("DROP TABLE IF EXISTS {$table}");
    }
}