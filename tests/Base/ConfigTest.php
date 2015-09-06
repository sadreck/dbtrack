<?php
namespace DBtrack\Base;

use DBtrack\Databases\MySQL;
use DBtrack\System\System;
use DBtrack\Tests\DatabaseHelper;

class ConfigTest extends \PHPUnit_Extensions_Database_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $stubSystem = null;

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

    public function testConstructor()
    {
        $this->setContainers();

        $this->stubSystem
            ->expects($this->any())
            ->method('getcwd')
            ->willReturn('/nothing');

        $configObject = new Config();
        $this->assertEquals('/nothing/.dbtrack', $configObject->dbtDirectory);

        $this->stubSystem
            ->expects($this->once())
            ->method('is_dir')
            ->willReturn(true);
        $this->stubSystem
            ->expects($this->once())
            ->method('is_writable')
            ->willReturn(false);

        try {
            $configObject = new Config();
            $this->assertTrue(false); // Fail.
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testIsInitialised()
    {
        $this->setContainers();
        $configObject = new Config('/nothing');

        $this->stubSystem
            ->method('file_exists')
            ->will($this->onConsecutiveCalls(true, false));

        $this->assertTrue($configObject->isInitialised());
        $this->assertFalse($configObject->isInitialised());
    }

    public function testSaveConfig()
    {
        $this->setContainers();
        $configObject = new Config('/nothing');

        $config = (object)array('test' => 'yes');

        $this->stubSystem
            ->method('file_exists')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, true));

        $this->stubSystem
            ->method('unlink')
            ->will($this->onConsecutiveCalls(false));

        $this->stubSystem
            ->method('is_dir')
            ->will($this->onConsecutiveCalls(false, false, true, true, true, true));

        // File exists but can't delete.
        $this->assertFalse($configObject->saveConfig($config));

        // Can't create directory.
        $this->assertFalse($configObject->saveConfig($config));

        // Can't create directory.
        $this->assertFalse($configObject->saveConfig($config));

        // Can't create directory.
        $this->assertTrue($configObject->saveConfig($config));
    }

    public function testLoadConfig()
    {
        $this->setContainers();

        $configObject = new Config('/nothing');

        $this->stubSystem
            ->method('file_exists')
            ->will(
                $this->onConsecutiveCalls(
                    // 1
                    false,
                    // 2
                    true,
                    // 3
                    true,
                    // 4
                    true,
                    // 5
                    true
                )
            );

        $config = array(
            'datatype' => 'MySQL',
            'hostname' => 'localhost',
            'database' => 'nodb',
            'username' => 'myuser',
            'password' => 'mypass'
        );

        $this->stubSystem
            ->method('file_get_contents')
            ->will(
                $this->onConsecutiveCalls(
                    // 2
                    '',
                    // 3
                    '{"test":"yes"}',
                    // 4
                    json_encode($config),
                    // 5
                    'broken-json'
                )
            );

        // Not initialised.
        $this->assertFalse($configObject->loadConfig());

        // Empty file.
        $this->assertFalse($configObject->loadConfig());

        // Invalid config.
        $this->assertFalse($configObject->loadConfig());

        // All OK.
        $this->assertEquals(
            json_encode($config),
            json_encode($configObject->loadConfig())
        );

        // Broken config.
        $this->assertFalse($configObject->loadConfig());
    }

    public function testIsRunning()
    {
        $this->restoreContainers();

        $configObject = new Config('/nothing');

        // Config table does not exist.
        $this->assertFalse($configObject->isRunning());

        $this->dbHelper->deleteTable('dbtrack_config');
        $this->dbHelper->createTable('dbtrack_config');

        // No values found - is not running.
        $this->assertFalse($configObject->isRunning());

        $this->dbms->executeQuery(
            "INSERT INTO dbtrack_config (name, value) VALUES('running', '1')"
        );

        // Value running is set to 1 - is running.
        $this->assertTrue($configObject->isRunning());
    }

    public function testSetRunning()
    {
        $this->restoreContainers();

        $configObject = new Config('/nothing');

        // Config table does not exist.
        $this->assertFalse($configObject->setRunning(true));

        $this->dbHelper->deleteTable('dbtrack_config');
        $this->dbHelper->createTable('dbtrack_config');

        $configObject->setRunning(true);
        $this->assertTrue($configObject->isRunning());

        $configObject->setRunning(false);
        $this->assertFalse($configObject->isRunning());
    }

    public function testDeleteConfigDirectory()
    {
        $this->setContainers();

        $configObject = new Config('/nothing');

        $this->stubSystem
            ->method('is_dir')
            ->will(
                $this->onConsecutiveCalls(
                    // 1
                    false,
                    false,
                    // 2
                    true,
                    false,
                    true
                )
            );

        $this->stubSystem
            ->method('scandir')
            ->will(
                $this->onConsecutiveCalls(
                    // 2
                    array(
                        '.',
                        '..',
                        'file1.txt',
                        'directory1'
                    )
                )
            );

        $this->assertTrue($configObject->deleteConfigDirectory());

        // This is just for 100% code coverage.
        $this->assertTrue($configObject->deleteConfigDirectory());
    }

    public function setUp()
    {
        require_once(dirname(__FILE__) . '/../Helpers/DatabaseHelper.php');

        Container::initContainer(true);
        $connection = $this->getConnection();
        $this->dbHelper = new DatabaseHelper($this->dbms);
        $this->dbHelper->deleteAllTables();
    }

    public function tearDown()
    {
        $this->dbHelper = new DatabaseHelper($this->dbms);
        $this->dbHelper->deleteAllTables();
    }

    protected function setContainers()
    {
        $this->stubSystem = $this->getMockBuilder("DBtrack\\System\\System")
            ->getMock();

        Container::addContainer('system', $this->stubSystem, true);
    }

    protected function restoreContainers()
    {
        $system = new System();
        Container::addContainer('system', $system, true);
    }
}