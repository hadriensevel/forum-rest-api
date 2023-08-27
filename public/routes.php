<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: routes.php
 */

use Controller\Api\QuestionController;
use Controller\Api\AnswerController;
use Controller\Api\LikeController;

require_once __DIR__ . '/../vendor/autoload.php';

// Available actions
// - list : get the list of questions (parameter: limit (optional))
//get(API_ROOT_URI . '/question/$action', function ($action) {
//    setCorsHeaders();
//    $objFeedController = new QuestionController();
//    $objFeedController->{$action . 'Action'}();
//});

// Get the number of questions in a page
get(API_ROOT_URI . '/get-questions-count/$pageId', function ($pageId) {
    setCorsHeaders();
    (new QuestionController())->getQuestionsCountForPage($pageId);
});

// Get the number of questions in a page with a div id
get(API_ROOT_URI . '/get-questions-count/$pageId/$divId', function ($pageId, $divId = '') {
    setCorsHeaders();
    (new QuestionController())->getQuestionsCountForPage($pageId, $divId);
});

// Get the number of questions in each div of a page
get(API_ROOT_URI . '/get-questions-count-divs/$pageId', function ($pageId) {
    setCorsHeaders();
    (new QuestionController())->getDivQuestionCount($pageId);
});

// Get questions in a page
get(API_ROOT_URI . '/get-questions/$pageId', function ($pageId) {
    setCorsHeaders();
    (new QuestionController())->fetchQuestionsByPage($pageId);
});

// Get questions in a page with a div id
get(API_ROOT_URI . '/get-questions/$pageId/$divId', function ($pageId, $divId = '') {
    setCorsHeaders();
    (new QuestionController())->fetchQuestionsByPage($pageId, $divId);
});

// Get a question by its ID
get(API_ROOT_URI . '/question/get/$id', function ($id) {
    setCorsHeaders();
    (new QuestionController())->fetchQuestion($id);
});

// Create a new question
post(API_ROOT_URI . '/question/new', function () {
    setCorsHeaders();
    //checkAuthentication();
    (new QuestionController())->create();
});

// Delete a question (parameter: question id (required))
delete(API_ROOT_URI . '/question/delete/$id', function ($id) {
    setCorsHeaders();
    checkAuthentication();
    if (!$id) {
        header('Location: /400');
        exit;
    }
    (new QuestionController())->delete($id);
});

// Add an answer to a question
post(API_ROOT_URI . '/answer/new', function () {
    setCorsHeaders();
    //checkAuthentication();
    (new AnswerController())->addAnswerToQuestion();
});

// Serve the images from the public upload folder
get(API_ROOT_URI . '/image/$filename', function ($filename) {
    setCorsHeaders();
    serveFile('uploads/'. $filename);
});

post(API_ROOT_URI . '/like/add/$questionId/$userId', function ($questionId, $userId) {
    setCorsHeaders();
    //checkAuthentication();
    (new LikeController())->addLikeToQuestion($questionId, $userId);
});

delete(API_ROOT_URI . '/like/remove/$questionId/$userId', function ($questionId, $userId) {
    setCorsHeaders();
    //checkAuthentication();
    (new LikeController())->deleteLikeFromQuestion($questionId, $userId);
});

// Authentication routes
get('/auth/login', function () {
    setCorsHeaders();
    $redirectUrl = (isset($_GET['redirect']) && $_GET['redirect']) ? $_GET['redirect'] : '/';
    authenticate('Analyse I S. Friedli');
    header('Location: ' . $redirectUrl);
});

get('/auth/logout', function () {
    setCorsHeaders();
    $redirectUrl = (isset($_GET['redirect']) && $_GET['redirect']) ? $_GET['redirect'] : 'https://localhost';
    logout($redirectUrl);
});

get('/auth/details', function () {
    setCorsHeaders();
    checkAuthentication();
    getUserDetails();
});

// Admin routes
// TODO: add admin routes
get(ADMIN_ROOT_URI, function () {
    setCorsHeaders();
    checkAuthentication();
    ensureAdmin();
    echo 'Hello admin!';
});

// PHP info route
get(API_ROOT_URI . '/php-info', function () {
    checkAuthentication();
    ensureAdmin();
    phpinfo();
});

// Bad request route
any('/400', '400.php');
