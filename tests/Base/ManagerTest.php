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
            'InitClass',
            false
        );
        class_alias('InitClass', 'DBtrack\Commands\Init');
        $mock->method('execute')->willReturn(true);
        $manager->run('init', array());
    }
}