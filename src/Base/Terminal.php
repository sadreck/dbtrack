<?php
namespace DBtrack\Base;

class Terminal
{
    /** @var */
    private $inputStream = STDIN;

    /** @var */
    private $outputStream = STDOUT;

    /**
     * Define available foreground colours.
     * @var array
     */
    protected $foreground = array(
        'black'         => '0;30',
        'blue'          => '0;34',
        'green'         => '0;32',
        'cyan'          => '0;36',
        'red'           => '0;31',
        'yellow'        => '1;33',
        'white'         => '1;37',
        'light_blue'    => '1;34',
        'light_green'   => '1;32',
        'light_cyan'    => '1;36',
        'light_red'     => '1;31',
        'light_gray'    => '0;37',
    );

    /**
     * Define available background colours.
     * @var array
     */
    protected $background = array(
        'black'         => '40',
        'red'           => '41',
        'green'         => '42',
        'yellow'        => '43',
        'blue'          => '44',
        'magenta'       => '45',
        'cyan'          => '46',
        'light_gray'    => '47'
    );

    /**
     * @param resource $inputStream
     * @param resource $outputStream
     */
    public function __construct($inputStream = STDIN, $outputStream = STDOUT)
    {
        $this->inputStream = $inputStream;
        $this->outputStream = $outputStream;
    }

    /**
     * Prompt the user with a specific message (or no message).
     * @param $message
     * @return string
     */
    public function prompt($message)
    {
        if (strlen($message) > 0) {
            fputs($this->outputStream, $message);
        }

        return trim(fgets($this->inputStream));
    }

    /**
     * Display a message on the user's terminal.
     * @param $message
     * @param bool|true $newLine
     * @param string $lineBreak
     */
    public function display($message, $newLine = true, $lineBreak = PHP_EOL)
    {
        if (strlen($message) > 0) {
            fputs($this->outputStream, $message);
        }

        if ($newLine) {
            fputs($this->outputStream, $lineBreak);
        }
    }

    /**
     * Return coloured text.
     * @param $text
     * @param $foreColour
     * @param string $backColour
     * @return string
     */
    public function getColourText($text, $foreColour, $backColour = '')
    {
        if (!empty($foreColour) && isset($this->foreground[$foreColour])) {
            $foreColour = "\033[" . $this->foreground[$foreColour] . "m";
        } else {
            $foreColour = '';
        }

        if (!empty($backColour) && isset($this->background[$backColour])) {
            $backColour = "\033[" . $this->background[$backColour] . "m";
        } else {
            $backColour = '';
        }

        if (empty($foreColour) && empty($backColour)) {
            return $text;
        }

        return $foreColour . $backColour . $text . "\033[0m";
    }
}