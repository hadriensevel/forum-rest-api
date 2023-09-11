<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: AnswerController.php
 */

namespace Controller\Api;

use Model\AnswerModel;
use Exception;

class AnswerController extends BaseController
{
    /**
     * Get the list of answers for a question by its ID.
     * @param int $questionId The ID of the question.
     * @return void
     * @throws Exception
     */
    public function fetchQuestionAnswers(int $questionId): void
    {
        $response = [];

        // Create an instance of the model
        $answerModel = new AnswerModel();

        // Fetch answers for the given question from the model
        $result = $answerModel->getQuestionAnswers($questionId);

        // Check if there are answers and if yes, transform them into an array
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }

        $this->sendOutput('HTTP/1.1 200 OK', $response);
    }

    /**
     * Add an answer to a question.
     * @param int $sciper
     * @return void
     * @throws Exception
     */
    public function addAnswerToQuestion(int $sciper): void
    {
        if (!isset($_SERVER['CONTENT_TYPE']) || !str_contains($_SERVER['CONTENT_TYPE'], 'multipart/form-data')) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Content-Type must be multipart/form-data']);
            return;
        }

        $postData = $_POST;

        if (!isset($postData['answer-body']) || !isset($postData['question-id'])) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Missing required fields']);
            return;
        }

        // Escape HTML in the body
        $answerBody = htmlspecialchars($postData['answer-body']);
        $questionId = $postData['question-id'];

        $answerModel = new AnswerModel();

        if ($answerModel->addAnswer($answerBody, $sciper, $questionId) === 0) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Wrong question ID or user ID']);
            return;
        }

        $this->sendOutput('HTTP/1.1 200 OK');
    }
}