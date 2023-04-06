<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: QuestionModel.php
 */

require_once PROJECT_ROOT_PATH . 'Model/DatabaseModel.php';

class QuestionModel extends DatabaseModel
{

    /**
     * MySQL query to get the list of the questions
     * @param int $limit
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getQuestions(int $limit): false|mysqli_result
    {
        $query = "SELECT QuestionDate, Users.Name, Topics.Category, Topics.Number, Topics.Name AS TopicName, Questions.ID, IDNotesDiv, Question, Title, l.Likes, HasAcceptedAnswer, Anonymous
        FROM Questions
        LEFT JOIN Users
        ON Questions.IDUser = Users.Sciper 
        LEFT JOIN Topics
        ON Questions.IDTopic = Topics.IDTopic
        LEFT JOIN (SELECT IDQuestion, COUNT(*) AS Likes FROM LikesQuestions GROUP BY IDQuestion) l
        ON Questions.ID = l.IDQuestion
        LEFT JOIN Bookmarks
        ON Questions.ID = Bookmarks.IDQuestion
        WHERE Visible = true
        GROUP BY Questions.ID
        LIMIT ?";
        $params = array($limit);
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * MySQL query to add a question
     * @param string $question
     * @param int $user
     * @param int $IDTopic
     * @param string $title
     * @return int
     * @throws Exception
     */
    public function addQuestion(string $question, int $user, int $IDTopic, string $title): int
    {
        $query = "INSERT INTO Questions (Question, IDUser, IDTopic, Title) VALUES (?, ?, ?, NULLIF(?, ''))";
        $params = array($question, $user, $IDTopic, $title);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to delete a question
     * @param int $id
     * @return int
     * @throws Exception
     */
    public function deleteQuestion(int $id): int
    {
        $query = "DELETE FROM Questions WHERE ID = ?";
        $params = array($id);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

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

    /**
     * @param int $sciper
     * @return false|mysqli_result
     * @throws Exception
     */
    public function checkUser(int $sciper): false|mysqli_result
    {
        $query = "SELECT * FROM Users WHERE Sciper = ?";
        $params = array($sciper);
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * @param int $sciper
     * @param string $name
     * @param string $email
     * @return int
     * @throws Exception
     */
    public function addUser(int $sciper, string $name, string $email): int
    {
        $query = "INSERT INTO Users (Sciper, Name, Email) VALUES (?, ?, ?)";
        $params = array($sciper, $name, $email);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }
}