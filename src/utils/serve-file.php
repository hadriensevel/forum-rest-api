<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: serve-file.php
 */

/**
 * Serve a file from the public directory.
 * @param string $filePath
 * @param string $baseDir
 * @return void
 */
function serveFile(string $filePath, string $baseDir = __DIR__ . '/../../public/'): void
{
    // Complete path to the file
    $fullPath = $baseDir . $filePath;

    // File existence check
    if (!file_exists($fullPath)) {
        header('HTTP/1.0 404 Not Found');
        echo "File not found";
        exit;
    }

    // Determine MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fullPath);
    finfo_close($finfo);

    // Set headers and serve the file
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}



