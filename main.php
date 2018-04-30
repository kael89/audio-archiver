<?php

require_once 'autoload.php';

function main($dir, $options = [])
{
    global $globals;

    try {
        $globals = new Globals($options);
        $archiver = new Program($dir);
        $archiver->run();
    } catch (Exception $e) {
        throw $e;
    }
}
