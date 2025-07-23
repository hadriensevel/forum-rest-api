/*
 * Copyright (c) 2025. Hadrien Sevel
 * Project: forum-rest-api
 * File: database.sql
 */

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `h187m8_botafogo`
--

-- --------------------------------------------------------

--
-- Table structure for table `dev_answers`
--

CREATE TABLE `dev_answers` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `body` mediumtext NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_parent_question` int(11) NOT NULL,
  `accepted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dev_bookmarks`
--

CREATE TABLE `dev_bookmarks` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_question` int(11) NOT NULL,
  `bookmark_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dev_feature_flags`
--

CREATE TABLE `dev_feature_flags` (
  `name` varchar(100) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dev_likes_answers`
--

CREATE TABLE `dev_likes_answers` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_answer` int(11) NOT NULL,
  `like_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dev_likes_questions`
--

CREATE TABLE `dev_likes_questions` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_question` int(11) NOT NULL,
  `like_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dev_questions`
--

CREATE TABLE `dev_questions` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `last_activity` datetime NOT NULL DEFAULT current_timestamp(),
  `body` mediumtext NOT NULL,
  `image` varchar(100) DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  `id_page` varchar(100) NOT NULL,
  `id_notes_div` varchar(50) DEFAULT NULL,
  `location` enum('course','exercise') NOT NULL,
  `visible` tinyint(1) DEFAULT 1,
  `locked` tinyint(1) DEFAULT 0,
  `resolved` tinyint(1) DEFAULT 0,
  `html` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dev_sections`
--

CREATE TABLE `dev_sections` (
  `id_section` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dev_users`
--

CREATE TABLE `dev_users` (
  `sciper` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('student','assistant','teacher','llm') NOT NULL DEFAULT 'student',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `endorsed_assistant` tinyint(1) NOT NULL DEFAULT 0,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prod_answers`
--

CREATE TABLE `prod_answers` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `body` mediumtext NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_parent_question` int(11) NOT NULL,
  `accepted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prod_bookmarks`
--

CREATE TABLE `prod_bookmarks` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_question` int(11) NOT NULL,
  `bookmark_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prod_feature_flags`
--

CREATE TABLE `prod_feature_flags` (
  `name` varchar(100) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prod_likes_answers`
--

CREATE TABLE `prod_likes_answers` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_answer` int(11) NOT NULL,
  `like_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prod_likes_questions`
--

CREATE TABLE `prod_likes_questions` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_question` int(11) NOT NULL,
  `like_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prod_questions`
--

CREATE TABLE `prod_questions` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `last_activity` datetime NOT NULL DEFAULT current_timestamp(),
  `body` mediumtext NOT NULL,
  `image` varchar(100) DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  `id_page` varchar(100) NOT NULL,
  `id_notes_div` varchar(50) DEFAULT NULL,
  `location` enum('course','exercise') NOT NULL,
  `visible` tinyint(1) DEFAULT 1,
  `locked` tinyint(1) DEFAULT 0,
  `resolved` tinyint(1) DEFAULT 0,
  `html` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prod_sections`
--

CREATE TABLE `prod_sections` (
  `id_section` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prod_users`
--

CREATE TABLE `prod_users` (
  `sciper` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('student','assistant','teacher','llm') NOT NULL DEFAULT 'student',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `endorsed_assistant` tinyint(1) NOT NULL DEFAULT 0,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dev_answers`
--
ALTER TABLE `dev_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_answers_parent_question` (`id_parent_question`);

--
-- Indexes for table `dev_bookmarks`
--
ALTER TABLE `dev_bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_question` (`id_question`),
  ADD KEY `idx_bookmarks_user` (`id_user`);

--
-- Indexes for table `dev_feature_flags`
--
ALTER TABLE `dev_feature_flags`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `dev_likes_answers`
--
ALTER TABLE `dev_likes_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_likes_answers` (`id_answer`);

--
-- Indexes for table `dev_likes_questions`
--
ALTER TABLE `dev_likes_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_likes_questions` (`id_question`);

--
-- Indexes for table `dev_questions`
--
ALTER TABLE `dev_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_questions_notes_div` (`id_notes_div`),
  ADD KEY `idx_questions_page` (`id_page`);

--
-- Indexes for table `dev_sections`
--
ALTER TABLE `dev_sections`
  ADD PRIMARY KEY (`id_section`);

--
-- Indexes for table `dev_users`
--
ALTER TABLE `dev_users`
  ADD PRIMARY KEY (`sciper`);

--
-- Indexes for table `prod_answers`
--
ALTER TABLE `prod_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_answers_parent_question` (`id_parent_question`);

--
-- Indexes for table `prod_bookmarks`
--
ALTER TABLE `prod_bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_question` (`id_question`),
  ADD KEY `idx_bookmarks_user` (`id_user`);

--
-- Indexes for table `prod_feature_flags`
--
ALTER TABLE `prod_feature_flags`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `prod_likes_answers`
--
ALTER TABLE `prod_likes_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_likes_answers` (`id_answer`);

--
-- Indexes for table `prod_likes_questions`
--
ALTER TABLE `prod_likes_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_likes_questions` (`id_question`);

--
-- Indexes for table `prod_questions`
--
ALTER TABLE `prod_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_questions_notes_div` (`id_notes_div`),
  ADD KEY `idx_questions_page` (`id_page`);

--
-- Indexes for table `prod_sections`
--
ALTER TABLE `prod_sections`
  ADD PRIMARY KEY (`id_section`);

--
-- Indexes for table `prod_users`
--
ALTER TABLE `prod_users`
  ADD PRIMARY KEY (`sciper`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dev_answers`
--
ALTER TABLE `dev_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dev_bookmarks`
--
ALTER TABLE `dev_bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dev_likes_answers`
--
ALTER TABLE `dev_likes_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dev_likes_questions`
--
ALTER TABLE `dev_likes_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dev_questions`
--
ALTER TABLE `dev_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prod_answers`
--
ALTER TABLE `prod_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prod_bookmarks`
--
ALTER TABLE `prod_bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prod_likes_answers`
--
ALTER TABLE `prod_likes_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prod_likes_questions`
--
ALTER TABLE `prod_likes_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prod_questions`
--
ALTER TABLE `prod_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dev_answers`
--
ALTER TABLE `dev_answers`
  ADD CONSTRAINT `dev_answers_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `dev_users` (`sciper`),
  ADD CONSTRAINT `dev_answers_ibfk_2` FOREIGN KEY (`id_parent_question`) REFERENCES `dev_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `dev_bookmarks`
--
ALTER TABLE `dev_bookmarks`
  ADD CONSTRAINT `dev_bookmarks_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `dev_users` (`sciper`),
  ADD CONSTRAINT `dev_bookmarks_ibfk_2` FOREIGN KEY (`id_question`) REFERENCES `dev_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dev_likes_answers`
--
ALTER TABLE `dev_likes_answers`
  ADD CONSTRAINT `dev_likes_answers_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `dev_users` (`sciper`),
  ADD CONSTRAINT `dev_likes_answers_ibfk_2` FOREIGN KEY (`id_answer`) REFERENCES `dev_answers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `dev_likes_questions`
--
ALTER TABLE `dev_likes_questions`
  ADD CONSTRAINT `dev_likes_questions_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `dev_users` (`sciper`),
  ADD CONSTRAINT `dev_likes_questions_ibfk_2` FOREIGN KEY (`id_question`) REFERENCES `dev_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dev_questions`
--
ALTER TABLE `dev_questions`
  ADD CONSTRAINT `dev_questions_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `dev_users` (`sciper`);

--
-- Constraints for table `prod_answers`
--
ALTER TABLE `prod_answers`
  ADD CONSTRAINT `prod_answers_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `prod_users` (`sciper`),
  ADD CONSTRAINT `prod_answers_ibfk_2` FOREIGN KEY (`id_parent_question`) REFERENCES `prod_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `prod_bookmarks`
--
ALTER TABLE `prod_bookmarks`
  ADD CONSTRAINT `prod_bookmarks_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `prod_users` (`sciper`),
  ADD CONSTRAINT `prod_bookmarks_ibfk_2` FOREIGN KEY (`id_question`) REFERENCES `prod_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prod_likes_answers`
--
ALTER TABLE `prod_likes_answers`
  ADD CONSTRAINT `prod_likes_answers_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `prod_users` (`sciper`),
  ADD CONSTRAINT `prod_likes_answers_ibfk_2` FOREIGN KEY (`id_answer`) REFERENCES `prod_answers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `prod_likes_questions`
--
ALTER TABLE `prod_likes_questions`
  ADD CONSTRAINT `prod_likes_questions_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `prod_users` (`sciper`),
  ADD CONSTRAINT `prod_likes_questions_ibfk_2` FOREIGN KEY (`id_question`) REFERENCES `prod_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prod_questions`
--
ALTER TABLE `prod_questions`
  ADD CONSTRAINT `prod_questions_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `prod_users` (`sciper`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
