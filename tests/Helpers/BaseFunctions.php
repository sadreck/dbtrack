<?php
namespace DBtrack\Base;

function is_writable($filename)
{
    return BaseFunctions::$functions->is_writable($filename);
}

function getcwd()
{
    return BaseFunctions::$functions->getcwd();
}

class BaseFunctions
{
    /** @var \Mockery\MockInterface */
    static public $functions = null;

    public static function init()
    {
        if (self::$functions == null) {
            self::$functions = \Mockery::mock();
        }
    }
}