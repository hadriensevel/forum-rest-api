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

    /**
     * Get the feature flag with the given name
     * @param string $name The name of the feature flag
     * @return false|mysqli_result
     * @throws Exception
     */
    public function getFeatureFlag(string $name): false|mysqli_result
    {
        $query = "SELECT * FROM {{feature_flags}} WHERE name = ?";
        return $this->createAndRunPreparedStatement($query, [$name]);
    }

    /**
     * Toggle a feature flag (enable/disable)
     * @param string $name The name of the feature flag
     * @param bool $enabled Whether the flag should be enabled
     * @return int Number of affected rows
     * @throws Exception
     */
    public function toggleFeatureFlag(string $name, bool $enabled): int
    {
        $query = "UPDATE {{feature_flags}} SET enabled = ? WHERE name = ?";
        return $this->createAndRunPreparedStatement($query, [$enabled ? 1 : 0, $name], returnAffectedRows: true);
    }

}