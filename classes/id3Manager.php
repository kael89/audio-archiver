<?php

class id3Manager
{
    const VERSIONS = [
        1,
        2,
    ];
    const FIELDS = [
        'TIT2' => [
            'v1' => 'Title',
        ],
        'TPE1' => [
            'v1' => 'Artist',
        ],
        'TPE2' => [
            'v1' => 'Artist',
        ],
        'TALB' => [
            'v1' => 'Album',
        ],
        'TYER' => [
            'v1' => 'Year',
        ],
        'TCON' => [
            'v1' => 'Genre',
        ],
        'COMM' => [
            'v1' => 'Comment',
        ],
        'TRCK' => [
            'v1' => 'Track',
        ],
    ];

    private static function isVersionHeader($line, $version)
    {
        if (self::getVersionByHeader($line) === $version) {
            return true;
        }

        if (substr($line, -12) === "No ID3v$version tag") {
            return true;
        }

        return false;
    }

    private static function getVersionByHeader($line)
    {
        return (strpos($line, 'tag info for') === 6) ? (int)substr($line, 4, 1) : 0;
    }

    private static function getTagLinesPerVersion($output)
    {
        $results = [
            'v1' => [],
            'v2' => [],
        ];

        $i = 0;
        foreach (self::VERSIONS as $num) {
            if (self::getVersionByHeader($output[$i]) !== $num) {
                continue;
            }
            $i++;

            $otherNum = ($num === 1) ? 2 : 1;
            for (; $i < count($output); $i++) {
                if (self::isVersionHeader($output[$i], $otherNum)) {
                    break;
                }

                $results["v$num"][] = $output[$i];
            }
        }

        return $results;
    }

    private static function getV1Tags($lines)
    {
        if (is_array($lines)) {
            $lines = implode(' ', $lines);
        }
        // Add leading space to also capture the first tag with our regex
        $lines = ' ' . $lines;

        $fields = extractDimension(self::FIELDS, 'v1');
        $tags = fillValues($fields);
        $matches = preg_split('/\s(\S*)\s*:/', $lines, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $matches = array_map('trim', $matches);

        $currentKey = '';
        foreach ($matches as $match) {
            $key = array_search($match, $fields);

            if ($key !== false) {
                $currentKey = $key;
                unset($fields[$key]);
            } elseif ($currentKey) {
                $tags[$currentKey] .= $match;
            }
        }

        // Remove redundant artist field if not set
        if (!strlen($tags['TPE1']) || !strlen($tags['TPE2'])) {
            $key = !strlen('TPE1') ? 'TPE1' : 'TPE2';
            unset($tags[$key]);
        }
        // Filter year value, since it contains a trailing comma
        $tags['TYER'] = (int)getByKey($tags, 'TYER') ?: '';
        return $tags;
    }

    private static function getV2Tags($lines)
    {
        $tags = [];
        $fields = array_keys(self::FIELDS);

        foreach ($lines as $line) {
            $id = current(explode(' ', $line));
            if (in_array($id, $fields)) {
                $tags[$id] = substr($line, strpos($line, ':') + 2);
            }
        }

        return $tags;
    }

    private static function getFinalTags($tagLines)
    {
        return !empty($tagLines['v2']) ? self::getV2Tags($tagLines['v2']) : self::getV1Tags($tagLines['v1']);
    }

    private static function validateFilepath($filepath)
    {
        if (!isAudio($filepath)) {
            throw new Exception("$filepath is not an audio file");
        }
    }

    private function sanitizeExecArg($arg)
    {
        return (strlen($arg)) ? '"' . $arg . '"' : '';
    }

    private static function exec(array $options, $filepath)
    {
        if (empty($options[0])) {
            return '';
        }

        // Build command arguments
        $args = ['id3v2'];
        $args[] = strPush($options[0], strlen($options[0]) > 1 ? '--' : '-');
        if (isset($options[1])) {
            $args[] = $options[1];
        }
        $args[] = $filepath;

        return execCommand($args);
    }

    public static function getTags($filepath)
    {
        self::validateFilepath($filepath);

        $output = self::exec(['l'], $filepath);
        $tagLines = self::getTagLinesPerVersion($output);

        return self::getFinalTags($tagLines);
    }

    public static function writeTags($filepath, $tags)
    {
        self::validateFilepath($filepath);

        $tags = subArray(array_keys(self::FIELDS), $tags);
        foreach ($tags as $id => $value) {
            self::exec([$id, $value], $filepath);
        }
    }
}
