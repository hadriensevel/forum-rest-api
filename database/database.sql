/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: database.sql
 */

CREATE TABLE users
(
    sciper              INT                                      NOT NULL PRIMARY KEY,
    email               VARCHAR(100)                             NOT NULL,
    role                ENUM ('student', 'assistant', 'teacher') NOT NULL DEFAULT 'student',
    is_admin            BOOLEAN                                  NOT NULL DEFAULT false,
    email_notifications BOOLEAN                                  NOT NULL DEFAULT true
);

CREATE TABLE questions
(
    id            INT                         NOT NULL AUTO_INCREMENT PRIMARY KEY,
    date          DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    body          TEXT                        NOT NULL,
    image         VARCHAR(100)                         DEFAULT NULL,
    id_user       INT                         NOT NULL,
#     id_topic            INT                   DEFAULT NULL,
    id_page       VARCHAR(100)                NOT NULL,
    id_notes_div  VARCHAR(50)                          DEFAULT NULL,
    location      ENUM ('course', 'exercise') NOT NULL,
    visible       BOOLEAN                              DEFAULT true,
    locked        BOOLEAN                              DEFAULT false,
    resolved      BOOLEAN                              DEFAULT false, -- Denormalized boolean to check if the question is resolved (not in use, check the answers instead)
    html          BOOLEAN                              DEFAULT false,
#    likes_count    INT                   DEFAULT 0,     -- Denormalized count of likes (not implemented yet)
#    answers_count  INT                   DEFAULT 0,     -- Denormalized count of answers (not implemented yet)
    FOREIGN KEY (id_user) REFERENCES users (sciper)
);

-- Index for querying by id_notes_div and id_page
CREATE INDEX idx_questions_notes_div ON questions (id_notes_div);
CREATE INDEX idx_questions_page ON questions (id_page);

# No nested answers for now
CREATE TABLE answers
(
    id                 INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    date               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    body               TEXT     NOT NULL,
    id_user            INT      NOT NULL,
    id_parent_question INT      NOT NULL,
#    id_parent_answer   INT               DEFAULT NULL,
    accepted           BOOLEAN           DEFAULT false,
    FOREIGN KEY (id_user) REFERENCES users (sciper),
    FOREIGN KEY (id_parent_question) REFERENCES questions (id) ON DELETE CASCADE ON UPDATE CASCADE
#    FOREIGN KEY (id_parent_answer) REFERENCES answers (id) ON DELETE CASCADE
);

-- Index for querying by id_parent_question
CREATE INDEX idx_answers_parent_question ON answers (id_parent_question);
#CREATE INDEX idx_answers_parent_answer ON answers (id_parent_answer);

CREATE TABLE likes_questions
(
    id          INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_user     INT      NOT NULL,
    id_question INT      NOT NULL,
    like_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users (sciper),
    FOREIGN KEY (id_question) REFERENCES questions (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Index for querying by id_question to count likes
CREATE INDEX idx_likes_questions ON likes_questions (id_question);

CREATE TABLE likes_answers
(
    id        INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_user   INT      NOT NULL,
    id_answer INT      NOT NULL,
    like_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users (sciper),
    FOREIGN KEY (id_answer) REFERENCES answers (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Index for querying by id_answer to count likes
CREATE INDEX idx_likes_answers ON likes_answers (id_answer);

# No bookmarks for now
# CREATE TABLE bookmarks
# (
#     id            INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
#     id_user       INT      NOT NULL,
#     id_question   INT      NOT NULL,
#     bookmark_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
#     FOREIGN KEY (id_user) REFERENCES users (sciper),
#     FOREIGN KEY (id_question) REFERENCES questions (id) ON DELETE CASCADE
# );

CREATE TABLE feature_flags
(
    name    VARCHAR(100) NOT NULL PRIMARY KEY UNIQUE,
    enabled BOOLEAN      NOT NULL DEFAULT false
);

CREATE TABLE sections
(
    id_section VARCHAR(100) NOT NULL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL
);

# Insert default feature flags
INSERT INTO feature_flags (name, enabled)
VALUES ('authentication', 1);
INSERT INTO feature_flags (name, enabled)
VALUES ('questions', 1);
INSERT INTO feature_flags (name, enabled)
VALUES ('newQuestion', 1);