<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/init.php';

(function() {
    $commandName = $_SERVER['argc'] >= 2 ? $_SERVER['argv'][1] : 'help';
    try {
        $resources = new \App\ResourceManager();
        $commandClass = \App\Console\CommandRouter::route($commandName);
        $commandObject = new $commandClass($resources);
        $statusCode = (int) $commandObject();
    } catch (Throwable $e) {
        echo "Error: {$e->getMessage()}\n";
        echo "\nException " . get_class($e) . " was thrown in file {$e->getFile()} at line {$e->getLine()}\nDebug backtrace:\n{$e->getTraceAsString()}\n";
        if ($e->getCode() != 0) {
            $statusCode = $e->getCode();
        } else {
            $statusCode = 255;
        }
    }
    exit($statusCode);
})();
