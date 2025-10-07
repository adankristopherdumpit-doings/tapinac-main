-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 06:40 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
  `year_start` year(4) NOT NULL,
  `year_end` year(4) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `year_start`, `year_end`, `status`) VALUES
(1, '2025', '2026', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `quarter_id` int(11) NOT NULL,
  `old_grade` decimal(5,2) DEFAULT NULL,
  `new_grade` decimal(5,2) DEFAULT NULL,
  `action` enum('add','edit','delete') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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

-- --------------------------------------------------------

--
-- Table structure for table `final_grades`
--

CREATE TABLE `final_grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `quarter_id` int(11) NOT NULL,
  `ww_total` decimal(5,2) DEFAULT NULL,
  `ww1` decimal(5,2) DEFAULT NULL,
  `ww2` decimal(5,2) DEFAULT NULL,
  `ww3` decimal(5,2) DEFAULT NULL,
  `ww4` decimal(5,2) DEFAULT NULL,
  `ww5` decimal(5,2) DEFAULT NULL,
  `ww6` decimal(5,2) DEFAULT NULL,
  `ww7` decimal(5,2) DEFAULT NULL,
  `ww8` decimal(5,2) DEFAULT NULL,
  `ww9` decimal(5,2) DEFAULT NULL,
  `ww10` decimal(5,2) DEFAULT NULL,
  `pt1` decimal(5,2) DEFAULT NULL,
  `pt2` decimal(5,2) DEFAULT NULL,
  `pt3` decimal(5,2) DEFAULT NULL,
  `pt4` decimal(5,2) DEFAULT NULL,
  `pt5` decimal(5,2) DEFAULT NULL,
  `pt6` decimal(5,2) DEFAULT NULL,
  `pt7` decimal(5,2) DEFAULT NULL,
  `pt8` decimal(5,2) DEFAULT NULL,
  `pt9` decimal(5,2) DEFAULT NULL,
  `pt10` decimal(5,2) DEFAULT NULL,
  `qa` decimal(5,2) DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `quarterly_grade` decimal(5,2) DEFAULT NULL,
  `ww_ps` decimal(5,2) DEFAULT NULL,
  `ww_ws` decimal(5,2) DEFAULT NULL,
  `pt_total` decimal(5,2) DEFAULT NULL,
  `pt_ps` decimal(5,2) DEFAULT NULL,
  `pt_ws` decimal(5,2) DEFAULT NULL,
  `qa_ps` decimal(5,2) DEFAULT NULL,
  `qa_ws` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject_id`, `academic_year_id`, `teacher_id`, `quarter_id`, `ww_total`, `ww1`, `ww2`, `ww3`, `ww4`, `ww5`, `ww6`, `ww7`, `ww8`, `ww9`, `ww10`, `pt1`, `pt2`, `pt3`, `pt4`, `pt5`, `pt6`, `pt7`, `pt8`, `pt9`, `pt10`, `qa`, `final_grade`, `quarterly_grade`, `ww_ps`, `ww_ws`, `pt_total`, `pt_ps`, `pt_ws`, `qa_ps`, `qa_ws`) VALUES
(0, 1, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 6, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 8, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 7, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 12, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 11, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 10, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 9, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 2, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 3, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 1, 17, 1, 2, 1, 50.00, 10.00, 10.00, 10.00, 10.00, 10.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 50.00, 72.00, 100.00, 50.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 6, 17, 1, 2, 1, 46.00, 10.00, 9.00, 9.00, 10.00, 8.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 46.00, 71.00, 92.00, 46.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 8, 17, 1, 2, 1, 29.00, 10.00, 4.00, 5.00, 5.00, 5.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 29.00, 67.00, 58.00, 29.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 7, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 12, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 11, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 10, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 9, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 2, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(0, 3, 17, 1, 2, 1, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `grade_levels`
--

CREATE TABLE `grade_levels` (
  `id` int(11) NOT NULL,
  `grade_name` varchar(50) DEFAULT NULL
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
-- Table structure for table `grading_components`
--

CREATE TABLE `grading_components` (
  `id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `quarter_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `ww_percent` int(11) DEFAULT NULL,
  `pt_percent` int(11) DEFAULT NULL,
  `qa_percent` int(11) DEFAULT NULL,
  `ww_total_max` int(11) DEFAULT NULL,
  `pt_total_max` int(11) DEFAULT NULL,
  `qa_max` int(11) DEFAULT NULL,
  `ww1_max` int(11) DEFAULT NULL,
  `ww2_max` int(11) DEFAULT NULL,
  `ww3_max` int(11) DEFAULT NULL,
  `ww4_max` int(11) DEFAULT NULL,
  `ww5_max` int(11) DEFAULT NULL,
  `ww6_max` int(11) DEFAULT NULL,
  `ww7_max` int(11) DEFAULT NULL,
  `ww8_max` int(11) DEFAULT NULL,
  `ww9_max` int(11) DEFAULT NULL,
  `ww10_max` int(11) DEFAULT NULL,
  `pt1_max` int(11) DEFAULT NULL,
  `pt2_max` int(11) DEFAULT NULL,
  `pt3_max` int(11) DEFAULT NULL,
  `pt4_max` int(11) DEFAULT NULL,
  `pt5_max` int(11) DEFAULT NULL,
  `pt6_max` int(11) DEFAULT NULL,
  `pt7_max` int(11) DEFAULT NULL,
  `pt8_max` int(11) DEFAULT NULL,
  `pt9_max` int(11) DEFAULT NULL,
  `pt10_max` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quarters`
--

CREATE TABLE `quarters` (
  `id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quarters`
--

INSERT INTO `quarters` (`id`, `academic_year_id`, `name`, `start_date`, `end_date`) VALUES
(1, 1, '1st Quarter', NULL, NULL),
(2, 1, '2nd Quarter', NULL, NULL),
(3, 1, '3rd Quarter', NULL, NULL),
(4, 1, '4th Quarter', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rankings`
--

CREATE TABLE `rankings` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `grade_level_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `rank_position` int(11) NOT NULL
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
-- Table structure for table `school_info`
--

CREATE TABLE `school_info` (
  `id` int(11) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `school_id` varchar(50) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `division` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_info`
--

INSERT INTO `school_info` (`id`, `school_name`, `school_id`, `region`, `division`, `district`) VALUES
(1, 'Tapinac Elementary School', '107141', 'III', 'Olongapo', 'District IV-B');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `grade_level_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `grade_level_id`, `section_name`, `teacher_id`) VALUES
(1, 1, 'SSES', NULL),
(2, 1, 'ROSE', NULL),
(3, 1, 'SANTAN', NULL),
(4, 1, 'DAISY', NULL),
(5, 2, 'SSES', NULL),
(6, 2, 'VENUS', NULL),
(7, 2, 'NEPTUNE', NULL),
(8, 2, 'SATURN', NULL),
(9, 3, 'SSES', NULL),
(10, 3, 'MABINI', NULL),
(11, 3, 'BONIFACIO', 2),
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
(22, 6, 'LOVE', NULL),
(23, 6, 'HOPE', NULL),
(24, 6, 'HUMILITY', NULL),
(25, 6, 'FAITH', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `fname`, `mname`, `lname`, `gender`, `grade_level_id`, `section_id`, `teacher_id`, `created_at`, `updated_at`) VALUES
(1, 'Adan', '', 'Dumpit', 'Male', 3, 11, NULL, '2025-10-06 13:11:19', '2025-10-06 13:11:19'),
(2, 'Mark ', '', 'Zacker', 'Male', 3, 11, NULL, '2025-10-06 13:23:41', '2025-10-06 13:23:41'),
(3, 'Marky ', '', 'Zacker', 'Male', 3, 11, NULL, '2025-10-06 13:27:31', '2025-10-06 13:27:31'),
(4, 'Mark ', '', 'Mark', 'Male', 1, 3, NULL, '2025-10-06 21:18:09', '2025-10-06 21:18:09'),
(5, 'dsad', '', 'dsad', 'Female', 1, 2, NULL, '2025-10-06 21:36:25', '2025-10-06 21:36:25'),
(6, 'Mark', '', 'fdasf', 'Male', 3, 11, NULL, '2025-10-06 22:01:12', '2025-10-06 22:01:12'),
(7, 'fdsf', '', 'fdsfda', 'Male', 3, 11, NULL, '2025-10-06 22:01:33', '2025-10-06 22:01:33'),
(8, 'fdsf', '', 'fdsaf', 'Female', 3, 11, 2, '2025-10-06 22:05:54', '2025-10-06 22:05:54'),
(9, 'hello', '', 'world', 'Male', 3, 11, 2, '2025-10-06 22:06:10', '2025-10-06 22:06:10'),
(10, 'Daryl', '', 'Umali', 'Male', 3, 11, 2, '2025-10-06 22:18:28', '2025-10-06 22:18:28'),
(11, 'Germel', '', 'Mojeca', 'Male', 3, 11, 2, '2025-10-06 22:29:59', '2025-10-06 22:29:59'),
(12, 'ler', '', 'ler', 'Male', 3, 11, 2, '2025-10-07 10:39:15', '2025-10-07 10:39:15');

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_subjects`
--

INSERT INTO `student_subjects` (`id`, `student_id`, `subject_id`) VALUES
(1, 0, 17),
(2, 0, 18),
(3, 0, 19),
(4, 0, 20),
(5, 0, 21),
(6, 0, 22),
(7, 0, 23),
(8, 0, 24),
(9, 0, 17),
(10, 0, 18),
(11, 0, 19),
(12, 0, 20),
(13, 0, 21),
(14, 0, 22),
(15, 0, 23),
(16, 0, 24),
(17, 3, 17),
(18, 3, 18),
(19, 3, 19),
(20, 3, 20),
(21, 3, 21),
(22, 3, 22),
(23, 3, 23),
(24, 3, 24),
(25, 4, 1),
(26, 4, 2),
(27, 4, 3),
(28, 4, 4),
(29, 4, 5),
(30, 4, 6),
(31, 4, 7),
(32, 4, 8),
(33, 5, 1),
(34, 5, 2),
(35, 5, 3),
(36, 5, 4),
(37, 5, 5),
(38, 5, 6),
(39, 5, 7),
(40, 5, 8),
(41, 6, 17),
(42, 6, 18),
(43, 6, 19),
(44, 6, 20),
(45, 6, 21),
(46, 6, 22),
(47, 6, 23),
(48, 6, 24),
(49, 7, 17),
(50, 7, 18),
(51, 7, 19),
(52, 7, 20),
(53, 7, 21),
(54, 7, 22),
(55, 7, 23),
(56, 7, 24),
(57, 8, 17),
(58, 8, 18),
(59, 8, 19),
(60, 8, 20),
(61, 8, 21),
(62, 8, 22),
(63, 8, 23),
(64, 8, 24),
(65, 9, 17),
(66, 9, 18),
(67, 9, 19),
(68, 9, 20),
(69, 9, 21),
(70, 9, 22),
(71, 9, 23),
(72, 9, 24),
(73, 10, 17),
(74, 10, 18),
(75, 10, 19),
(76, 10, 20),
(77, 10, 21),
(78, 10, 22),
(79, 10, 23),
(80, 10, 24),
(81, 11, 17),
(82, 11, 18),
(83, 11, 19),
(84, 11, 20),
(85, 11, 21),
(86, 11, 22),
(87, 11, 23),
(88, 11, 24),
(89, 1, 22),
(90, 2, 22),
(91, 3, 22),
(92, 6, 22),
(93, 7, 22),
(94, 8, 22),
(95, 9, 22),
(96, 10, 22),
(97, 11, 22),
(98, 12, 17),
(99, 12, 18),
(100, 12, 19),
(101, 12, 20),
(102, 12, 21),
(103, 12, 22),
(104, 12, 23),
(105, 12, 24);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(100) DEFAULT NULL,
  `grade_level_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `grade_level_id`) VALUES
(1, 'FILIPINO', 1),
(2, 'ENGLISH', 1),
(3, 'MATHEMATICS', 1),
(4, 'SCIENCE', 1),
(5, 'ARALING PANLIPUNAN', 1),
(6, 'COMPUTER', 1),
(7, 'MAPEH', 1),
(8, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 1),
(9, 'FILIPINO', 2),
(10, 'ENGLISH', 2),
(11, 'MATHEMATICS', 2),
(12, 'SCIENCE', 2),
(13, 'ARALING PANLIPUNAN', 2),
(14, 'COMPUTER', 2),
(15, 'MAPEH', 2),
(16, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 2),
(17, 'FILIPINO', 3),
(18, 'ENGLISH', 3),
(19, 'MATHEMATICS', 3),
(20, 'SCIENCE', 3),
(21, 'ARALING PANLIPUNAN', 3),
(22, 'COMPUTER', 3),
(23, 'MAPEH', 3),
(24, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 3),
(25, 'FILIPINO', 4),
(26, 'ENGLISH', 4),
(27, 'MATHEMATICS', 4),
(28, 'SCIENCE', 4),
(29, 'ARALING PANLIPUNAN', 4),
(30, 'COMPUTER', 4),
(31, 'MAPEH', 4),
(32, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 4),
(33, 'FILIPINO', 5),
(34, 'ENGLISH', 5),
(35, 'MATHEMATICS', 5),
(36, 'SCIENCE', 5),
(37, 'ARALING PANLIPUNAN', 5),
(38, 'COMPUTER', 5),
(39, 'MAPEH', 5),
(40, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 5),
(41, 'FILIPINO', 6),
(42, 'ENGLISH', 6),
(43, 'MATHEMATICS', 6),
(44, 'SCIENCE', 6),
(45, 'ARALING PANLIPUNAN', 6),
(46, 'COMPUTER', 6),
(47, 'MAPEH', 6),
(48, 'EDUKASYON SA PAGPAPAKATAO (ESP)', 6);

-- --------------------------------------------------------

--
-- Table structure for table `subject_assignments`
--

CREATE TABLE `subject_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade_level_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_assignments`
--

INSERT INTO `subject_assignments` (`id`, `teacher_id`, `subject_id`, `grade_level_id`, `section_id`, `academic_year_id`) VALUES
(0, 2, 17, 3, 11, 1),
(0, 3, 18, 3, 11, 1),
(0, 3, 22, 3, 11, 1);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `fname`, `mname`, `lname`, `email`, `created_at`, `updated_at`) VALUES
(1, 'Juan', 'Cruz', 'Dela Cruz', 'kramsetlab99@gmail.com', '2025-08-21 06:23:03', '2025-10-06 11:03:54'),
(2, 'Maria', 'Luna', 'Santos', 'maria.santos@example.com', '2025-08-21 06:23:03', '2025-10-06 11:03:54'),
(3, 'Jose', 'Reyes', 'Tan', 'jose.tan@example.com', '2025-08-21 06:23:03', '2025-10-06 11:03:54'),
(4, 'Ana', 'Lopez', 'Garcia', 'ana.garcia@example.com', '2025-08-21 06:23:03', '2025-10-06 11:03:54'),
(5, 'Pedro', 'M.', 'Ramos', 'pedro.ramos@example.com', '2025-08-21 06:23:03', '2025-10-06 11:03:54'),
(6, 'Diamond', 'santa', 'six', 'diamondsantasiz@gmail.com', '2025-08-23 15:26:01', '2025-10-06 11:03:54'),
(0, 'Marky ', 'D', 'Monkey', 'mbaltes2803@gmail.com', '2025-10-07 10:20:27', '2025-10-07 10:20:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `force_password_reset` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role_id`, `teacher_id`, `force_password_reset`, `created_at`, `updated_at`) VALUES
(1, 'admin1', '', '$2y$10$xV8eZsIbojFtsAMrI0.hwe.H8mhcUDve.H8Qctr4SRxaHGxXvgHeq', 1, 1, 0, '2025-10-06 11:03:54', '2025-10-06 11:03:54'),
(2, 'adviser1', '', '$2y$10$0Lt.20KjkFkQbehCrfynGeITk9OfR0u9PLClKe8LoAPRloc5S56uK', 2, 2, 0, '2025-10-06 11:03:54', '2025-10-06 11:03:54'),
(3, 'teacher1', '', '$2y$10$0SW36NMwc0xs6HLRaAE8Rupd9IDocaRq6P7XP9yZwehrCveY/25Da', 3, 3, 0, '2025-10-06 11:03:54', '2025-10-06 11:03:54'),
(4, 'principal1', '', '$2y$10$yYqwTAIYYoH8FUNraZ2FE.Nih.mcnhiYei2jPdk0Ydg3mwGEwdd6y', 4, 4, 1, '2025-10-06 11:03:54', '2025-10-07 10:48:52'),
(5, 'masterteacher1', '', '$2y$10$P..APb8NR0jkDvSXI9zUme4T9Q34WwKAcsgIEwRasMvoYd2UGqhtW', 5, 5, 0, '2025-10-06 11:03:54', '2025-10-06 11:03:54'),
(0, 'marky1', '', '$2y$10$/Q5XKhbJxcPkBdkiCht/I.elSjNUbenBHKeiDjp4ONmO/pQQT4e2C', 3, 0, 0, '2025-10-07 10:20:27', '2025-10-07 10:21:28');

-- --------------------------------------------------------

--
-- Table structure for table `yearly_summary`
--

CREATE TABLE `yearly_summary` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `general_average` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `grading_components`
--
ALTER TABLE `grading_components`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `grading_components`
--
ALTER TABLE `grading_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `student_subjects`
--
ALTER TABLE `student_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
