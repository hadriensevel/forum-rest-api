<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: authentication.php
 */

require_once __DIR__ . '/../tequila/tequila-jwt.php';

use Controller\Api\UserController;
use Exception\ExpiredToken;
use Exception\InvalidToken;
use Exception\ExpiredSession;

/**
 * Authenticate with Tequila
 * @param string $appName Name to display on the Tequila login page.
 * @return string
 */
function authenticate(string $appName): string
{
    // Create an instance of the TequilaClient class
    $tequila = new TequilaClientJWT();

    // Set Tequila parameters
    $tequila->setApplicationName($appName);
    $tequila->setWantedAttributes(['displayname', 'email', 'uniqueid']);

    // Call the authenticate function
    $token = $tequila->authenticate();

    // Also store the token in a secure cookie if it's not empty
    if (!empty($token)) setcookie('token', $token, 0, '/', '', true, true);

    return $token;
}

/**
 * Get the user information
 * @param string $token JWT token of the user.
 * @param bool $enforceInDatabase If true, the user must be in the database.
 * @return array User information.
 * @throws ExpiredSession|ExpiredToken|InvalidToken
 * @throws Exception
 */
function getUserDetails(string $token, bool $enforceInDatabase = false): array
{
    $tequila = $tequila ?? new TequilaClientJWT();
    $tequila->setWantedAttributes(['displayname', 'email', 'uniqueid']);

    $userController = $userController ?? new UserController();

    // Create an instance of the TequilaClient class
    $attributes = $tequila->verifyTokenAndGetAttributes($token);
    $sciper = $attributes['uniqueid'];
    $displayName = $attributes['displayname'];
    $email = $attributes['email'];

    // Return the user information from the database
    return $userController->getUserDetails($sciper, $displayName, $email, $enforceInDatabase);
}

/**
 * Send the user details
 * @param string $token JWT token of the user.
 * @return void
 * @throws Exception
 */
function sendUserDetails(string $token): void
{
    try {
        $userDetails = getUserDetails($token);
        sendSuccessResponse($userDetails);
    } catch (ExpiredToken|InvalidToken|ExpiredSession $e) {
        sendUnauthorizedResponse($e->getMessage());
    }
}

/**
 * Logout from Tequila
 * @param string $redirectURL URL to redirect the user after the logout.
 * @return void
 */
function logout(string $redirectURL = ''): void
{
    // Delete the token cookie
    setcookie('token', '', time() - 3600, '/', '', true, true);

    // Create an instance of the TequilaClient class
    $tequila = new TequilaClientJWT();

    // Call the logout function
    $tequila->logout($redirectURL);
}

/**
 * Send a success response with the data
 * @param array $data Data to send.
 * @return void
 */
function sendSuccessResponse(array $data): void
{
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}

/**
 * Send an unauthorized response
 * @param string $message Message to send.
 * @return void
 */
function sendUnauthorizedResponse(string $message = 'Unauthorized'): void
{
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message]);
}

/**
 * Extract the token from the Authorization header
 * @return string|null
 * @throws Exception if the token is not present or is malformed.
 */
function getTokenFromHeader(): ?string
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }

    throw new Exception('No token provided or token is malformed.');
}

/**
 * Get the token from the Authorization header or die
 * @return string
 */
function getTokenOrDie(): string
{
    try {
        return getTokenFromHeader();
    } catch (Exception $e) {
        sendUnauthorizedResponse($e->getMessage());
        exit();
    }
}

/**
 * Return user information from the token
 * @param bool $enforceToken
 * @return array|null
 * @throws ExpiredSession|ExpiredToken|InvalidToken
 */
function getUserFromToken(bool $enforceToken = true): ?array
{
    if ($enforceToken) {
        $token = getTokenOrDie();
        return getUserDetails($token, true);
    } else try {
        $token = getTokenFromHeader();
        return getUserDetails($token);
    } catch (Exception) {
        return null;
    }
}
