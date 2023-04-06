<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: QuestionController.php
 */

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

                // TODO: Change the data here
                if (isset($postData['title']) && isset($postData['content'])) {
                    $questionModel = new QuestionModel();

                    // Save the new question to the database
                    $newQuestionId = $questionModel->addQuestion($postData['title'], $postData['content']);

                    $responseData = array('id' => $newQuestionId);
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
                'HTTP/1.1 201 Created',
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


    /**
     * Get the ID of a topic
     * @param string $topic type of topic ('section', 'exercice', 'quiz')
     * @param string $topicNumber number of the topic
     * @return int|null
     * @throws Exception
     */
    private function getTopic(string $topic, string $topicNumber): int|null
    {
        try {
            $questionModel = new QuestionModel();
            $response = $questionModel->getTopic($topic, $topicNumber);
            return $response->fetch_assoc()['IDTopic'] ?? null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Check if the user is in the database
     * @param int $sciper
     * @return bool
     * @throws Exception
     */
    private function checkUser(int $sciper): bool
    {
        try {
            $questionModel = new QuestionModel();
            $response = $questionModel->checkUser($sciper);
            return $response->fetch_assoc()['IDUser'] ?? false;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Add a user to the database
     * @param int $sciper
     * @param string $name
     * @param string $email
     * @return void
     * @throws Exception
     */
    private function addUser(int $sciper, string $name, string $email): void
    {
        try {
            $questionModel = new QuestionModel();
            $questionModel->addUser($sciper, $name, $email);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
