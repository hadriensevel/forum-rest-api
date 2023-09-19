<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: SectionModel.php
 */

namespace Model;

use Exception;

class SectionModel extends DatabaseModel
{
    /**
     * MySQL query to save the sections in the database.
     * @param string $sectionId The ID of the section.
     * @param string $name The name of the section.
     * @return int The number of affected rows.
     * @throws Exception
     */
    public function addSection(string $sectionId, string $name): int
    {
        $query = "INSERT INTO {{sections}} (id_section, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)";
        $params = array($sectionId, $name);
        return $this->createAndRunPreparedStatement($query, $params, returnAffectedRows: true);
    }
}