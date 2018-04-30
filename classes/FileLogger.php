<?php

class FileLogger extends Logger
{
    const LINE_SEPARATOR = "\n";

    private $handle;
    private $tableWidths;

    public function __construct($filepath)
    {
        $dir = dirname($filepath);
        $filename = createFilename($dir, basename($filepath));
        if (strlen($filename) == 0) {
            throw new Exception("Could not create file log $filepath");
        }

        $this->handle = fopen(joinPath($dir, $filename), 'x');
        parent::__construct(self::LINE_SEPARATOR);
    }

    public function log($msg = '')
    {
        fwrite($this->handle, $msg);
    }

    public function logLine($msg = '', $underlined = false)
    {
        parent::logLine($msg);
        if ($underlined) {
            $line = str_repeat('-', strSize($msg));
            parent::logLine($line);
        }
    }

    private function logTableRow($row)
    {
        $lastIndex = count($row) - 1;
        foreach ($row as $i => $cell) {
            $cell = ' ' . str_pad($cell, $this->tableWidths[$i]);
            if ($i !== $lastIndex) {
                $cell .= ' |';
            }
            $this->log($cell);
        }
        $this->logLine();
    }

    private function getTableColumn($rows, $i)
    {
        return extractDimension($rows, $i);
    }

    public function logTable(array $headers, $rows)
    {
        $widths = array_values($headers);
        $allRows = [array_keys($headers)] + $rows;

        $dashes = [];
        foreach ($widths as $i => $width) {
            if ($width < 1) {
                // If width is not > 0, use the width of the longest string in the column
                $widths[$i] = maxLength($this->getTableColumn($allRows, $i));
            }

            $dashes[] = str_repeat('-', $widths[$i]);
        }
        $this->tableWidths = $widths;
        array_splice($allRows, 1, 0, [$dashes]);

        array_walk($allRows, [$this, 'logTableRow']);
    }

    public function close()
    {
        if ($this->handle instanceof Resource) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
