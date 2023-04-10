<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: TopicController.php
 */

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
        try {
            $topicModel = new TopicModel();
            $response = $topicModel->getTopic($topic, $topicNumber);
            return $response->fetch_assoc()['IDTopic'] ?? null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}