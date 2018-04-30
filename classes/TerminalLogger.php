<?php

class TerminalLogger extends Logger
{
    const CHAR_ESCAPE = 0x1B;
    const LINE_SEPARATOR = "\n";

    public function __construct()
    {
        parent::__construct(self::LINE_SEPARATOR);
    }

    public function log($msg = '')
    {
        echo $msg;
    }

    /**
     * Moves the cursor n columns horizontally
     *
     * @param int $n The number of columns to move the cursor.
     * The cursor will be moved to the left if $n is negative
     */
    public function moveCursor($n = 0)
    {
        if (!is_numeric($n)) {
            return;
        }

        $n = (int)$n;
        if ($n > 0) {
            $char = 'C';
        } else {
            $char = 'D';
            $n *= -1;
        }

        printf("%c[{$n}{$char}", self::CHAR_ESCAPE);
    }
}
