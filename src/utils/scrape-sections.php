<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: scrape-sections.php
 */

use Model\SectionModel;

/**
 * Scrape the sections of the lecture notes and put them in the database.
 * @param string $url The URL of the lecture notes.
 * @return void
 * @throws Exception
 */
function scrapeSections(string $url): void
{
    // Get the HTML of the lecture notes
    $html = file_get_contents($url);

    // Create a DOM document
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // suppress errors (due to malformed HTML)

    // Load the HTML into the DOM document
    @$dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//li[@class="had-nav-link-secondlevel"]/a');

    // Create an instance of the model
    $sectionModel = new SectionModel();

    // Add the sections to the database
    foreach ($nodes as $node) {
        $name = $node->nodeValue;
        $href = $node->getAttribute('href');
        $parts = explode('/', $href);
        $lastPart = end($parts);
        $sectionId = str_replace('.html', '', $lastPart);

        // Check if the section ID starts with "AN-" followed by a year
        if (preg_match('/AN-(\d{4})/', $sectionId, $matches)) {
            $year = $matches[1]; // Extract the year
            $name = 'Examen ' . $year . ' - ' . $name;
        }

        $sectionModel->addSection($sectionId, $name);
        echo $name . ' ' . $sectionId . '<br>';
    }

    echo '<b>Scraped successfully :)</b>';
}
