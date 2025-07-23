<?php
/*
 * Copyright (c) 2024. Hadrien Sevel
 * Project: forum-rest-api
 * File: send-question-to-LLM.php
 */

function sendQuestionToLLM(string $id_page, string $id_notes_div, string $question)
{
    $url = 'https://botafogo.saitis.net/llm?' . http_build_query([
            'id_page' => $id_page,
            'id_notes_div' => $id_notes_div,
        ]);

    $data = ['question' => $question];
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data),
            'timeout' => 300,
        ],
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === false) {
        throw new Exception('Failed to send question to LLM');
    }

    return $result;
}
