<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: QuestionController.php
 */

namespace Controller\Api;

use Model\QuestionModel;
use Model\AnswerModel;
use Users\UserPermissions;
use Exception;

class QuestionController extends BaseController
{
    /**
     * Get the list of all questions, or for a page, or a page with a div id.
     * @param string|null $pageId The ID of the page (optional).
     * @param string|null $divId The ID of the notes division (optional).
     * @param int|null $userId The ID of the user requesting the questions (optional).
     * @param bool $onlyUsersQuestions Whether to fetch only the questions of the given user (optional).
     * @param bool $onlyBookmarkedQuestions Whether to fetch only the bookmarked questions of the given user (optional).
     * @param int|null $pageNumber The page number (optional).
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public function fetchQuestions(
        string  $sort,
        ?string $pageId = null,
        ?string $divId = null,
        ?int    $userId = null,
        bool    $onlyUsersQuestions = false,
        bool    $onlyBookmarkedQuestions = false,
        ?int    $pageNumber = null): void
    {
        $questionsPerPage = 50;

        $questionModel = new QuestionModel();

        $totalQuestions = $questionModel->countQuestions($pageId, $divId, $userId, $onlyUsersQuestions);
        $totalPages = ceil($totalQuestions / $questionsPerPage);

        $result = $questionModel->getQuestions($pageId, $divId, $userId, $onlyUsersQuestions, $onlyBookmarkedQuestions, $pageNumber, $questionsPerPage, $sort);

        // Fetch all rows directly into an array
        $questions = $result->fetch_all(MYSQLI_ASSOC);

        // Generate the preview for each question and remove the body
        foreach ($questions as &$question) {
            $question['preview'] = generatePreview($question['body'], $question['html']);
            unset($question['body']);
            unset($question['html']);
        }

        $this->sendOutput('HTTP/1.1 200 OK', ['questions' => $questions, 'total_pages' => $totalPages]);
    }


    /**
     * Get the number of questions for a page, or a page with a div id.
     * @param string $pageId The ID of the page.
     * @param string|null $divId The ID of the notes division (optional).
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public
    function getQuestionsCountForPage(string $pageId, ?string $divId = null): void
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
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public
    function getDivQuestionCount(string $pageId): void
    {
        $questionModel = new QuestionModel();

        // Fetch the count of questions for each divId
        $result = $questionModel->getQuestionCountByDivId($pageId);

        // Fetch all rows directly and transform into the desired format
        $data = array_map(function ($row) {
            return [
                'div_id' => $row['id_notes_div'],
                'questions_count' => (int)$row['questions_count']
            ];
        }, $result->fetch_all(MYSQLI_ASSOC));

        $this->sendOutput('HTTP/1.1 200 OK', $data);
    }

    /**
     * Get a question by its ID along with all its associated answers.
     * @param int $questionId The ID of the question.
     * @param int|null $sciper The ID of the user requesting the question (optional).
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public
    function fetchQuestion(int $questionId, ?int $sciper): void
    {
        // Create an instance of the model
        $questionModel = new QuestionModel();

        // Fetch question and its answers from the model
        $response = $questionModel->getQuestionWithAnswers($questionId, $sciper);

        // Escape HTML entities in question
        if (!$response['question']['html']) $response['question']['body'] = htmlspecialchars($response['question']['body']);
        unset($response['question']['html']);

        // Escape HTML entities in answers
        foreach ($response['question']['answers'] as &$answer) {
            // If the role of the user is teacher or user is admin, don't escape HTML entities
            if ($answer['user_role'] !== 'teacher' && $answer['user_is_admin'] !== 1 && $answer['user_role'] !== 'llm') {
                $answer['body'] = htmlspecialchars($answer['body']);
            }
        }

        // Check if there's any data
        if (!$response) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Invalid question ID']);
            return;
        }

        $this->sendOutput('HTTP/1.1 200 OK', $response);
    }

    /**
     * Create a new question.
     * @param int $sciper The ID of the user creating the question.
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public
    function create(int $sciper): void
    {
        // Check if the feature flag for new questions is enabled
        $featureFlagsController = new FeatureFlagsController();
        if (!$featureFlagsController->getFeatureFlag('newQuestion')) {
            throw new Exception('The new question feature is disabled');
        }

        // Check Content-Type
        if (!isset($_SERVER['CONTENT_TYPE']) || !str_contains($_SERVER['CONTENT_TYPE'], 'multipart/form-data')) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Content-Type must be multipart/form-data']);
            return;
        }

        $postData = $_POST;

        $imageName = handleImageUpload();

        // Check required fields
        if (!isset($postData['question-body'], $postData['question-location'], $postData['page'])) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Missing required fields']);
            return;
        }

        if ($postData['div-id'] === 'undefined') {
            $postData['div-id'] = null;
        }

        // Remove .html from the page name if it's there
        $postData['page'] = str_replace('.html', '', $postData['page']);

        $questionModel = new QuestionModel();
        $questionId = $questionModel->addQuestion(
            $postData['question-body'],
            $imageName,
            $sciper,
            $postData['page'],
            $postData['div-id'],
            $postData['question-location'],
        );

        if (!$questionId) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Error while saving the question']);
            return;
        }

        $this->sendOutput('HTTP/1.1 200 OK', exit: false);

        // Execute the LLM processing asynchronously
        if ($postData['page'] !== 'questions_generales_GM' && $postData['page'] !== 'questions_generales_OL') {
            $this->executeLLMProcess($postData['page'], $postData['div-id'] ?? 'nan', $postData['question-body'], $questionId);
        }

        // Send the question to LLM and save response in the database (if the page is not questions_generales_GM or questions_generales_OL)
        /*if ($postData['page'] !== 'questions_generales_GM' && $postData['page'] !== 'questions_generales_OL') {
            $response = sendQuestionToLLM($postData['page'], $postData['div-id'], $postData['question-body']);
            $response = json_decode($response, true);

            // Save the answer in the database if it's not empty
            if (!empty($response['answer'])) {
                $answerModel = new AnswerModel();
                $answerModel->addAnswer($response['answer'], 0, $questionId);
            }
        }*/
    }

    function sendQuestionToLLM(int $questionId): void
    {
        $questionModel = new QuestionModel();
        $question = $questionModel->getQuestion($questionId)->fetch_assoc();
        $this->executeLLMProcess($question['id_page'], $question['id_notes_div'] ?? 'nan', $question['body'], $questionId);
    }

    /**
     * Execute the LLM processing task asynchronously.
     *
     * @param string $page
     * @param string|null $divId
     * @param string $questionBody
     * @param int $questionId
     * @return void
     */
    protected function executeLLMProcess(string $page, ?string $divId, string $questionBody, int $questionId): void
    {
        $command = sprintf(
            'php %s/../../utils/process-LLM.php %s %s %s %d > /dev/null 2>&1 &',
            __DIR__,
            escapeshellarg($page),
            escapeshellarg($divId),
            escapeshellarg($questionBody),
            $questionId
        );

        exec($command);
    }

    /**
     * Edit a question.
     * @param int $id The ID of the question to be edited.
     * @param array $user The details of the user editing the question.
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public
    function edit(int $id, array $user): void
    {
        // Check Content-Type
        if (!isset($_SERVER['CONTENT_TYPE']) || !str_contains($_SERVER['CONTENT_TYPE'], 'multipart/form-data')) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Content-Type must be multipart/form-data']);
            return;
        }

        $postData = $_POST;

        // Check required fields
        if (!isset($postData['question-body'])) {
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

        $affectedRows = $questionModel->editQuestion($id, $postData['question-body']);

        if ($affectedRows === 0) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Error while saving the question']);
            return;
        }

        $this->sendOutput('HTTP/1.1 200 OK');
    }

    /**
     * Delete a question.
     * @param int $id The ID of the question to be deleted.
     * @param array $user The details of the user deleting the question.
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public
    function delete(int $id, array $user): void
    {
        $questionModel = new QuestionModel();
        $userIsAuthor = $this->getAuthor($id) === $user['sciper'];

        // Authorization check
        if (!UserPermissions::canDeleteQuestion($user['role'], $user['is_admin'], $userIsAuthor)) {
            $this->sendOutput('HTTP/1.1 403 Forbidden', ['error' => 'The user is not authorized to delete this question']);
            return;
        }

        // Check if there's an image associated with the question
        $imageName = $questionModel->getQuestionImage($id)->fetch_assoc()['image'];
        if ($imageName) {
            deleteImage($imageName);
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
     * Get the author of a question.
     * @param int $id The ID of the question.
     * @return int The sciper of the author.
     * @throws Exception
     */
    private
    function getAuthor(int $id): int
    {
        $questionModel = new QuestionModel();
        $response = $questionModel->getQuestionAuthor($id);
        return $response->fetch_assoc()['id_user'];
    }

    /**
     * Lock or unlock a question.
     * @param int $id The ID of the question to be locked.
     * @param array $user The details of the user locking the question.
     * @param bool $lock Whether to lock or unlock the question.
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public
    function lockQuestion(int $id, array $user, bool $lock = true): void
    {
        $questionModel = new QuestionModel();

        // Authorization check
        if (!UserPermissions::canLockQuestion($user['role'], $user['is_admin'])) {
            $this->sendOutput('HTTP/1.1 403 Forbidden', ['error' => 'The user is not authorized to lock this question']);
            return;
        }

        if ($questionModel->lockQuestion($id, $lock)) {
            $this->sendOutput('HTTP/1.1 200 OK');
        } else {
            $this->sendOutput(
                'HTTP/1.1 400 Bad Request',
                ['error' => 'Invalid question ID']
            );
        }
    }

    /**
     * Mark a question for LLM training
     * @param int $id The ID of the question to mark
     * @param array $user The user details
     * @return void
     * @throws Exception
     */
    public function markQuestionForLLMTraining(int $id, array $user): void
    {
        // Authorization check
        if (!UserPermissions::canMarkForLLMTraining($user['is_admin'])) {
            $this->sendOutput('HTTP/1.1 403 Forbidden', ['error' => 'The user is not authorized to mark questions for LLM training']);
            return;
        }

        $questionModel = new QuestionModel();
        
        if ($questionModel->markQuestionForLLMTraining($id, true)) {
            $this->sendOutput('HTTP/1.1 200 OK', ['message' => 'Question marked for LLM training']);
        } else {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Invalid question ID or question already marked']);
        }
    }

    /**
     * Unmark a question for LLM training
     * @param int $id The ID of the question to unmark
     * @param array $user The user details
     * @return void
     * @throws Exception
     */
    public function unmarkQuestionForLLMTraining(int $id, array $user): void
    {
        // Authorization check
        if (!UserPermissions::canMarkForLLMTraining($user['is_admin'])) {
            $this->sendOutput('HTTP/1.1 403 Forbidden', ['error' => 'The user is not authorized to unmark questions for LLM training']);
            return;
        }

        $questionModel = new QuestionModel();
        
        if ($questionModel->markQuestionForLLMTraining($id, false)) {
            $this->sendOutput('HTTP/1.1 200 OK', ['message' => 'Question unmarked for LLM training']);
        } else {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Invalid question ID or question already unmarked']);
        }
    }
}
