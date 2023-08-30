<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: LikeController.php
 */

namespace Controller\Api;

use Model\LikeModel;
use Exception;

class LikeController extends BaseController
{
    /**
     * Add a like to a question
     * @param int $questionId
     * @return void
     * @throws Exception
     */
    public function addLikeToQuestion(int $questionId): void
    {
        $likeModel = new LikeModel();

        // Get the sciper of the user who is logged in
        $userId = getSciper();

        // Check if the user has already liked the question
        if ($likeModel->checkLike($userId, $questionId)) {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                array('message' => 'User has already liked this question')
            );
            return;
        }

        // Add the like
        $affectedRows = $likeModel->addLike($userId, $questionId);

        if ($affectedRows === 0) {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                array('message' => 'Wrong question ID or user ID')
            );
        } else {
            $this->sendOutput(
                'HTTP/1.1 200 OK',
            );
        }
    }

    /**
     * Delete a like from a question
     * @param int $questionId
     * @return void
     * @throws Exception
     */
    public function deleteLikeFromQuestion(int $questionId): void
    {
        $likeModel = new LikeModel();

        // Get the sciper of the user who is logged in
        $userId = getSciper();

        $affectedRows = $likeModel->deleteLike($userId, $questionId);

        if ($affectedRows === 0) {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                array('message' => 'Wrong question ID or user ID')
            );
        } else {
            $this->sendOutput(
                'HTTP/1.1 200 OK',
            );
        }
    }
}