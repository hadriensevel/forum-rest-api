<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: UserController.php
 */

namespace Controller\Api;

use Model\UserModel;
use Exception;

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
        $userModel = new UserModel();
        $response = $userModel->getUser($sciper);
        return $response->fetch_assoc()['sciper'] ?? false;
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
        $userModel = new UserModel();
        $userModel->addUser($sciper, $name, $email);
    }

    /**
     * Get the user information
     * @param int $sciper
     * @return array
     * @throws Exception
     */
    public function getUserDetails(int $sciper): array
    {
        $userModel = new UserModel();
        $response = $userModel->getUser($sciper);
        return $response->fetch_assoc();
    }

    /**
     * Check if the user is an admin
     * @param int $sciper
     * @return bool
     * @throws Exception
     */
    public function isUserAdmin(int $sciper): bool
    {
        $userModel = new UserModel();
        $response = $userModel->getUser($sciper);
        return $response->fetch_assoc()['isAdmin'];
    }
}