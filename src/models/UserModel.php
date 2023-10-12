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
     * @return int
     * @throws Exception
     */
    public function addUser(int $sciper, string $email): int
    {
        $query = "INSERT INTO {{users}} (sciper, email) VALUES (?, ?)";
        $params = array($sciper, $email);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
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