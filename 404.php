<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: 404.php
 */

header('HTTP/1.0 404 Not Found');
echo json_encode(array(
    'error' => '404 Not Found'
));