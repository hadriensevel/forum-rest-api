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
        $query = "SELECT question_date, users.name, topics.category, topics.number, topics.name AS topic_name, questions.id, id_notes_div, question, title, l.likes, has_accepted_answer, anonymous
        FROM questions
        LEFT JOIN users
        ON questions.id_user = users.sciper 
        LEFT JOIN topics
        ON questions.id_topic = topics.id_topic
        LEFT JOIN (SELECT id_question, COUNT(*) AS likes FROM likes_questions GROUP BY id_question) l
        ON questions.id = l.id_question
        LEFT JOIN bookmarks
        ON questions.id = bookmarks.id_question
        WHERE visible = true
        GROUP BY questions.id
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
        $query = "INSERT INTO questions (question, id_user, id_topic, title, id_notes_div, anonymous) VALUES (?, ?, ?, ?, ?, ?)";
        $params = array($question, $user, $topicID, $title, $divID, $anonymous);
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
        $query = "DELETE FROM questions WHERE id = ?";
        $params = array($id);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }
}