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
     * Add a like to a question or an answer.
     * @param int $itemID The ID of the question or the answer to be liked.
     * @param int $sciper The ID of the user liking the question.
     * @param bool $isQuestion Whether the item to be liked is a question or an answer.
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public function addLikeToQuestion(int $itemID, int $sciper, bool $isQuestion = true): void
    {
        $likeModel = new LikeModel($isQuestion);

        if ($likeModel->checkLike($sciper, $itemID)) {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                ['message' => 'User has already liked this question/answer']
            );
            return;
        }

        if ($likeModel->addLike($sciper, $itemID)) {
            $this->sendOutput('HTTP/1.1 200 OK');
        } else {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                ['message' => 'Wrong question/answer ID or user ID']
            );
        }
    }

    /**
     * Delete a like from a question or an answer.
     * @param int $itemID The ID of the question or the answer.
     * @param int $sciper The ID of the user removing the like.
     * @param bool $isQuestion Whether the item to be unliked is a question or an answer.
     * @return void
     * @throws Exception
     */
    public function deleteLikeFromQuestion(int $itemID, int $sciper, bool $isQuestion = true): void
    {
        $likeModel = new LikeModel($isQuestion);

        if ($likeModel->deleteLike($sciper, $itemID)) {
            $this->sendOutput('HTTP/1.1 200 OK');
        } else {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                ['message' => 'Wrong question/answer ID or user ID']
            );
        }
    }
}