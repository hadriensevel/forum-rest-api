/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: database.sql
 */

CREATE TABLE Users
(
    Sciper VARCHAR(50)                          NOT NULL PRIMARY KEY,
    Name   VARCHAR(100)                         NOT NULL,
    Email  VARCHAR(100)                         NOT NULL,
    Role   ENUM ('student', 'teacher', 'admin') NOT NULL DEFAULT 'student'
);

CREATE TABLE Topics
(
    IDTopic       INT                                  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IDParentTopic INT DEFAULT NULL,
    Category      ENUM ('exercise', 'section', 'quiz') NOT NULL,
    Name          VARCHAR(100),
    Number        VARCHAR(10),
    FOREIGN KEY (IDParentTopic) REFERENCES Topics (IDTopic)
);

CREATE TABLE Questions
(
    ID                INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    QuestionDate      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Question          TEXT     NOT NULL,
    IDUser            VARCHAR(50),
    IDTopic           INT,
    IDNotesDiv        VARCHAR(50)       DEFAULT NULL,
    Title             VARCHAR(300),
    Visible           BOOLEAN           DEFAULT false,
    HasAcceptedAnswer BOOLEAN           DEFAULT false,
    Anonymous         BOOLEAN           DEFAULT false,
    FOREIGN KEY (IDUser) REFERENCES Users (Sciper),
    FOREIGN KEY (IDTopic) REFERENCES Topics (IDTopic)
);

CREATE TABLE Answers
(
    ID               INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    AnswerDate       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Answer           TEXT     NOT NULL,
    IDUser           VARCHAR(50),
    IDParentQuestion INT      NOT NULL,
    IDParentAnswer   INT               DEFAULT NULL,
    AcceptedAnswer   BOOLEAN           DEFAULT false,
    Anonymous        BOOLEAN           DEFAULT false,
    FOREIGN KEY (IDUser) REFERENCES Users (Sciper),
    FOREIGN KEY (IDParentQuestion) REFERENCES Questions (ID) ON DELETE CASCADE,
    FOREIGN KEY (IDParentAnswer) REFERENCES Answers (ID) ON DELETE CASCADE
);

CREATE TABLE LikesQuestions
(
    ID         INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IDUser     VARCHAR(50) NOT NULL,
    IDQuestion INT         NOT NULL,
    LikeDate   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IDUser) REFERENCES Users (Sciper),
    FOREIGN KEY (IDQuestion) REFERENCES Questions (ID) ON DELETE CASCADE
);

CREATE TABLE LikesAnswers
(
    ID       INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IDUser   VARCHAR(50) NOT NULL,
    IDAnswer INT         NOT NULL,
    LikeDate DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IDUser) REFERENCES Users (Sciper),
    FOREIGN KEY (IDAnswer) REFERENCES Answers (ID) ON DELETE CASCADE
);

CREATE TABLE Bookmarks
(
    ID           INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IDUser       VARCHAR(50) NOT NULL,
    IDQuestion   INT         NOT NULL,
    BookmarkDate DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IDUser) REFERENCES Users (Sciper),
    FOREIGN KEY (IDQuestion) REFERENCES Questions (ID) ON DELETE CASCADE
);