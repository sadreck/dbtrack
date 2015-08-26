<?php
namespace DBtrack\Base;

require_once(dirname(dirname(__FILE__)) . '/Helpers/BaseFunctions.php');

class CommandTest extends \PHPUnit_Framework_TestCase
{
    public function testInitContainer()
    {
        $stub = $this->getMockBuilder('DBtrack\Base\Command')
            ->setConstructorArgs(array(array()))
            ->setMethods(array('execute'))
            ->getMock();
        $stub->expects($this->any())->method('execute')->willReturn(true);


        BaseFunctions::$functions
            ->shouldReceive('getcwd')
            ->once()
            ->andReturn('/nothing');

        $config = $stub->getClassInstance('config');
        $this->assertEquals('DBtrack\Base\Config', get_class($config));

        $terminal = $stub->getClassInstance('terminal');
        $this->assertEquals('DBtrack\Base\Terminal', get_class($terminal));

        $dbmanager = $stub->getClassInstance('dbmanager');
        $this->assertEquals('DBtrack\Base\DBManager', get_class($dbmanager));
    }

    public function setUp()
    {
        BaseFunctions::init();
    }

    public function tearDown()
    {
        \Mockery::close();
    }
}