<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: routes.php
 */

use Controller\Api\QuestionController;
use Controller\Api\AnswerController;
use Controller\Api\LikeController;
use Controller\Api\FeatureFlagsController;

require_once __DIR__ . '/../vendor/autoload.php';

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
get(API_ROOT_URI . '/get-questions/$pageId/$divId', function ($pageId, $divId = null) {
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

// Edit a question
post(API_ROOT_URI . '/question/edit/$id', function ($id) {
    setCorsHeaders();
    checkAuthentication();
    (new QuestionController())->edit($id);
});

// Delete a question (only for admins)
delete(API_ROOT_URI . '/question/delete/$id', function ($id) {
    setCorsHeaders();
    checkAuthentication();
    ensureAdmin();
    (new QuestionController())->delete($id);
});

// Add an answer to a question
post(API_ROOT_URI . '/answer/new', function () {
    setCorsHeaders();
    checkAuthentication();
    (new AnswerController())->addAnswerToQuestion();
});

// Serve the images from the public upload folder
get(API_ROOT_URI . '/image/$filename', function ($filename) {
    setCorsHeaders();
    serveFile('uploads/'. $filename);
});

// Add a like to a question
post(API_ROOT_URI . '/like/add/$questionId', function ($questionId) {
    setCorsHeaders();
    checkAuthentication();
    (new LikeController())->addLikeToQuestion($questionId);
});

// Remove a like from a question
delete(API_ROOT_URI . '/like/remove/$questionId', function ($questionId) {
    setCorsHeaders();
    checkAuthentication();
    (new LikeController())->deleteLikeFromQuestion($questionId);
});

// Feature flags routes
get(API_ROOT_URI . '/feature-flags', function () {
    setCorsHeaders();
    (new FeatureFlagsController())->fetchFeatureFlags();
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
//get(ADMIN_ROOT_URI, function () {
//    setCorsHeaders();
//    checkAuthentication();
//    ensureAdmin();
//});

// PHP info route
get(API_ROOT_URI . '/php-info', function () {
    checkAuthentication();
    ensureAdmin();
    phpinfo();
});

// Bad request route
any('/400', '400.php');
