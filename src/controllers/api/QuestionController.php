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
     * Get the number of questions for a page, or a page with a div id
     * @param string $pageId The ID of the page.
     * @param string $divId The ID of the notes division (optional).
     * @return void
     * @throws Exception
     */
    public function getQuestionsCountForPage(string $pageId, string $divId = ''): void
    {
        $questionModel = new QuestionModel();

        // Fetch the number of questions from the database
        $result = $questionModel->getQuestionsCount($pageId, $divId);

        $data = $result->fetch_assoc();
        $response = isset($data['questions_count']) ? (int)$data['questions_count'] : 0;

        $this->sendOutput(
            'HTTP/1.1 200 OK',
            array('questions_count' => $response)
        );
    }

    /**
     * Get the count of questions for each divId associated with the given pageId.
     * @param string $pageId The ID of the page.
     * @throws Exception
     */
    public function getDivQuestionCount(string $pageId): void {
        $questionModel = new QuestionModel();

        // Fetch the count of questions for each divId
        $result = $questionModel->getQuestionCountByDivId($pageId);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'div_id' => $row['id_notes_div'],
                'questions_count' => (int) $row['questions_count']
            ];
        }

        $this->sendOutput(
            'HTTP/1.1 200 OK',
            $data
        );
    }

/**
     * Get the list of questions for a page, or a page with a div id
     * @param string $pageId The ID of the page.
     * @param string|null $divId The ID of the notes div (optional).
     * @return void
     * @throws Exception
     */
    public function fetchQuestionsByPage(string $pageId, ?string $divId = null): void
    {
        $questionModel = new QuestionModel();

        $result = $questionModel->getQuestionsByPage($pageId, $divId);

        $questions = [];
        while ($row = $result->fetch_assoc()) {
            $questions[] = $row;
        }

        $this->sendOutput(
            'HTTP/1.1 200 OK',
            array('questions' => $questions)
        );
    }

    /**
     * Get a question by its ID along with all its associated answers.
     * @param int $questionId The ID of the question.
     * @return void
     * @throws Exception
     */
    public function fetchQuestion(int $questionId): void
    {
        $strErrorDesc = $strErrorHeader = '';

        // Create an instance of the model
        $questionModel = new QuestionModel();

        // Get the sciper of the user who is logged in
        $userId = getSciper();

        // Fetch question and its answers from the model
        $response = $questionModel->getQuestionWithAnswers($questionId, $userId);

        // Check if there's any data
        if (!$response) {
            $strErrorDesc = 'Invalid question ID';
            $strErrorHeader = 'HTTP/1.1 400 Bad Request';
        }

        // Send the appropriate response
        if (!$strErrorDesc) {
            $this->sendOutput(
                'HTTP/1.1 200 OK',
                $response
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
     * @throws Exception
     */
    public function create(): void
    {
        $strErrorDesc = $strErrorHeader = '';

        // Get the sciper of the user who is logged in
        $userId = getSciper();

        // Check Content-Type
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === false) {
            $strErrorDesc = 'Content-Type must be multipart/form-data';
            $strErrorHeader = 'HTTP/1.1 400 Bad Request';
        } else {
            // Read the raw POST data
            $postData = $_POST;

            // Get the image if there is one
            $imageName = null;

            // TODO: check image type and size before saving it
            // If there is an image, give it a random name and move it to the uploads folder
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imageName = uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../../public/uploads/' . $imageName);
            }

            // Check if the required fields are present
            if (isset($postData['question-title']) &&
                isset($postData['question-body']) &&
                isset($postData['question-location']) &&
                isset($postData['page']) &&
                isset($userId)) {

                // Escape HTML in the title and body
                $postData['question-title'] = htmlspecialchars($postData['question-title']);
                $postData['question-body'] = htmlspecialchars($postData['question-body']);

                // If div id is "undefined", set it to null
                if ($postData['div-id'] === 'undefined') {
                    $postData['div-id'] = null;
                }

                // Create a new question model
                $questionModel = new QuestionModel();

                // Save the new question to the database
                $affectedRows = $questionModel->addQuestion(
                    $postData['question-title'],
                    $postData['question-body'],
                    $imageName,
                    $userId,
                    $postData['page'],
                    $postData['div-id'],
                    $postData['question-location'],
                );

                // Check if the question has been saved
                if ($affectedRows === 0) {
                    $strErrorDesc = 'Error while saving the question (check the request body)';
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

    /**
     * Edit a question
     * @param int $id
     * @return void
     * @throws Exception
     */
    public function edit(int $id): void
    {
        $strErrorDesc = $strErrorHeader = '';

        // Get the sciper of the user who is logged in
        $userId = getSciper();

        // Check Content-Type
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === false) {
            $strErrorDesc = 'Content-Type must be multipart/form-data';
            $strErrorHeader = 'HTTP/1.1 400 Bad Request';
        } else {
            // Read the raw POST data
            $postData = $_POST;

            // Check if the required fields are present
            if (isset($postData['question-title']) &&
                isset($postData['question-body']) &&
                isset($userId)) {

                // Escape HTML in the title and body
                $postData['question-title'] = htmlspecialchars($postData['question-title']);
                $postData['question-body'] = htmlspecialchars($postData['question-body']);

                // Create a new question model
                $questionModel = new QuestionModel();

                // Save the new question to the database
                $affectedRows = $questionModel->editQuestion(
                    $id,
                    $postData['question-title'],
                    $postData['question-body'],
                );

                // Check if the question has been saved
                if ($affectedRows === 0) {
                    $strErrorDesc = 'Error while saving the question (check the request body)';
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

    /**
     * Delete a question
     * @param int $id
     * @return void
     * @throws Exception
     */
    public function delete(int $id): void
    {
        $strErrorDesc = $strErrorHeader = '';

        $questionModel = new QuestionModel();
        if (!$questionModel->deleteQuestion($id)) {
            $strErrorDesc = 'Invalid question ID';
            $strErrorHeader = 'HTTP/1.1 400 Bad Request';
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
     * Get the author of a question
     * @param int $id
     * @return int
     * @throws Exception
     */
    public function getAuthor(int $id): int
    {
        $questionModel = new QuestionModel();
        $response = $questionModel->getQuestionAuthor($id);
        return $response->fetch_assoc()['id_user'];
    }
}
