<?php
namespace DBtrack\Core;

use DBtrack\Base\Container;
use DBtrack\Base\Database;
use DBtrack\Base\Events;
use DBtrack\Databases\MySQL;
use DBtrack\Tests\DatabaseHelper;

class RevertManagerTest extends \PHPUnit_Extensions_Database_TestCase
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
        return $this->createMySQLXMLDataSet(
            dirname(__FILE__) . '/../Fixtures/test1.xml'
        );
    }

    public function setUp()
    {
        require_once(dirname(__FILE__) . '/../Helpers/DatabaseHelper.php');

        Container::initContainer(true);
        $connection = $this->getConnection();
        $this->dbHelper = new DatabaseHelper($this->dbms);
        $this->dbHelper->deleteAllTables();

        Events::toggleTriggers('eventDisplayMessage', false);

        $climate = $this->getMock('League\CLImate\CLImate', array('progress'));
        $climate->method('progress')->willReturnCallback(
            function ($value) {
                $progress = $this->getMock(
                    'League\CLImate\TerminalObject\Dynamic\Progress',
                    array('current')
                );
                $progress->method('current')->willReturn(false);
                return $progress;
            }
        );
        Container::addContainer('climate', $climate, true);
    }

    public function tearDown()
    {
        $this->dbHelper->deleteAllTriggers();
        $this->dbHelper->deleteAllTables();
    }

    protected function refreshDatabase()
    {
        $this->dbHelper->deleteAllTables();
        $this->dbHelper->createAllTables(true);
        $this->getDatabaseTester()->setDataSet($this->getDataSet());
        $this->getDatabaseTester()->onSetUp();
    }

    public function testRevert()
    {
        $this->refreshDatabase();

        $revertManager = new RevertManager();
        $result = $revertManager->revert(777);
        $this->assertFalse($result);

        $result = $revertManager->revert(2);
        $this->assertEquals(5, $result);
    }

    public function testFail()
    {
        $this->refreshDatabase();

        // Test 1
        $stub = $this->getMockBuilder('DBtrack\Core\RevertManager')
            ->setMethods(array('revertGroup'))
            ->getMock();
        $stub->method('revertGroup')->willReturn(false);
        $this->assertFalse($stub->revert(2));

        $this->refreshDatabase();

        // Test 2
        $stub = $this->getMockBuilder('DBtrack\Core\RevertManager')
            ->setMethods(array('revertActions'))
            ->getMock();
        $stub->method('revertActions')->willReturn(false);
        $this->assertFalse($stub->revert(2));
    }
}