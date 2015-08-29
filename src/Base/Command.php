<?php
namespace DBtrack\Base;

abstract class Command
{
    /** @var array */
    protected $arguments = array();

    /** @var Config */
    protected $config = null;

    /** @var Terminal */
    protected $terminal = null;

    /** @var DBManager */
    protected $dbManager = null;

    /** @var Database */
    protected $dbms = null;

    /**
     * Abstract function that executes the command line.
     */
    abstract public function execute();

    /**
     * @param array $arguments
     */
    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
        Container::initContainer();

        $this->terminal = Container::getClassInstance('terminal');
        $this->dbManager = Container::getClassInstance('dbmanager');
        $this->config = Container::getClassInstance('config');
    }

    /**
     * Connect to the database.
     * @param \stdClass $config
     * @return bool|Database
     */
    protected function connectToDatabase(\stdClass $config)
    {
        // Try to connect to the database.
        $dbms = $this->dbManager->connect(
            $config->datatype,
            $config->hostname,
            $config->database,
            $config->username,
            $config->password
        );
        if (false === $dbms) {
            return false;
        }

        // Add the database connection to the class container.
        Container::addContainer('dbms', $dbms);
        return $dbms;
    }

    /**
     * Prepare command by creating required objects and validating config.
     * @return bool
     */
    protected function prepareCommand()
    {
        // Check if <dbt init> has been ran.
        if (!$this->config->isInitialised()) {
            $this->terminal->display(
                'dbtrack has not been initialised. Run <dbt init> first.'
            );
            return false;
        }

        // Try to load config.
        $config = $this->config->loadConfig();
        if (false === $config) {
            $this->terminal->display(
                'Could not load config. Please run <dbt init> again.'
            );
            return false;
        }

        // Connect to the database.
        $dbms = $this->connectToDatabase($config);
        if (false === $dbms) {
            $this->terminal->display(
                'Could not connect to database. Try running <dbt init> again.'
            );
            return false;
        }

        $this->dbms = $dbms;

        return true;
    }

    /**
     * Filter which tables need to be returned from the whole table list.
     * @param array $allTables
     * @param array $toTrack
     * @param array $toIgnore
     * @return array
     */
    protected function filterTables(
        array $allTables,
        array $toTrack,
        array $toIgnore
    ) {
        $tables = (0 == count($toTrack))
            ? $allTables
            : $this->filterTablesList($allTables, $toTrack);

        if (0 < count($toIgnore)) {
            $toIgnore = $this->filterTablesList($tables, $toIgnore);
            foreach ($toIgnore as $table) {
                $i = array_search($table, $tables);
                if (false !== $i) {
                    unset($tables[$i]);
                }
            }
        }

        return array_values($tables);
    }

    /**
     * Return only tables that exist in both variables passed (check for
     * wildcards as well).
     * @param array $allTables
     * @param array $filterTables
     * @return array
     */
    protected function filterTablesList(array $allTables, array $filterTables)
    {
        $tables = array();
        foreach ($filterTables as $table) {
            // Check if we have any wildcards in the table name.
            if (false === stripos($table, '*')) {
                if (in_array($table, $allTables)) {
                    $tables[] = $table;
                }
            } else {
                // Clean parameter.
                $table = preg_replace('/[^\*A-Za-z0-9_-]/i', '', $table);
                if (!empty($table)) {
                    // Create regex.
                    $regex = '/' . str_replace('*', '.*', $table) . '/i';
                    $found = preg_grep($regex, $allTables);
                    if (0 < count($found)) {
                        $tables = array_merge($tables, $found);
                    }
                }
            }
        }

        return $tables;
    }

    /**
     * Parameters may have a shortcode as well. This return any of them set.
     * For instance, if both --track and -t are set this will return a merged
     * array of the 2.
     * @param array $arguments
     * @param $longArg
     * @param $shortArg
     * @return array
     */
    protected function getArguments(array $arguments, $longArg, $shortArg)
    {
        $params = array();
        if (isset($arguments[$longArg]) || isset($arguments[$shortArg])) {
            if (isset($arguments[$longArg], $arguments[$shortArg])) {
                $args1 = is_array($arguments[$longArg]) ?
                    $arguments[$longArg] :
                    array($arguments[$longArg]);
                $args2 = is_array($arguments[$shortArg]) ?
                    $arguments[$shortArg] :
                    array($arguments[$shortArg]);
                $params = array_merge($args1, $args2);
            } else {
                $params = isset($arguments[$longArg]) ?
                    $arguments[$longArg] :
                    $arguments[$shortArg];

                // If only 1 param is passed it won't be an array.
                if (!is_array($params)) {
                    $params = array($params);
                }
            }
        }

        return $params;
    }
}
