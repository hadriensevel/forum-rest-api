<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: BookmarkController.php
 */

/* BOOKMARKS ARE NOT IN USE FOR NOW */

//namespace Controller\Api;
//
//use Model\BookmarkModel;
//use Model\QuestionModel;
//
//class BookmarkController extends BaseController
//{
//    public function bookmarkToggle(int $idQuestion): void
//    {
//        $bookmarkModel = new BookmarkModel();
//        $questionModel = new QuestionModel();
//        $question = $questionModel->getQuestion($idQuestion);
//        if ($question->num_rows === 0) {
//            // question not found
//        }
//        $question = $question->fetch_assoc();
//        if ($bookmarkModel->questionBookmarked($this->user->sciper, $idQuestion)) {
//            $bookmarkModel->deleteBookmark($this->user->sciper, $idQuestion);
//            $this->sendSuccess("Question unbookmarked");
//        } else {
//            $bookmarkModel->bookmarkQuestion($this->user->sciper, $idQuestion);
//            $this->sendSuccess("Question bookmarked");
//        }
//    }
//
//}