<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: error.php
 */

use Mailer\Mailer;

// Hide errors
ini_set('display_errors', 0);

/**
 * Error handler: email the admin with the error details and send a 500 response
 * Debug mode: display the error details
 * @param $error
 * @return void
 */
function errorHandler($error): void
{
    $errorId = uniqid('error_');
    $logFile = __DIR__ . '/../../logs/error.log'; // Path to the error log file

    // Format the error details for logging
    $errorDetails = [
        'errorId' => $errorId,
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $error,
    ];
    $logMessage = json_encode($errorDetails, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    // Log the error to a file
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);

    if (API_DEBUG) {
        header('HTTP/1.1 500 Internal Server Error');
        echo '<pre>';
        echo 'Error ID: ' . $errorId . '<br />';
        print_r($error);
        echo '</pre>';
    } else {
        // Log the error details
        error_log("[$errorId] " . json_encode($error));

        // Send email to admin (ignore errors)
        try {
            $mailer = new Mailer();
            $mailer->sendErrorEmail($errorId, json_encode($error, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Exception $e) {}

        // Send 500 response with error ID
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(array(
            'error' => 'Internal Server Error',
            'errorId' => $errorId
        )));
    }
}

/**
 * Check for fatal errors
 * @return void
 */
function check_for_fatal(): void
{
    $error = error_get_last();
    if ($error !== null && $error["type"] == E_ERROR) {
        errorHandler($error);
    }
}

function exceptionHandler($exception): void
{
    errorHandler(array(
        'type' => 'Exception',
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ));
}

register_shutdown_function("check_for_fatal");
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}, E_ALL);
set_exception_handler('exceptionHandler');

