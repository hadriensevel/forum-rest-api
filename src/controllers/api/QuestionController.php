<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: QuestionController.php
 */

namespace Controller\Api;

use Model\QuestionModel;
use Users\UserPermissions;
use Exception;

class QuestionController extends BaseController
{
    /**
     * Get the number of questions for a page, or a page with a div id.
     * @param string $pageId The ID of the page.
     * @param string|null $divId The ID of the notes division (optional).
     * @return void
     * @throws Exception
     */
    public function getQuestionsCountForPage(string $pageId, ?string $divId = null): void
    {
        $questionModel = new QuestionModel();

        // Fetch the number of questions from the database
        $data = $questionModel->getQuestionsCount($pageId, $divId)->fetch_assoc();
        $questionsCount = $data['questions_count'] ?? 0;

        $this->sendOutput('HTTP/1.1 200 OK', ['questions_count' => (int)$questionsCount]);
    }

    /**
     * Get the count of questions for each divId associated with the given pageId.
     * @param string $pageId The ID of the page.
     * @throws Exception
     */
    public function getDivQuestionCount(string $pageId): void
    {
        $questionModel = new QuestionModel();

        // Fetch the count of questions for each divId
        $result = $questionModel->getQuestionCountByDivId($pageId);

        // Fetch all rows directly and transform into the desired format
        $data = array_map(function ($row) {
            return [
                'div_id' => $row['id_notes_div'],
                'questions_count' => (int) $row['questions_count']
            ];
        }, $result->fetch_all(MYSQLI_ASSOC));

        $this->sendOutput('HTTP/1.1 200 OK', $data);
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

        // Fetch all rows directly into an array
        $questions = $result->fetch_all(MYSQLI_ASSOC);

        $this->sendOutput('HTTP/1.1 200 OK', ['questions' => $questions]);
    }

    /**
     * Get a question by its ID along with all its associated answers.
     * @param int $questionId The ID of the question.
     * @param int|null $sciper
     * @return void
     * @throws Exception
     */
    public function fetchQuestion(int $questionId, ?int $sciper): void
    {
        // Create an instance of the model
        $questionModel = new QuestionModel();

        // Fetch question and its answers from the model
        $response = $questionModel->getQuestionWithAnswers($questionId, $sciper);

        // Check if there's any data
        if (!$response) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Invalid question ID']);
            return;
        }

        $this->sendOutput('HTTP/1.1 200 OK', $response);
    }

    /**
     * Create a new question
     * @param int $sciper
     * @return void
     * @throws Exception
     */
    public function create(int $sciper): void
    {
        // Check Content-Type
        if (!isset($_SERVER['CONTENT_TYPE']) || !str_contains($_SERVER['CONTENT_TYPE'], 'multipart/form-data')) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Content-Type must be multipart/form-data']);
            return;
        }

        $postData = $_POST;

        $imageName = $this->handleImageUpload();

        // Check required fields
        if (!isset($postData['question-title'], $postData['question-body'], $postData['question-location'], $postData['page'])) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Missing required fields']);
            return;
        }

        $postData['question-title'] = htmlspecialchars($postData['question-title']);
        $postData['question-body'] = htmlspecialchars($postData['question-body']);

        if ($postData['div-id'] === 'undefined') {
            $postData['div-id'] = null;
        }

        $questionModel = new QuestionModel();
        $affectedRows = $questionModel->addQuestion(
            $postData['question-title'],
            $postData['question-body'],
            $imageName,
            $sciper,
            $postData['page'],
            $postData['div-id'],
            $postData['question-location']
        );

        if ($affectedRows === 0) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Error while saving the question']);
            return;
        }

        $this->sendOutput('HTTP/1.1 200 OK');
    }

    /**
     * Handle the image upload and return its name if successful
     * @return string|null
     */
    private function handleImageUpload(): ?string
    {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageName = uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../../public/uploads/' . $imageName);
            return $imageName;
        }
        return null;
    }

    /**
     * Edit a question
     * @param int $id
     * @param array $user
     * @return void
     * @throws Exception
     */
    public function edit(int $id, array $user): void
    {
        // Check Content-Type
        if (!isset($_SERVER['CONTENT_TYPE']) || !str_contains($_SERVER['CONTENT_TYPE'], 'multipart/form-data')) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Content-Type must be multipart/form-data']);
            return;
        }

        $postData = $_POST;
        // Check required fields
        if (!isset($postData['question-title']) || !isset($postData['question-body'])) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Missing required fields']);
            return;
        }

        $questionModel = new QuestionModel();
        $userIsAuthor = $this->getAuthor($id) === $user['sciper'];

        // Authorization check
        if (!UserPermissions::canEditQuestion($user['role'], $user['is_admin'], $userIsAuthor)) {
            $this->sendOutput('HTTP/1.1 403 Forbidden', ['error' => 'The user is not authorized to edit this question']);
            return;
        }

        $postData['question-title'] = htmlspecialchars($postData['question-title']);
        $postData['question-body'] = htmlspecialchars($postData['question-body']);
        $affectedRows = $questionModel->editQuestion($id, $postData['question-title'], $postData['question-body']);

        if ($affectedRows === 0) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Error while saving the question']);
            return;
        }

        $this->sendOutput('HTTP/1.1 200 OK');
    }

    /**
     * Delete a question.
     * @param int $id The ID of the question to be deleted.
     * @param array $user
     * @return void
     * @throws Exception
     */
    public function delete(int $id, array $user): void
    {
        $questionModel = new QuestionModel();
        $userIsAuthor = $this->getAuthor($id) === $user['sciper'];

        // Authorization check
        if (!UserPermissions::canDeleteQuestion($user['role'], $user['is_admin'], $userIsAuthor)) {
            $this->sendOutput('HTTP/1.1 403 Forbidden', ['error' => 'The user is not authorized to delete this question']);
            return;
        }

        if ($questionModel->deleteQuestion($id)) {
            $this->sendOutput('HTTP/1.1 200 OK');
        } else {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                ['error' => 'Invalid question ID']
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
