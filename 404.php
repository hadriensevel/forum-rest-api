<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: 404.php
 */

header('HTTP/1.0 404 Not Found');
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    'error' => '404 Not Found'
));