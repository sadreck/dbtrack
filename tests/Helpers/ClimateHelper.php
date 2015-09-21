<?php
namespace DBtrack\Tests;

class ClimateHelper
{
    public static $output = array();

    public function out($message)
    {
        self::$output[] = $message;
    }

    public function progress()
    {
        return new ClimateProgressHelper();
    }

    public static function cleanUp()
    {
        self::$output = array();
    }
}

class ClimateProgressHelper
{
    public function current()
    {
        return null;
    }
}