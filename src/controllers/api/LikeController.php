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
     * @param int $questionID
     * @param int $userID
     * @return void
     * @throws Exception
     */
    public function addLikeToQuestion(int $questionID, int $userID): void
    {
        $likeModel = new LikeModel();
        $affectedRows = $likeModel->addLike($userID, $questionID);

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
     * @param int $questionID
     * @param int $userID
     * @return void
     * @throws Exception
     */
    public function deleteLikeFromQuestion(int $questionID, int $userID): void
    {
        $likeModel = new LikeModel();
        $affectedRows = $likeModel->deleteLike($userID, $questionID);

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