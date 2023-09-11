<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: FeatureFlagsModel.php
 */

namespace Model;
use Exception;
use mysqli_result;

class FeatureFlagsModel extends DatabaseModel
{
    /**
     * Get the feature flags
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getFeatureFlags(): false|mysqli_result
    {
        $query = "SELECT * FROM {{feature_flags}}";
        return $this->createAndRunPreparedStatement($query);
    }

}