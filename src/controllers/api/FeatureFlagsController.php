<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: FeatureFlagsController.php
 */

namespace Controller\Api;

use Model\FeatureFlagsModel;
use Exception;

class FeatureFlagsController extends BaseController
{
    /**
     * Get the list of feature flags.
     * @return void
     * @throws Exception
     */
    public function fetchFeatureFlags(): void
    {
        $response = [];

        // Create an instance of the model
        $featureFlagsModel = new FeatureFlagsModel();

        // Fetch feature flags from the model
        $result = $featureFlagsModel->getFeatureFlags();

        // Check if there are feature flags and if yes, transform them into an array
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }

        $this->sendOutput('HTTP/1.1 200 OK', $response);
    }

    /**
     * Get the feature flag with the given name
     * @param string $name The name of the feature flag
     * @return bool The value of the feature flag
     * @throws Exception
     */
    public function getFeatureFlag(string $name): bool
    {
        // Create an instance of the model
        $featureFlagsModel = new FeatureFlagsModel();

        // Fetch feature flags from the model
        $result = $featureFlagsModel->getFeatureFlag($name);

        // Get the value of the feature flag
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['enabled'] === 1;
        }

        return false;
    }
}