<?php
namespace DBtrack\Base;

use DBtrack\Tests\DatabaseHelper;

class DatabaseTest extends \PHPUnit_Extensions_Database_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $stubSystem = null;

    /** @var null */
    private $conn = null;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
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

    public function setUp()
    {
        Container::initContainer(true);

        require_once(dirname(__FILE__) . '/../Helpers/DatabaseHelper.php');

        $connection = $this->getConnection();
        $this->dbHelper = new DatabaseHelper($this->dbms);
        $this->dbHelper->deleteAllTables();
    }

    public function tearDown()
    {
        $this->dbHelper = new DatabaseHelper($this->dbms);
        $this->dbHelper->deleteAllTables();
    }

    public function testTableExists()
    {
        $this->dbms->method('getTableList')->willReturn(
            array(
                'table1',
                'table2',
                'table3',
                'table4'
            )
        );

        $this->assertTrue($this->dbms->tableExists('table1'));
        $this->assertFalse($this->dbms->tableExists('table0'));
    }

    public function testGetType()
    {
        $this->dbms->setType('dbtype');
        $this->assertEquals('dbtype', $this->dbms->getType());
    }

    public function testRows()
    {
        $this->dbms->setType('MySQL');
        $this->dbHelper->deleteAllTables();
        $this->dbHelper->createAllTables();

        $this->dbms->executeScript(
            "INSERT INTO dbtrack_config(name, value)
            VALUES('name1', 'value1'), ('name2', 'value2'), ('name3', 'value3')"
        );

        $allRows = $this->dbms->getRows(
            "SELECT * FROM dbtrack_config WHERE name <> 'version'"
        );
        $this->assertEquals(3, count($allRows));

        $specificRows = $this->dbms->getRows(
            "SELECT * FROM dbtrack_config WHERE name IN(:name1, :name2)",
            array('name1' => 'name1', 'name2' => 'name2')
        );
        $this->assertEquals(2, count($specificRows));

        $oneRow = $this->dbms->getRow(
            "SELECT * FROM dbtrack_config WHERE name = :name3",
            array('name3' => 'name3')
        );
        $this->assertEquals('name3', $oneRow->name);

        $this->dbms->executeQuery(
            "DELETE FROM dbtrack_config WHERE name = :name1",
            array('name1' => 'name1')
        );
        $oneRow = $this->dbms->getRow(
            "SELECT * FROM dbtrack_config WHERE name = :name1",
            array('name1' => 'name1')
        );
        $this->assertFalse($oneRow);
    }

    public function testActionDescriptions()
    {
        $this->dbms->setType('MySQL');
        $this->dbHelper->deleteAllTables();
        $this->dbHelper->createAllTables();

        $this->assertEquals('INSERT', $this->dbms->getActionDescription(1));
        $this->assertEquals('', $this->dbms->getActionDescription(-1));

        $this->assertEquals(
            1,
            $this->dbms->getActionDescriptionFromText('INSERT')
        );

        $this->assertEquals(
            '',
            $this->dbms->getActionDescriptionFromText('ERROR')
        );
    }

    public function testTransactions()
    {
        $this->dbms->setType('MySQL');
        $this->dbHelper->deleteAllTables();
        $this->dbHelper->createAllTables();

        $this->dbms->beginTransaction();
        $this->dbms->executeScript(
            "INSERT INTO dbtrack_config(name, value)
            VALUES('name1', 'value1'), ('name2', 'value2'), ('name3', 'value3')"
        );
        $this->dbms->commitTransaction();

        $oneRow = $this->dbms->getRow(
            "SELECT * FROM dbtrack_config WHERE name = :name3",
            array('name3' => 'name3')
        );
        $this->assertEquals('name3', $oneRow->name);

        $this->dbms->beginTransaction();
        try {
            $this->dbms->executeQuery(
                "DELETE FROM dbtrack_config WHERE name = :name3",
                array('name3' => 'name3')
            );
            throw new \Exception('testing transaction rollback.');
        } catch (\Exception $e) {
            $this->dbms->rollbackTransaction();
        }
        $oneRow = $this->dbms->getRow(
            "SELECT * FROM dbtrack_config WHERE name = :name3",
            array('name3' => 'name3')
        );
        $this->assertEquals('name3', $oneRow->name);
    }

    public function testLoadSQLFile()
    {
        $system = $this->getMockBuilder("DBtrack\\System\\System")->getMock();
        Container::addContainer('system', $system, true);

        $system->method('file_exists')->will(
            $this->onConsecutiveCalls(
                // 1
                false,
                // 2
                true,
                // 3
                true
            )
        );

        $system->method('file_get_contents')->will(
            $this->onConsecutiveCalls(
                // 2
                '',
                // 3
                'test'
            )
        );

        $this->assertFalse($this->dbms->loadSQLFile(''));
        $this->assertFalse($this->dbms->loadSQLFile(''));
        $this->assertEquals('test', $this->dbms->loadSQLFile(''));
    }
}