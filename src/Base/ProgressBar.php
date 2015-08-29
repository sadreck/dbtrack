<?php
namespace DBtrack\Base;

class ProgressBar
{
    /** @var string */
    protected $progressCharacter = '';

    /** @var Terminal */
    protected $terminal = null;

    /** @var int */
    protected $updateFrequency = 0;

    /** @var int */
    protected $maxValue = 100;

    /**
     * @param Terminal $terminal
     * @param int $maxValue
     * @param int $updateFrequency
     * @param string $progressCharacter
     */
    public function __construct(
        Terminal $terminal,
        $maxValue = 100,
        $updateFrequency = 0,
        $progressCharacter = '='
    ) {
        $this->terminal = $terminal;
        $this->maxValue = $maxValue;
        $this->updateFrequency = $updateFrequency;
        $this->progressCharacter = $progressCharacter;
    }

    /**
     * @param string $progressCharacter
     */
    public function setProgressCharacter($progressCharacter)
    {
        $this->progressCharacter = $progressCharacter;
    }

    /**
     * @param int $updateFrequency
     */
    public function setUpdateFrequency($updateFrequency)
    {
        $this->updateFrequency = $updateFrequency;
    }

    /**
     * @param int $maxValue
     */
    public function setMaxValue($maxValue)
    {
        $this->maxValue = $maxValue;
    }

    /**
     * Get number of columns.
     * @return string
     */
    protected function getColumns()
    {
        // TODO - Check on Windows.
        return (int)exec("tput cols");
    }

    /**
     * @param $value
     * @return bool
     */
    public function update($value)
    {
        // Avoid division by zero and percentage > 100%.
        if (0 == $this->maxValue|| $value > $this->maxValue) {
            return false;
        } elseif (0 < $this->updateFrequency
            && $value != $this->maxValue
            && (0 != $value % $this->updateFrequency)) {
            return false;
        }

        $columns = $this->getColumns();

        if ($value == $this->maxValue) {
            // Move a line up.
            $this->terminal->display("\x1b[A", false);
            // Clean line.
            $this->terminal->display(str_repeat(' ', $columns));
            // Move a line up.
            $this->terminal->display("\x1b[A", false);
            return false;
        }

        $width = $columns - 2;
        $percent = (float)($value / $this->maxValue);
        $bar = floor($percent * $width);

        // Move cursor to the beginning of the row.
        $this->terminal->display("\r", false);

        $output = '[';
        $output .= str_repeat($this->progressCharacter, $bar);
        if ($bar < $width) {
            $output .= '>';
            $output .= str_repeat(' ', $width - $bar - 1);
        }
        $output .= ']';

        $this->terminal->display($output, false);

        if ($value == $this->maxValue) {
            $this->terminal->display(''); // New line.
        }
        return true;
    }
}