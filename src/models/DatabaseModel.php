<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: DatabaseModel.php
 */

namespace Model;

use Exception;
use Mysqli;
use mysqli_result;
use mysqli_stmt;

class DatabaseModel
{
    private array $replacements = [
        '{{questions}}' => DB_PREFIX . 'questions',
        '{{answers}}' => DB_PREFIX . 'answers',
        '{{likes_questions}}' => DB_PREFIX . 'likes_questions',
        '{{likes_answers}}' => DB_PREFIX . 'likes_answers',
        '{{users}}' => DB_PREFIX . 'users',
        '{{feature_flags}}' => DB_PREFIX . 'feature_flags',
        '{{sections}}' => DB_PREFIX . 'sections',
        '{{bookmarks}}' => DB_PREFIX . 'bookmarks',
    ];

    protected ?mysqli $connection = null;

    /**
     * Constructor for the connection to the database
     * @throws Exception
     */
    public function __construct()
    {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $this->connection->set_charset('utf8');
        } catch (Exception) {
            throw new Exception('Database connection failed');
        }
    }

    /**
     * Prepare and execute a SQL statement
     * @param string $query
     * @param array $params
     * @return mysqli_stmt
     * @throws Exception
     */
    private function executeStatement(string $query, array $params = array()): mysqli_stmt
    {
        $statement = $this->connection->prepare($query);
        if (!$statement) {
            throw new Exception('Database query failed: ' . $this->connection->error);
        }
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
            }
            $bind_names[] = $types;
            for ($i = 0; $i < count($params); $i++) {
                $bind_name = 'bind' . $i;
                $$bind_name = $params[$i];
                $bind_names[] = &$$bind_name;
            }
            call_user_func_array(array($statement, 'bind_param'), $bind_names);
        }
        $statement->execute();
        return $statement;
    }

    /**
     * Prepare a SQL query by replacing the table names with the correct prefix
     * @param string $query
     * @return string
     */
    function prepareQuery(string $query): string
    {
        return str_replace(array_keys($this->replacements), array_values($this->replacements), $query);
    }

    /**
     * Prepare, execute and return the result of a SQL statement
     * @param string $query
     * @param array $params
     * @param bool $returnAffectedRows
     * @param bool $returnId
     * @return false|mysqli_result|int
     * @throws Exception
     */
    public function createAndRunPreparedStatement(string $query, array $params = array(),
                                                  bool   $returnAffectedRows = false, bool $returnId = false): false|mysqli_result|int
    {
        $query = $this->prepareQuery($query);
        $statement = $this->executeStatement($query, $params);
        $result = $statement->get_result();
        if ($returnAffectedRows) {
            $affectedRows = $statement->affected_rows;
            $statement->close();
            return $affectedRows;
        } elseif ($returnId) {
            $id = $this->connection->insert_id;
            $statement->close();
            return $id;
        } else {
            $statement->close();
            return $result;
        }
    }

}