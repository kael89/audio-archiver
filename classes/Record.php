<?php

class Record
{
    private $folderName;
    private $parentDir;
    private $artist;
    private $title;
    private $year;
    private $genre;
    private $tracks;

    const AUDIO_FIELDS_TO_RECORD = [
        'band' => 'artist',
        'performer' => 'artist',
        'album' => 'title',
        'year' => 'year',
        'genre' => 'genre',
    ];
    const DUPLICATE_FIELDS = [
        'artist'
    ];

    public function __construct($path)
    {
        if (!is_dir($path)) {
            return null;
        }

        $this->folderName = basename($path);
        $this->parentDir = dirname($path);
        $this->findTracks();
        $this->findInfo();
        $this->normalizeTrackInfo();
    }

    private function findTracks()
    {
        $this->tracks = [];

        $files = dirFiles($this->getPath());
        foreach ($files as $file) {
            if (!isAudio($file)) {
                continue;
            }

            $track = new AudioFile(joinPath($this->getPath(), $file));
            $this->tracks[] = $track;
        }

        usort($this->tracks, function ($a, $b) {
            return $a->getTrackNumber() > $b->getTrackNumber();
        });
    }

    private function isDuplicateField($field)
    {
        return in_array($field, self::DUPLICATE_FIELDS);
    }

    private function findInfo()
    {
        if (empty($this->tracks)) {
            return;
        }

        foreach (self::AUDIO_FIELDS_TO_RECORD as $audioField => $recordField) {
            // A record field may already be set if it is a duplicate field
            if ($this->{"get$recordField"}()) {
                continue;
            }

            $track = reset($this->tracks);
            do {
                $value = $track->{"get$audioField"}();
                $track = next($this->tracks);
            } while (!$value && $track !== false);

            if (empty($value) && !$this->isDuplicateField($recordField) && method_exists($this, "parse$recordField")) {
                $value = $this->{"parse$recordField"}();
            }

            $this->{"set$recordField"}($value);
        }
    }

    private function parseInfo($year = false)
    {
        $src = basename($this->getPath());
        $matches = [];
        $match = '';
        $results = [];

        // Year regex
        $dr = '\W?(\d{4})\W?';
        // Available regexes and matching offsets
        $regexes = [
            ["/^$dr\s+(-\s+)?(.*)/", $year ? 0 : 2],
            ["/(.*)\s+(-\s+)?$dr\$/", $year ? 2 : 0],
        ];
        if (!$year) {
            $regexes[2] = ["/(.*)/", 0];
        }

        foreach ($regexes as $regex) {
            if (preg_match($regex[0], $src, $matches)) {
                $match = $matches[$regex[1] + 1];
                break;
            }
        }

        $results = ($year) ? [$match] : explode('-', $match);
        return $results;
    }

    private function parseArtist()
    {
        $info = $this->parseInfo();
        // If artist not found, get info from parent directory
        $artist = (count($info) > 1) ? $info[0] : basename(dirname($this->getPath()));
        return $artist;
    }

    private function parseTitle()
    {
        $info = $this->parseInfo();
        $title = end($info);
        return $title;
    }

    private function parseYear()
    {
        $year = $this->parseInfo(true);
        return $year[0];
    }

    public function normalizeTrackInfo()
    {
        $values = [];
        foreach (self::AUDIO_FIELDS_TO_RECORD as $audioField => $recordField) {
            $values[$audioField] = $this->{"get$recordField"}();
        }

        foreach ($this->tracks as $track) {
            foreach ($values as $audioField => $value) {
                if (empty($track->{"get$audioField"}())) {
                    $track->{"set$audioField"}($value);
                }
            }
        }
    }

    public function getFolderName()
    {
        return $this->folderName;
    }

    private function setFolderName($folder)
    {
        $this->folderName = trim($folder);
    }

    public function getParentDir()
    {
        return $this->parentDir;
    }

    public function getPath()
    {
        return joinPath($this->parentDir, $this->folderName);
    }

    public function getArtist()
    {
        return $this->artist;
    }

    public function setArtist($artist)
    {
        $this->artist = trim($artist);
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = trim($title);
    }

    public function getYear()
    {
        return $this->year;
    }

    public function setYear($year)
    {
        if (is_numeric($year)) {
            $this->year = (int)$year;
        }
    }

    public function getGenre()
    {
        return $this->genre;
    }

    public function setGenre($genre)
    {
        $this->genre = trim($genre);
    }

    public function getTracks()
    {
        return $this->tracks;
    }

    public function rename($newName)
    {
        $newName = trim($newName);
        if (!$newName) {
            return;
        }

        $this->folderName = $newName;
        foreach ($this->tracks as $track) {
            $track->setDir($this->getPath());
        }
    }

    public function writeTrackMetadata()
    {
        foreach ($this->tracks as $track) {
            $track->writeMetadata();
        }
    }
}
