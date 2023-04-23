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
    $isAuthenticated = $tequila->loadSession();

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

/**
 * Authenticate with Tequila
 * @param string $appName Name to display on the Tequila login page.
 * @param string $appURL URL of the application to redirect the user after the authentication.
 * @return void
 */
function authenticate(string $appName, string $appURL): void
{
    // Create an instance of the TequilaClient class
    $tequila = new TequilaClient();

    // Set Tequila parameters
    $tequila->setApplicationName($appName);
    $tequila->setWantedAttributes(array(
        'displayname',
        'email',
        'uniqueid'
    ));
    $tequila->SetAllowsFilter('org=epfl');
    $tequila->setApplicationURL($appURL);

    // Call the Authenticate function
    $tequila->authenticate();
}

/**
 * Logout from Tequila
 * @param string $redirectURL URL to redirect the user after the logout.
 * @return void
 */
function logout(string $redirectURL = ''): void
{
    // Create an instance of the TequilaClient class
    $tequila = new TequilaClient();

    // Call the Logout function to redirect the user to the Tequila logout page
    $tequila->logout($redirectURL);
}