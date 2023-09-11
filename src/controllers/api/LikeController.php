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
     * Add a like to a question.
     * @param int $questionId The ID of the question to be liked.
     * @param int $sciper The ID of the user liking the question.
     * @return void
     * @throws Exception
     */
    public function addLikeToQuestion(int $questionId, int $sciper): void
    {
        $likeModel = new LikeModel();

        if ($likeModel->checkLike($sciper, $questionId)) {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                ['message' => 'User has already liked this question']
            );
            return;
        }

        if ($likeModel->addLike($sciper, $questionId)) {
            $this->sendOutput('HTTP/1.1 200 OK');
        } else {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                ['message' => 'Wrong question ID or user ID']
            );
        }
    }

    /**
     * Delete a like from a question.
     * @param int $questionId The ID of the question.
     * @param int $sciper The ID of the user removing the like.
     * @return void
     * @throws Exception
     */
    public function deleteLikeFromQuestion(int $questionId, int $sciper): void
    {
        $likeModel = new LikeModel();

        if ($likeModel->deleteLike($sciper, $questionId)) {
            $this->sendOutput('HTTP/1.1 200 OK');
        } else {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                ['message' => 'Wrong question ID or user ID']
            );
        }
    }
}