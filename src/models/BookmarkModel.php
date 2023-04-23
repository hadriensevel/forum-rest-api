<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: BookmarkModel.php
 */

namespace Model;
use Exception;
use mysqli_result;

class BookmarkModel extends DatabaseModel
{
    /**
     * MySQL query to check if a question is bookmarked by a user
     * @param int $userID
     * @param int $questionID
     * @return bool
     * @throws Exception
     */
    public function checkBookmark(int $userID, int $questionID): bool
    {
        $query = "SELECT id FROM bookmarks WHERE id_user = ? AND id_question = ?";
        $params = array($userID, $questionID);
        $result = $this->createAndRunPreparedStatement($query, $params);
        return $result->num_rows > 0;
    }

    /**
     * MySQL query to bookmark a question
     * @param int $userID
     * @param int $questionID
     * @return int
     * @throws Exception
     */
    public function addBookmark(int $userID, int $questionID): int
    {
        $query = "INSERT INTO bookmarks (id_user, id_question) VALUES (?, ?)";
        $params = array($userID, $questionID);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to delete a bookmark
     * @param int $userID
     * @param int $questionID
     * @return int
     * @throws Exception
     */
    public function deleteBookmark(int $userID, int $questionID): int
    {
        $query = "DELETE FROM bookmarks WHERE id_user = ? AND id_question = ?";
        $params = array($userID, $questionID);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to get the list of the bookmarks of a user
     * @param int $userID
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getBookmarks(int $userID): false|mysqli_result
    {
        $query = "SELECT id_question, questions.id_topic FROM bookmarks LEFT JOIN questions ON bookmarks.id_question = questions.id WHERE bookmarks.id_user = ?";
        $params = array($userID);
        return $this->createAndRunPreparedStatement($query, $params);
    }
}