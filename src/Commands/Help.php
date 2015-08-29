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
        $helpPath = $this->getHelpPath();

        /** @var $terminal Terminal */
        $terminal = Container::getClassInstance('terminal');
        $terminal->display(
            'dbtrack v' . Config::VERSION . "\tPavel Tsakalidis [ p@vel.gr ]"
        );
        $terminal->display('');

        $commands = $this->getCommandsToDisplay(
            $helpPath,
            $this->arguments['raw-command']
        );
        if (0 == count($commands)) {
            $commands = $this->getCommandsToDisplay($helpPath, array());
        }

        foreach ($commands as $command) {
            $help = $this->getCommandText($command, $helpPath);
            $terminal->display($help);
        }

        $terminal->display('');
        $terminal->display('For more options type: dbt help <command>');
    }

    /**
     * Get a list of the files that we need to display.
     * @param $helpPath
     * @param array $arguments
     * @return array
     */
    protected function getCommandsToDisplay($helpPath, array $arguments)
    {
        $files = glob($helpPath . '/*.txt');
        $commands = array();

        $commandFile = '';
        if (3 == count($arguments)) {
            $commandFile = $arguments[2] . '.more';
        } elseif (3 < count($arguments)) {
            $commandFile = implode('.', array_splice($arguments, 2));
        }

        foreach ($files as $file) {
            $info = pathinfo($file);
            if (empty($commandFile)
                && false === strpos($info['filename'], '.')) {

                $commands[] = $info['filename'];
            } elseif ($commandFile == $info['filename']) {
                $commands[] = $info['filename'];
            }
        }

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
