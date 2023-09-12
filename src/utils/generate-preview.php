<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: generate-preview.php
 */

/**
 * Generate a preview of a question, with a given limit of characters,
 * without cutting LaTeX content
 * @param string $content The content of the question
 * @param int $limit The maximum number of characters
 * @return string
 */
function generatePreview(string $content, int $limit = 100): string
{
    // Match both inline and block LaTeX content
    $pattern = '/\\\\\([\s\S]*?\\\\\)|\\\\\[[\s\S]*?\\\\\]/';

    $offset = 0;
    $preview = '';
    $lengthAccumulated = 0;

    while (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE, $offset)) {
        list($match, $startPos) = $matches[0];

        // Add plain text until the next LaTeX part, considering the limit
        $plainText = substr($content, $offset, $startPos - $offset);
        if ($lengthAccumulated + strlen($plainText) > $limit) {
            $preview .= substr($plainText, 0, $limit - $lengthAccumulated);
            return $preview . '...';
        }
        $preview .= $plainText;
        $lengthAccumulated += strlen($plainText);

        // Add the entire LaTeX part if within limit, otherwise exit
        if ($lengthAccumulated + strlen($match) > $limit) {
            return $preview . '...';
        }
        $preview .= $match;
        $lengthAccumulated += strlen($match);

        $offset = $startPos + strlen($match);
    }

    // If there's any remaining content, add as much as possible considering the limit
    if ($lengthAccumulated < $limit) {
        $remainingText = substr($content, $offset);
        $preview .= substr($remainingText, 0, $limit - $lengthAccumulated);
    }

    // If the preview ends with a whitespace or a line break (\r\n or \n), remove it
    // TODO

    // If the preview is shorter than the content, add an ellipsis
    if (strlen($preview) < strlen($content)) {
        $preview .= '...';
    }

    return $preview;
}

