<?php
namespace DBtrack\Core;

use DBtrack\Base\Container;
use DBtrack\Base\Database;
use DBtrack\Base\Events;
use DBtrack\Databases\MySQL;
use DBtrack\Tests\DatabaseHelper;

class ActionsTest extends \PHPUnit_Extensions_Database_TestCase
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

    public function testCommitList()
    {
        $this->refreshDatabase();

        $actions = new Actions();
        $list = $actions->getCommitsList();

        $this->assertEquals(
            array((object)array(
                'id' => 2,
                'message' => 'This is a test',
                'mintime' => 1441918123,
                'maxtime' => 1441918151,
                'actioncount' => 5
            )),
            $list
        );
    }

    public function testGroupExists()
    {
        $this->refreshDatabase();

        $actions = new Actions();
        $this->assertFalse($actions->groupExists(7));
        $this->assertTrue($actions->groupExists(2));
    }

    public function testGetActionsList()
    {
        $this->refreshDatabase();

        $actions = new Actions();
        $this->assertEquals(0, count($actions->getActionsList(7)));
        $fullRows = $actions->getActionsList(2);
        $this->assertEquals(5, count($fullRows));
        $this->assertEquals('Record3', $fullRows[4]->oldData->subject);
    }

    public function testFilterActions()
    {
        $this->refreshDatabase();

        $actions = new Actions();
        $fullRows = $actions->getActionsList(2);

        $inserts = $actions->filterActions(
            $fullRows,
            array('insert'),
            array()
        );
        $this->assertEquals(3, count($inserts));

        $noUpdates = $actions->filterActions(
            $fullRows,
            array(),
            array('update')
        );
        $this->assertEquals(4, count($noUpdates));

        $this->assertEquals(
            5,
            count($actions->filterActions($fullRows, array(), array()))
        );
    }
    public function testFilterTables()
    {
        $this->refreshDatabase();

        $actions = new Actions();
        $fullRows = $actions->getActionsList(2);

        $this->assertEquals(
            5,
            count($actions->filterTables($fullRows, array(), array()))
        );

        $this->assertEquals(
            5,
            count($actions->filterTables($fullRows, array('atest'), array()))
        );

        $this->assertEquals(
            0,
            count($actions->filterTables($fullRows, array(''), array('atest')))
        );
    }

    public function testExport()
    {
        $this->refreshDatabase();

        $actions = new Actions();
        $fullRows = $actions->getActionsList(2);

        $tempFile = tempnam(sys_get_temp_dir(), 'dbtrack');
        unlink($tempFile);

        $this->assertTrue($actions->export($fullRows, $tempFile));
        $this->assertEquals(315, filesize($tempFile));
        unlink($tempFile);
    }
}