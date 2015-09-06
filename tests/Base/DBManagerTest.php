<?php
namespace DBtrack\Base;

class DBManagerTest extends \PHPUnit_Extensions_Database_TestCase
{
    /** @var null */
    private $conn = null;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dbms = null;

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

        $this->dbms = $this->getMockBuilder("DBtrack\\Base\\Database")
            ->setConstructorArgs(array('a', 'b', 'c', 'd'))
            ->getMockForAbstractClass();

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

    public function testGetSupportedDatabases()
    {
        $system = $this->getMockBuilder("DBtrack\\System\\System")->getMock();
        Container::addContainer('system', $system, true);

        $system->method('glob')->will(
            $this->onConsecutiveCalls(
                array('/some/path/MySQL.php', '/some/path/pgsql.php')
            )
        );

        $dbManager = new DBManager();
        $list = $dbManager->getSupportedDatabases();

        $this->assertEquals(2, count($list));
        $this->assertEquals('MySQL', $list[0]);
        $this->assertEquals('pgsql', $list[1]);
    }

    public function testGetDatabaseClass()
    {
        $system = $this->getMockBuilder("DBtrack\\System\\System")->getMock();
        Container::addContainer('system', $system, true);

        $system->method('glob')->will(
            $this->onConsecutiveCalls(
                array('/some/path/MySQL.php')
            )
        );

        $dbManager = new DBManager();
        $this->assertEquals('MySQL', $dbManager->getDatabaseClass('mysql'));
    }

    public function testConnect()
    {
        $system = $this->getMockBuilder("DBtrack\\System\\System")->getMock();
        Container::addContainer('system', $system, true);

        $system->method('glob')->will(
            $this->onConsecutiveCalls(
                array('/some/path/MySQL.php', '/some/path/pgsql.php'),
                array('/some/path/Dummy.php'),
                array('/some/path/Dummy.php')
            )
        );

        Events::toggleTriggers('eventDisplayMessage', false);

        $dbManager = new DBManager();
        $this->assertFalse($dbManager->connect('dummy', '', '', '', ''));

        $mock = $this->getMockBuilder('DBtrack\Base\Database')
            ->setMockClassName('DummyDatabaseClass')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        class_alias('DummyDatabaseClass', 'DBtrack\Databases\Dummy');

        $this->assertFalse($dbManager->connect('dummy', 'h', 'd', 'u', 'p'));
    }

    public function testTriggers()
    {
        $this->getConnection();

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

        $this->dbms->method('createTrackingTrigger')->will(
            $this->onConsecutiveCalls(
                // 1
                false,
                // 2
                true
            )
        );

        $dbManager = new DBManager();
        $dbManager->setDBMS($this->dbms);
        $this->assertFalse($dbManager->createTriggers(array('table1')));
        $this->assertTrue($dbManager->createTriggers(array('table1')));

        $this->dbms->method('getTriggerList')->will(
            $this->onConsecutiveCalls(
                // 1
                array(
                    'trigger1',
                    'trigger2',
                    'trigger3',
                    'dbtrack_trigger',
                )
            )
        );

        $this->dbms->method('deleteTrigger')->willReturn(true);

        $this->assertTrue($dbManager->deleteTriggers());
    }

    public function setUp()
    {
        Container::initContainer(true);
    }
}