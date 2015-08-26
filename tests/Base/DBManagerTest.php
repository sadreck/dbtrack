<?php
namespace DBtrack\Base;

function dirname($path)
{
    return DBManagerTest::$functions->dirname($path);
}

function glob($pattern, $flags = null)
{
    return DBManagerTest::$functions->glob($pattern, $flags);
}

class DBManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Mockery\MockInterface */
    static public $functions = null;

    public function testGetSupportedDatabases()
    {
        self::$functions = \Mockery::mock();
        self::$functions
            ->shouldReceive('dirname')
            ->twice()
            ->andReturn('/some/path');
        self::$functions
            ->shouldReceive('glob')
            ->once()
            ->andReturn(array('/some/path/mysql.php', '/some/path/pgsql.php'));

        $DBManager = new DBManager();
        $databases = $DBManager->getSupportedDatabases();
        $this->assertEquals(2, count($databases));
        $this->assertEquals('mysql', $databases[0]);
        $this->assertEquals('pgsql', $databases[1]);

        \Mockery::close();
    }
}