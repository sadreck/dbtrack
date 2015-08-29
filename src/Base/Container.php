<?php
namespace DBtrack\Base;

class Container
{
    /** @var \Pimple\Container */
    public static $container = null;

    /**
     * Initialise the container that will hold all the objects.
     */
    public static function initContainer()
    {
        self::$container = new \Pimple\Container();

        self::$container['config'] = function ($c) {
            return new Config();
        };

        self::$container['terminal'] = function ($c) {
            return new Terminal();
        };

        self::$container['dbmanager'] = function ($c) {
            return new DBManager();
        };
    }

    /**
     * Add an instance of a class to the container.
     * @param $name
     * @param $object
     * @param bool|false $replace If set to true, it will replace the object if
     * it already exists.
     */
    public static function addContainer($name, $object, $replace = false)
    {
        if (!isset(self::$container[$name]) ||
            (isset(self::$container[$name]) && $replace)) {

            self::$container[$name] = $object;
        }
    }

    /**
     * Retrieve a class instance.
     * @param $name
     * @param bool|true $throwException Throw an exception is objects does not
     * exist.
     * @return bool|mixed
     * @throws \Exception
     */
    public static function getClassInstance($name, $throwException = true)
    {
        if (!isset(self::$container[$name])) {
            if ($throwException) {
                throw new \Exception('Class instance does not exist: ' . $name);
            }
            return false;
        }
        return self::$container[$name];
    }
}