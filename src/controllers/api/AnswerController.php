<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: AnswerController.php
 */

namespace Controller\Api;

use Model\AnswerModel;
use Model\QuestionModel;
use Model\UserModel;
use Users\UserPermissions;
use Mailer\Mailer;
use Exception;

class AnswerController extends BaseController
{
    /**
     * Get the list of answers for a question by its ID.
     * @param int $questionId The ID of the question.
     * @return void The HTTP code and the JSON data to the client.
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
     * @param int $sciper The ID of the user adding the answer.
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public function addAnswerToQuestion(int $sciper): void
    {
        // Check content type
        if (!isset($_SERVER['CONTENT_TYPE']) || !str_contains($_SERVER['CONTENT_TYPE'], 'multipart/form-data')) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Content-Type must be multipart/form-data']);
            return;
        }

        $postData = $_POST;

        if (!isset($postData['answer-body']) || !isset($postData['question-id'])) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Missing required fields']);
            return;
        }

        $answerBody = $postData['answer-body'];
        $questionId = $postData['question-id'];

        $answerModel = new AnswerModel();

        if ($answerModel->addAnswer($answerBody, $sciper, $questionId) === 0) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Wrong question ID or user ID']);
            return;
        }

        // Update the last activity date of the question
        $questionModel = new QuestionModel();
        $questionModel->updateQuestionLastActivity($questionId);

        // Respond success immediately and continue best-effort notifications
        $this->sendOutput('HTTP/1.1 200 OK', exit: false);

        // Send an email notification to the author of the question
        try {
            $questionAuthorId = $questionModel->getQuestionAuthor($questionId)->fetch_assoc()['id_user'];

            $userModel = new UserModel();
            $questionAuthorEmailNotif = $userModel->getUserEmail($questionAuthorId)->fetch_assoc();
            $questionAuthorEmail = $questionAuthorEmailNotif['email'];

            // Check if the user posting the answer is the user who asked the question
            if ($questionAuthorId === $sciper) {
                $questionAuthorEmailNotif['email_notifications'] = false;
            }

            if ($questionAuthorEmailNotif['email_notifications']) {
                $questionSection = $questionModel->getQuestionSection($questionId)->fetch_assoc()['section_name'];

                $mailer = new Mailer();
                $mailer->sendNewAnswerNotification(
                    $questionSection,
                    $questionId,
                    $questionAuthorEmail
                );
            }
        } catch (Exception $e) {
            // Do not crash the request if notifications fail
            error_log('ADD_ANSWER_NOTIFICATION: ' . $e->getMessage());
            // Best-effort: avoid attempting to send another email from within error handling
        }
    }

    /**
     * Edit an answer from a question.
     * @param int $id The ID of the answer.
     * @param array $user The ID of the user editing the answer.
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public function editAnswerFromQuestion(int $id, array $user): void
    {
        if (!isset($_SERVER['CONTENT_TYPE']) || !str_contains($_SERVER['CONTENT_TYPE'], 'multipart/form-data')) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Content-Type must be multipart/form-data']);
            return;
        }

        $postData = $_POST;

        if (!isset($postData['answer-body'])) {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Missing required fields']);
            return;
        }

        $answerBody = $postData['answer-body'];

        $answerModel = new AnswerModel();
        $userIsAuthor = $this->getAuthor($id) === $user['sciper'];

        // Authorization check
        if (!UserPermissions::canEditAnswer($user['role'], $user['is_admin'], $userIsAuthor)) {
            $this->sendOutput('HTTP/1.1 403 Forbidden', ['error' => 'The user is not authorized to edit this answer']);
            return;
        }

        if ($answerModel->editAnswer($id, $answerBody)) {
            $this->sendOutput('HTTP/1.1 200 OK');
        } else {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Invalid answer ID']);
        }
    }

    /**
     * Delete an answer from a question.
     * @param int $id The ID of the answer.
     * @param array $user The ID of the user deleting the answer.
     * @return void
     * @throws Exception
     */
    public function deleteAnswerFromQuestion(int $id, array $user): void
    {
        $answerModel = new AnswerModel();
        $userIsAuthor = $this->getAuthor($id) === $user['sciper'];

        // Authorization check
        if (!UserPermissions::canDeleteAnswer($user['role'], $user['is_admin'], $userIsAuthor)) {
            $this->sendOutput('HTTP/1.1 403 Forbidden', ['error' => 'The user is not authorized to delete this answer']);
            return;
        }

        if ($answerModel->deleteAnswer($id)) {
            $this->sendOutput('HTTP/1.1 200 OK');
        } else {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Invalid answer ID']);
        }
    }

    /**
     * Accept or unaccept an answer.
     * @param int $id The ID of the answer.
     * @param array $user The ID of the user accepting the answer.
     * @param bool $accept Whether to accept or unaccept the answer.
     * @return void The HTTP code and the JSON data to the client.
     * @throws Exception
     */
    public function acceptAnswer(int $id, array $user, bool $accept = true): void
    {
        $answerModel = new AnswerModel();

        // Authorization check
        if (!UserPermissions::canAcceptAnswer($user['role'], $user['is_admin'])) {
            $this->sendOutput('HTTP/1.1 403 Forbidden', ['error' => 'The user is not authorized to accept answers']);
            return;
        }

        if ($answerModel->acceptAnswer($id, $accept)) {
            $this->sendOutput('HTTP/1.1 200 OK');
        } else {
            $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Invalid answer ID or answer already accepted/unaccepted']);
        }
    }

    /**
     * Get the author of an answer.
     * @param int $answerId The ID of the answer.
     * @return int The ID of the author.
     * @throws Exception
     */
    private function getAuthor(int $answerId): int
    {
        $answerModel = new AnswerModel();
        $response = $answerModel->getAnswerAuthor($answerId);
        return $response->fetch_assoc()['id_user'];
    }
}
