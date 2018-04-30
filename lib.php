<?php

/**
 * Joins the provided path parts to a full path
 *
 * @param array $parts The path parts
 * @return string The resulting full path
 */
function joinPath(...$parts)
{
    array_walk($parts, function ($part) {
        return preg_replace('/\\' . DIRECTORY_SEPARATOR . '{2,}/', DIRECTORY_SEPARATOR, $part);
    });
    return implode(DIRECTORY_SEPARATOR, $parts);
}

/**
 * Returns the name the provided file, excluding its extension
 *
 * @param string $filepath The filepath of the file
 * @return string The resulting name
 */
function getFileName($filepath)
{
    return pathinfo($filepath)['filename'];
}

/**
 * Returns the extension of the provided filepath
 *
 * @param string $filepath The provided filepath
 * @return string The resulting extension
 */
function getExtension($filepath)
{
    $info = pathinfo($filepath);
    return (!empty($info['extension'])) ? $info['extension'] : '';
}

/**
 * Creates a unique filename by appending a number in parentheses
 * in the end of the original name if required. Works with files or folders
 *
 * @param string $dir The directory where the file is located
 * @param string $filename The provided filename (possibly including extension)
 * @param int $limit If set, sets a maximum value for the appended number
 * @return string The created unique filename
 */
function createFilename($dir, $filename, $limit = 100)
{
    $filepath = joinPath($dir, $filename);
    if (!is_dir($dir) || strlen($filename) == 0) {
        return '';
    }
    $name = getFileName($filename);
    $ext = getExtension($filepath);

    $i = 1;
    while (file_exists($filepath)) {
        if ($i == $limit + 1) {
            return '';
        }

        $filename = "$name ($i).$ext";
        $filepath = joinPath($dir, $filename);
        $i++;
    }

    return $filename;
}

/**
 * Checks if the provided function is callable
 *
 * @param Closure|array|string $func A closure, [object, methodName] array or function name
 * @return bool
 */
function isCallable($func)
{
    switch (gettype($func)) {
        case 'object':
            return get_class($func) === 'Closure';
        case 'array':
            return count($func) > 1 && method_exists($func[0], $func[1]);
        case 'string':
            return function_exists($func);
        default:
            return false;
    }
}

/**
 * Converts snake case to camel case
 *
 * @param string $str
 * @return string
 */
function snakeToCamel($str)
{
    $str = ucwords($str, '_');
    return lcfirst(str_replace('_', '', $str));
}

/**
 * Executes a shell command after sanitizing its arguments
 *
 * @param array $args The provided arguments
 * @return array $output An array containing the lines of the command output
 */
function execCommand(array $args)
{
    $output = [];
    $args = array_map('escapeshellarg', $args);
    exec(implode(' ', $args), $output);
    return $output;
}

/**
 * Scans for all subdirectories in the provided directory
 *
 * @param string $dir The target directory
 * @return array|bool Array of directories on success, false on failure
 */
function scanFolders($dir)
{
    static $folders = [];

    if (!is_dir($dir)) {
        return false;
    }

    $isRoot = empty($folders);

    // Recurse in directory folders
    $items = dirFolders($dir);
    foreach ($items as $item) {
        $path = "$dir/$item";

        $folders[] = $path;
        scanFolders($path);
    }

    // When scanning ends, reset function and return results
    if ($isRoot) {
        $result = $folders;
        rsort($result);
        $folders = [];

        return $result;
    }
}

/**
 * Returns all contents in the provided directory
 *
 * @param string $dir The target directory
 * @param string $flag Restricts the result set. Available values: 'FILES', 'FOLDERS' (optional)
 * @return array Array of directory's contents
 */
function dirContents($dir, $flag = '')
{
    $contents = [];
    $flag = strtoupper($flag);

    if (!is_dir($dir)) {
        return $contents;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if (in_array($item, ['.', '..'])) {
            continue;
        }
        if ($flag == 'FILES' && is_dir("$dir/$item")) {
            continue;
        }
        if ($flag == 'FOLDERS' && is_file("$dir/$item")) {
            continue;
        }

        $contents[] = $item;
    }

    return $contents;
}

/**
 * Returns all subfolders in the provided directory
 *
 * @param string $dir The target directory
 * @return array Array of directory's folders
 */
function dirFolders($dir)
{
    return dirContents($dir, 'FOLDERS');
}

/**
 * Returns all files in the provided directory
 *
 * @param $dir string The target directory
 * @return array Array of directory's files
 */
function dirFiles($dir)
{
    return dirContents($dir, 'FILES');
}

/**
 * Checks whether the provided file is of audio type
 *
 * @param string $file Filepath of the target file
 * @return bool Result flag
 */
function isAudio($file)
{
    $extensions = [
        'mp3',
        'wav',
    ];

    $ext = strtolower(getExtension($file));
    if (!$ext) {
        return false;
    }

    return in_array($ext, $extensions);
}

/**
 * Checks if a particular Bash command exists
 *
 * @param $command
 * @return bool
 */
function commandExists($command)
{
    $result = shell_exec(sprintf('which %s', escapeshellarg($command)));
    return !empty($result);
}

/**
 * Returns the number of cursor positions a string will occupy
 *
 * @param string $str The provided string
 * @param int $tabSize The size of the tab character. Defaults to 4
 * @return int
 */
function strSize($str, $tabSize = 4)
{
    $matches = [];
    preg_match_all("/\t/", $str, $matches, PREG_OFFSET_CAPTURE);
    $matches = getByKey($matches, 0);

    $size = strlen($str);
    $offset = 0;

    foreach ($matches as $match) {
        $match[1] += $offset;
        $increment = $tabSize - ($match[1] % $tabSize) - 1;

        $size += $increment;
        $offset += $increment;
    }

    return $size;
}

/**
 * Removes a part of a string
 *
 * @param string $str The given string
 * @param int $start The position from where the removal should start
 * @param int $length The number of characters to be removed
 * @return string
 */
function strSplice($str, $start, $length)
{
    if (!is_string($str)) {
        return '';
    }
    if (!isset($start)) {
        $start = 0;
    }
    if (!isset($length)) {
        $length = strlen($str);
    }
    if (!is_numeric($start) || !is_numeric($length)) {
        return '';
    }

    $start = $start >= 0 ? (int)$start : 0;
    $length = $length >= 0 ? (int)$length : 0;

    return substr($str, 0, $start) . substr($str, $start + $length);
}

/**
 * Adds a prefix to the beginning of a string
 *
 * @param string $str
 * @param string $prefix
 */
function strPush($str, $prefix)
{
    return $prefix . $str;
}

/**
 * Adds left padding to a string
 *
 * @param $str
 * @param $padLength
 * @param string $padString
 * @return string
 */
function leftPad($str, $padLength, $padString = ' ')
{
    return str_pad($str, $padLength, $padString, STR_PAD_LEFT);
}

/**
 * Adds leading zeros to the input
 *
 * @param string $str
 * @param int $padLength
 * @return string string
 */
function numericPad($str, $padLength)
{
    return leftPad($str, $padLength, '0');
}

/**
 * Removes all non string or empty string values from
 * an array
 *
 * @param array $arr The given array
 * @return array The filtered array
 */
function filterStrings($arr)
{
    $result = array_filter($arr, function ($item) {
        return is_string($item) && strlen($item);
    });
    return $result;
}

/**
 * Returns the max string length in an array of strings
 *
 * @param array $arr
 * @return int
 */
function maxLength(array $arr)
{
    $max = 0;
    foreach ($arr as $item) {
        $length = strlen($item);
        if ($length > $max) {
            $max = $length;
        }
    }

    return $max;
}

/**
 * Fills an array with the specified value, retaining its keys
 *
 * @param array $arr
 * @param string $value
 * @return array
 */
function fillValues($arr = [], $value = '')
{
    return array_fill_keys(array_keys($arr), $value);
}

/**
 * Returns a one-dimensional array with the keys of all the
 * final elements (leaves) of a multi-dimensional associative array
 *
 * @param array $arr The given array
 * @return array An array of all final element keys, joined by periods
 */
function getKeys($arr)
{
    $keys = [];

    foreach ($arr as $key => $value) {
        if (is_array($value)) {
            $innerKeys = array_map(function ($innerKey) use ($key) {
                return "$key.$innerKey";
            }, getKeys($value));
            $keys = array_merge($keys, $innerKeys);
        } else {
            $keys[] = $key;
        }
    }

    return $keys;
}

/**
 * Gets the array value that corresponds to the specified key
 *
 * @param array $arr The input array
 * @param string $key The specified key. Dot notation can be used for multidimensional arrays
 * @param mixed $default Fallback value
 * @return mixed|null The resulting value, or `$default` on lookup failure
 */
function getByKey(array $arr, $key, $default = null)
{
    $keys = explode('.', $key);

    $result = $default;
    foreach ($keys as $key) {
        if (!isset($arr[$key])) {
            break;
        }

        $result = $arr[$key];
        $arr = $arr[$key];
    }

    return $result;
}

/**
 * Sets the array value that corresponds to the specified key.
 * The original array will be modified
 *
 * @param array $arr The array to be modified
 * @param string $key The specified key. Dot notation can be used for multidimensional arrays
 */
function setByKey(&$arr, $key, $value)
{
    $keys = explode('.', $key);

    $item =& $arr;
    foreach ($keys as $key) {
        $item =& $item[$key];
    }

    $item = $value;
}

/**
 * Changes the key of an array. The original array will be modified.
 *
 * @param array $arr The array to be modified
 * @param string $oldKey The old key
 * @param string $newKey The new key. If this key already exists,
 * the corresponding value will be overwritten
 */
function changeKey(&$arr, $oldKey, $newKey)
{
    $arr[$newKey] = $arr[$oldKey];
    unset($arr[$oldKey]);
}

/**
 * Extracts part of an array by given keys
 *
 * @param array $keys Specify which elements to keep
 * @param array $arr The original array
 * @param bool $filter If true the filtering is done by the original array's keys,
 * if not, by the given keys. Used for optimization
 * @return array The resulting array
 */
function subArray($keys, array $arr, $filter = false)
{
    $results = [];

    if (!is_array($arr) || empty($arr)) {
        return $results;
    }

    if (!is_array($keys)) {
        return (isset($arr[$keys])) ? $arr[$keys] : $results;
    }

    if ($filter) {
        $results = array_filter($arr, function ($value, $key) use ($keys) {
            return in_array($key, $keys);
        }, ARRAY_FILTER_USE_BOTH);
    } else {
        foreach ($keys as $key) {
            if (isset($arr[$key])) {
                $results[$key] = $arr[$key];
            }
        }
    }

    return $results;
}

/**
 * Combines two arrays, one with desired values and one with default values.
 * The default values array specifies the keys of the resulting array.
 *
 * @param array $values The provided values
 * @param array $default An array of default values
 * @return array
 */
function arrayDefault($values, $default)
{
    return subArray(array_keys($default), array_merge($default, $values));
}

/**
 * Extracts the specified dimension key from an associative array
 *
 * @param array $arr
 * @param string $dimension
 */
function extractDimension(array $arr, $dimension)
{
    $results = [];
    foreach ($arr as $key => $value) {
        if (isset($value[$dimension])) {
            $results[$key] = $value[$dimension];
        }
    }

    return $results;
}

/**
 * Maps the values of the first array to the ones of the second,
 * using their common keys as link
 *
 * @param array $arr1
 * @param array $arr2
 * @return array
 */
function keyMap(array $arr1, array $arr2)
{
    if (!hasElements($arr1) || !hasElements($arr2)) {
        return [];
    }

    $keys = array_intersect(array_keys($arr1), array_keys($arr2));
    return array_combine(subArray($keys, $arr1), subArray($keys, $arr2));
}

/**
 * Optionally case insensitive array value lookup
 *
 * @param $needle
 * @param $haystack
 * @return bool
 */
function inArray($needle, $haystack, $caseInsensitive = false)
{
    if ($caseInsensitive) {
        $needle = mb_strtolower($needle);
        $haystack = array_map('mb_strtolower', $haystack);
    }

    return in_array($needle, $haystack);
}

/**
 * Checks whether the provided input is a non empty array
 *
 * @param mixed $arr
 * @return bool
 */
function hasElements($arr)
{
    return (is_array($arr) && !empty($arr));
}

/**
 * Returns whether the input is a positive number
 * @param mixed $var The provided input
 * @return bool The result flag
 */
function isPositive($var)
{
    return (is_numeric($var) && $var > 0);

}

/**
 * Formats time input
 *
 * @param float $secs Time in seconds
 * @param int $decimals The number of decimal digits
 * @return float
 */
function formatTime($secs, $decimals = 3)
{
    return number_format($secs, $decimals);
}