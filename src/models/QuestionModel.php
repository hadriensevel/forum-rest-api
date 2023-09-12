<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: QuestionModel.php
 */

namespace Model;

use Exception;
use mysqli_result;

class QuestionModel extends DatabaseModel
{
    /**
     * MySQL query to get the number of questions for a page in the lecture notes or exercises
     * or for an id in the lecture notes if specified
     * @param string $pageId The ID of the page.
     * @param string|null $divId The ID of the notes division (optional).
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getQuestionsCount(string $pageId, ?string $divId = ''): false|mysqli_result
    {
        $query = "SELECT COUNT(*) AS questions_count
        FROM {{questions}}
        WHERE visible = true AND id_page = ?";
        $params = array($pageId);
        if ($divId != '') {
            $query .= " AND id_notes_div = ?";
            $params[] = $divId;
        }
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * MySQL query to get the count of questions for each divId associated with the given pageId.
     * @param string $pageId The ID of the page.
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getQuestionCountByDivId(string $pageId): false|mysqli_result
    {
        $query = "
        SELECT 
            id_notes_div,
            COUNT(*) AS questions_count
        FROM {{questions}}
        WHERE id_page = ?
        GROUP BY id_notes_div";

        $params = array($pageId);
        return $this->createAndRunPreparedStatement($query, $params);
    }


    /**
     * MySQL query to get the list of the questions for a page in the lecture notes or exercises
     * or for an id in the lecture notes if specified
     * @param string $pageId The ID of the page.
     * @param string|null $divId The ID of the notes division (optional).
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getQuestionsByPage(string $pageId, ?string $divId): false|mysqli_result
    {
        $query = "
        SELECT 
            {{questions}}.id as id,
            date,
            body,
            IF(COUNT(DISTINCT acceptedAnswers.id) > 0, TRUE, {{questions}}.resolved) AS resolved,
            IFNULL(l.likes, 0) AS likes,
            IFNULL(a.answers, 0) as answers,
            locked
        FROM {{questions}}
        LEFT JOIN (
            SELECT id_question, COUNT(*) AS likes
            FROM {{likes_questions}}
            GROUP BY id_question
        ) l ON {{questions}}.id = l.id_question
        LEFT JOIN (
            SELECT id_parent_question, COUNT(*) AS answers
            FROM {{answers}}
            GROUP BY id_parent_question
        ) a ON {{questions}}.id = a.id_parent_question
        LEFT JOIN (
            SELECT id, id_parent_question
            FROM {{answers}}
            WHERE accepted = true
        ) acceptedAnswers ON {{questions}}.id = acceptedAnswers.id_parent_question
        WHERE visible = true AND id_page = ?";
        $params = array($pageId);
        if ($divId != '') {
            $query .= " AND id_notes_div = ?";
            $params[] = $divId;
        }
        $query .= " GROUP BY {{questions}}.id";

        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * MySQL query to get a question by its ID along with all its associated answers.
     * @param int $questionId The ID of the question.
     * @param int|null $userId The ID of the current user (optional).
     * @return array An associative array containing the question and its answers.
     * @throws Exception
     */
    public function getQuestionWithAnswers(int $questionId, ?int $userId): array
    {
        // Base query to fetch the main question details
        $questionQuery = "
        SELECT 
            {{questions}}.id as id,
            date,
            title,
            body,
            image,
            IFNULL(l.likes, 0) AS likes,
            locked,
            resolved";

        // Add user_liked part if userId is not null
        if ($userId !== null) {
            $questionQuery .= ",
            CASE WHEN {{questions}}.id_user = ? THEN TRUE ELSE FALSE END AS user_is_author,
            CASE WHEN user_likes.id_user IS NOT NULL THEN TRUE ELSE FALSE END AS user_liked";
        }

        $questionQuery .= "
        FROM {{questions}}
        LEFT JOIN (
            SELECT id_question, COUNT(*) AS likes 
            FROM {{likes_questions}} 
            GROUP BY id_question
        ) l ON {{questions}}.id = l.id_question";

        if ($userId !== null) {
            $questionQuery .= "
        LEFT JOIN {{likes_questions}} AS user_likes
        ON {{questions}}.id = user_likes.id_question AND user_likes.id_user = ?";
        }

        $questionQuery .= " WHERE {{questions}}.id = ?";

        if ($userId) $questionParams = array($userId, $userId, $questionId);
        else $questionParams = array($questionId);

        $questionResult = $this->createAndRunPreparedStatement($questionQuery, $questionParams);

        if (!$questionResult || $questionResult->num_rows === 0) {
            return [];
        }

        $question = $questionResult->fetch_assoc();

        // Query to fetch the associated answers
        $answersQuery = "
        SELECT 
            {{answers}}.id,
            date,
            body,
            accepted,
            {{users}}.role as user_role
        FROM {{answers}}
        JOIN {{users}} ON {{answers}}.id_user = {{users}}.sciper
        WHERE id_parent_question = ?";

        $answersParams = array($questionId);
        $answersResult = $this->createAndRunPreparedStatement($answersQuery, $answersParams);

        $answers = [];
        $isResolved = $question['resolved']; // Initially get the resolved status from the question
        while ($row = $answersResult->fetch_assoc()) {
            if ($row['accepted']) {
                $isResolved = true; // If any answer is accepted, mark the question as resolved
            }
            $answers[] = $row;
        }
        $question['resolved'] = $isResolved; // Update the resolved status
        $question['answers'] = $answers;

        return [
            'question' => $question
        ];
    }

    /**
     * MySQL query to add a question
     * @param string|null $title The title of the question.
     * @param string $body The body of the question.
     * @param string|null $image The image of the question.
     * @param int $sciper The sciper of the author.
     * @param string $page The ID of the page.
     * @param string|null $divId The ID of the notes div.
     * @param string $location The location of the question.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function addQuestion(?string $title, string $body, ?string $image, int $sciper, string $page, ?string $divId, string $location): int
    {
        $query = "INSERT INTO {{questions}} (title, body, image, id_user, id_page, id_notes_div, location) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = array($title, $body, $image, $sciper, $page, $divId, $location);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to edit a question
     * @param int $id The ID of the question.
     * @param string|null $title The new title of the question.
     * @param string $body The new body of the question.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function editQuestion(int $id, ?string $title, string $body): int
    {
        $query = "UPDATE {{questions}} SET title = ?, body = ? WHERE id = ?";
        $params = array($title, $body, $id);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to delete a question
     * @param int $id The ID of the question.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function deleteQuestion(int $id): int
    {
        $query = "DELETE FROM {{questions}} WHERE id = ?";
        $params = array($id);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to get the author of a question
     * @param int $id The ID of the question.
     * @return false|mysqli_result The result of the query.
     * @throws Exception
     */
    public function getQuestionAuthor(int $id): false|mysqli_result
    {
        $query = "SELECT id_user FROM {{questions}} WHERE id = ?";
        $params = array($id);
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * MySQL query to lock a question
     * @param int $id The ID of the question to be locked.
     * @param bool $lock Whether to lock or unlock the question.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function lockQuestion(int $id, bool $lock = true): int
    {
        $query = "UPDATE {{questions}} SET locked = $lock WHERE id = ?";
        $params = array($id);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to get the image associated with a question
     * @param int $id The ID of the question.
     * @return false|mysqli_result The result of the query.
     * @throws Exception
     */
    public function getQuestionImage(int $id): false|mysqli_result
    {
        $query = "SELECT image FROM {{questions}} WHERE id = ?";
        $params = array($id);
        return $this->createAndRunPreparedStatement($query, $params);
    }

}