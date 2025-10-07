-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 28, 2025 at 03:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `grading_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `academic_year`) VALUES
(1, '2023-2024'),
(2, '2024-2025');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `quarter` varchar(10) DEFAULT NULL,
  `old_grade` decimal(5,2) DEFAULT NULL,
  `new_grade` decimal(5,2) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `academic_year_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archives`
--

CREATE TABLE `archives` (
  `id` int(11) NOT NULL,
  `fname` varchar(50) DEFAULT NULL,
  `mname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `dropped_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archives`
--

INSERT INTO `archives` (`id`, `fname`, `mname`, `lname`, `gender`, `grade_level_id`, `section_id`, `dropped_at`) VALUES
(2, 'Shuna', 'Mercada', 'Skusk', 'Male', NULL, NULL, '2025-08-21 06:29:02'),
(4, 'Prinkie', 'Bacate', 'Baliba', 'Female', 5, 18, '2025-08-23 13:45:38'),
(5, 'Bray', 'Dine', 'Bacate', 'Male', 3, 10, '2025-08-23 14:34:52'),
(8, 'Shima', 'Mimi', 'Kobati', 'Male', 3, 11, '2025-08-28 12:48:02');

-- --------------------------------------------------------

--
-- Table structure for table `final_grades`
--

CREATE TABLE `final_grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `academic_year_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `quarter` varchar(10) DEFAULT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `academic_year_id` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `adviser_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_levels`
--

CREATE TABLE `grade_levels` (
  `id` int(11) NOT NULL,
  `grade_name` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_levels`
--

INSERT INTO `grade_levels` (`id`, `grade_name`) VALUES
(1, 'Grade 1'),
(2, 'Grade 2'),
(3, 'Grade 3'),
(4, 'Grade 4'),
(5, 'Grade 5'),
(6, 'Grade 6');

-- --------------------------------------------------------

--
-- Table structure for table `rankings`
--

CREATE TABLE `rankings` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `grade_level` varchar(10) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `average` decimal(5,2) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `academic_year_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'admin'),
(2, 'adviser'),
(3, 'teacher'),
(4, 'principal'),
(5, 'masterteacher');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `section_name` varchar(50) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `grade_level_id`, `section_name`, `teacher_id`) VALUES
(1, 1, 'SSES', NULL),
(2, 1, 'ROSE', 2),
(3, 1, 'SANTAN', NULL),
(4, 1, 'DAISY', NULL),
(5, 2, 'SSES', NULL),
(6, 2, 'VENUS', NULL),
(7, 2, 'NEPTUNE', NULL),
(8, 2, 'SATURN', NULL),
(9, 3, 'SSES', NULL),
(10, 3, 'MABINI', NULL),
(11, 3, 'BONIFACIO', NULL),
(12, 3, 'RIZAL', NULL),
(13, 4, 'SSES', NULL),
(14, 4, 'Topaz', NULL),
(15, 4, 'Emerald', NULL),
(16, 4, 'Jade', NULL),
(17, 5, 'SSES', NULL),
(18, 5, 'DIAMOND', NULL),
(19, 5, 'PEARL', NULL),
(20, 5, 'RUBY', NULL),
(21, 6, 'SSES', NULL),
(22, 6, 'LOVE', 6),
(23, 6, 'HOPE', NULL),
(24, 6, 'HUMILITY', NULL),
(25, 6, 'FAITH', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `fname` varchar(50) DEFAULT NULL,
  `mname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `fname`, `mname`, `lname`, `gender`, `grade_level_id`, `section_id`) VALUES
(1, 'Kedlau', 'New', 'York', 'Male', 4, 13),
(3, 'allen iverson', 'ednalan', 'bravo', 'Male', 1, 1),
(6, 'Germel', 'Lurtak', 'Lele', 'Male', 6, 25),
(7, 'Kiroi', 'Sika', 'Zambales', 'Female', 4, 15),
(9, 'Shuten', 'Miskei', 'Durtsch', 'Female', 5, 18);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(100) DEFAULT NULL,
  `grade_level` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `grade_level`) VALUES
(1, 'FILIPINO', 'Grade 1'),
(2, 'ENGLISH', 'Grade 1'),
(3, 'MATHEMATICS', 'Grade 1'),
(4, 'SCIENCE', 'Grade 1'),
(5, 'ARALING PANLIPUNAN', 'Grade 1'),
(6, 'COMPUTER', 'Grade 1'),
(7, 'MAPEH', 'Grade 1'),
(8, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 'Grade 1'),
(9, 'FILIPINO', 'Grade 2'),
(10, 'ENGLISH', 'Grade 2'),
(11, 'MATHEMATICS', 'Grade 2'),
(12, 'SCIENCE', 'Grade 2'),
(13, 'ARALING PANLIPUNAN', 'Grade 2'),
(14, 'COMPUTER', 'Grade 2'),
(15, 'MAPEH', 'Grade 2'),
(16, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 'Grade 2'),
(17, 'FILIPINO', 'Grade 3'),
(18, 'ENGLISH', 'Grade 3'),
(19, 'MATHEMATICS', 'Grade 3'),
(20, 'SCIENCE', 'Grade 3'),
(21, 'ARALING PANLIPUNAN', 'Grade 3'),
(22, 'COMPUTER', 'Grade 3'),
(23, 'MAPEH', 'Grade 3'),
(24, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 'Grade 3'),
(25, 'FILIPINO', 'Grade 4'),
(26, 'ENGLISH', 'Grade 4'),
(27, 'MATHEMATICS', 'Grade 4'),
(28, 'SCIENCE', 'Grade 4'),
(29, 'ARALING PANLIPUNAN', 'Grade 4'),
(30, 'COMPUTER', 'Grade 4'),
(31, 'MAPEH', 'Grade 4'),
(32, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 'Grade 4'),
(33, 'FILIPINO', 'Grade 5'),
(34, 'ENGLISH', 'Grade 5'),
(35, 'MATHEMATICS', 'Grade 5'),
(36, 'SCIENCE', 'Grade 5'),
(37, 'ARALING PANLIPUNAN', 'Grade 5'),
(38, 'COMPUTER', 'Grade 5'),
(39, 'MAPEH', 'Grade 5'),
(40, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 'Grade 5'),
(41, 'FILIPINO', 'Grade 6'),
(42, 'ENGLISH', 'Grade 6'),
(43, 'MATHEMATICS', 'Grade 6'),
(44, 'SCIENCE', 'Grade 6'),
(45, 'ARALING PANLIPUNAN', 'Grade 6'),
(46, 'COMPUTER', 'Grade 6'),
(47, 'MAPEH', 'Grade 6'),
(48, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 'Grade 6');

-- --------------------------------------------------------

--
-- Table structure for table `subject_assignments`
--

CREATE TABLE `subject_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade_level_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `academic_year_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_assignments`
--

INSERT INTO `subject_assignments` (`id`, `teacher_id`, `subject_id`, `grade_level_id`, `section_id`, `academic_year_id`) VALUES
(1, 5, 18, 5, 18, 2),
(2, 2, 18, 1, 2, 2),
(3, 1, 17, 2, 6, 2),
(4, 5, 18, 1, 1, 2),
(5, 4, 18, 3, 10, 2),
(6, 3, 19, 4, 14, 2),
(7, 5, 17, 6, 22, 2),
(8, 5, 9, 6, 22, 2),
(9, 3, 8, 5, 19, 2),
(10, 3, 18, 5, 20, 2),
(11, 3, 12, 2, 6, 2),
(12, 6, 18, 6, 25, 2),
(13, 4, 17, 3, 10, 2),
(14, 2, 17, 6, 22, 2),
(15, 3, 18, 2, 6, 2),
(16, 5, 17, 5, 18, 2),
(17, 6, 15, 4, 13, 1),
(18, 4, 17, 3, 12, 2),
(19, 6, 17, 4, 15, 2);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `fname` varchar(50) DEFAULT NULL,
  `mname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `fname`, `mname`, `lname`, `email`, `created_at`, `updated_at`) VALUES
(1, 'Juan', 'Cruz', 'Dela Cruz', 'kramsetlab99@gmail.com', '2025-08-21 06:23:03', '2025-08-21 06:23:03'),
(2, 'Maria', 'Luna', 'Santos', 'maria.santos@example.com', '2025-08-21 06:23:03', '2025-08-21 06:23:03'),
(3, 'Jose', 'Reyes', 'Tan', 'jose.tan@example.com', '2025-08-21 06:23:03', '2025-08-21 06:23:03'),
(4, 'Ana', 'Lopez', 'Garcia', 'ana.garcia@example.com', '2025-08-21 06:23:03', '2025-08-21 06:23:03'),
(5, 'Pedro', 'M.', 'Ramos', 'pedro.ramos@example.com', '2025-08-21 06:23:03', '2025-08-21 06:23:03'),
(6, 'Diamond', 'santa', 'six', 'diamondsantasiz@gmail.com', '2025-08-23 15:26:01', '2025-08-23 15:26:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `force_password_reset` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role_id`, `teacher_id`, `force_password_reset`) VALUES
(1, 'admin1', '$2y$10$bxJnzL6pDU8tfWk0tyk5MOyfqVq0tCivKldGibBdL2QQ8a5ZoE1tW', 1, 1, 0),
(2, 'adviser1', '$2y$10$cJk9KlArEMqsKC68yJyBdubX1cdnYSMZcXvg5s9OjuN247ofUt2x6', 2, 2, 0),
(3, 'teacher1', '$2y$10$D65j27b/L4qUKjAOm.oCDO9eBvxDB6.unAB35sIJhr2HKAbozd7zG', 3, 3, 0),
(4, 'principal1', '3549f22fb8622a6d216ef2dcd592e04ed1f1e604cef032d7e5c425e8e72a878e', 4, 4, 1),
(5, 'masterteacher1', '$2y$10$5spvDrxgFKW2ZU9YeDPUuuN0MM.EgzM3cpJ/kUDdx1WByih7roCsG', 5, 5, 0),
(6, 'adviser2', '52c827c6be058fa43d7d47e17339336c341e3c650bab4fc2dee5ca4642cb8ae1', 2, 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `yearly_summary`
--

CREATE TABLE `yearly_summary` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `general_average` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(100) DEFAULT NULL,
  `academic_year_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `archives`
--
ALTER TABLE `archives`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `final_grades`
--
ALTER TABLE `final_grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `grade_levels`
--
ALTER TABLE `grade_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rankings`
--
ALTER TABLE `rankings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grade_level_id` (`grade_level_id`),
  ADD KEY `fk_sections_teacher` (`teacher_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grade_level_id` (`grade_level_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subject_assignments`
--
ALTER TABLE `subject_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `grade_level_id` (`grade_level_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `yearly_summary`
--
ALTER TABLE `yearly_summary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `archives`
--
ALTER TABLE `archives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `final_grades`
--
ALTER TABLE `final_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_levels`
--
ALTER TABLE `grade_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rankings`
--
ALTER TABLE `rankings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `subject_assignments`
--
ALTER TABLE `subject_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `yearly_summary`
--
ALTER TABLE `yearly_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `activity_logs_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `activity_logs_ibfk_4` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `final_grades`
--
ALTER TABLE `final_grades`
  ADD CONSTRAINT `final_grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `final_grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `final_grades_ibfk_3` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `grades_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `grades_ibfk_4` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `rankings`
--
ALTER TABLE `rankings`
  ADD CONSTRAINT `rankings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `rankings_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `fk_sections_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`),
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`);

--
-- Constraints for table `subject_assignments`
--
ALTER TABLE `subject_assignments`
  ADD CONSTRAINT `subject_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `subject_assignments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `subject_assignments_ibfk_3` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`),
  ADD CONSTRAINT `subject_assignments_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  ADD CONSTRAINT `subject_assignments_ibfk_5` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `yearly_summary`
--
ALTER TABLE `yearly_summary`
  ADD CONSTRAINT `yearly_summary_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `yearly_summary_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
