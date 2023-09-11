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
    $user = getUserFromToken(false);
    $sciper = $user ? $user['sciper'] : null;
    (new QuestionController())->fetchQuestion($id, $sciper);
});

// Create a new question
post(API_ROOT_URI . '/question/new', function () {
    setCorsHeaders();
    $user = getUserFromToken();
    (new QuestionController())->create($user['sciper']);
});

// Edit a question
post(API_ROOT_URI . '/question/edit/$id', function ($id) {
    setCorsHeaders();
    $user = getUserFromToken();
    (new QuestionController())->edit($id, $user);
});

// Delete a question
delete(API_ROOT_URI . '/question/delete/$id', function ($id) {
    setCorsHeaders();
    $user = getUserFromToken();
    (new QuestionController())->delete($id, $user);
});

// Add an answer to a question
post(API_ROOT_URI . '/answer/new', function () {
    setCorsHeaders();
    $user = getUserFromToken();
    (new AnswerController())->addAnswerToQuestion($user['sciper']);
});

// Serve the images from the public upload folder
get(API_ROOT_URI . '/image/$filename', function ($filename) {
    serveFile('uploads/'. $filename);
});

// Add a like to a question
post(API_ROOT_URI . '/like/add/$questionId', function ($questionId) {
    setCorsHeaders();
    $user = getUserFromToken();
    (new LikeController())->addLikeToQuestion($questionId, $user['sciper']);
});

// Remove a like from a question
delete(API_ROOT_URI . '/like/remove/$questionId', function ($questionId) {
    setCorsHeaders();
    $user = getUserFromToken();
    (new LikeController())->deleteLikeFromQuestion($questionId, $user['sciper']);
});

// Feature flags routes
get(API_ROOT_URI . '/feature-flags', function () {
    setCorsHeaders();
    (new FeatureFlagsController())->fetchFeatureFlags();
});

// Authentication routes
get('/auth/login', function () {
    $redirectUrl = (isset($_GET['redirect']) && $_GET['redirect']) ? $_GET['redirect'] : '/';
    $token = authenticate('Analyse I S. Friedli');
    // Create the redirect URL with the token (depends on if the redirect URL already has a query string)
    if (str_contains($redirectUrl, '?')) {
        $redirectUrl .= '&token=' . $token;
    } else {
        $redirectUrl .= '?token=' . $token;
    }

    header('Location: ' . $redirectUrl);
});

get('/auth/logout', function () {
    $redirectUrl = (isset($_GET['redirect']) && $_GET['redirect']) ? $_GET['redirect'] : '';
    logout($redirectUrl);
});

get('/auth/details', function () {
    setCorsHeaders();
    $token = getTokenOrDie();
    sendUserDetails($token);
});

// Admin routes
// TODO: add admin routes
//get(ADMIN_ROOT_URI, function () {
//    setCorsHeaders();
//    checkAuthentication();
//    ensureAdmin();
//});

// PHP info route
//get(API_ROOT_URI . '/php-info', function () {
//    checkAuthentication();
//    ensureAdmin();
//    phpinfo();
//});

// Respond to preflights
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    setCorsHeaders();
    exit();
}

// Bad request route
any('/400', '400.php');
