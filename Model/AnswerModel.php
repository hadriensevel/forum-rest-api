<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: AnswerModel.php
 */

class AnswerModel extends DatabaseModel
{
    /**
     * MySQL query to get the list of answers of a question
     * @param int $idQuestion
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getParentAnswers(int $idQuestion): false|mysqli_result
    {
        $query = "SELECT id, answer_date, users.name, id_parent_question, id_parent_answer, answer, accepted_answer, anonymous 
        FROM answers 
        LEFT JOIN users
        ON answers.id_user = users.sciper 
        WHERE id_parent_question = ?
        AND id_parent_answer IS NULL
        ORDER BY answer_date DESC";
        $params = array($idQuestion);
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * MySQL query to get the list of answers of an answer
     * @param int $idParentAnswer
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getChildrenAnswers(int $idParentAnswer): false|mysqli_result
    {
        $query = "SELECT id, answer_date, users.name, id_parent_question, id_parent_answer, answer, accepted_answer, anonymous 
        FROM answers 
        LEFT JOIN users
        ON answers.id_user = users.sciper 
        WHERE id_parent_answer = ?
        ORDER BY answer_date DESC";
        $params = array($idParentAnswer);
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * MySQL query to add an answer
     * @param string $answer
     * @param int $userID
     * @param int $parentQuestionID
     * @param int $parentAnswerID
     * @param bool $anonymous
     * @return int
     * @throws Exception
     */
    public function addAnswer(string $answer, int $userID, int $parentQuestionID, int $parentAnswerID, bool $anonymous): int
    {
        $query = "INSERT INTO answers (answer, id_user, id_parent_question, id_parent_answer, anonymous) VALUES (?, ?, ?, ?, ?)";
        $params = array($answer, $userID, $parentQuestionID, $parentAnswerID, $anonymous);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }
}