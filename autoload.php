<?php

/* Constants */
define('PROGRAM_DIR', getcwd());
define('CUSTOM_CONFIG', 'settings/custom.ini');

require_once 'lib.php';
spl_autoload_register('loadClass');

/* Custom Functions */

/**
 * Class autoloader for the program
 * @param string $class The name of the class to be loaded
 */
function loadClass($class)
{
    require joinPath(PROGRAM_DIR, 'classes', "$class.php");
}

/**
 * Prints debugging information about a collection of tracks
 *
 * @param array $track An array of AudioFile objects
 */
function debugTrackInfo($tracks = [])
{
    foreach ($tracks as $track) {
        echo $track->getPath() . "\n";
        echo "Number: " . $track->getTrackNumber() . "\n";
        echo "Title:  " . $track->getTitle() . "\n";
        echo "\n";
    }
}

/**
 * Prints debugging information about a collection of records
 *
 * @param array $records An array of Record objects
 */
function debugRecordInfo($records = [])
{
    foreach ($records as $record) {
        echo $record->getPath() . "\n";
        echo "Band: " . $record->getBand() . "\n";
        echo "Title:  " . $record->getTitle() . "\n";
        echo "Year:   " . $record->getYear() . "\n";
        echo "\n";
    }
}

