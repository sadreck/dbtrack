<?php
namespace DBtrack\Commands;

use DBtrack\Base\Container;
use DBtrack\Base\Database;
use DBtrack\Base\Events;
use DBtrack\Databases\MySQL;
use DBtrack\Tests\ClimateHelper;
use DBtrack\Tests\DatabaseHelper;

class ConfigTest extends \PHPUnit_Extensions_Database_TestCase
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

    public function testConfigFail()
    {
        ClimateHelper::cleanUp();
        $config = $this
            ->getMockBuilder('DBtrack\Base\Config')
            ->disableOriginalConstructor()
            ->getMock();
        Container::addContainer('config', $config, true);

        $config->method('isInitialised')->willReturn(false);
        $command = new Config(array());
        $this->assertFalse($command->execute());
        $this->assertEquals(1, count(ClimateHelper::$output));
        $this->assertEquals(
            'dbtrack is not initialised',
            ClimateHelper::$output[0]
        );
    }

    public function testConfigSuccess()
    {
        ClimateHelper::cleanUp();
        $config = $this
            ->getMockBuilder('DBtrack\Base\Config')
            ->disableOriginalConstructor()
            ->getMock();
        Container::addContainer('config', $config, true);

        $config->method('isInitialised')->willReturn(true);
        $config->method('loadConfig')->willReturn(
            (object)array(
                'datatype' => 'MySQL',
                'hostname' => 'localhost',
                'database' => 'mydummydb',
                'username' => 'mydummyusername',
                'password' => 'hahaDUMMY'
            )
        );

        ClimateHelper::cleanUp();
        $command = new Config(array());
        $this->assertTrue($command->execute());
        $data = implode(PHP_EOL, ClimateHelper::$output);
        $this->assertGreaterThanOrEqual(0, strpos($data, 'MySQL'));
        $this->assertGreaterThanOrEqual(0, strpos($data, 'localhost'));
        $this->assertGreaterThanOrEqual(0, strpos($data, 'mydummydb'));
        $this->assertGreaterThanOrEqual(0, strpos($data, 'mydummyusername'));
        $this->assertFalse(strpos($data, 'hahaDUMMY'));

        ClimateHelper::cleanUp();
        $command = new Config(
            array('all' => '')
        );
        $this->assertTrue($command->execute());
        $data = implode(PHP_EOL, ClimateHelper::$output);
        $this->assertGreaterThanOrEqual(0, strpos($data, 'MySQL'));
        $this->assertGreaterThanOrEqual(0, strpos($data, 'localhost'));
        $this->assertGreaterThanOrEqual(0, strpos($data, 'mydummydb'));
        $this->assertGreaterThanOrEqual(0, strpos($data, 'mydummyusername'));
        $this->assertGreaterThanOrEqual(0, strpos($data, 'hahaDUMMY'));
    }
}