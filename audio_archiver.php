<?php

require_once 'main.php';

try {
    // Get arguments
    $args = parseArgs($argv);

    // Initialize global objects
    $options = [
        'backup' => $args['backup'],
        'convert' => $args['convert'],
        'debug' => $args['debug'],
        'load_custom' => $args['load_custom'],
    ];

    // Start program
    main($args['dir'], $options);
} catch (Exception $e) {
    handleException($e);
}

function parseArgs($argv)
{
    $args = ['dir' => ''];
    $availOptions = [
        'b' => 'backup',
        'c' => 'convert',
        'd' => 'debug',
        's' => 'load_custom',
    ];

    // Read options
    $inputOptions = getopt(implode('', array_keys($availOptions)));
    foreach ($availOptions as $letter => $name) {
        $args[$name] = array_key_exists($letter, $inputOptions);
    }
    $off = count($inputOptions);

    // Read target directory
    if (empty($argv[$off + 1])) {
        throw new Exception('Usage: php [-b] [-c] [-s] audio_archiver.php target', E_ERROR);
    }

    $dir = $argv[$off + 1];
    $args['dir'] = realpath($dir);
    if (!is_dir($args['dir'])) {
        throw new Exception("'$dir' is not a valid directory", E_ERROR);
    }

    return $args;
}

function handleException($e)
{
    switch ($e->getCode()) {
        case E_WARNING:
            $type = '[Warning]';
        case E_ERROR:
        default:
            $type = '[Error]';
    }

    echo($type . ' ' . $e->getMessage() . "\n");
    if ($e->getCode() == E_ERROR) {
        die;
    }
}
