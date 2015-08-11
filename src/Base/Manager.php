<?php
namespace DBtrack\Base;

class Manager
{
    /**
     * Run the specified command.
     * @param $command
     * @param array $arguments
     * @throws \Exception
     */
    public function run($command, array $arguments)
    {
        $command = ucfirst(strtolower($command));
        $className = "DBtrack\\Commands\\{$command}";
        if (!class_exists($className)) {
            throw new \Exception("Command does not exist: {$command}");
        }
        /** @var Command */
        $command = new $className($arguments);
        $command->execute();
    }
}
