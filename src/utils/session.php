<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: session.php
 */

// Change the parameters of the session cookie
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    //'domain' => DOMAIN,
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);
