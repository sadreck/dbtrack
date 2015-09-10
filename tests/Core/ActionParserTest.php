<?php
namespace DBtrack\Core;

use DBtrack\Base\Container;
use DBtrack\Base\Database;
use DBtrack\Base\Events;
use DBtrack\Databases\MySQL;
use DBtrack\Tests\DatabaseHelper;

class ActionParserTest extends \PHPUnit_Extensions_Database_TestCase
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

    protected static function getMethod($name)
    {
        $class = new \ReflectionClass('DBtrack\Core\ActionParser');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testGetGroupActions()
    {
        $this->refreshDatabase();

        $actionParser = new ActionParser();

        $getGroupActions = self::getMethod('getGroupActions');
        $actions = $getGroupActions->invokeArgs($actionParser, array(1));
        $this->assertEquals(0, count($actions));

        $actions = $getGroupActions->invokeArgs($actionParser, array(2));
        $this->assertEquals(5, count($actions));
        $this->assertEquals(81, $actions[1]->id);
    }

    public function testParser()
    {
        $this->refreshDatabase();

        $actionParser = new ActionParser();
        $parsedGroup = $actionParser->parseGroup(2);
        $this->assertEquals(5, count($parsedGroup));
        $this->assertEquals('Name2', $parsedGroup[3]->newData->name);

        $fullRows = $actionParser->getFullRows($parsedGroup);
        $this->assertEquals(5, count($fullRows));
        $this->assertEquals(3333, $fullRows[4]->newData->timestamper);

        $onlyInserts = $actionParser->filterByActions(
            $fullRows,
            array(),
            array()
        );
        $this->assertEquals(5, count($onlyInserts));

        $onlyInserts = $actionParser->filterByActions(
            $fullRows,
            array('INSERT', 'ERROR'),
            array()
        );
        $this->assertEquals(3, count($onlyInserts));
        $this->assertEquals(3, $onlyInserts[2]->primaryKeys[0]->value);

        $ignoreUpdates = $actionParser->filterByActions(
            $fullRows,
            array(),
            array('UPDATE')
        );
        $this->assertEquals(4, count($ignoreUpdates));
        $foundUpdate = false;
        foreach ($ignoreUpdates as $update) {
            if (2 == $update->actionType) {
                $foundUpdate = true;
                break;
            }
        }
        $this->assertFalse($foundUpdate);

        $onlyInserts = $actionParser->filterByActions(
            $fullRows,
            array('INSERT', 'UPDATE', 'DELETE'),
            array('UPDATE', 'DELETE')
        );
        $this->assertEquals(3, count($onlyInserts));
        $foundOther = false;
        foreach ($onlyInserts as $insert) {
            if (1 != $insert->actionType) {
                $foundOther = true;
                break;
            }
        }
        $this->assertFalse($foundOther);

        $filtered = $actionParser->filterByTables(
            $fullRows,
            array(),
            array()
        );
        $this->assertEquals(5, count($filtered));

        $filtered = $actionParser->filterByTables(
            $fullRows,
            array('atest'),
            array()
        );
        $this->assertEquals(5, count($filtered));

        $filtered = $actionParser->filterByTables(
            $fullRows,
            array(),
            array('atest')
        );
        $this->assertEquals(0, count($filtered));

        $filtered = $actionParser->filterByTables(
            $fullRows,
            array('atest'),
            array('atest')
        );
        $this->assertEquals(0, count($filtered));
    }

    public function testFailed()
    {
        $this->refreshDatabase();

        // Test 1
        $stub = $this->getMockBuilder('DBtrack\Core\ActionParser')
            ->setMethods(array('parseAction'))
            ->getMock();
        $stub->method('parseAction')->willReturn(false);
        $this->assertEquals(0, count($stub->parseGroup(2)));

        // Test 2
        $stub = $this->getMockBuilder('DBtrack\Core\ActionParser')
            ->setMethods(array('getGroupActions'))
            ->getMock();
        $stub->method('getGroupActions')->willReturn(
            array(
                (object)array(
                    'id' => 82,
                    'tablename' => 'atest'
                    // Missing attribute 'type'.
                )
            )
        );
        $this->assertEquals(0, count($stub->parseGroup(2)));

        // Test 3
        $stub = $this->getMockBuilder('DBtrack\Core\ActionParser')
            ->setMethods(array('getActionColumns'))
            ->getMock();
        $stub->method('getActionColumns')->willReturn(array());
        $this->assertEquals(0, count($stub->parseGroup(2)));

        $this->dbms->executeQuery('TRUNCATE TABLE atest');
        $actionParser = new ActionParser();
        $this->assertEquals(4, count($actionParser->parseGroup(2)));

    }

    protected function refreshDatabase()
    {
        $this->dbHelper->deleteAllTables();
        $this->dbHelper->createAllTables(true);
        $this->getDatabaseTester()->setDataSet($this->getDataSet());
        $this->getDatabaseTester()->onSetUp();
    }
}