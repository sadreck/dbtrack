<?php
namespace DBtrack\Base;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testRun()
    {
        $manager = new Manager();
        try {
            $manager->run('command-does-not-exist', array());
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

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
    }
}