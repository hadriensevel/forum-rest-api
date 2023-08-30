<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: authentication.php
 */

require_once __DIR__ . '/../tequila/tequila.php';

use Controller\Api\UserController;

/**
 * Check if the user is authenticated
 * @return void
 */
function checkAuthentication(): void
{
    // Create an instance of the TequilaClient class
    $tequila = new TequilaClient();

    // Check if the user is authenticated
    if (!$tequila->loadSession()) {
        // If not, send an error response
        sendUnauthorizedResponse();
    }
}

/**
 * Authenticate with Tequila and store user information in the database if needed
 * @param string $appName Name to display on the Tequila login page.
 * @return void
 * @throws Exception
 */
function authenticate(string $appName): void
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
    $tequila->setAllowsFilter('org=epfl');

    // Call the authenticate function
    $tequila->authenticate();

    // Get the user information
    $sciper = $tequila->getValue('uniqueid');
    $name = $tequila->getValue('displayname');
    $email = $tequila->getValue('email');

    // Check if the user is in the database and add it if not
    $userController = new UserController();
    if (!$userController->checkUser($sciper)) {
        $userController->addUser($sciper, $name, $email);
    }
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

    // Call the logout function
    $tequila->logout($redirectURL);
}

/**
 * Util function to get the user's unique ID if the user is authenticated
 * @return int|null
 */
function getSciper(): ?int
{
    // Create an instance of the TequilaClient class
    $tequila = new TequilaClient();

    // Check if the user is authenticated
    if (!$tequila->loadSession()) {
        // If not, return null
        return null;
    }

    // Return the user's unique ID
    return $tequila->getValue('uniqueid');
}


/**
 * Get and send the user information
 * @return void
 * @throws Exception
 */
function getUserDetails(): void
{
    // Create an instance of the UserController class
    $userController = new UserController();

    // Get the user information from the database
    $userDetails = $userController->getUserDetails($_SESSION['uniqueid']);

    // Send the user information
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($userDetails);
}

/**
 * Check if the user is an admin
 * @return void
 * @throws Exception
 */
function ensureAdmin(): void
{
    // Create an instance of the UserController class
    $userController = new UserController();

    // If the user is not an admin, send an error response
    if (!$userController->isUserAdmin($_SESSION['uniqueid'])) {
        // If not, send an error response
        sendUnauthorizedResponse();
    }
}

/**
 * Send an unauthorized response
 * @return void
 */
function sendUnauthorizedResponse(): void
{
    // If not, send an error response
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(array(
        'error' => 'Unauthorized'
    )));
}