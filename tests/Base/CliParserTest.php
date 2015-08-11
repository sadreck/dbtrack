<?php
namespace DBtrack\Base;

class CliParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParser()
    {
        $parser = new CliParser();

        $args = array(
            '/long/path/to/dbt',
            'command',
            '--long-argument',
            'long-arg-value',
            '-short-argument',
            'short-argument-value',
            '-multi',
            'value1',
            'value2',
            'value3',
            '--quotes',
            '"this is a very long parameter"',
            '--no-value'
        );
        list($command, $arguments) = $parser->parseCommandLine($args);

        $this->assertEquals('command', $command);

        $this->assertArrayHasKey('long-argument', $arguments);
        $this->assertArrayHasKey('short-argument', $arguments);
        $this->assertArrayHasKey('multi', $arguments);
        $this->assertArrayHasKey('quotes', $arguments);
        $this->assertArrayHasKey('no-value', $arguments);

        $this->assertEquals(
            'long-arg-value',
            $arguments['long-argument']
        );
        $this->assertEquals(
            'short-argument-value',
            $arguments['short-argument']
        );
        $this->assertEquals(
            '"this is a very long parameter"',
            $arguments['quotes']
        );
        $this->assertEquals(3, count($arguments['multi']));
        $this->assertEquals('value1', $arguments['multi'][0]);
        $this->assertEquals('value2', $arguments['multi'][1]);
        $this->assertEquals('value3', $arguments['multi'][2]);
        $this->assertTrue(empty($arguments['no-value']));
    }

    public function testEmptyCommand()
    {
        $parser = new CliParser();
        list($command, $arguments) = $parser->parseCommandLine(array());

        $this->assertEmpty($command);
        $this->assertEquals(0, count($arguments));
    }
}
