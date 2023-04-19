<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: 400.php
 */

header('HTTP/1.1 400 Bad Request');
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    'error' => 'Bad request'
));