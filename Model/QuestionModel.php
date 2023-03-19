<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: QuestionModel.php
 */

require_once PROJECT_ROOT_PATH . 'Model/Database.php';

class QuestionModel extends Database
{

    /**
     * MySQL query to get the list of the questions
     * @param int $limit
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getQuestions(int $limit): false|mysqli_result
    {
        $query = "SELECT * FROM Questions ORDER BY QuestionDate DESC LIMIT ?";
        $params = array($limit);
        return $this->createAndRunPreparedStatement($query, $params);
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