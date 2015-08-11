<?php
namespace DBtrack\Base;

class TerminalTest extends \PHPUnit_Framework_TestCase
{
    public function testPrompt()
    {
        $inputStream = fopen('php://memory', 'w');
        $outputStream = fopen('php://memory', 'w');

        $terminal = new Terminal($inputStream, $outputStream);

        $terminal->prompt('Shall we play a game?');
        fseek($outputStream, 0);
        $output = stream_get_contents($outputStream);
        $this->assertEquals('Shall we play a game?', $output);

        fclose($inputStream);
        fclose($outputStream);
    }

    public function testDisplay()
    {
        $tests = array(
            array('newline', true, PHP_EOL, 'newline' . PHP_EOL),
            array('oneline', false, PHP_EOL, 'oneline'),
            array('customterm', true, '<br/>', 'customterm<br/>')
        );

        foreach ($tests as $test) {
            $inputStream = fopen('php://memory', 'w');
            $outputStream = fopen('php://memory', 'w');

            $terminal = new Terminal($inputStream, $outputStream);

            fseek($outputStream, 0);
            $terminal->display($test[0], $test[1], $test[2]);
            fseek($outputStream, 0);
            $this->assertEquals($test[3], stream_get_contents($outputStream));

            unset($terminal);

            fclose($inputStream);
            fclose($outputStream);
        }
    }

    public function testColours()
    {
        $tests = array(
            array('test', 'green', 'yellow', "\033[0;32m\033[43mtest\033[0m"),
            array('test', 'green', '', "\033[0;32mtest\033[0m"),
            array('test', '', 'yellow', "\033[43mtest\033[0m"),
            array('test', 'invalid', 'invalid', "test"),
            array('test', '', '', "test")
        );

        $terminal = new Terminal();

        foreach ($tests as $test) {
            $this->assertEquals(
                $test[3],
                $terminal->getColourText($test[0], $test[1], $test[2])
            );
        }
    }
}