<?php
/*
 * Copyright (c) 2025. Hadrien Sevel
 * Project: forum-rest-api
 * File: SessionModel.php
 */

namespace Model;

use Exception;
use mysqli_result;

class SessionModel extends DatabaseModel
{
    /**
     * Register a new session.
     *
     * @param string $sessionId
     * @param int $expiresAt
     * @param object $claims
     * @throws Exception
     */
    public function registerSession(string $sessionId, int $expiresAt, object $claims): void
    {
        $query = "
        INSERT INTO {{sessions}} (
            id, user_sciper, user_display_name, user_email, expires_at
        ) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))";

        $params = array(
            $sessionId,
            $claims->uniqueid,
            $claims->given_name . ' ' . $claims->family_name,
            $claims->mail,
            $expiresAt
        );

        $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * Get user information from session ID.
     *
     * @param string $sessionId
     * @return array|null
     * @throws Exception
     */
    public function getUserFromSessionId(string $sessionId): ?array
    {
        $query = "SELECT user_sciper, user_display_name, user_email FROM {{sessions}} WHERE id = ?";
        $params = array($sessionId);
        $result = $this->createAndRunPreparedStatement($query, $params);
        if ($result->num_rows === 0) {
            return null; // No session found
        }
        return $result->fetch_assoc();
    }

    /**
     * Delete a session by its ID.
     *
     * @param string $sessionId
     * @throws Exception
     */
    public function deleteSession(string $sessionId): void
    {
        $query = "DELETE FROM {{sessions}} WHERE id = ?";
        $params = array($sessionId);
        $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * Check if a session is still valid based on its ID.
     *
     * @param string $sessionId
     * @return bool
     * @throws Exception
     */
    public function checkSession(string $sessionId): bool
    {
        $query = "SELECT id FROM {{sessions}} WHERE id = ? AND expires_at > NOW()";
        $params = array($sessionId);
        $result = $this->createAndRunPreparedStatement($query, $params);
        return $result->num_rows > 0;
    }

    /**
     * Update the last activity column in the session table for a specific session ID.
     *
     * @param string $sessionId
     * @throws Exception
     */
    public function updateLastActivity(string $sessionId): void
    {
        $query = "UPDATE {{sessions}} SET last_activity = NOW() WHERE id = ?";
        $params = array($sessionId);
        $this->createAndRunPreparedStatement($query, $params);
    }
}

/*
 CREATE TABLE dev_sessions (
    id VARCHAR(64) PRIMARY KEY,
    user_sciper int(11) NOT NULL,
    user_display_name VARCHAR(255) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_expires_at (expires_at),
    INDEX idx_user_sciper (user_sciper)
);
 */