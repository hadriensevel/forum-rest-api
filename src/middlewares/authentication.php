<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: authentication.php
 */

require_once __DIR__ . '/../tequila/tequila.php';

/**
 * Check if the user is authenticated
 * @return void
 */
function checkAuthentication(): void
{
    // Create an instance of the TequilaClient class
    $tequila = new TequilaClient();

    // Call the LoadSession function to check if the user is authenticated
    $isAuthenticated = $tequila->LoadSession();

    // Check if the user is authenticated
    if (!$isAuthenticated) {
        // Send an error response indicating that the user is not authenticated
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(array(
            'error' => 'Unauthorized'
        )));
    }
}
