<?php
namespace DBtrack\System;

/**
 * This is just for 100% code coverage.
 * Class SystemTest
 * @package DBtrack\System
 */
class SystemTest extends \PHPUnit_Framework_TestCase {
    public function testFunctions()
    {
        $system = new System();
        $this->assertEquals('/dbtrack/test', $system->getcwd());
        $this->assertTrue($system->is_dir('/dbtrack/test'));
        $this->assertTrue($system->is_writable('/dbtrack/test'));
        $this->assertTrue($system->file_exists('/dbtrack/test'));
        $this->assertTrue($system->unlink('/dbtrack/test'));
        $this->assertTrue($system->umask('/dbtrack/test'));
        $this->assertTrue($system->mkdir('/dbtrack/test'));
        $this->assertTrue($system->rmdir('/dbtrack/test'));
        $this->assertTrue($system->file_put_contents('/dbtrack/test', ''));
        $this->assertTrue($system->file_get_contents('/dbtrack/test'));
        $this->assertTrue($system->scandir('/dbtrack/test'));
        $this->assertTrue($system->glob('/dbtrack/test'));
    }
}

function getcwd()
{
    return '/dbtrack/test';
}

function is_dir($filename)
{
    return true;
}

function is_writable($filename)
{
    return true;
}

function file_exists($filename)
{
    return true;
}

function unlink($filename, $context = null)
{
    return true;
}

function umask($mask = null)
{
    return true;
}

function mkdir(
    $pathname,
    $mode = 0777,
    $recursive = false,
    $context = null
) {
    return true;
}

function rmdir($dirname, $context = null)
{
    return true;
}

function file_put_contents(
    $filename,
    $data,
    $flags = 0
) {
    return true;
}

function file_get_contents($filename)
{
    return true;
}

function scandir($directory, $sorting_order = SCANDIR_SORT_ASCENDING)
{
    return true;
}

function glob($pattern, $flags = 0)
{
    return true;
}