<?php

class AudioFile extends File
{
    protected $title;
    protected $performer;
    protected $band;
    protected $album;
    protected $year;
    protected $genre;
    protected $comment;
    protected $trackNumber;

    const INFO_FIELDS = [
        'TIT2' => 'title',
        'TPE1' => 'performer',
        'TPE2' => 'band',
        'TALB' => 'album',
        'TYER' => 'year',
        'TCON' => 'genre',
        'COMM' => 'comment',
        'TRCK' => 'trackNumber',
    ];

    public function __construct($path)
    {
        try {
            parent::__construct($path);
            $this->findInfo();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function findInfo()
    {
        $metadata = id3Manager::getTags($this->getPath());

        foreach (self::INFO_FIELDS as $id => $field) {
            $result = getByKey($metadata, $id, '');
            if (!$result) {
                $result = method_exists($this, "parse$field") ? $this->{"parse$field"}() : '';
            }

            $this->{"set$field"}($result);
        }
    }

    private function parseInfo($number = false)
    {
        $matches = [];
        // number regex
        if ($number) {
            $rgx = '';
            $group = 0;
        } else {
            $rgx = '(.*)';
            $group = 2;
        }

        preg_match("/^(\d{1,2})(\W|_)$rgx/", basename($this->getPath()), $matches);
        return (!empty($matches[$group + 1])) ? $matches[$group + 1] : '';
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        if (is_string($title)) {
            $this->title = trim($title);
        }
    }

    public function parseTitle()
    {
        $regex = '/\\' . $this->getExtension() . '$/';
        return preg_replace($regex, '', $this->parseInfo());
    }

    public function getPerformer()
    {
        return $this->performer;
    }

    public function setPerformer($performer)
    {
        if (is_string($performer)) {
            $this->performer = trim($performer);
        }
    }

    public function getBand()
    {
        return $this->band;
    }

    public function setBand($band)
    {
        if (is_string($band)) {
            $this->band = trim($band);
        }
    }

    public function getAlbum()
    {
        return $this->album;
    }

    public function setAlbum($album)
    {
        if (is_string($album)) {
            $this->album = trim($album);
        }
    }

    public function getYear()
    {
        return $this->year;
    }

    public function setYear($year)
    {
        $year = (int)$year;
        if ($year > 0) {
            $this->year = $year;
        }
    }

    public function getGenre()
    {
        return $this->genre;
    }

    public function setGenre($genre)
    {
        if (is_string($genre)) {
            $this->genre = preg_replace('/\s+\(\d+\)$/', '', trim($genre));
        }
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment)
    {
        if (is_string($comment)) {
            $this->comment = trim($comment);
        }
    }

    public function getTrackNumber()
    {
        return $this->trackNumber;
    }

    public function setTrackNumber($num)
    {
        $num = preg_replace('/\/\d+$/', '', $num);
        if (isPositive($num)) {
            $this->trackNumber = (int)$num;
        }
    }

    public function parseTrackNumber()
    {
        return $this->parseInfo(true);
    }

    private function getMetadataFields()
    {
        $metadata = [];
        foreach (self::INFO_FIELDS as $id => $field) {
            $metadata[$id] = $this->{"get$field"}();
        }

        return $metadata;
    }

    public function writeMetadata()
    {
        id3Manager::writeTags($this->getPath(), $this->getMetadataFields());
    }
}
