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
     * @param int $sciper
     * @param string $name
     * @param string $email
     * @return int
     * @throws Exception
     */
    public function addUser(int $sciper, string $name, string $email): int
    {
        $query = "INSERT INTO {{users}} (sciper, name, email) VALUES (?, ?, ?)";
        $params = array($sciper, $name, $email);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }
}