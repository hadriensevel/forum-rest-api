<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: BookmarkModel.php
 */

namespace Model;
use Exception;

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
        $query = "SELECT id FROM {{bookmarks}} WHERE id_user = ? AND id_question = ?";
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
        $query = "INSERT INTO {{bookmarks}} (id_user, id_question) VALUES (?, ?)";
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
        $query = "DELETE FROM {{bookmarks}} WHERE id_user = ? AND id_question = ?";
        $params = array($userID, $questionID);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }
}