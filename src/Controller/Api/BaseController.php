<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: BaseController.php
 */

namespace Controller\Api;
class BaseController
{
    /**
     * Return a 404 error if the method doesn't exist
     * @param $name
     * @param $arguments
     * @return void
     */
    public function __call($name, $arguments)
    {
        $this->sendOutput(
            'HTTP/1.1 400 Bad Request',
            array('error' => 'Invalid request')
        );
    }

    /**
     * Return the query string parameters of the URL
     * @return array|null
     */
    protected function getQueryStringParams(): ?array
    {
        if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) {
            parse_str($_SERVER['QUERY_STRING'], $query);
            return $query;
        } else {
            return array();
        }
    }

    /**
     * Return the HTTP code and the JSON data to the client
     * @param array $data
     * @param string $httpResponseCode
     * @return void
     */
    protected function sendOutput(string $httpResponseCode, array $data = array()): void
    {
        header_remove('Set-Cookie');
        header('Content-Type: application/json; charset=utf-8');
        header($httpResponseCode);
        if (!empty($data)) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}