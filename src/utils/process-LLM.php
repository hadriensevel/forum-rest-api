<?php
/*
 * Copyright (c) 2024. Hadrien Sevel
 * Project: forum-rest-api
 * File: process-LLM.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Mailer\Mailer;
use Model\AnswerModel;
use Model\QuestionModel;
use Model\UserModel;

if ($argc < 5) {
    exit("Usage: php process-LLM.php <page> <div_id> <question_body> <question_id>\n");
}

$page = $argv[1];
$divId = $argv[2];
$questionBody = $argv[3];
$questionId = (int)$argv[4];

// Send the question to the LLM
$response = sendQuestionToLLM($page, $divId, $questionBody);
$response = json_decode($response, true);

// Create the answer in the database only if a valid response is received
if (!empty($response['answer'])) {
    $answerModel = new AnswerModel();
    $answerModel->addAnswer($response['answer'], 0, $questionId);

    // Send an email notification to the author of the question
    $questionModel = new QuestionModel();
    $questionAuthorId = $questionModel->getQuestionAuthor($questionId)->fetch_assoc()['id_user'];

    $userModel = new UserModel();
    $questionAuthorEmailNotif = $userModel->getUserEmail($questionAuthorId)->fetch_assoc();
    $questionAuthorEmail = $questionAuthorEmailNotif['email'];


    if ($questionAuthorEmailNotif['email_notifications']) {
        $questionSection = $questionModel->getQuestionSection($questionId)->fetch_assoc()['section_name'];

        $mailer = new Mailer();
        $mailer->sendNewAnswerNotification(
            $questionSection,
            $questionId,
            $questionAuthorEmail
        );
    }

}
