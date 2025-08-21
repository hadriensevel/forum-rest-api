<?php
/*
 * Copyright (c) 2025. Hadrien Sevel
 * Project: forum-rest-api
 * File: SessionController.php
 */

namespace Controller\Api;

use Model\SessionModel;

class SessionController extends BaseController
{
    /**
     * Register a new session.
     */
    public function registerSession(string $sessionId, int $expiresAt, object $claims): void
    {
        $sessionModel = new SessionModel();
        $sessionModel->registerSession($sessionId, $expiresAt, $claims);
    }

    /**
     * Get the user from the session ID.
     */
    public function getUserInfo(string $sessionId): ?array
    {
        $sessionModel = new SessionModel();
        $user = $sessionModel->getUserFromSessionId($sessionId);

        if (!$user) {
            return null;
        }

        return [
            'sciper' => $user['user_sciper'],
            'displayName' => $user['user_display_name'],
            'email' => $user['user_email']
        ];
    }

    /**
     * Delete a session by its ID.
     */
    public function deleteSession(string $sessionId): void
    {
        $sessionModel = new SessionModel();
        $sessionModel->deleteSession($sessionId);
    }

    /**
     * Check if a session is still valid.
     */
    public function checkSession(string $sessionId): bool
    {
        $sessionModel = new SessionModel();
        return $sessionModel->checkSession($sessionId);
    }

    /**
     * Update last activity of a session.
     */
    public function updateLastActivity(string $sessionId): void
    {
        $sessionModel = new SessionModel();
        $sessionModel->updateLastActivity($sessionId);
    }

    public function cleanupExpiredSessions(): void
    {
        $sessionModel = new SessionModel();
        $sessionModel->cleanupExpiredSessions();
    }
}