<?php
namespace DBtrack\Databases;

use DBtrack\Base\Container;
use DBtrack\Base\Database;
use DBtrack\Base\Events;
use DBtrack\Tests\DatabaseHelper;

class MySQLTest extends \PHPUnit_Extensions_Database_TestCase
{
    /** @var null */
    private $conn = null;

    /** @var Database */
    protected $dbms = null;

    /** @var DatabaseHelper */
    protected $dbHelper = null;

    /** @var null */
    static private $pdo = null;

    protected function getConnection()
    {
        if (null === $this->conn) {
            if (self::$pdo == null) {
                self::$pdo = new \PDO(
                    $GLOBALS['DB_DSN_MYSQL'],
                    $GLOBALS['DB_USERNAME'],
                    $GLOBALS['DB_PASSWORD']
                );
            }

            $this->conn = $this->createDefaultDBConnection(
                self::$pdo,
                $GLOBALS['DB_DBNAME']
            );
        }

        $this->dbms = new MySQL(
            $GLOBALS['DB_HOSTNAME'],
            $GLOBALS['DB_DBNAME'],
            $GLOBALS['DB_USERNAME'],
            $GLOBALS['DB_PASSWORD']
        );

        $this->dbms->setConnection($this->conn->getConnection());
        Container::addContainer('dbms', $this->dbms, true);

        return $this->conn;
    }

    protected function getDataSet()
    {
        $datasets = new \PHPUnit_Extensions_Database_DataSet_CompositeDataSet(
            array()
        );
        return $datasets;
    }

    public function setUp()
    {
        require_once(dirname(__FILE__) . '/../Helpers/DatabaseHelper.php');

        Container::initContainer(true);
        $connection = $this->getConnection();
        $this->dbHelper = new DatabaseHelper($this->dbms);
        $this->dbHelper->deleteAllTables();

        Events::toggleTriggers('eventDisplayMessage', false);
    }

    public function testConnect()
    {
        $database = new MySQL(
            $GLOBALS['DB_HOSTNAME'],
            $GLOBALS['DB_DBNAME'],
            $GLOBALS['DB_USERNAME'],
            $GLOBALS['DB_PASSWORD'] . 'ERROR'
        );

        // Connect with an error.
        $this->assertFalse($database->connect());

        // Connect.
        $database = new MySQL(
            $GLOBALS['DB_HOSTNAME'],
            $GLOBALS['DB_DBNAME'],
            $GLOBALS['DB_USERNAME'],
            $GLOBALS['DB_PASSWORD']
        );

        $this->assertTrue($database->connect());
        // Try to connect again, should be true.
        $this->assertTrue($database->connect());
    }

    public function testTables()
    {
        $this->dbHelper->deleteAllTables();
        $this->dbHelper->createAllTables();

        // Connect.
        $database = new MySQL(
            $GLOBALS['DB_HOSTNAME'],
            $GLOBALS['DB_DBNAME'],
            $GLOBALS['DB_USERNAME'],
            $GLOBALS['DB_PASSWORD']
        );

        $this->assertTrue($database->connect());

        $tables = $database->getTableList();
        $this->assertEquals(4, count($tables));

        $columns = $database->getTableColumns('dbtrack_config');
        $this->assertEquals(2, count($columns));

        $primaryKeys = $database->getTablePrimaryKeys('dbtrack_config');
        $this->assertEquals(1, count($primaryKeys));
        $this->assertEquals('name', $primaryKeys[0]);

        $sql = "DROP TABLE IF EXISTS two_primary_keys;
                CREATE TABLE two_primary_keys (
                  id1 INT NOT NULL,
                  id2 INT NOT NULL,
                  col1 VARCHAR(45) NULL,
                  col2 VARCHAR(45) NULL,
                  PRIMARY KEY (id1, id2)
                );";
        $database->executeScript($sql);
        $primaryKeys = $database->getTablePrimaryKeys('two_primary_keys');
        $this->assertEquals(2, count($primaryKeys));

        $tables = $database->getTableList();
        $this->assertTrue(in_array('two_primary_keys', $tables));

        $database->deleteTable('two_primary_keys');
        $tables = $database->getTableList();
        $this->assertFalse(in_array('two_primary_keys', $tables));
    }

    public function tearDown()
    {
        $this->dbHelper->deleteAllTriggers();
        $this->dbHelper->deleteAllTables();
    }

    public function testTriggers()
    {
        $this->dbHelper->deleteAllTables();
        $this->dbHelper->createAllTables();

        // Connect.
        $database = new MySQL(
            $GLOBALS['DB_HOSTNAME'],
            $GLOBALS['DB_DBNAME'],
            $GLOBALS['DB_USERNAME'],
            $GLOBALS['DB_PASSWORD']
        );

        $this->assertTrue($database->connect());
        $sql = "DROP TABLE IF EXISTS two_primary_keys;
                CREATE TABLE two_primary_keys (
                  id1 INT NOT NULL,
                  id2 INT NOT NULL,
                  col1 VARCHAR(45) NULL,
                  col2 VARCHAR(45) NULL,
                  PRIMARY KEY (id1, id2)
                );";
        $database->executeScript($sql);

        $this->dbHelper->deleteAllTriggers();
        $this->assertTrue($database->createTrackingTrigger('two_primary_keys'));
        $this->assertEquals(3, count($database->getTriggerList()));

        Container::initContainer(true);
        $system = $this->getMockBuilder('DBtrack\System\System')->getMock();
        Container::addContainer('system', $system, true);

        $stub = $this->getMockBuilder('DBtrack\Databases\MySQL')
            ->setMethods(
                array(
                    'loadSQLFile',
                    'getTableColumns',
                    'getTablePrimaryKeys'
                )
            )->setConstructorArgs(
                array(
                    $GLOBALS['DB_HOSTNAME'],
                    $GLOBALS['DB_DBNAME'],
                    $GLOBALS['DB_USERNAME'],
                    $GLOBALS['DB_PASSWORD']
                )
            )->getMock();

        $stub->method('loadSQLFile')->willReturn(false);
        $stub->method('getTableColumns')->willReturn(array('id', 'column1'));
        $stub->method('getTablePrimaryKeys')->willReturn(array('id'));

        $this->dbHelper->deleteAllTriggers();
        $this->assertFalse($stub->createTrackingTrigger('two_primary_keys'));
    }
}