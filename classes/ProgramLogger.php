<?php

class ProgramLogger extends FileLogger
{
    const DATE_FORMAT = DATE_RFC822;
    // Unix timestamp
    private $startDate;
    // Seconds (float)
    private $executionTime;
    private $records = [];
    private $trackCount = 0;

    public function __construct($filepath)
    {
        $this->setStartDate();
        parent::__construct($filepath);
    }

    public function getStartDate()
    {
        return date(self::DATE_FORMAT, $this->startDate);
    }

    public function setStartDate()
    {
        $this->startDate = time();
    }

    public function getExecutionTime()
    {
        return $this->executionTime;
    }

    public function setExecutionTime($time)
    {
        if (!is_numeric($time)) {
            return;
        }
        $this->executionTime = $time;
    }

    private function extractTrackInfo($track)
    {
        $track = [
            'filename' => $track->getFilename(),
            'number' => $track->getTrackNumber(),
            'title' => $track->getTitle()
        ];
        return $track;
    }

    public function setRecords(array $records)
    {
        foreach ($records as $record) {
            $tracks = array_map([$this, 'extractTrackInfo'], $record->getTracks());
            $this->trackCount += count($tracks);

            $this->records[] = [
                'artist' => $record->getArtist(),
                'title' => $record->getTitle(),
                'year' => $record->getYear(),
                'genre' => $record->getGenre(),
                'tracks' => $tracks
            ];
        }
    }

    private function logIntro()
    {
        $date = $this->getStartDate();
        $executionTime = $this->getExecutionTime();

        $this->logLine("Audio archiver run at $date and completed successfully after $executionTime seconds");
    }

    private function logStatistics()
    {
        $recordCount = count($this->records);
        $pad = max(strlen($recordCount), strlen($this->trackCount));

        $this->logLine('Records archived: ' . leftPad($recordCount, $pad));
        $this->logLine('Tracks archived:  ' . leftPad($this->trackCount, $pad));
    }

    private function logTracks($tracks)
    {
        $this->logLine('Tracks:');

        $headers = [
            'No' => 0,
            'Title' => 0,
            'Filename' => 0
        ];

        $columns = array_map(function ($track) {
            $column = [
                numericPad($track['number'], 2),
                $track['title'],
                $track['filename']
            ];
            return $column;
        }, $tracks);

        $this->logTable($headers, $columns);
    }

    private function logRecord($record)
    {
        $tracks = $record['tracks'];
        unset($record['tracks']);

        foreach ($record as $key => $value) {
            $this->logLine(ucfirst($key) . ":\t" . $value);
        }
        $this->logLine();
        $this->logTracks($tracks);
    }

    private function logAllRecords()
    {
        $this->logLine('RECORD INFO', true);
        foreach ($this->records as $record) {
            $this->logRecord($record);
            $this->logLines(2);
        }
    }

    public function run()
    {
        $this->logIntro();
        $this->logLine();

        $this->logStatistics();
        $this->logLine();

        $this->logAllRecords();
    }
}