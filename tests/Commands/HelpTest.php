<?php
namespace DBtrack\Commands;

use DBtrack\Base\Container;
use DBtrack\Base\Database;
use DBtrack\Base\Events;
use DBtrack\Databases\MySQL;
use DBtrack\Tests\ClimateHelper;
use DBtrack\Tests\DatabaseHelper;

class HelpTest extends \PHPUnit_Extensions_Database_TestCase
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
        return new \PHPUnit_Extensions_Database_DataSet_CompositeDataSet(
            array()
        );
    }

    public function setUp()
    {
        require_once(dirname(__FILE__) . '/../Helpers/DatabaseHelper.php');
        require_once(dirname(__FILE__) . '/../Helpers/ClimateHelper.php');

        Container::initContainer(true);
        $connection = $this->getConnection();
        $this->dbHelper = new DatabaseHelper($this->dbms);
        $this->dbHelper->deleteAllTables();

        Events::toggleTriggers('eventDisplayMessage', false);

        $climate = new ClimateHelper();
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

    public function testHelp()
    {
        ClimateHelper::cleanUp();
        $system = $this->getMockBuilder('DBtrack\System\System')->getMock();
        Container::addContainer('system', $system, true);

        $system->method('glob')->willReturn(
            array(
                '/nothing/commandname.txt',
                '/nothing/commandname.more.txt'
            )
        );

        $system->method('file_exists')->willReturn(true);

        $system->method('file_get_contents')->will(
            $this->returnCallback(array($this, 'file_get_contents'))
        );

        $command = new Help(
            array('raw-command' => array(
                0 => '/some/path/dbt',
                1 => 'help'
            ))
        );
        $this->assertTrue($command->execute());
        $this->assertTrue(in_array('this-is-file-1', ClimateHelper::$output));

        $command = new Help(
            array('raw-command' => array(
                0 => '/some/path/dbt',
                1 => 'help',
                2 => 'commandname'
            ))
        );
        $this->assertTrue($command->execute());
        $this->assertTrue(in_array('this-is-file-2', ClimateHelper::$output));

        $command = new Help(
            array('raw-command' => array(
                0 => '/some/path/dbt',
                1 => 'help',
                2 => 'commandname',
                3 => 'extra'
            ))
        );

        $system = $this->getMockBuilder('DBtrack\System\System')->getMock();
        Container::addContainer('system', $system, true);

        $system->method('file_exists')->willReturn(false);

        $system->method('glob')->willReturn(
            array(
                '/nothing/commandname.txt',
                '/nothing/commandname.more.txt'
            )
        );

        $system->method('file_get_contents')->will(
            $this->returnCallback(array($this, 'file_get_contents'))
        );

        $command = new Help(
            array('raw-command' => array(
                0 => '/some/path/dbt',
                1 => 'help',
                2 => 'commandname',
                3 => 'extra',
                4 => 'nothing'
            ))
        );

        ClimateHelper::cleanUp();
        $this->assertTrue($command->execute());
        $this->assertEquals(5, count(ClimateHelper::$output));
    }

    public function file_get_contents()
    {
        $arguments = func_get_args();
        $filename = basename($arguments[0]);
        switch ($filename) {
            case 'commandname.txt':
                return 'this-is-file-1';
            case 'commandname.more.txt':
                return 'this-is-file-2';
            default:
                return '';
        }
    }
}