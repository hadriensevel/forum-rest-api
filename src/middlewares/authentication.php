<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: authentication.php
 */

use Controller\Api\SessionController;
use Controller\Api\UserController;
use Jumbojett\OpenIDConnectClient;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

// ============================================================================
// AUTHENTICATION FUNCTIONS
// ============================================================================

/**
 * Authenticate with Entra ID
 * @return never This function always redirects and exits
 * @throws Exception
 */
function authenticate(): never
{
    handleRedirectCookie();
    $oidc = configureOidcClient();
    $oidc->authenticate();
    
    $claims = $oidc->getVerifiedClaims();
    $sessionId = generateSessionId();
    $sessionExpiry = time() + SESSION_LIFETIME;
    
    registerUserSession($sessionId, $sessionExpiry, $claims);
    $token = createJwtToken($sessionId, $claims);
    
    redirectWithToken($token);
}

/**
 * Handle redirect URL storage in secure cookie
 */
function handleRedirectCookie(): void
{
    if (isset($_GET['redirect']) && $_GET['redirect']) {
        setcookie(
            'redirect_after_auth',
            $_GET['redirect'],
            time() + 600, // 10 minutes
            '/',
            '',
            true, // secure
            true  // httponly
        );
    }
}

/**
 * Configure OIDC client
 * @return OpenIDConnectClient
 */
function configureOidcClient(): OpenIDConnectClient
{
    $oidc = new OpenIDConnectClient(
        AUTH_URL,
        CLIENT_ID,
        CLIENT_SECRET
    );
    $oidc->setRedirectURL(OIDC_REDIRECT_URI);
    $oidc->setResponseTypes(['code']);
    
    return $oidc;
}

/**
 * Generate a secure session ID
 * @return string 64-character hex string
 * @throws Exception
 */
function generateSessionId(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Register user session in database
 * @param string $sessionId
 * @param int $sessionExpiry
 * @param object $claims
 */
function registerUserSession(string $sessionId, int $sessionExpiry, object $claims): void
{
    $sessionController = new SessionController();
    $sessionController->registerSession($sessionId, $sessionExpiry, $claims);
}

/**
 * Create JWT token with user claims
 * @param string $sessionId
 * @param object $claims
 * @return string
 * @throws Exception
 */
function createJwtToken(string $sessionId, object $claims): string
{
    $userController = new UserController();
    $userDetails = $userController->getUserDetails(
        $claims->uniqueid,
        $claims->given_name . ' ' . $claims->family_name,
        $claims->mail,
        false
    );
    
    $jwtData = [
        'sid' => $sessionId,
        'exp' => time() + JWT_LIFETIME,
        'iat' => time(),
        'sciper' => $claims->uniqueid,
        'name' => $claims->given_name . ' ' . $claims->family_name,
        'role' => $userDetails['role'],
        'isadmin' => $userDetails['is_admin'],
    ];
    
    return JWT::encode($jwtData, JWT_SECRET, 'HS256');
}

/**
 * Redirect user with token
 * @param string $token
 * @return never This function always exits
 */
function redirectWithToken(string $token): never
{
    $redirectUrl = $_COOKIE['redirect_after_auth'] ?? '/';
    setcookie('redirect_after_auth', '', time() - 3600, '/');
    
    $separator = str_contains($redirectUrl, '?') ? '&' : '?';
    $redirectUrl .= $separator . 'token=' . $token;
    
    header('Location: ' . $redirectUrl);
    exit;
}

// ============================================================================
// TOKEN VALIDATION FUNCTIONS
// ============================================================================

/**
 * Validate token and return appropriate HTTP status code
 * @return void
 */
function validateToken(): void
{
    try {
        $token = getTokenFromHeader();
        
        if (!isTokenWellFormed($token)) {
            sendErrorResponse(401, 'Malformed token');
        }
        
        $decoded = verifyTokenSignature($token);
        if (!$decoded) {
            return; // Error already sent
        }
        
        if (!isSessionValid($decoded->sid)) {
            sendErrorResponse(403, 'Session expired');
        }
        
        // Periodically cleanup expired sessions (1% chance)
        if (rand(1, 100) === 1) {
            $sessionController = new SessionController();
            $sessionController->cleanupExpiredSessions();
        }
        
        http_response_code(200);
        
    } catch (Exception $e) {
        http_response_code(401);
    }
}

/**
 * Check if token has proper JWT structure
 * @param string $token
 * @return bool
 */
function isTokenWellFormed(string $token): bool
{
    $parts = explode('.', $token);
    return count($parts) === 3;
}

/**
 * Verify JWT token signature and handle exceptions
 * @param string $token
 * @return object|null
 */
function verifyTokenSignature(string $token): ?object
{
    try {
        return JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    } catch (ExpiredException) {
        sendErrorResponse(403, 'Token expired');
    } catch (Exception) {
        sendErrorResponse(401, 'Invalid token signature');
    }
}

/**
 * Check if session is valid in database
 * @param string $sessionId
 * @return bool
 */
function isSessionValid(string $sessionId): bool
{
    $sessionController = new SessionController();
    return $sessionController->checkSession($sessionId);
}

/**
 * Refresh JWT token
 * @return string New JWT token
 * @throws Exception
 */
function refreshToken(): string
{
    $token = getTokenFromHeader();
    $sessionId = extractSessionIdFromToken($token);
    
    validateTokenForRefresh($token);
    validateSessionForRefresh($sessionId);
    
    $userInfo = getSessionUserInfo($sessionId);
    $userDetails = getUserDetailsFromDatabase($userInfo);
    
    updateSessionActivity($sessionId);
    
    return generateNewJwtToken($sessionId, $userInfo, $userDetails);
}

/**
 * Extract session ID from token payload
 * @param string $token
 * @return string
 * @throws Exception
 */
function extractSessionIdFromToken(string $token): string
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        throw new Exception('Invalid token format');
    }
    
    $payload = json_decode(base64_decode($parts[1]), true);
    if (!$payload || !isset($payload['sid'])) {
        throw new Exception('Invalid token payload');
    }
    
    return $payload['sid'];
}

/**
 * Validate token for refresh (allow expired tokens with valid signature)
 * @param string $token
 * @throws Exception
 */
function validateTokenForRefresh(string $token): void
{
    try {
        JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    } catch (ExpiredException) {
        // Token expired but signature valid - this is OK for refresh
    } catch (Exception) {
        throw new Exception('Invalid token signature');
    }
}

/**
 * Validate session for refresh
 * @param string $sessionId
 * @throws Exception
 */
function validateSessionForRefresh(string $sessionId): void
{
    $sessionController = new SessionController();
    if (!$sessionController->checkSession($sessionId)) {
        throw new Exception('Session expired or not found');
    }
}

/**
 * Get user info from session
 * @param string $sessionId
 * @return array
 * @throws Exception
 */
function getSessionUserInfo(string $sessionId): array
{
    $sessionController = new SessionController();
    $userInfo = $sessionController->getUserInfo($sessionId);
    
    if (!$userInfo) {
        throw new Exception('Session not found');
    }
    
    return $userInfo;
}

/**
 * Get user details from database
 * @param array $userInfo
 * @return array
 * @throws Exception
 */
function getUserDetailsFromDatabase(array $userInfo): array
{
    $userController = new UserController();
    return $userController->getUserDetails(
        $userInfo['sciper'],
        $userInfo['displayName'],
        $userInfo['email'],
        false
    );
}

/**
 * Update session last activity
 * @param string $sessionId
 */
function updateSessionActivity(string $sessionId): void
{
    $sessionController = new SessionController();
    $sessionController->updateLastActivity($sessionId);
}

/**
 * Generate new JWT token
 * @param string $sessionId
 * @param array $userInfo
 * @param array $userDetails
 * @return string
 */
function generateNewJwtToken(string $sessionId, array $userInfo, array $userDetails): string
{
    $jwtData = [
        'sid' => $sessionId,
        'exp' => time() + JWT_LIFETIME,
        'iat' => time(),
        'sciper' => $userInfo['sciper'],
        'name' => $userInfo['displayName'],
        'role' => $userDetails['role'],
        'isadmin' => $userDetails['is_admin'],
    ];
    
    return JWT::encode($jwtData, JWT_SECRET, 'HS256');
}

// ============================================================================
// USER MANAGEMENT FUNCTIONS
// ============================================================================

/**
 * Get the user information from JWT token
 * @param string $token JWT token of the user
 * @param bool $enforceInDatabase If true, the user must be in the database
 * @return array User information
 * @throws Exception
 */
function getUserDetails(string $token, bool $enforceInDatabase = false): array
{
    $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    
    $sessionController = new SessionController();
    $userInfo = $sessionController->getUserInfo($decoded->sid);
    
    $userController = new UserController();
    return $userController->getUserDetails(
        $userInfo['sciper'],
        $userInfo['displayName'],
        $userInfo['email'],
        $enforceInDatabase
    );
}

/**
 * Send the user details as JSON response
 * @param string $token JWT token of the user
 * @return void
 */
function sendUserDetails(string $token): void
{
    try {
        $userDetails = getUserDetails($token);
        sendSuccessResponse($userDetails);
    } catch (Exception $e) {
        sendUnauthorizedResponse($e->getMessage());
    }
}

/**
 * Return user information from the token
 * @param bool $enforceToken
 * @return array|null
 * @throws Exception
 */
function getUserFromToken(bool $enforceToken = true): ?array
{
    if ($enforceToken) {
        $token = getTokenOrDie();
        return getUserDetails($token, true);
    }
    
    try {
        $token = getTokenFromHeader();
        return getUserDetails($token);
    } catch (Exception) {
        return null;
    }
}

// ============================================================================
// SESSION MANAGEMENT FUNCTIONS
// ============================================================================

/**
 * Logout user and cleanup session
 * @return never This function always redirects and exits
 */
function logout(): never
{
    $token = $_GET['token'] ?? '';
    
    if (!empty($token)) {
        cleanupUserSession($token);
    }
    
    $redirectUrl = $_GET['redirect'] ?? '/';
    header('Location: ' . $redirectUrl);
    exit;
}

/**
 * Cleanup user session from database
 * @param string $token
 */
function cleanupUserSession(string $token): void
{
    try {
        $sessionController = new SessionController();
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        $sessionController->deleteSession($decoded->sid);
    } catch (Exception) {
        // Token invalid, but continue with logout
    }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Extract the token from the Authorization header
 * @return string
 * @throws Exception if the token is not present or is malformed
 */
function getTokenFromHeader(): string
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

// ============================================================================
// RESPONSE HELPER FUNCTIONS
// ============================================================================

/**
 * Send a success response with the data
 * @param array $data Data to send
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
 * @param string $message Message to send
 * @return void
 */
function sendUnauthorizedResponse(string $message = 'Unauthorized'): void
{
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message]);
}

/**
 * Send error response with specific code and message
 * @param int $code HTTP status code
 * @param string $message Error message
 * @return never This function always exits
 */
function sendErrorResponse(int $code, string $message): never
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}
