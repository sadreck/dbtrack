<?php
namespace DBtrack\Base;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testContainer()
    {
        Container::initContainer(true);

        $classes = array(
            'config' => 'DBtrack\Base\Config',
            'climate' => 'League\CLImate\CLImate',
            'dbmanager' => 'DBtrack\Base\DBManager',
            'system' => 'DBtrack\System\System',
        );

        foreach ($classes as $name => $class) {
            $object = Container::getClassInstance($name);
            $this->assertEquals($class, get_class($object));
        }

        try {
            $object = Container::getClassInstance('error');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        $object = Container::getClassInstance('error', false);
        $this->assertFalse($object);

        Container::addContainer('dummy', null);
        $object = Container::getClassInstance('dummy');
        $this->assertNull($object);
    }
}