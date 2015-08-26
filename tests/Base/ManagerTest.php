<?php
namespace DBtrack\Base;

use Mockery\CountValidator\Exception;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testRun()
    {
        $manager = new Manager();
        $result = $manager->run('command-does-not-exist', array());
        $this->assertFalse($result);

        $mock = $this->getMock(
            'DBtrack\Base\Command',
            array(),
            array(),
            'DummyClass',
            false
        );
        class_alias('DummyClass', 'DBtrack\Commands\Dummy');
        $mock->method('execute')->willReturn(true);
        $manager->run('dummy', array());

        $command = $manager->run('', array());
        $this->assertEquals('Help', $command);
    }
}