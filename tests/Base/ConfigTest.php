<?php
namespace DBtrack\Base;

require_once(dirname(dirname(__FILE__)) . '/Helpers/BaseFunctions.php');

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected $baseDir = '';

    public function testConstructor()
    {
        $config = new Config($this->baseDir);
        $this->assertFalse(is_dir($this->baseDir));
        $this->assertFalse($config->isInitialised());

        mkdir($this->baseDir, 0777, true);
        touch($this->baseDir . '/config');
        $this->assertTrue($config->isInitialised());
        unlink($this->baseDir . '/config');
        unset($config);

        BaseFunctions::$functions
            ->shouldReceive('getcwd')
            ->once()
            ->andReturn('/nothing');
        $config = new Config();
        $this->assertEquals('/nothing/.dbtrack', $config->dbtDirectory);

        BaseFunctions::$functions
            ->shouldReceive('is_writable')
            ->once()
            ->andReturn(false);
        try {
            $config = new Config($this->baseDir);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function setUp()
    {
        BaseFunctions::init();
        $this->baseDir = sys_get_temp_dir() . '/.dbtrack.tests';
        $this->deleteDirectory($this->baseDir);
    }

    public function tearDown()
    {
        $this->deleteDirectory($this->baseDir);
        \Mockery::close();
    }

    protected function deleteDirectory($directory)
    {
        $directory = rtrim($directory, '/');
        if (is_dir($directory)) {
            $list = scandir($directory);
            foreach ($list as $item) {
                if ('.' == $item || '..' == $item) {
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