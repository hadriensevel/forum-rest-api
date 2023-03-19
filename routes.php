<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: routes.php
 */

require_once __DIR__ . '/router.php';
require __DIR__ . '/inc/bootstrap.php';

// Available actions
// - list : get the list of the questions (parameter: limit (optional))
get(ROOT_URI . '/question/$action', function($action) {
    $objFeedController = new QuestionController();
    $objFeedController->{$action . 'Action'}();
});

// Delete a question (parameter: question id (required))
get(ROOT_URI . '/question/delete/$id', function($id) {
    if (!$id) {
        header('Location: /400');
        exit;
    }
    $objFeedController = new QuestionController();
    $objFeedController->delete($id);
});

// 404 route
any('/404', '404.php');

// 400 route
get('/400', function() {
    header('HTTP/1.1 400 Bad Request');
    return json_encode(array('error' => 'Invalid request'));
});
