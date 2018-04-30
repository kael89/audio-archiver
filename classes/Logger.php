<?php

abstract class Logger
{
    protected $lineSeparator;

    public function __construct($lineSeparator)
    {
        $this->lineSeparator = $lineSeparator;
    }

    abstract public function log($msg = '');

    public function logLine($msg = '')
    {
        $this->log($msg . $this->lineSeparator);
    }

    public function logLines($num)
    {
        for ($i = 0; $i < $num; $i++) {
            $this->logLine();
        }
    }
}
