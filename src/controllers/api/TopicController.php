<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: TopicController.php
 */

namespace Controller\Api;

use Model\TopicModel;
use Exception;

class TopicController extends BaseController
{
    /**
     * Get the ID of a topic
     * @param string $topic type of topic ('section', 'exercise', 'quiz')
     * @param string $topicNumber number of the topic
     * @return int|null
     * @throws Exception
     */
    public function getTopic(string $topic, string $topicNumber): int|null
    {
        $topicModel = new TopicModel();
        $response = $topicModel->getTopic($topic, $topicNumber);
        return $response->fetch_assoc()['IDTopic'] ?? null;
    }
}