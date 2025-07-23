<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: LikeModel.php
 */

namespace Model;
use Exception;
use mysqli_result;

class LikeModel extends DatabaseModel
{
    private const TABLE_LIKES_QUESTIONS = '{{likes_questions}}';
    private const TABLE_LIKES_ANSWERS = '{{likes_answers}}';
    private const COL_ID_QUESTION = 'id_question';
    private const COL_ID_ANSWER = 'id_answer';
    private string $table;
    private string $id;

    /**
     * LikeModel constructor.
     * @param bool $isQuestion true if the like is for a question, false if it's for an answer
     * @throws Exception
     */
    public function __construct(bool $isQuestion = true)
    {
        parent::__construct();
        $this->table = $isQuestion ? self::TABLE_LIKES_QUESTIONS : self::TABLE_LIKES_ANSWERS;
        $this->id = $isQuestion ? self::COL_ID_QUESTION : self::COL_ID_ANSWER;
    }

    /**
     * MySQL query to check if a question or an answer is liked by a user
     * @param int $userID
     * @param int $itemID
     * @return bool
     * @throws Exception
     */
    public function checkLike(int $userID, int $itemID): bool
    {
        $query = "SELECT id FROM $this->table WHERE id_user = ? AND $this->id = ?";
        $params = array($userID, $itemID);
        $result = $this->createAndRunPreparedStatement($query, $params);
        return $result->num_rows > 0;
    }

    /**
     * MySQL query to like a question or an answer
     * @param int $userID
     * @param int $itemID
     * @return int
     * @throws Exception
     */
    public function addLike(int $userID, int $itemID): int
    {
        $query = "INSERT INTO $this->table (id_user, $this->id) VALUES (?, ?)";
        $params = array($userID, $itemID);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to delete a like
     * @param int $userID
     * @param int $itemID
     * @return int
     * @throws Exception
     */
    public function deleteLike(int $userID, int $itemID): int
    {
        $query = "DELETE FROM $this->table WHERE id_user = ? AND $this->id = ?";
        $params = array($userID, $itemID);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }
}