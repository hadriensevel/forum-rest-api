<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: QuestionModel.php
 */

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
     * @param int $topicID
     * @param string $title
     * @param int $divID
     * @param bool $anonymous
     * @return int
     * @throws Exception
     */
    public function addQuestion(string $question, int $user, int $topicID, string $title, int $divID, bool $anonymous): int
    {
        $query = "INSERT INTO Questions (Question, IDUser, IDTopic, Title, IDNotesDiv, Anonymous) VALUES (?, ?, ?, ?, ?, ?)";
        $params = array($question, $user, $topicID, $title, $anonymous);
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
}