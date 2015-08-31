<?php
namespace DBtrack\Base;

abstract class Database
{
    /** @var \stdClass */
    protected $config = null;

    /** @var string */
    protected $type = '';

    /** @var \PDO */
    protected $connection = null;

    /**
     * Constants.
     */
    const TRIGGER_ACTION_INSERT = 1;
    const TRIGGER_ACTION_UPDATE = 2;
    const TRIGGER_ACTION_DELETE = 3;

    /**
     * Set descriptions for tracking actions.
     * @var array
     */
    protected $actionDescriptions = array(
        self::TRIGGER_ACTION_INSERT => 'INSERT',
        self::TRIGGER_ACTION_UPDATE => 'UPDATE',
        self::TRIGGER_ACTION_DELETE => 'DELETE'
    );

    /**
     * Set reverse action descriptions.
     * @var array
     */
    protected $actionDescriptionsText = array(
        'INSERT' => self::TRIGGER_ACTION_INSERT,
        'UPDATE' => self::TRIGGER_ACTION_UPDATE,
        'DELETE' => self::TRIGGER_ACTION_DELETE,
    );

    /**
     * If true the PDO connection must be stored in $this->connection.
     * @return bool
     */
    abstract public function connect();

    /**
     * Must return a table list within an array.
     * @return array
     */
    abstract public function getTableList();

    /**
     * Create a tracking trigger for the given table.
     * @param $table
     * @return mixed
     */
    abstract public function createTrackingTrigger($table);

    /**
     * Get all table's columns in an array.
     * @param $table
     * @return array
     */
    abstract public function getTableColumns($table);

    /**
     * Retrieve primary key(s) for the given table.
     * @param $table
     * @return mixed
     */
    abstract public function getTablePrimaryKeys($table);

    /**
     * Must return a trigger list within an array.
     * @return array
     */
    abstract public function getTriggerList();

    /**
     * Delete given trigger.
     * @param $trigger
     * @return mixed
     */
    abstract public function deleteTrigger($trigger);

    /**
     * Delete given table.
     * @param $table
     * @return mixed
     */
    abstract public function deleteTable($table);

    /**
     * Initialise database handler.
     * @param $hostname
     * @param $database
     * @param $username
     * @param $password
     * @throws \Exception
     */
    public function __construct($hostname, $database, $username, $password)
    {
        $this->config = new \stdClass();
        $this->config->hostname = $hostname;
        $this->config->database = $database;
        $this->config->username = $username;
        $this->config->password = $password;

        if (empty($this->type)) {
            throw new \Exception('Database type not set.');
        }
    }

    /**
     * Check if a given table exists in the database.
     * @param $table
     * @return bool
     */
    public function tableExists($table)
    {
        $tables = $this->getTableList();
        return in_array($table, $tables);
    }

    /**
     * Return database type.
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Execute a whole SQL script.
     * @param $sqlScript
     */
    public function executeScript($sqlScript)
    {
        $this->connection->exec($sqlScript);
    }

    /**
     * Execute SQL query and return results.
     * @param $sql
     * @param array $params
     * @param int $fetchType
     * @return array
     */
    public function getRows(
        $sql,
        array $params = array(),
        $fetchType = \PDO::FETCH_CLASS
    ) {
        if (0 == count($params)) {
            $statement = $this->connection->query($sql);
        } else {
            $statement = $this->connection->prepare($sql);
            foreach ($params as $name => $value) {
                $statement->bindValue($name, $value);
            }
            $statement->execute();
        }

        return $statement->fetchAll($fetchType);
    }

    /**
     * Get a single result.
     * @param $sql
     * @param array $params
     * @return array|mixed
     */
    public function getRow($sql, array $params = array())
    {
        $results = $this->getRows($sql, $params);
        return (0 == count($results)) ? false : reset($results);
    }

    /**
     * Load an SQL file template.
     * @param $fileName
     * @return bool|string
     */
    public function loadSQLFile($fileName)
    {
        if (!file_exists($fileName)) {
            return false;
        }

        $sqlScript = trim(@file_get_contents($fileName));
        if (empty($sqlScript)) {
            return false;
        }

        return $sqlScript;
    }

    /**
     * Execute a given SQL query.
     * @param $sql
     * @param array $params
     */
    public function executeQuery($sql, array $params = array())
    {
        $statement = $this->connection->prepare($sql);
        foreach ($params as $variable => $value) {
            $statement->bindValue($variable, $value);
        }
        $statement->execute();
    }

    /**
     * Get description for the passed action type.
     * @param $actionType
     * @return string
     */
    public function getActionDescription($actionType)
    {
        return isset($this->actionDescriptions[$actionType])
            ? $this->actionDescriptions[$actionType]
            : '';
    }

    /**
     * Return the action type from the action name.
     * @param $actionName
     * @return string
     */
    public function getActionDescriptionFromText($actionName)
    {
        return isset($this->actionDescriptionsText[$actionName])
            ? $this->actionDescriptionsText[$actionName]
            : '';
    }

    /**
     * Begin database transaction.
     */
    public function beginTransaction()
    {
        if (!$this->connection->inTransaction()) {
            $this->connection->beginTransaction();
        }
    }

    /**
     * Rollback database transaction.
     */
    public function rollbackTransaction()
    {
        if ($this->connection->inTransaction()) {
            $this->connection->rollBack();
        }
    }

    /**
     * Commit database transaction.
     */
    public function commitTransaction()
    {
        if ($this->connection->inTransaction()) {
            $this->connection->commit();
        }
    }
}