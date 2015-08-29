<?php
namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Base\Config;
use DBtrack\Base\Container;
use DBtrack\Base\Terminal;

class Help extends Command
{
    public function execute()
    {
        /** @var $terminal Terminal */
        $terminal = Container::getClassInstance('terminal');
        $terminal->display(
            'dbtrack v' . Config::VERSION . "\tPavel Tsakalidis [ p@vel.gr ]"
        );
        $terminal->display('');

        $helpPath = $this->getHelpPath();

        $commands = $this->getCommands($helpPath);
        foreach ($commands as $command) {
            $help = $this->getCommandText($command, $helpPath);
            $terminal->display($help);
        }

        $terminal->display('');
        $terminal->display('For more options type: dbt <command> more');
    }

    /**
     * Get all commands that have help files.
     * @param $path
     * @return array
     */
    protected function getCommands($path)
    {
        $commands = array();
        $files = glob($path . '/*.txt');
        foreach ($files as $file) {
            $info = pathinfo($file);
            $commands[] = $info['filename'];
        }
        sort($commands);
        return $commands;
    }

    /**
     * Get command line help text.
     * @param $command
     * @param $path
     * @return bool|string
     */
    protected function getCommandText($command, $path)
    {
        $helpFile = $path . '/' . $command . '.txt';

        if (!file_exists($helpFile)) {
            return false;
        }

        return file_get_contents($helpFile);
    }

    /**
     * Get the path where all help texts are stored.
     * @return string
     */
    protected function getHelpPath()
    {
        return dirname(__FILE__) . '/Help';
    }
}
