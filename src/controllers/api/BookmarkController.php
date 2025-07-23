<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: BookmarkController.php
 */

namespace Controller\Api;

use Model\BookmarkModel;
use Exception;

class BookmarkController extends BaseController
{
    /**
     * Add a bookmark to a question.
     * @param int $questionID
     * @param int $userID
     * @return void
     * @throws Exception
     */
    public function bookmarkQuestion(int $questionID, int $userID): void
    {
        $bookmarkModel = new BookmarkModel();

        if ($bookmarkModel->checkBookmark($userID, $questionID)) {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                ['message' => 'User has already bookmarked this question']
            );
            return;
        }

        if ($bookmarkModel->addBookmark($userID, $questionID)) {
            $this->sendOutput('HTTP/1.1 200 OK');
        } else {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                ['message' => 'Wrong question ID or user ID']
            );
        }
    }

    /**
     * Delete a bookmark from a question.
     * @param int $questionID
     * @param int $userID
     * @return void
     * @throws Exception
     */
    public function deleteBookmark(int $questionID, int $userID): void
    {
        $bookmarkModel = new BookmarkModel();

        if ($bookmarkModel->deleteBookmark($userID, $questionID)) {
            $this->sendOutput('HTTP/1.1 200 OK');
        } else {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                ['message' => 'Wrong question ID or user ID']
            );
        }
    }
}