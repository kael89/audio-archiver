<?php

class Formatter
{
    const TYPE_NUMERIC = 0;
    const TYPE_STRING = 1;
    const REPLACE_MAP = [
        'a' => [
            'class' => 'Record',
            'method' => 'getArtist',
            'type' => self::TYPE_STRING,
        ],
        'n' => [
            'class' => 'AudioFile',
            'method' => 'getTrackNumber',
            'type' => self::TYPE_NUMERIC,
        ],
        'r' => [
            'class' => 'Record',
            'method' => 'getTitle',
            'type' => self::TYPE_STRING,
        ],
        't' => [
            'class' => 'AudioFile',
            'method' => 'getTitle',
            'type' => self::TYPE_STRING,
        ],
        'y' => [
            'class' => 'Record',
            'method' => 'getYear',
            'type' => self::TYPE_NUMERIC,
        ],
    ];
    const DELIMITER = '%';
    const TRACK_METADATA_FIELDS = [
        'title',
        'performer',
        'band',
        'album',
        'genre',
    ];

    private $records;
    private $settings;
    private $renameMethod;
    private $replaceMap;

    public function __construct($records, $settings)
    {
        global $globals;

        $this->records = $records;
        $this->settings = $settings;
        $this->fileHandler = $globals->getFileHandler();
        $this->setRenameMethod();
        $this->setReplaceMap();
    }

    public function getRenameMethod()
    {
        return $this->renameMethod;
    }

    public function setRenameMethod($method = '')
    {
        $this->renameMethod = isCallable($method) ? $method : ($this->renameMethod ?: 'rename');
    }

    /**
     * Set mapping of replacement patterns to methods to be executed
     * Returns an associative array with class names as keys, eg
     * [
     *      'Record' => [
     *          '/%a/' => [
     *              'method' => 'getArtist',
     *              'type' => TYPE_STRING
     *          ]
     *      ]
     * ]
     */
    private function setReplaceMap()
    {
        $replaceMap = [];
        foreach (self::REPLACE_MAP as $key => $replace) {
            // Get current element's class
            $class = $replace['class'];
            if (!isset($replaceMap[$class])) {
                $replaceMap[$class] = [];
            }

            // Get replacement pattern
            $pattern = self::DELIMITER . $key;
            if ($replace['type'] === self::TYPE_NUMERIC) {
                $pattern .= '({\d+})?';
            }
            $pattern = "/$pattern/";

            $replaceMap[$class][$pattern] = [
                'method' => $replace['method'],
                'type' => $replace['type'],
            ];
        }

        $this->replaceMap = $replaceMap;
    }

    /**
     * Returns an array of string patterns to be replaced, eg
     * [
     *      '%a' => [
     *          'value' => 'Revolted Masses',
     *          'type' => TYPE_STRING
     *      ]
     * ]
     *
     * @param Object $obj The object to get the information from
     * @return array An array with replacements
     */
    private function getReplaces($obj)
    {
        $replaces = [];

        $class = get_class($obj);
        if (empty($this->replaceMap[$class])) {
            return [];
        }

        foreach ($this->replaceMap[$class] as $pattern => $replaceInfo) {
            $replaces[$pattern] = [
                'value' => $obj->{$replaceInfo['method']}(),
                'type' => $replaceInfo['type']
            ];
        }

        return $replaces;
    }

    private function getSetting($key)
    {
        return $this->settings->getSetting($key);
    }

    private function getFormatPad($format)
    {
        $matches = [];
        preg_match('/{(\d+)}/', $format, $matches);
        return !empty($matches[1]) ? $matches[1] : 0;
    }

    private function format($formatKey, $replaces)
    {
        $format = $this->getSetting($formatKey);
        $values = [];
        foreach ($replaces as $key => $replace) {
            $value = $replace['value'];
            if ($replace['type'] == self::TYPE_NUMERIC) {
                $value = $this->formatNumeric($format, $value);
            } else {
                $value = $this->formatWordCase($value);
            }

            $values[] = $value;
        }

        return preg_replace(array_keys($replaces), $values, $format);
    }

    private function formatNumeric($format, $value)
    {
        $pad = $this->getFormatPad($format);
        if ($pad) {
            $value = numericPad(substr($value, -1 * $pad), $pad);
        }

        return $value;
    }

    private function formatWordCase($value)
    {
        $toLower = explode(',', $this->getSetting('lowercase'));
        $words = preg_split('/\s+/', $value);
        $firstWord = ucfirst(array_shift($words));

        array_walk($words, function (&$word) use ($toLower) {
            $word = inArray($word, $toLower, true) ? strtolower($word) : ucfirst($word);
        });
        array_unshift($words, $firstWord);

        return implode(' ', $words);
    }

    private function formatRecord($record, $replaces)
    {
        try {
            $newName = $this->format('record.title', $replaces);
            $this->rename($record->getPath(), joinPath($record->getParentDir(), $newName));
            $record->rename($newName);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function formatTrack($track, $replaces)
    {
        try {
            $newName = $this->format('track.title', $replaces) . $track->getExtension();
            $this->rename($track->getPath(), joinPath($track->getDir(), $newName));
            $track->setName(getFileName($newName));
            $this->formatTrackMetadata($track);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function formatTrackMetadata($track)
    {
        foreach (self::TRACK_METADATA_FIELDS as $field) {
            $value = $this->formatWordCase($track->{"get$field"}());
            $track->{"set$field"}($value);
        }
    }

    public function run()
    {
        try {
            foreach ($this->records as $record) {
                // Format records
                $replaces = $this->getReplaces($record);
                $this->formatRecord($record, $replaces);

                // Format tracks
                foreach ($record->getTracks() as $track) {
                    $replaces = $this->getReplaces($track);
                    $this->formatTrack($track, $replaces);
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function rename($oldName, $newName)
    {
        try {
            $rename = $this->getRenameMethod();
            $rename($oldName, $newName);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
