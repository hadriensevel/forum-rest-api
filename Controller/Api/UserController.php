<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: UserController.php
 */

class UserController extends BaseController
{
    /**
     * Check if the user is in the database
     * @param int $sciper
     * @return bool
     * @throws Exception
     */
    public function checkUser(int $sciper): bool
    {
        try {
            $userModel = new UserModel();
            $response = $userModel->checkUser($sciper);
            return $response->fetch_assoc()['IDUser'] ?? false;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Add a user to the database
     * @param int $sciper
     * @param string $name
     * @param string $email
     * @return void
     * @throws Exception
     */
    public function addUser(int $sciper, string $name, string $email): void
    {
        try {
            $userModel = new UserModel();
            $userModel->addUser($sciper, $name, $email);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}