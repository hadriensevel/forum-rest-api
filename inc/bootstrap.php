<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: bootstrap.php
 */

const PROJECT_ROOT_PATH = __DIR__ . '/../';

require_once PROJECT_ROOT_PATH . '/inc/config.php';
require_once PROJECT_ROOT_PATH . '/Controller/Api/BaseController.php';
require_once PROJECT_ROOT_PATH . '/Controller/Api/QuestionController.php';
require_once PROJECT_ROOT_PATH . '/Model/QuestionModel.php';