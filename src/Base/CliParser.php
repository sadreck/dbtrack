<?php
namespace DBtrack\Base;

class CliParser
{
    /**
     * Parse passed command line arguments.
     * @param array $arguments
     * @return array
     */
    public function parseCommandLine(array $arguments)
    {
        $command = '';
        $parsed = array();
        if (count($arguments) <= 1) {
            return array($command, $parsed);
        }

        $command = trim($arguments[1]);

        // We begin from 2 because [0] is the path of the script and [1] is the
        // name of the command.
        $param = '';
        for ($i = 2; $i < count($arguments); $i++) {
            $argument = $arguments[$i];
            if ('-' == $argument[0]) {
                $param = trim($argument, '-');
                if (!isset($parsed[$param])) {
                    $parsed[$param] = array();
                }
            } elseif (!empty($param)) {
                $parsed[$param][] = $argument;
            }
        }

        $parsed = $this->processArguments($parsed);

        return array($command, $parsed);
    }

    /**
     * Process input arguments. What this effectively does is convert to string
     * arrays that have only one element.
     * So: $a = array(1) becomes $a = 1.
     * @param array $arguments
     * @return array
     */
    protected function processArguments(array $arguments)
    {
        foreach ($arguments as $param => $values) {
            if (1 == count($values)) {
                $arguments[$param] = $values[0];
            }
        }
        return $arguments;
    }
}
