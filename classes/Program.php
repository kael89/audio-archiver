<?php

class Program
{
    private $dir;
    private $records;
    private $options;
    private $settings;
    private $timer;
    private $printer;
    private $logger;
    private $fileHandler;

    const DEFAULT_OPTIONS = [
        'backup' => false,
        'convert' => false,
        'backup_dir' => 'archiver_backup',
        'log_filename' => 'archiver_log',
    ];

    public function __construct($dir)
    {
        global $globals;

        try {
            $this->initTimer();
            $this->setDir($dir);
            $this->records = [];
            $this->setOptions($globals->getOptions());
            $this->settings = $globals->getSettings();
            $this->printer = $globals->getLogger();
            $this->initLogger();
            $this->fileHandler = $globals->getFileHandler();
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), E_ERROR);
        }
    }

    private function initTimer()
    {
        $this->timer = new Timer();
        $this->timer->setFormatMethod('formatTime');
    }

    private function initLogger()
    {
        $logFilename = $this->getOption('log_filename') . '_' . date('Y-m-d') . '.txt';
        $this->logger = new ProgramLogger(joinPath($this->dir, $logFilename));
    }

    private function setDir($dir)
    {
        $dir = trim($dir);
        if (!is_dir($dir)) {
            throw new Exception("$dir is not a directory");
        }

        $this->dir = $dir;
    }

    private function setOptions($options)
    {
        $this->options = arrayDefault($options, self::DEFAULT_OPTIONS);
    }

    public function getOption($option)
    {
        return $this->options[$option];
    }

    public function getSetting($setting)
    {
        return $this->settings->getSetting($setting);
    }

    public function backup()
    {
        // Create backup folder
        $backupDir = $this->getOption('backup_dir');
        $backupFolder = createFilename($this->dir, $backupDir);
        if (strlen($backupFolder) == 0) {
            throw new Exception("Could not create backup folder", E_ERROR);
        }

        // Copy contents into backup folder
        try {
            $this->makeDir($this->dir, $backupFolder);
            $this->copy($this->dir, $backupFolder, $backupDir);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function detectRecords()
    {
        $recordFolders = [];

        // Get all folders containing audio files
        $folders = scanFolders($this->dir);
        $folders[] = $this->dir;
        foreach ($folders as $folder) {
            $files = dirFiles($folder);
            foreach ($files as $file) {
                if (isAudio("$folder/$file")) {
                    $recordFolders[] = $folder;
                    break;
                }
            }
        }

        // Get record objects
        foreach ($recordFolders as $folder) {
            $record = new Record($folder);
            $this->records[] = $record;
        }

        // Store record data in logger
        $this->logger->setRecords($this->records);
    }

    private function writeMetadata()
    {
        foreach ($this->records as $record) {
            $record->writeTrackMetadata();
        }
    }

    private function formatTitles()
    {
        try {
            $formatter = new Formatter($this->records, $this->settings);
            $formatter->setRenameMethod([$this->fileHandler, 'rename']);
            $formatter->run();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function getTrackCount()
    {
        $count = 0;
        foreach ($this->records as $record) {
            $count += count($record->getTracks());
        }

        return $count;
    }

    private function convert()
    {
        // Depends on 'ffmpeg' program
        if (!commandExists("ffmpeg")) {
            throw new Exception("ffmpeg needs to be installed for audio file conversion");
        }

        $i = 1;
        $count = $this->getTrackCount();
        $padLength = strlen($count);
        $cursorOffset = $padLength * 2 + 5;

        foreach ($this->records as $record) {
            $dir = $record->getPath();

            foreach ($record->getTracks() as $track) {
                $this->print(" " . leftPad($i, $padLength) . "/$count...");
                
                $path = $track->getPath();
                $newPath = "$dir/" . createFilename($dir, $track->getFilename());

                $args = [
                    // program name
                    'ffmpeg' ,
                    // disable program logging
                    '-loglevel',
                    'quiet',
                    // source filepath
                    '-i',
                    $path,
                    // destination bitrate
                    '-b:a',
                     $this->getSetting('bitrate') . 'k',
                    // destination filepath
                    $newPath,
                ];

                execCommand($args);
                $i++;
                $this->printer->moveCursor(-1 * $cursorOffset);

                try {
                    $this->move($newPath, $path);
                } catch (Exception $e) {
                    $this->printer->moveCursor($cursorOffset);
                    throw $e;
                }
            }
        }
    }

    public function run()
    {
        try {
            $this->initLog();
            $this->printLine("Executing audio_archiver in $this->dir");

            // Backup
            if ($this->getOption('backup')) {
                $this->printLine('Backing up folders...');
                $this->backup();
            }

            // Format filenames/metadata
            $this->printLine('Getting record info...');
            $this->detectRecords();

            $this->printLine('Formatting file names...');
            $this->formatTitles();

            $this->printLine('Writing audio files metadata...');
            $this->writeMetadata();

            // Convert
            if ($this->getOption('convert')) {
                $this->print('Converting audio files');
                $this->convert();
                $this->printLine();
            }

            $this->printLine('Writing log...');
            $this->writeLog();

            $this->printLine('Done!');
        } catch (Exception $e) {
            throw $e;
        } finally {
            $this->closeLog();
        }
    }

    public function getExecutionTime()
    {
        return $this->timer->getDuration();
    }

    private function print($msg = '')
    {
        $this->printer->log($msg);
    }

    private function printLine($msg = '')
    {
        $this->printer->logLine($msg);
    }

    private function initLog()
    {
        $this->logger->setStartDate();
    }

    private function writeLog()
    {
        $this->logger->setExecutionTime($this->getExecutionTime());
        $this->logger->run();
    }

    private function closeLog()
    {
        $this->logger->close();
    }

    private function makeDir($dir, $folder)
    {
        try {
            $this->fileHandler->makeDir($dir, $folder);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function copy($source, $dest)
    {
        try {
            $this->fileHandler->copy($source, $dest);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function move($source, $dest)
    {
        try {
            $this->fileHandler->move($source, $dest);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
