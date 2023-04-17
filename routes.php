<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: routes.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Available actions
// - list : get the list of the questions (parameter: limit (optional))
get(API_ROOT_URI . '/question/$action', function ($action) {
    setCorsHeaders();
    $objFeedController = new QuestionController();
    $objFeedController->{$action . 'Action'}();
});

// Create a new question
post(API_ROOT_URI . '/question/new', function () {
    setCorsHeaders();
    // checkAuthentication();
    $objFeedController = new QuestionController();
    $objFeedController->create();
});

// Delete a question (parameter: question id (required))
delete(API_ROOT_URI . '/question/delete/$id', function ($id) {
    setCorsHeaders();
    // checkAuthentication();
    if (!$id) {
        header('Location: /400');
        exit;
    }
    $objFeedController = new QuestionController();
    $objFeedController->delete($id);
});

// Bad request route
any('/400', '400.php');
