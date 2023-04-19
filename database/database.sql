/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: database.sql
 */

CREATE TABLE users
(
    sciper INT                                  NOT NULL PRIMARY KEY,
    name   VARCHAR(100)                         NOT NULL,
    email  VARCHAR(100)                         NOT NULL,
    role   ENUM ('student', 'teacher', 'admin') NOT NULL DEFAULT 'student'
);

CREATE TABLE topics
(
    id_topic        INT                                  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_parent_topic INT          DEFAULT NULL,
    category        ENUM ('exercise', 'section', 'quiz') NOT NULL,
    name            VARCHAR(100) DEFAULT NULL,
    number          VARCHAR(10)                          NOT NULL,
    FOREIGN KEY (id_parent_topic) REFERENCES topics (id_topic)
);

CREATE TABLE questions
(
    id                  INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    question_date       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    question            TEXT     NOT NULL,
    id_user             INT      NOT NULL,
    id_topic            INT               DEFAULT NULL,
    id_notes_div        VARCHAR(50)       DEFAULT NULL,
    title               VARCHAR(300)      DEFAULT NULL,
    visible             BOOLEAN           DEFAULT false,
    has_accepted_answer BOOLEAN           DEFAULT false,
    anonymous           BOOLEAN           DEFAULT false,
    FOREIGN KEY (id_user) REFERENCES users (sciper),
    FOREIGN KEY (id_topic) REFERENCES topics (id_topic)
);

CREATE TABLE answers
(
    id                 INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    answer_date        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    answer             TEXT     NOT NULL,
    id_user            INT      NOT NULL,
    id_parent_question INT      NOT NULL,
    id_parent_answer   INT               DEFAULT NULL,
    accepted_answer    BOOLEAN           DEFAULT false,
    anonymous          BOOLEAN           DEFAULT false,
    FOREIGN KEY (id_user) REFERENCES users (sciper),
    FOREIGN KEY (id_parent_question) REFERENCES questions (id) ON DELETE CASCADE,
    FOREIGN KEY (id_parent_answer) REFERENCES answers (id) ON DELETE CASCADE
);

CREATE INDEX idx_answers_parent_question ON answers (id_parent_question);
CREATE INDEX idx_answers_parent_answer ON answers (id_parent_answer);

CREATE TABLE likes_questions
(
    id          INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_user     INT      NOT NULL,
    id_question INT      NOT NULL,
    like_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users (sciper),
    FOREIGN KEY (id_question) REFERENCES questions (id) ON DELETE CASCADE
);

CREATE TABLE likes_answers
(
    id        INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_user   INT      NOT NULL,
    id_answer INT      NOT NULL,
    like_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users (sciper),
    FOREIGN KEY (id_answer) REFERENCES answers (id) ON DELETE CASCADE
);

CREATE TABLE bookmarks
(
    id            INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_user       INT      NOT NULL,
    id_question   INT      NOT NULL,
    bookmark_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users (sciper),
    FOREIGN KEY (id_question) REFERENCES questions (id) ON DELETE CASCADE
);