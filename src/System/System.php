<?php
namespace DBtrack\System;

class System
{
    public function getcwd()
    {
        return getcwd();
    }

    public function is_dir($filename)
    {
        return is_dir($filename);
    }

    public function is_writable($filename)
    {
        return is_writable($filename);
    }

    public function file_exists($filename)
    {
        return file_exists($filename);
    }

    public function unlink($filename, $context = null)
    {
        return unlink($filename, $context);
    }

    public function umask($mask = null)
    {
        return umask($mask);
    }

    public function mkdir(
        $pathname,
        $mode = 0777,
        $recursive = false,
        $context = null
    ) {
        return mkdir($pathname, $mode, $recursive, $context);
    }

    public function rmdir($dirname, $context = null)
    {
        return rmdir($dirname, $context);
    }

    public function file_put_contents(
        $filename,
        $data,
        $flags = 0
    ) {
        return file_put_contents($filename, $data, $flags);
    }

    public function file_get_contents($filename)
    {
        return file_get_contents($filename);
    }

    public function scandir($directory, $sorting_order = SCANDIR_SORT_ASCENDING)
    {
        return scandir($directory, $sorting_order);
    }

    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }
}