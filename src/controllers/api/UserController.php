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
     * Get the user information.
     * @param int $sciper
     * @param string $name
     * @param string $email
     * @param bool $enforceInDatabase
     * @return array
     * @throws Exception
     */
    public function getUserDetails(int $sciper, string $name, string $email, bool $enforceInDatabase): array
    {
        $userModel = new UserModel();
        $userDetails = $userModel->getUser($sciper)->fetch_assoc();

        $defaultDetails = [
            'sciper' => $sciper,
            'role' => 'student',
            'is_admin' => false,
            'name' => $name
        ];

        // If userDetails is empty and we need to enforce the user's presence in the database
        if (empty($userDetails) && $enforceInDatabase) {
            $this->addUser($sciper, $name, $email);
            return $defaultDetails;
        }

        // If userDetails is empty (but not enforced in database), or the fetched details don't have certain attributes,
        // the values from defaultDetails will be used.
        return array_merge($defaultDetails, $userDetails ?: []);
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
}