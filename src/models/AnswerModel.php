<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: AnswerModel.php
 */

namespace Model;
use Exception;
use mysqli_result;

class AnswerModel extends DatabaseModel
{
    /**
     * MySQL query to get the list of answers of a question
     * @param int $idQuestion
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getQuestionAnswers(int $idQuestion): false|mysqli_result
    {
        $query = "SELECT id, date, body, users.role, id_parent_question as id_question, accepted
        FROM answers 
        LEFT JOIN users
        ON answers.id_user = users.sciper 
        WHERE id_parent_question = ?
        ORDER BY answer_date DESC";
        $params = array($idQuestion);
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * MySQL query to get the list of answers of an answer (not used as no nested answers for now)
     * @param int $idParentAnswer
     * @return false|mysqli_result
     * @throws Exception
     */
    /*public function getChildrenAnswers(int $idParentAnswer): false|mysqli_result
    {
        $query = "SELECT id, answer_date, users.name, id_parent_question, id_parent_answer, answer, accepted_answer, anonymous 
        FROM answers 
        LEFT JOIN users
        ON answers.id_user = users.sciper 
        WHERE id_parent_answer = ?
        ORDER BY answer_date DESC";
        $params = array($idParentAnswer);
        return $this->createAndRunPreparedStatement($query, $params);
    }*/

    /**
     * MySQL query to add an answer
     * @param string $answer
     * @param int $userId
     * @param int $parentQuestionId
     * @return int
     * @throws Exception
     */
    public function addAnswer(string $answer, int $userId, int $parentQuestionId): int
    {
        $query = "INSERT INTO answers (body, id_user, id_parent_question) VALUES (?, ?, ?)";
        $params = array($answer, $userId, $parentQuestionId);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }
}