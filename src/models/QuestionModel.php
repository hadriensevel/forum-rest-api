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
     * MySQL query to get the list of the questions, all or for a page in the lecture notes or exercises
     * or for an id in the lecture notes if specified.
     * @param string|null $pageId The ID of the page (optional).
     * @param string|null $divId The ID of the notes division (optional).
     * @param int|null $userId The ID of the user requesting the questions (optional).
     * @param bool $onlyUsersQuestions Whether to only get the questions of the user (optional).
     * @param bool $onlyBookmarkedQuestions Whether to only get the bookmarked questions of the user (optional).
     * @param int|null $pageNumber The page number (optional).
     * @param int|null $questionsPerPage The number of questions per page (optional).
     * @param string $sortBy The sorting method (optional).
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getQuestions(
        ?string $pageId = null,
        ?string $divId = null,
        ?int    $userId = null,
        bool    $onlyUsersQuestions = false,
        bool    $onlyBookmarkedQuestions = false,
        ?int    $pageNumber = null,
        ?int    $questionsPerPage = null,
        string  $sortBy = 'date'): false|mysqli_result
    {
        $params = [];

        $query = "
        SELECT 
            {{questions}}.id AS id,
            {{questions}}.date,
            last_activity,
            body,
            IF(COUNT(DISTINCT acceptedAnswers.id) > 0, TRUE, {{questions}}.resolved) AS resolved,
            IFNULL(l.likes, 0) AS likes,
            IFNULL(a.answers, 0) AS answers,
            locked,
            location,
            html,
            {{sections}}.name AS section_name,
            IF(COUNT(DISTINCT llm_answers.id) > 0, TRUE, FALSE) AS has_llm_answer";

        // Add user_is_author part if userId is not null
        if ($userId !== null) {
            $query .= ",
            CASE WHEN {{questions}}.id_user = ? THEN TRUE ELSE FALSE END AS user_is_author";
            $params[] = $userId;
        }

        $query .= "
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
        LEFT JOIN {{sections}}
        ON {{questions}}.id_page = {{sections}}.id_section
        LEFT JOIN (
            SELECT a.id_parent_question, a.date, u.role
            FROM {{answers}} a
            INNER JOIN {{users}} u ON a.id_user = u.sciper
            WHERE a.id IN (
                SELECT MAX(id)
                FROM {{answers}}
                GROUP BY id_parent_question
            )
        ) la ON {{questions}}.id = la.id_parent_question
        LEFT JOIN (
            SELECT DISTINCT a.id, a.id_parent_question
            FROM {{answers}} a
            INNER JOIN {{users}} u ON a.id_user = u.sciper
            WHERE u.role = 'llm'
        ) llm_answers ON {{questions}}.id = llm_answers.id_parent_question
        WHERE visible = true";

        if ($pageId !== null) {
            $query .= " AND id_page = ?";
            $params[] = $pageId;
        }
        if ($divId !== null) {
            $query .= " AND id_notes_div = ?";
            $params[] = $divId;
        }
        if ($userId !== null && $onlyUsersQuestions && !$onlyBookmarkedQuestions) {
            $query .= " AND {{questions}}.id_user = ?";
            $params[] = $userId;
        }
        if ($userId !== null && $onlyBookmarkedQuestions) {
            $query .= " AND {{questions}}.id IN (SELECT id_question FROM {{bookmarks}} WHERE id_user = ?)";
            $params[] = $userId;
        }

        $query .= " GROUP BY {{questions}}.id";

        // Dynamic ORDER BY clause
        $query .= match ($sortBy) {
            'likes' => " ORDER BY likes DESC, {{questions}}.date DESC",
            'resolved' => " ORDER BY resolved DESC, {{questions}}.date DESC",
            'non-resolved' => " ORDER BY resolved ASC, {{questions}}.date DESC",
            'answers' => " ORDER BY answers DESC, {{questions}}.date DESC",
            'no-answer' => " ORDER BY answers ASC, {{questions}}.date DESC",
            //'last-activity' => " ORDER BY last_activity DESC, date DESC",
            'last-activity' => " ORDER BY CASE WHEN la.role = 'student' THEN 0 ELSE 1 END, la.date DESC, {{questions}}.date DESC",
            default => " ORDER BY {{questions}}.date DESC",
        };

        // Add the LIMIT and OFFSET clauses to implement pagination
        if ($pageNumber && $questionsPerPage) {
            $offset = ($pageNumber - 1) * $questionsPerPage;
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $questionsPerPage;
            $params[] = $offset;
        }

        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * MySQL query to count the number of questions, all or for a page in the lecture notes or exercises or for a user.
     * @param string|null $pageId The ID of the page (optional).
     * @param string|null $divId The ID of the notes division (optional).
     * @param int|null $userId The ID of the user requesting the questions (optional).
     * @param bool $onlyUsersQuestions Whether to only get the questions of the user (optional).
     * @return int The number of questions.
     * @throws Exception
     */
    public function countQuestions(?string $pageId = null, ?string $divId = null, ?int $userId = null, bool $onlyUsersQuestions = false): int
    {
        $params = [];
        $query = "SELECT COUNT(*) AS total FROM {{questions}} WHERE visible = true";

        if ($pageId !== null) {
            $query .= " AND id_page = ?";
            $params[] = $pageId;
        }
        if ($divId !== null) {
            $query .= " AND id_notes_div = ?";
            $params[] = $divId;
        }
        if ($userId !== null && $onlyUsersQuestions) {
            $query .= " AND id_user = ?";
            $params[] = $userId;
        }

        $result = $this->createAndRunPreparedStatement($query, $params);

        if ($result) {
            $row = $result->fetch_assoc();
            return intval($row['total']);
        } else {
            throw new Exception("Failed to count questions");
        }
    }

    /**
     * MySQL query to get the number of questions for a page in the lecture notes or exercises
     * or for an id in the lecture notes if specified.
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
            {{questions}}.id AS id,
            date,
            last_activity,
            body,
            image,
            id_page AS page_id,
            id_notes_div AS div_id,
            location,
            IFNULL(l.likes, 0) AS likes,
            locked,
            resolved,
            html,
            llm_training AS marked_for_llm_training,
            {{sections}}.name AS section_name";

        // Add user_is_author, user_liked, and user_bookmarked part if userId is not null
        if ($userId !== null) {
            $questionQuery .= ",
            CASE WHEN {{questions}}.id_user = ? THEN TRUE ELSE FALSE END AS user_is_author,
            CASE WHEN user_likes.id_user IS NOT NULL THEN TRUE ELSE FALSE END AS user_liked,
            CASE WHEN user_bookmarks.id_user IS NOT NULL THEN TRUE ELSE FALSE END AS user_bookmarked";
        }

        $questionQuery .= "
        FROM {{questions}}
        LEFT JOIN (
            SELECT id_question, COUNT(*) AS likes 
            FROM {{likes_questions}} 
            GROUP BY id_question
        ) l ON {{questions}}.id = l.id_question
        LEFT JOIN {{sections}}
        ON {{questions}}.id_page = {{sections}}.id_section";

        if ($userId !== null) {
            $questionQuery .= "
            LEFT JOIN {{likes_questions}} AS user_likes
            ON {{questions}}.id = user_likes.id_question AND user_likes.id_user = ?
            LEFT JOIN {{bookmarks}} AS user_bookmarks
            ON {{questions}}.id = user_bookmarks.id_question AND user_bookmarks.id_user = ?";
        }

        $questionQuery .= " WHERE {{questions}}.id = ?";

        if ($userId) $questionParams = array($userId, $userId, $userId, $questionId);
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
            IFNULL(l.likes, 0) AS likes,
            {{users}}.role as user_role,
            {{users}}.is_admin as user_is_admin,
            {{users}}.endorsed_assistant as endorsed_assistant";

        // Add user_is_author and user_liked if userId is not null
        if ($userId !== null) {
            $answersQuery .= ",
            CASE WHEN {{answers}}.id_user = ? THEN TRUE ELSE FALSE END AS user_is_author,
            CASE WHEN user_likes.id_user IS NOT NULL THEN TRUE ELSE FALSE END AS user_liked";
        }

        // Add check for answer being by OP
        $answersQuery .= ",
        CASE WHEN {{answers}}.id_user = (SELECT id_user FROM {{questions}} WHERE id = ?) THEN TRUE ELSE FALSE END AS is_op";

        $answersQuery .= "
        FROM {{answers}}
        LEFT JOIN (
            SELECT id_answer, COUNT(*) AS likes 
            FROM {{likes_answers}} 
            GROUP BY id_answer
        ) l ON {{answers}}.id = l.id_answer
        JOIN {{users}} ON {{answers}}.id_user = {{users}}.sciper";

        if ($userId !== null) {
            $answersQuery .= "
            LEFT JOIN {{likes_answers}} AS user_likes
            ON {{answers}}.id = user_likes.id_answer AND user_likes.id_user = ?";
        }

        $answersQuery .= " WHERE id_parent_question = ?";

        $answersQuery .= "
        ORDER BY 
            CASE WHEN user_role = 'llm' THEN 1 ELSE 0 END ASC,
            date ASC";

        if ($userId) $answersParams = array($userId, $questionId, $userId, $questionId);
        else $answersParams = array($questionId, $questionId);
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
     * MySQL query to add a question.
     * @param string $body The body of the question.
     * @param string|null $image The image of the question.
     * @param int $sciper The sciper of the author.
     * @param string $page The ID of the page.
     * @param string|null $divId The ID of the notes div.
     * @param string $location The location of the question.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function addQuestion(string $body, ?string $image, int $sciper, string $page, ?string $divId, string $location): int
    {
        $query = "INSERT INTO {{questions}} (body, image, id_user, id_page, id_notes_div, location) VALUES (?, ?, ?, ?, ?, ?)";
        $params = array($body, $image, $sciper, $page, $divId, $location);
        return $this->createAndRunPreparedStatement($query, $params, returnId: true);
    }

    /**
     * MySQL query to edit a question.
     * @param int $id The ID of the question.
     * @param string $body The new body of the question.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function editQuestion(int $id, string $body): int
    {
        $query = "UPDATE {{questions}} SET body = ? WHERE id = ?";
        $params = array($body, $id);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    /**
     * MySQL query to delete a question.
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
     * MySQL query to get the author of a question.
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
     * MySQL query to lock a question.
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
     * MySQL query to get the image associated with a question.
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

    public function getQuestionSection(int $id): false|mysqli_result
    {
        $query = "SELECT {{sections}}.name AS section_name 
        FROM {{questions}}
        LEFT JOIN {{sections}}
        ON {{questions}}.id_page = {{sections}}.id_section
        WHERE id = ?";
        $params = array($id);
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * MySQL query to update the last updated date of a question.
     * @param int $id The ID of the question.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function updateQuestionLastActivity(int $id): int
    {
        $query = "UPDATE {{questions}} SET last_activity = CURRENT_TIMESTAMP WHERE id = ?";
        $params = array($id);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

    public function getQuestion(int $id): false|mysqli_result
    {
        $query = "SELECT body, id_page, id_notes_div FROM {{questions}} WHERE id = ?";
        $params = array($id);
        return $this->createAndRunPreparedStatement($query, $params);
    }

    /**
     * Mark or unmark a question for LLM training
     * @param int $id The ID of the question
     * @param bool $marked Whether to mark (true) or unmark (false) for training
     * @return int Number of affected rows
     * @throws Exception
     */
    public function markQuestionForLLMTraining(int $id, bool $marked): int
    {
        $query = "UPDATE {{questions}} SET llm_training = ? WHERE id = ?";
        $params = array($marked ? 1 : 0, $id);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }

}