-- StudentHub Database Schema
-- Exact match to curriculum requirements
-- Database: studenthub

DROP DATABASE IF EXISTS `studenthub`;
CREATE DATABASE `studenthub`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `studenthub`;

-- =====================================================================
-- 1. Courses Table
-- =====================================================================
CREATE TABLE `courses` (
    `course_id` INT(11) NOT NULL AUTO_INCREMENT,
    `course_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. Students Table
-- =====================================================================
CREATE TABLE `students` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `mobile` VARCHAR(15) NOT NULL,
    `course_id` INT(11),
    `year` INT(11),
    `gender` VARCHAR(20),
    PRIMARY KEY (`id`),
    FOREIGN KEY (`course_id`)
        REFERENCES `courses`(`course_id`)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. Events Table
-- =====================================================================
CREATE TABLE `events` (
    `event_id` INT NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(150) NOT NULL,
    `event_date` DATE NOT NULL,
    `location` VARCHAR(100),
    `category` VARCHAR(50),
    PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. Registrations Table
-- =====================================================================
CREATE TABLE `registrations` (
    `reg_id` INT NOT NULL AUTO_INCREMENT,
    `student_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `registered_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`reg_id`),
    UNIQUE (`student_id`, `event_id`),
    FOREIGN KEY (`student_id`)
        REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`)
        REFERENCES `events`(`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED DATA (matching exact column schemas)
-- =====================================================================

-- Courses
INSERT INTO `courses` (`course_name`) VALUES
('B.Tech'),
('MCA'),
('M.Sc'),
('BBA'),
('MBA');

-- Events
INSERT INTO `events` (`title`, `event_date`, `location`, `category`) VALUES
('Tech Fest 2026', '2026-11-15', 'Main Auditorium', 'Technical'),
('Career Fair 2026', '2026-12-01', 'Convention Center', 'Career'),
('AI/ML Workshop', '2026-11-20', 'Computer Lab 1', 'Workshop'),
('Web Development Bootcamp', '2026-11-25', 'Computer Lab 2', 'Workshop'),
('Cultural Night', '2026-12-10', 'Open Air Theatre', 'Cultural'),
('Sports Meet 2026', '2026-12-15', 'Sports Complex', 'Sports');

-- Students (exact columns: name, email, mobile, course_id, year, gender)
INSERT INTO `students` (`name`, `email`, `mobile`, `course_id`, `year`, `gender`) VALUES
('Rahul Sharma', 'rahul.sharma@studenthub.edu', '+919876543210', 1, 2, 'male'),
('Priya Patel', 'priya.patel@studenthub.edu', '+919876543211', 1, 1, 'female'),
('Amit Kumar', 'amit.kumar@studenthub.edu', '+919876543212', 2, 1, 'male'),
('Sneha Reddy', 'sneha.reddy@studenthub.edu', '+919876543213', 3, 2, 'female'),
('Vikram Singh', 'vikram.singh@studenthub.edu', '+919876543214', 1, 3, 'male');

-- Registrations
INSERT INTO `registrations` (`student_id`, `event_id`) VALUES
(1, 1),
(2, 1),
(3, 3),
(4, 4),
(5, 2);