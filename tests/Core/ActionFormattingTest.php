<?php
namespace DBtrack\Core;

use DBtrack\Base\Container;
use DBtrack\Base\Database;
use DBtrack\Base\Events;
use DBtrack\Databases\MySQL;
use DBtrack\Tests\DatabaseHelper;

class ActionFormattingTest extends \PHPUnit_Extensions_Database_TestCase
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

    public function testFormat()
    {
        $action = (object)array(
            'actionType' => 2,
            'tableName' => 'atest',
            'primaryKeys' => array(
                (object)array(
                    'name' => 'id',
                    'value' => 3
                )
            ),
            'newData' => (object)array(
                'id' => 3,
                'name' => 'Name2',
                'subject' => 'Record2',
                'timestamper' => 3333
            ),
            'oldData' => (object)array(
                'name' => 'Name3',
                'subject' => 'Record3'
            )
        );

        $expected = array(
            'atest' => 'UPDATE',
            'id' => 3,
            'name' => '<light_yellow>Name2...</light_yellow>',
            'subject' => '<light_yellow>Recor...</light_yellow>',
            'timestamper' => 3333
        );

        $actionFormatting = new ActionFormatting();
        $row = $actionFormatting->formatRow($action, 5);
        $this->assertEquals($expected, $row);

        $rows = $actionFormatting->formatList(array($action), 5);
        $this->assertEquals(1, count($rows));
        $this->assertEquals($expected, $rows[0]);
    }
}