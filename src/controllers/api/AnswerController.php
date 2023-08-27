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
     * @return void
     * @throws Exception
     */
    public function addAnswerToQuestion(): void
    {
        $strErrorDesc = $strErrorHeader = '';

        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === false) {
            $strErrorDesc = 'Content-Type must be multipart/form-data';
            $strErrorHeader = 'HTTP/1.1 400 Bad Request';
        } else {
            // Read the raw POST data
            $postData = $_POST;

            // Check if the required fields are present
            if (isset($postData['answer-body']) &&
                isset($postData['sciper']) &&
                isset($postData['question-id'])) {

                // Escape HTML in the body
                $postData['answer-body'] = htmlspecialchars($postData['answer-body']);

                // Create an instance of the model
                $answerModel = new AnswerModel();

                // Add the answer to the database
                $affectedRows = $answerModel->addAnswer($postData['answer-body'], $postData['sciper'], $postData['question-id']);

                // Check if the answer was added
                if ($affectedRows === 0) {
                    $strErrorDesc = 'Wrong question ID or user ID';
                    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
                }
            } else {
                $strErrorDesc = 'Missing required fields';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            }
        }

        if (!$strErrorDesc) {
            $this->sendOutput(
                'HTTP/1.1 200 OK',
            );
        } else {
            $this->sendOutput(
                $strErrorHeader,
                array('error' => $strErrorDesc)
            );
        }
    }
}