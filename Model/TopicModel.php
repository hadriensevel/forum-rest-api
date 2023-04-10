<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: TopicModel.php
 */

class TopicModel extends DatabaseModel
{
    /**
     * MySQL query to get the ID of a topic
     * @param string $topic
     * @param string $topicNumber
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getTopic(string $topic, string $topicNumber): false|mysqli_result
    {
        $query = "SELECT * FROM Topics WHERE Category = ? AND Number = ?";
        $params = array($topic, $topicNumber);
        return $this->createAndRunPreparedStatement($query, $params);
    }
}