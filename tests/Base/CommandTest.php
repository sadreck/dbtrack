<?php
namespace DBtrack\Base;

class CommandTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Container::initContainer(true);
    }

    public function testConstructor()
    {
        $dbManager = $this->getMockBuilder('DBtrack\Base\DBManager')->getMock();
        Container::addContainer('dbmanager', $dbManager, true);

        $commandStub = $this->getMockBuilder('DBtrack\Base\Command')
            ->setConstructorArgs(array(array()))
            ->getMockForAbstractClass();
        $class = new $commandStub(array());
    }

    public function testConnectToDatabase()
    {
        $dbManager = $this->getMockBuilder('DBtrack\Base\DBManager')->getMock();
        $dbManager->method('connect')->will(
            $this->onConsecutiveCalls(
                // 1
                false,
                // 2
                $this->getMockBuilder('DBtrack\Base\Database')->disableOriginalConstructor()->getMockForAbstractClass()
            )
        );
        Container::addContainer('dbmanager', $dbManager, true);

        $commandStub = $this->getMockBuilder('DBtrack\Base\Command')
            ->setConstructorArgs(array(array()))
            ->getMockForAbstractClass();

        $config = (object)array(
            'datatype' => 'Dummy',
            'hostname' => 'localhost',
            'database' => 'nodb',
            'username' => 'myuser',
            'password' => 'mypass'
        );

        $method = self::getMethod('connectToDatabase');
        $this->assertFalse($method->invokeArgs($commandStub, array($config)));

        $dbms = $method->invokeArgs($commandStub, array($config));
        $this->assertEquals('Mock_Database_', substr(get_class($dbms), 0, 14));
    }

    protected static function getMethod($name)
    {
        $class = new \ReflectionClass('DBtrack\Base\Command');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testFilterTables()
    {
        $commandStub = $this->getMockBuilder('DBtrack\Base\Command')
            ->setConstructorArgs(array(array()))
            ->getMockForAbstractClass();

        $allTables = array(
            'table1',
            'table2',
            'table3',
            'notable1',
            'notable2'
        );

        $method = self::getMethod('filterTables');
        $tables = $method->invokeArgs(
            $commandStub,
            array($allTables, array(), array())
        );
        $this->assertEquals(5, count($tables));

        $tables = $method->invokeArgs(
            $commandStub,
            array($allTables, array('table1'), array())
        );
        $this->assertEquals(1, count($tables));
        $this->assertEquals('table1', $tables[0]);

        $tables = $method->invokeArgs(
            $commandStub,
            array($allTables, array(), array('no*'))
        );
        $this->assertEquals(3, count($tables));
        $this->assertEquals('table1', $tables[0]);
        $this->assertEquals('table2', $tables[1]);
        $this->assertEquals('table3', $tables[2]);
    }

    public function testGetArguments()
    {
        $commandStub = $this->getMockBuilder('DBtrack\Base\Command')
            ->setConstructorArgs(array(array()))
            ->getMockForAbstractClass();

        $arguments = array(
            't' => array('table1', 'table2'),
            'table' => array('table3'),
            'nothing' => 'nothing'
        );

        $method = self::getMethod('getArguments');
        $tables = $method->invokeArgs(
            $commandStub,
            array($arguments, 't', 'table')
        );
        $this->assertEquals(3, count($tables));

        $nothing = $method->invokeArgs(
            $commandStub,
            array($arguments, 'nothing', 'n')
        );
        $this->assertEquals(1, count($nothing));
        $this->assertEquals('nothing', $nothing[0]);
    }

    public function testPrepareCommand()
    {
        Container::initContainer(true);

        $climate = $this->getMock('League\CLImate\CLImate');
        Container::addContainer('climate', $climate, true);

        $config = $this->getMock('DBtrack\Base\Config');

        $config->method('isInitialised')->will(
            $this->onConsecutiveCalls(
                // 1
                false,
                // 2
                true,
                // 3
                true,
                // 4
                true
            )
        );

        $config->method('loadConfig')->will(
            $this->onConsecutiveCalls(
                // 2
                false,
                // 3
                new \stdClass(),
                // 4
                new \stdClass()
            )
        );

        Container::addContainer('config', $config, true);

        $commandStub = $this->getMockBuilder('DBtrack\Base\Command')
            ->setMethods(array('execute', 'connectToDatabase'))
            ->setConstructorArgs(array(array()))
            ->getMock();
        $commandStub->method('connectToDatabase')->will(
            $this->onConsecutiveCalls(
                // 3
                false,
                // 4
                true
            )
        );

        $method = self::getMethod('prepareCommand');
        $this->assertFalse($method->invokeArgs($commandStub, array()));
        $this->assertFalse($method->invokeArgs($commandStub, array()));
        $this->assertFalse($method->invokeArgs($commandStub, array()));
        $this->assertTrue($method->invokeArgs($commandStub, array()));
    }
}