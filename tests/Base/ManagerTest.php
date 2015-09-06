<?php
namespace DBtrack\Base;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testRun()
    {
        $manager = new Manager(true);

        $mock = $this->getMock(
            'DBtrack\Base\Command',
            array(),
            array(),
            'DummyCommandClass',
            false
        );
        class_alias('DummyCommandClass', 'DBtrack\Commands\Dummy');
        $mock->method('execute')->willReturn(true);
        $command = $manager->run('dummy', array());
        $this->assertEquals('Dummy', $command);

        $manager->showHelpNoCommand = false;
        $this->assertFalse($manager->run('', array()));

        Events::toggleTriggers('eventDisplayMessage', false);
        $this->assertFalse($manager->run('invalid-command', array()));
    }
}