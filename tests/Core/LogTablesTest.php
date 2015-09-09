<?php
namespace DBtrack\Core;

use DBtrack\Base\Container;
use DBtrack\Base\Database;
use DBtrack\Base\Events;
use DBtrack\Databases\MySQL;
use DBtrack\Tests\DatabaseHelper;

class LogTablesTest extends \PHPUnit_Extensions_Database_TestCase
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

    public function tearDown()
    {
        $this->dbHelper->deleteAllTriggers();
        $this->dbHelper->deleteAllTables();
    }

    public function testPrepare()
    {
        Container::initContainer(true);
        $this->getConnection();

        $this->dbHelper->deleteAllTables();
        $this->dbHelper->createAllTables();
        $logTables = new LogTables();
        $this->assertTrue($logTables->prepare());

        $this->dbHelper->deleteAllTables();
        $this->assertTrue($logTables->prepare());

        $this->dbHelper->createAllTables();
        $logTables->deleteTrackTables();
        $this->assertEquals(0, count($this->dbms->getTableList()));
    }

    public function testPrepareFails()
    {
        $stub = $this->getMockBuilder('DBtrack\Core\LogTables')
            ->setMethods(array('createTables'))
            ->getMock();

        $stub->method('createTables')->will(
            $this->onConsecutiveCalls(
                false,
                true
            )
        );
        $this->assertFalse($stub->prepare());

        $this->dbHelper->deleteAllTables();
        $this->dbHelper->createAllTables();
        $this->dbms->executeQuery('TRUNCATE TABLE dbtrack_config');
        try {
            $stub->prepare();
            $this->assertTrue(false); // To fail.
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        // New stub.
        $stub = $this->getMockBuilder('DBtrack\Core\LogTables')
            ->setMethods(array('loadSQLFile'))
            ->getMock();

        $stub->method('loadSQLFile')->willReturn(false);

        $this->dbHelper->deleteAllTables();
        try {
            $stub->prepare();
            $this->assertTrue(false); // To fail.
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        $this->dbHelper->deleteAllTables();
        $dbms = $this->getMockBuilder('DBtrack\Databases\MySQL')
            ->setMethods(array('loadSQLFile', 'tableExists'))
            ->setConstructorArgs(
                array(
                    $GLOBALS['DB_HOSTNAME'],
                    $GLOBALS['DB_DBNAME'],
                    $GLOBALS['DB_USERNAME'],
                    $GLOBALS['DB_PASSWORD']
                )
            )->getMock();

        $dbms->method('loadSQLFile')->willReturn(false);
        $dbms->method('tableExists')->willReturn(false);
        Container::addContainer('dbms', $dbms, true);

        // New stub.
        $stub = $this->getMockBuilder('DBtrack\Core\LogTables')
            ->setMethods(array('isPrepared'))
            ->getMock();
        $stub->method('isPrepared')->willReturn(false);

        try {
            $stub->prepare();
            $this->assertTrue(false); // To fail.
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }
}