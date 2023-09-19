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
     * MySQL query to get the list of answers of a question.
     * @param int $idQuestion The ID of the question.
     * @return false|mysqli_result The result of the query.
     * @throws Exception
     */
    public function getQuestionAnswers(int $idQuestion): false|mysqli_result
    {
        $query = "SELECT id, date, body, {{users}}.role, id_parent_question as id_question, accepted
        FROM {{answers}} 
        LEFT JOIN {{users}}
        ON {{answers}}.id_user = {{users}}.sciper 
        WHERE id_parent_question = ?
        ORDER BY answer_date DESC";
        $params = array($idQuestion);
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * MySQL query to get the list of answers of an answer (not used as no nested answers for now).
     * @param int $idParentAnswer
     * @return false|mysqli_result
     * @throws Exception
     */
    /*public function getChildrenAnswers(int $idParentAnswer): false|mysqli_result
    {
        $query = "SELECT id, answer_date, users.name, id_parent_question, id_parent_answer, answer, accepted_answer, anonymous 
        FROM {{answers}}
        LEFT JOIN {{users}}
        ON {{answers}}.id_user = {{users}}.sciper
        WHERE id_parent_answer = ?
        ORDER BY answer_date DESC";
        $params = array($idParentAnswer);
        return $this->createAndRunPreparedStatement($query, $params);
    }*/

    /**
     * MySQL query to add an answer
     * @param string $answer The answer to add.
     * @param int $userId The ID of the user adding the answer.
     * @param int $parentQuestionId The ID of the question the answer is for.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function addAnswer(string $answer, int $userId, int $parentQuestionId): int
    {
        $query = "INSERT INTO {{answers}} (body, id_user, id_parent_question) VALUES (?, ?, ?)";
        $params = array($answer, $userId, $parentQuestionId);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to edit an answer.
     * @param int $answerId The ID of the answer.
     * @param string $answer The new answer.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function editAnswer(int $answerId, string $answer): int
    {
        $query = "UPDATE {{answers}} SET body = ? WHERE id = ?";
        $params = array($answer, $answerId);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to accept or unaccept an answer
     * @param int $answerId The ID of the answer.
     * @param bool $accept Whether to accept or unaccept the answer.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function acceptAnswer(int $answerId, bool $accept): int
    {
        $accept = $accept ? 1 : 0;
        $query = "UPDATE {{answers}} SET accepted = ? WHERE id = ?";
        $params = array($accept, $answerId);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to delete an answer.
     * @param int $answerId The ID of the answer.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function deleteAnswer(int $answerId): int
    {
        $query = "DELETE FROM {{answers}} WHERE id = ?";
        $params = array($answerId);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to get the author of an answer.
     * @param int $answerId The ID of the answer.
     * @return false|mysqli_result The result of the query.
     * @throws Exception
     */
    public function getAnswerAuthor(int $answerId): false|mysqli_result
    {
        $query = "SELECT id_user FROM {{answers}} WHERE id = ?";
        $params = array($answerId);
        return $this->createAndRunPreparedStatement($query, $params);
    }
}