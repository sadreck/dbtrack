<?php
namespace DBtrack;

use DBtrack\Base\CliParser;
use DBtrack\Base\Manager;

$manager = new Manager();
$cliParser = new CliParser();

list($command, $arguments) = $cliParser->parseCommandLine($argv);

try {
    $manager->run($command, $arguments);
} catch (\Exception $e) {
    fputs(
        STDOUT,
        $e->getMessage() . PHP_EOL . PHP_EOL . $e->getTraceAsString() . PHP_EOL
    );
}