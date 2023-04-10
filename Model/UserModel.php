<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: UserModel.php
 */

class UserModel extends DatabaseModel
{
    /**
     * @param int $sciper
     * @return false|mysqli_result
     * @throws Exception
     */
    public function checkUser(int $sciper): false|mysqli_result
    {
        $query = "SELECT * FROM Users WHERE Sciper = ?";
        $params = array($sciper);
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * @param int $sciper
     * @param string $name
     * @param string $email
     * @return int
     * @throws Exception
     */
    public function addUser(int $sciper, string $name, string $email): int
    {
        $query = "INSERT INTO Users (Sciper, Name, Email) VALUES (?, ?, ?)";
        $params = array($sciper, $name, $email);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }
}