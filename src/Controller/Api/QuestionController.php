<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: QuestionController.php
 */

namespace Controller\Api;

use Model\QuestionModel;
use Exception;

class QuestionController extends BaseController
{
    /**
     * Get the list of the questions
     * @return void
     */
    public function listAction(): void
    {
        $strErrorDesc = $strErrorHeader = $responseData = '';
        $arrQueryStringParams = $this->getQueryStringParams();

        try {
            $questionModel = new QuestionModel();

            $intLimit = 10;
            if (isset($arrQueryStringParams['limit']) && $arrQueryStringParams['limit']) {
                $intLimit = $arrQueryStringParams['limit'];
            }

            $arrQuestion = $questionModel->getQuestions($intLimit);
            $responseData = $arrQuestion->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            $strErrorDesc = $e->getMessage();
            $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
        }

        if (!$strErrorDesc) {
            $this->sendOutput(
                'HTTP/1.1 200 OK',
                $responseData
            );
        } else {
            $this->sendOutput(
                $strErrorHeader,
                array('error' => $strErrorDesc)
            );
        }
    }

    /**
     * Create a new question
     * The request body must be a JSON object with the following fields:
     * - topic: the topic of the question (string: 'section', 'exercise', 'quiz' or empty string for general questions)
     * - topicNumber: the topic number (string, e.g. '1-2')
     * - question: the question (string)
     * - sciper: the sciper number of the user who asked (int)
     * - title: the title of the question (string or empty string)
     * - divID: the ID of the div where the question is located (int or null)
     * - anonymous: true if the question is anonymous, false otherwise (bool)
     * @return void
     */
    public function create(): void
    {
        $strErrorDesc = $strErrorHeader = $responseData = '';

        try {
            // Check Content-Type
            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            if ($contentType !== 'application/json') {
                $strErrorDesc = 'Content-Type must be application/json';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            } else {
                // Read the raw POST data
                $rawData = file_get_contents('php://input');

                // Decode the JSON data into a PHP object or array
                $postData = json_decode($rawData, true);

                // Check if the required fields are present
                if (isset($postData['topic']) && isset($postData['topicNumber']) && isset($postData['question']) && isset($postData['sciper'])) {
                    $topicController = new TopicController();
                    $topicID = $topicController->getTopic($postData['topic'], $postData['topicNumber']);

                    // Check if the topic exists
                    if ($topicID) {
                        $questionModel = new QuestionModel();

                        // Save the new question to the database
                        $newQuestionId = $questionModel->addQuestion($postData['question'],
                            $postData['sciper'], $topicID, $postData['title'], $postData['divID'], $postData['anonymous']);

                        // Check if the question has been saved
                        if (!$newQuestionId) {
                            throw new Exception('Error while saving the question');
                        }

                    } else {
                        $strErrorDesc = 'Invalid topic';
                        $strErrorHeader = 'HTTP/1.1 400 Bad Request';
                    }

                } else {
                    $strErrorDesc = 'Missing required fields';
                    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
                }
            }
        } catch (Exception $e) {
            $strErrorDesc = $e->getMessage();
            $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
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


    /**
     * Delete a question
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $strErrorDesc = $strErrorHeader = '';
        try {
            $questionModel = new QuestionModel();
            if (!$questionModel->deleteQuestion($id)) {
                $strErrorDesc = 'Invalid question ID';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            }
        } catch (Exception $e) {
            $strErrorDesc = $e->getMessage();
            $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
        }

        if (!$strErrorDesc) {
            $this->sendOutput(
                'HTTP/1.1 200 OK'
            );
        } else {
            $this->sendOutput(
                $strErrorHeader,
                array('error' => $strErrorDesc)
            );
        }
    }
}
