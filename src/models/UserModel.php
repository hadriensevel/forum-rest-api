<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: UserModel.php
 */

namespace Model;
use Exception;
use mysqli_result;

class UserModel extends DatabaseModel
{
    /**
     * MySQL query to get the role of a user and if they are an admin
     * @param int $sciper
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getUser(int $sciper): false|mysqli_result
    {
        $query = "SELECT sciper, role, is_admin FROM {{users}} WHERE sciper = ?";
        $params = array($sciper);
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * MyQSL query to add a user in the database
     * @param int $sciper
     * @param string $email
     * @param string $role User role (student, assistant, teacher, llm)
     * @return int
     * @throws Exception
     */
    public function addUser(int $sciper, string $email, string $role = 'student'): int
    {
        $query = "INSERT INTO {{users}} (sciper, email, role) VALUES (?, ?, ?)";
        $params = array($sciper, $email, $role);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * Update a user's role in the database
     * @param int $sciper
     * @param string $role New role (student, assistant, teacher, llm)
     * @return int Number of affected rows
     * @throws Exception
     */
    public function updateUserRole(int $sciper, string $role): int
    {
        $query = "UPDATE {{users}} SET role = ? WHERE sciper = ?";
        $params = array($role, $sciper);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * Get all users with assistant role
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getAssistants(): false|mysqli_result
    {
        $query = "SELECT sciper, email, role, endorsed_assistant FROM {{users}} WHERE role = 'assistant' ORDER BY sciper";
        return $this->createAndRunPreparedStatement($query);
    }

    /**
     * Toggle endorsed assistant status for a user
     * @param int $sciper
     * @param bool $endorsed Whether the assistant should be endorsed
     * @return int Number of affected rows
     * @throws Exception
     */
    public function toggleEndorsedAssistant(int $sciper, bool $endorsed): int
    {
        $query = "UPDATE {{users}} SET endorsed_assistant = ? WHERE sciper = ? AND role = 'assistant'";
        $params = array($endorsed ? 1 : 0, $sciper);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * Manually add or update a user as assistant
     * @param int $sciper
     * @param string $email
     * @return int Number of affected rows
     * @throws Exception
     */
    public function addUserAsAssistant(int $sciper, string $email): int
    {
        // First check if user exists
        $existingUser = $this->getUser($sciper);
        
        if ($existingUser && $existingUser->num_rows > 0) {
            // User exists, update their role to assistant and disable email notifications
            $query = "UPDATE {{users}} SET role = 'assistant', email_notifications = 0 WHERE sciper = ?";
            $params = array($sciper);
            return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
        } else {
            // User doesn't exist, create them as assistant with email notifications disabled by default
            $query = "INSERT INTO {{users}} (sciper, email, role, email_notifications) VALUES (?, ?, 'assistant', 0)";
            $params = array($sciper, $email);
            return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
        }
    }

    /**
     * Remove assistant and transfer ownership of their content
     * @param int $sciper
     * @return int Number of affected rows
     * @throws Exception
     */
    public function removeAssistant(int $sciper): int
    {
        // Get user details to determine transfer target
        $userResult = $this->getUser($sciper);
        if (!$userResult || $userResult->num_rows === 0) {
            throw new Exception('User not found');
        }
        
        $user = $userResult->fetch_assoc();
        if ($user['role'] !== 'assistant') {
            throw new Exception('User is not an assistant');
        }
        
        // Determine transfer target based on endorsed status
        $transferSciper = 1; // Default to regular assistant super user
        
        // Check if user is endorsed assistant
        $endorsedQuery = "SELECT endorsed_assistant FROM {{users}} WHERE sciper = ?";
        $endorsedResult = $this->createAndRunPreparedStatement($endorsedQuery, [$sciper]);
        if ($endorsedResult && $endorsedResult->num_rows > 0) {
            $endorsedData = $endorsedResult->fetch_assoc();
            if ($endorsedData['endorsed_assistant']) {
                $transferSciper = 2; // Transfer to endorsed assistant super user
            }
        }
        
        // Transfer ownership of questions
        $transferQuestionsQuery = "UPDATE {{questions}} SET sciper = ? WHERE sciper = ?";
        $this->createAndRunPreparedStatement($transferQuestionsQuery, [$transferSciper, $sciper]);
        
        // Transfer ownership of answers
        $transferAnswersQuery = "UPDATE {{answers}} SET sciper = ? WHERE sciper = ?";
        $this->createAndRunPreparedStatement($transferAnswersQuery, [$transferSciper, $sciper]);
        
        // Remove the assistant from database
        $deleteQuery = "DELETE FROM {{users}} WHERE sciper = ? AND role = 'assistant'";
        return $this->createAndRunPreparedStatement($deleteQuery, [$sciper], returnAffectedRows: true);
    }

    /**
     * MySQL query to get the email address of a user
     * @param int $sciper
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getUserEmail(int $sciper): false|mysqli_result
    {
        $query = "SELECT email, email_notifications FROM {{users}} WHERE sciper = ?";
        $params = array($sciper);
        return $this->createAndRunPreparedStatement($query, $params);
    }
}