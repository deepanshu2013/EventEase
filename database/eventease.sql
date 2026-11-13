-- ========================================================
-- EventEase - College Event Registration Platform
-- Database Schema and Sample Data
-- Database: `eventease`
-- ========================================================

CREATE DATABASE IF NOT EXISTS `eventease` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `eventease`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'organizer') NOT NULL DEFAULT 'student',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `events`
-- --------------------------------------------------------
CREATE TABLE `events` (
  `event_id` INT(11) NOT NULL AUTO_INCREMENT,
  `organizer_id` INT(11) NOT NULL,
  `event_name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'General',
  `venue` VARCHAR(150) NOT NULL,
  `event_date` DATETIME NOT NULL,
  `capacity` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_events_organizer` (`organizer_id`),
  KEY `idx_events_date` (`event_date`),
  KEY `idx_events_category` (`category`),
  CONSTRAINT `fk_events_organizer` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `registrations`
-- --------------------------------------------------------
CREATE TABLE `registrations` (
  `registration_id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `event_id` INT(11) NOT NULL,
  `registration_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('registered', 'cancelled', 'attended') NOT NULL DEFAULT 'registered',
  PRIMARY KEY (`registration_id`),
  UNIQUE KEY `uq_student_event` (`student_id`, `event_id`),
  KEY `idx_reg_student` (`student_id`),
  KEY `idx_reg_event` (`event_id`),
  KEY `idx_reg_status` (`status`),
  CONSTRAINT `fk_reg_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Dumping initial seed data for table `users`
-- Default Passwords:
-- organizer@test.com  => organizer123
-- student@test.com    => student123
-- All passwords hashed with BCRYPT (password_hash)
-- --------------------------------------------------------
INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Test Student', 'student@test.com', '$2y$12$r3veRvrsEg9Mdpa4NNZOweouryXbBYBH6bIfJ2BH8rZ5NWv6Ud85y', 'student', '2026-11-01 10:00:00'),
(2, 'Dr. Rajesh Sharma', 'organizer@test.com', '$2y$12$Nqy1V54NABEDNEddjNNewur8XuFwZQnU2QH29/AMhVfx6F.6IZine', 'organizer', '2026-11-01 10:00:00'),
(3, 'Prof. Sunita Verma', 'sunita.verma@college.edu', '$2y$12$Nqy1V54NABEDNEddjNNewur8XuFwZQnU2QH29/AMhVfx6F.6IZine', 'organizer', '2026-11-02 11:30:00'),
(4, 'Amit Patel', 'amit.patel@student.edu', '$2y$12$r3veRvrsEg9Mdpa4NNZOweouryXbBYBH6bIfJ2BH8rZ5NWv6Ud85y', 'student', '2026-11-03 09:15:00'),
(5, 'Priya Nair', 'priya.nair@student.edu', '$2y$12$r3veRvrsEg9Mdpa4NNZOweouryXbBYBH6bIfJ2BH8rZ5NWv6Ud85y', 'student', '2026-11-04 14:20:00'),
(6, 'Rahul Sen', 'rahul.sen@student.edu', '$2y$12$r3veRvrsEg9Mdpa4NNZOweouryXbBYBH6bIfJ2BH8rZ5NWv6Ud85y', 'student', '2026-11-05 16:45:00');

-- --------------------------------------------------------
-- Dumping initial seed data for table `events`
-- Dates are set in the future relative to current semester
-- --------------------------------------------------------
INSERT INTO `events` (`event_id`, `organizer_id`, `event_name`, `description`, `category`, `venue`, `event_date`, `capacity`, `created_at`) VALUES
(1, 2, 'CodeCraft Hackathon 2026', 'A 24-hour inter-departmental coding hackathon focusing on AI-driven web apps and real-world problem solving with industry mentors and prizes.', 'Technical', 'Main Computer Center, Lab 3 & 4', '2026-11-25 09:30:00', 80, '2026-11-05 10:00:00'),
(2, 3, 'Rhythm & Raag Cultural Gala', 'Annual intra-college cultural night celebrating music, classical dance, theatrical drama, and battle of the college bands.', 'Cultural', 'College Grand Auditorium', '2026-11-28 17:00:00', 250, '2026-11-06 12:00:00'),
(3, 2, 'Inter-College Box Cricket League', 'Thrilling 7-a-side box cricket tournament between departments. Knockout format with trophies and medals for champions.', 'Sports', 'University Sports Complex', '2026-12-02 08:30:00', 60, '2026-11-07 14:30:00'),
(4, 2, 'Hands-on Full-Stack Web Bootcamp', 'Intensive practical workshop on modern PHP, REST APIs, MySQL optimization, and frontend UI design for MCA & B.Tech students.', 'Workshop', 'Seminar Hall B', '2026-12-05 10:00:00', 50, '2026-11-08 11:00:00'),
(5, 3, 'Cyber Security & Cloud Defense Summit', 'Distinguished industry leaders discuss zero-trust architecture, ethical hacking demonstrations, and cloud infrastructure safety.', 'Seminar', 'Conference Hall 1', '2026-12-10 11:00:00', 120, '2026-11-09 09:00:00'),
(6, 3, 'MCA Annual Research & Project Expo', 'Exhibition of innovative final year MCA and BCA student capstone projects evaluated by external industry judges.', 'Academic', 'Campus Exhibition Ground', '2026-12-15 10:00:00', 100, '2026-11-09 15:00:00');

-- --------------------------------------------------------
-- Dumping initial seed data for table `registrations`
-- --------------------------------------------------------
INSERT INTO `registrations` (`registration_id`, `student_id`, `event_id`, `registration_date`, `status`) VALUES
(1, 1, 1, '2026-11-09 10:15:00', 'registered'),
(2, 4, 1, '2026-11-09 11:30:00', 'registered'),
(3, 5, 1, '2026-11-09 14:00:00', 'registered'),
(4, 1, 2, '2026-11-10 09:45:00', 'registered'),
(5, 5, 2, '2026-11-10 10:20:00', 'registered'),
(6, 6, 2, '2026-11-10 12:10:00', 'attended'),
(7, 4, 3, '2026-11-11 15:00:00', 'registered'),
(8, 6, 3, '2026-11-11 16:30:00', 'registered'),
(9, 1, 4, '2026-11-12 09:00:00', 'registered'),
(10, 4, 4, '2026-11-12 10:30:00', 'registered');
