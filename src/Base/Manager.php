<?php
namespace DBtrack\Base;

class Manager
{
    /**
     * @param bool|false $loadEvents Whether to load Global event listeners.
     */
    public function __construct($loadEvents = false)
    {
        if ($loadEvents) {
            $this->addGlobalEventListeners();
        }
    }

    /**
     * Run the specified command.
     * @param $command
     * @param array $arguments
     * @return bool|string
     */
    public function run($command, array $arguments)
    {
        $command = $this->filterCommand($command);
        $className = "DBtrack\\Commands\\{$command}";
        if (!class_exists($className)) {
            Events::triggerSimple(
                'eventDisplayMessage',
                "Command does not exist: {$command}"
            );
            return false;
        }
        /** @var Command */
        $cmd = new $className($arguments);
        $cmd->execute();

        return $command;
    }

    /**
     * Filter given command.
     * @param $command
     * @return string
     */
    protected function filterCommand($command)
    {
        if (empty($command)) {
            $command = 'help';
        }
        return ucfirst(strtolower($command));
    }

    /**
     * Add global event listeners.
     * @throws \Exception
     */
    protected function addGlobalEventListeners()
    {
        $terminal = new Terminal();

        $listeners = array(
            /* Display a message to the terminal (to avoid exceptions). */
            (object)array(
                'event' => 'eventDisplayMessage',
                'function' => array($terminal, 'display')
            )
        );

        foreach ($listeners as $listener) {
            Events::addEventListener($listener);
        }
    }
}
