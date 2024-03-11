<?php

const PROJECT_ROOT_DIR = __DIR__;

// Force all PHP notices and warnings to be PHP exceptions
set_error_handler(
/**
 * @throws ErrorException
 */
    function(int $severity, string $message, string $file, int $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }, E_ALL);
