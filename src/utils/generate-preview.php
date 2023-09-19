<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: generate-preview.php
 */

/**
 * Generate a preview of a question, with a given limit of characters,
 * without cutting LaTeX content or in the middle of a word
 * @param string $content The content of the question
 * @param int $limit The maximum number of characters
 * @return string
 */
function generatePreview(string $content, int $limit = 200): string
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
            $preview .= truncateToLastWord($plainText, $limit - $lengthAccumulated);
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
        $preview .= truncateToLastWord($remainingText, $limit - $lengthAccumulated);
    }

    // Remove any line break and replace it with a space
    $preview = preg_replace('/[\\r\\n]+/', ' ', $preview);

    // Remove last space
    $preview = rtrim($preview);

    // If the preview is shorter than the content, add an ellipsis
    if (strlen($preview) < strlen($content)) {
        $preview .= '...';
    }

    return $preview;
}

/**
 * Truncate the string to the last word before the limit.
 * @param string $text The string to be truncated
 * @param int $limit The maximum number of characters
 * @return string
 */
function truncateToLastWord(string $text, int $limit): string
{
    if (strlen($text) <= $limit) {
        return $text;
    }

    $truncated = substr($text, 0, $limit);
    $lastSpacePos = strrpos($truncated, ' ');

    if ($lastSpacePos === false) {
        return $truncated;
    }

    return substr($truncated, 0, $lastSpacePos);
}
