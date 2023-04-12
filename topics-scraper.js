/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: topics-scraper.js
 */

/*
 * Function to retrieve SQL statements to populate the topics table.
 * The SQL statements are logged to the console.
 * The script must be run on the index page of the lecture notes.
 */
async function getSQLStatements() {

    // Create a new DOMParser object
    const parser = new DOMParser();

    // Get the base URL for the webpage
    const baseURL = window.location.href.replace(/index.html$/, '');

    // Retrieve the HTML content of the index page of the baseURL
    const response = await fetch(baseURL + 'index.html');
    const indexHtml = await response.text();

    // Parse the HTML content into a document object model (DOM) using the DOMParser object
    const indexDoc = parser.parseFromString(indexHtml, 'text/html');

    // Initialize variables to store SQL statements and skipped sections
    let sectionsSql = '';
    let subsectionsSql = '';
    let quizSql = '';
    let skippedSectionCount = 0;

    // Retrieve sectionNodes from the HTML
    const sectionNodes = indexDoc.querySelectorAll('ul .nav');

    // Iterate through each sectionNode
    for (let i = 0; i < sectionNodes.length; i++) {
        const sectionNode = sectionNodes[i];

        // Check if the section name starts with a capital letter
        if (!sectionNode.querySelector('a').innerText.match(/^[A-Z]/)) {

            // Extract sectionNumber and sectionName from the HTML
            const sectionNumber = i - skippedSectionCount + 1;
            const sectionName = sectionNode
                .querySelector('a')
                .innerText.replace(/^[\d\s\S]*\.\s/, '')
                .replace(/\\/gm, '\\\\')
                .replace(/'/gm, "\\'");

            // Store section information as an SQL statement
            sectionsSql += `(${sectionNumber}, 'Section', '${sectionName}', '${sectionNumber}.'), `;

            // Retrieve subNodes from the HTML
            const subNodes = sectionNode.querySelectorAll('li');

            // Initialize an array to store promises for fetching subUrls
            const subPromises = [];

            // Iterate through each subNode
            for (let j = 0; j < subNodes.length; j++) {
                const subNode = subNodes[j];

                // Extract subNumber and subName from the HTML
                const subNumber =
                    parseInt(String(sectionNumber) + String(j + 1)) + 100;
                const subName = subNode
                    .querySelector('a')
                    .innerText.replace(/^\d*\.\d*\s/, '')
                    .replace(/\\/gm, '\\\\')
                    .replace(/'/gm, "\\'");

                // Store subsection information as an SQL statement
                subsectionsSql += `(${subNumber}, ${sectionNumber}, 'Section', '${subName}', '${sectionNumber}.${j + 1}'), `;

                // Extract the subUrl from the HTML and create a promise for fetching it
                const subUrl = baseURL + subNode.querySelector('a').getAttribute('href');
                subPromises.push(fetch(subUrl));
            }

            // Wait for all subUrls to be fetched
            const subResponses = await Promise.all(subPromises);

            // Iterate through each subResponse
            for (let j = 0; j < subResponses.length; j++) {

                // Retrieve the HTML content of the subResponse and parse it into a DOM document
                const subHtml = await subResponses[j].text();
                const subDoc = parser.parseFromString(subHtml, 'text/html');

                // Retrieve quizNodes from the subDoc
                const quizNodes = subDoc.querySelectorAll('.quiz');

                // Iterate through each quizNode
                for (let k = 1; k <= quizNodes.length; k++) {

                    // Generate a quizNumber based on the sectionNumber, subNumber and quizNumber
                    const quizNumber =
                        parseInt(
                            String(sectionNumber) + String(j + 1) + String(k)
                        ) + 10000;

                    // Store quiz information as an SQL statement
                    quizSql += `(${quizNumber}, 'Quiz', '${sectionNumber}.${j + 1}-${k}'), `;
                }
            }
        } else {
            // Increment skippedSectionCount if the section name starts with a capital letter
            skippedSectionCount++;
        }
    }

    // Create SQL statements for sectionsSql, subsectionsSql, and quizSql
    const sqlSections = `INSERT INTO topics (id_topic, category, name, number)
                         VALUES ${sectionsSql.replace(/,\s$/, ';')}`;
    const sqlSubsections = `INSERT INTO topics (id_topic, id_parent_topic, category, name, number)
                            VALUES ${subsectionsSql.replace(/,\s$/, ';')}`;
    const sqlQuiz = `INSERT INTO topics (id_topic, category, number)
                     VALUES ${quizSql.replace(/,\s$/, ';')}`;

    // Log the SQL statements to the console
    console.log(sqlSections + '\n' + sqlSubsections + '\n' + sqlQuiz);
}

// Invoke the getSQLStatements function
getSQLStatements();
