<?php
namespace DBtrack\Base;

use Pimple\Container;

abstract class Command
{
    /** @var array */
    protected $arguments = array();

    /** @var Container */
    protected $container = null;

    /**
     * Abstract function that executes the command line.
     */
    abstract public function execute();

    /**
     * @param array $arguments
     */
    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
        $this->initContainer();
    }

    /**
     * Initialise Pimple container.
     */
    protected function initContainer()
    {
        $this->container = new Container();

        $this->container['config'] = function ($c) {
            return new Config();
        };

        $this->container['terminal'] = function ($c) {
            return new Terminal();
        };

        $this->container['dbmanager'] = function ($c) {
            return new DBManager();
        };
    }

    /**
     * Return an instance of the requested class.
     * @param $containerName
     * @return mixed
     */
    public function getClassInstance($containerName)
    {
        return $this->container[$containerName];
    }
}
