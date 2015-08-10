<?php
namespace DBtrack;

use DBtrack\Base\CliParser;

$cliParser = new CliParser();
list($command, $arguments) = $cliParser->parseCommandLine($argv);
