<?php
namespace DBtrack\Base;

use Mockery as Mock;

function is_writable($filename)
{
    return false;
}

function getcwd()
{
    return ConfigTest::$functions->getcwd();
}

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected $baseDir = '';

    /** @var \Mockery\MockInterface */
    static public $functions = null;

    public function testConstructor()
    {
        self::$functions->shouldReceive('is_writable')->passthru();
        $config = new Config($this->baseDir);
        $this->assertFalse(is_dir($this->baseDir));
        $this->assertFalse($config->isInitialised());

        mkdir($this->baseDir, 0777, true);
        touch($this->baseDir . '/config');
        $this->assertTrue($config->isInitialised());
        unlink($this->baseDir . '/config');
        unset($config);

        self::$functions->shouldReceive('getcwd')->andReturn('/nothing');
        $config = new Config();
        $this->assertEquals('/nothing/.dbtrack', $config->dbtDirectory);

        try {
            $config = new Config($this->baseDir);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function setUp()
    {
        self::$functions = Mock::mock();
        $this->baseDir = sys_get_temp_dir() . '/.dbtrack.tests';
        $this->deleteDirectory($this->baseDir);
    }

    public function tearDown()
    {
        $this->deleteDirectory($this->baseDir);
        Mock::close();
    }

    protected function deleteDirectory($directory)
    {
        $directory = rtrim($directory, '/');
        if (is_dir($directory)) {
            $list = scandir($directory);
            foreach ($list as $item) {
                if ($item == '.' || $item == '..') {
                    continue;
                }
                $path = $directory . '/' . $item;
                if (is_dir($path)) {
                    $this->deleteDirectory($path);
                } else {
                    unlink($path);
                }
            }
            rmdir($directory);
        }
    }
}