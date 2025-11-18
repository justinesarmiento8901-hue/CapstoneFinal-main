-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2025 at 01:05 PM
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
-- Database: `crudoperation`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` enum('add','view','delete','edit') NOT NULL,
  `entity_table` varchar(100) NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_table`, `entity_id`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'edit', 'parents', 1, 'Updated parent record with ID 1', '::1', '2025-10-28 19:46:23'),
(2, 1, 'edit', 'infantinfo', 1, 'Updated infant record with ID 1', '::1', '2025-10-28 19:47:54'),
(3, 1, 'delete', 'infantinfo', 20, 'Deleted infant record with ID 20', '::1', '2025-10-28 19:49:14'),
(4, 1, 'delete', 'parents', 7, 'Deleted parent record with ID 7', '::1', '2025-10-28 19:49:55');

-- --------------------------------------------------------

--
-- Table structure for table `growth_reference`
--

CREATE TABLE `growth_reference` (
  `id` int(11) NOT NULL,
  `age_in_months` int(11) NOT NULL,
  `sex` enum('Male','Female') DEFAULT NULL,
  `weight_min` decimal(4,1) NOT NULL,
  `weight_max` decimal(4,1) NOT NULL,
  `height_min` decimal(5,1) NOT NULL,
  `height_max` decimal(5,1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `growth_reference`
--

INSERT INTO `growth_reference` (`id`, `age_in_months`, `sex`, `weight_min`, `weight_max`, `height_min`, `height_max`) VALUES
(1, 0, NULL, 2.5, 4.3, 45.0, 55.0),
(2, 1, NULL, 3.4, 5.5, 50.0, 58.0),
(3, 2, NULL, 4.3, 6.8, 54.0, 62.0),
(4, 3, NULL, 5.0, 7.5, 57.0, 65.0),
(5, 4, NULL, 5.6, 8.1, 59.0, 67.0),
(6, 5, NULL, 6.1, 8.6, 61.0, 69.0),
(7, 6, NULL, 6.4, 9.1, 63.0, 71.0),
(8, 7, NULL, 6.7, 9.5, 65.0, 72.0),
(9, 8, NULL, 7.0, 9.8, 66.0, 74.0),
(10, 9, NULL, 7.2, 10.2, 67.0, 75.0),
(11, 10, NULL, 7.4, 10.5, 68.0, 76.0),
(12, 11, NULL, 7.6, 10.8, 69.0, 77.0),
(13, 12, NULL, 7.8, 11.0, 70.0, 78.0),
(14, 0, 'Male', 2.5, 4.3, 46.0, 54.0),
(15, 0, 'Female', 2.4, 4.2, 45.0, 53.0),
(16, 1, 'Male', 3.4, 5.8, 50.0, 59.0),
(17, 1, 'Female', 3.2, 5.5, 49.0, 58.0),
(18, 2, 'Male', 4.3, 7.0, 53.0, 62.0),
(19, 2, 'Female', 4.0, 6.6, 52.0, 61.0),
(20, 3, 'Male', 5.0, 7.8, 55.0, 65.0),
(21, 3, 'Female', 4.6, 7.5, 54.0, 64.0),
(22, 4, 'Male', 5.6, 8.6, 57.0, 67.0),
(23, 4, 'Female', 5.1, 8.2, 56.0, 66.0),
(24, 5, 'Male', 6.1, 9.2, 59.0, 69.0),
(25, 5, 'Female', 5.5, 8.8, 58.0, 68.0),
(26, 6, 'Male', 6.4, 9.7, 60.0, 70.0),
(27, 6, 'Female', 5.8, 9.2, 59.0, 70.0),
(28, 7, 'Male', 6.7, 10.2, 61.0, 71.5),
(29, 7, 'Female', 6.0, 9.6, 60.0, 71.0),
(30, 8, 'Male', 6.9, 10.6, 62.0, 73.0),
(31, 8, 'Female', 6.2, 10.0, 61.0, 72.0),
(32, 9, 'Male', 7.1, 11.0, 63.0, 74.0),
(33, 9, 'Female', 6.4, 10.4, 62.0, 73.5),
(34, 10, 'Male', 7.4, 11.3, 64.0, 75.0),
(35, 10, 'Female', 6.6, 10.8, 63.0, 74.5),
(36, 11, 'Male', 7.6, 11.7, 65.0, 76.0),
(37, 11, 'Female', 6.8, 11.1, 64.0, 75.5),
(38, 12, 'Male', 7.8, 12.0, 66.0, 77.0),
(39, 12, 'Female', 7.0, 11.5, 65.0, 77.0);

-- --------------------------------------------------------

--
-- Table structure for table `healthworker`
--

CREATE TABLE `healthworker` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `barangay_assigned` enum('Betes','Bibiclat','Bucot','La Purisima','Macabucod','Magsaysay','Pantoc','Poblacion Centro','Poblacion East I','Poblacion East II','Poblacion West III','Poblacion West IV','San Carlos','San Emiliano','San Eustacio','San Felipe Bata','San Felipe Matanda','San Juan','San Pablo Bata','San Pablo Matanda','Santiago','Santa Monica','Santo Rosario','Santo Tomas','Sunson','Umangan') NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `healthworker`
--

INSERT INTO `healthworker` (`id`, `user_id`, `firstname`, `middlename`, `lastname`, `gender`, `address`, `contact_number`, `barangay_assigned`, `license_number`, `position`, `created_at`) VALUES
(1, 46, 'Justine', 'argon', 'Sarmiento', 'Male', 'zone 2, Bibiclat, Aliaga, Nueva Ecija', '09925094535', 'Bibiclat', '4113', 'Healthworker', '2025-10-27 16:35:25'),
(2, 47, 'Noeliza', 'Angeles', 'Bombio', 'Female', 'zone 2, Bucot, Aliaga, Nueva Ecija', '09925094535', 'Umangan', '4114', 'Healthworker', '2025-10-27 17:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `infantinfo`
--

CREATE TABLE `infantinfo` (
  `id` int(11) NOT NULL,
  `firstname` varchar(25) NOT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `surname` varchar(25) NOT NULL,
  `dateofbirth` varchar(25) NOT NULL,
  `placeofbirth` varchar(25) NOT NULL,
  `sex` enum('Male','Female') DEFAULT NULL,
  `weight` double NOT NULL,
  `height` double NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `bloodtype` varchar(5) NOT NULL,
  `nationality` varchar(25) NOT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `infantinfo`
--

INSERT INTO `infantinfo` (`id`, `firstname`, `middlename`, `surname`, `dateofbirth`, `placeofbirth`, `sex`, `weight`, `height`, `remarks`, `bloodtype`, `nationality`, `parent_id`) VALUES
(1, 'Miguelito', 'Mendoza', 'Santos', '2025-10-01', 'Cabanatuan City', 'Male', 2.5, 46, NULL, 'O-', 'Filipino', 1),
(2, 'Rafael', 'Mendoza', 'Santos', '2025-01-15', 'Tarlac', 'Male', 7.1, 63, NULL, 'O-', 'Filipino', 1),
(4, 'Angela', 'Mendoza', 'Santos', '2025-02-04', 'Cabanatuan City', 'Female', 8, 65, NULL, 'O-', 'Filipino', 1),
(5, 'Maricel', 'Tolentino', 'Dela Cruz', '2025-03-23', 'Cabanatuan City', 'Female', 6, 72, NULL, 'AB+', 'Filipino', 2),
(6, 'Jun', 'Tolentino', 'Dela Cruz', '2025-05-03', 'Cabanatuan City', 'Male', 6.1, 59, NULL, 'AB+', 'Filipino', 2),
(7, 'Clarisse', 'Tolentino', 'Dela Cruz', '2025-07-21', 'Cabanatuan City', 'Female', 4.8, 65, NULL, 'AB+', 'Filipino', 2),
(8, 'Joey', 'Garcia', 'Villanueva', '2025-03-02', 'Cabanatuan City', 'Male', 6.6, 60, NULL, 'B+', 'Filipino', 3),
(9, 'Mark', 'Garcia', 'Villanueva', '2025-10-10', 'Cabanatuan City', 'Male', 2.1, 45, NULL, 'A+', 'Filipino', 3),
(10, 'Alyssa', 'Garcia', 'Villanueva', '2025-03-04', 'Cabanatuan City', 'Female', 5.9, 73, NULL, 'A-', 'Filipino', 3),
(11, 'Ella', 'Alonzo', 'Ramos', '2025-04-17', 'Cabanatuan City', 'Female', 7.6, 65, NULL, 'A+', 'Filipino', 4),
(12, 'Jonas', 'Alonzo', 'Ramos', '2025-01-29', 'Cabanatuan City', 'Male', 3, 50, NULL, 'B+', 'Filipino', 4),
(13, 'Paula', 'Alonzo', 'Ramos', '2025-04-21', 'Cabanatuan City', 'Female', 5.8, 59, NULL, 'AB+', 'Filipino', 4),
(14, 'Rico', 'Dela Rosa', 'Garcia', '2025-03-04', 'Cabanatuan City', 'Male', 6.7, 61, NULL, 'AB+', 'Filipino', 5),
(15, 'Liza', 'Dela Rosa', 'Garcia', '2025-05-07', 'Cabanatuan City', 'Female', 8.9, 69, NULL, 'AB+', 'Filipino', 5),
(16, 'Daryl', 'Dela Rosa', 'Garcia', '2025-02-13', 'Cabanatuan City', 'Male', 6.8, 61, NULL, 'B+', 'Filipino', 5),
(17, 'Grace', 'Santiago', 'Navarro', '2025-07-04', 'Cabanatuan City', 'Female', 4.6, 67, NULL, 'AB+', 'Filipino', 6),
(18, 'Noel', 'Santiago', 'Navarro', '2025-02-02', 'Cabanatuan City', 'Male', 6.9, 62, NULL, 'AB+', 'Filipino', 6),
(19, 'Carla', 'Santiago', 'Navarro', '2025-07-07', 'Cabanatuan City', 'Female', 4.7, 56, NULL, 'AB-', 'Filipino', 6),
(23, 'Kristine', 'Flores', 'Mendoza', '2025-05-04', 'Cabanatuan City', 'Female', 5.4, 58, NULL, 'B+', 'Filipino', 8),
(24, 'joshua', 'Flores', 'Mendoza', '2025-08-20', 'Cabanatuan City', 'Male', 4.3, 53, NULL, 'AB+', 'Filipino', 8),
(25, 'Arnel', 'Flores', 'Mendoza', '2025-01-25', 'Tarlac', 'Male', 6.8, 61, NULL, 'AB+', 'Filipino', 8),
(26, 'Nico', 'Castillo', 'Bautista', '2025-03-03', 'Aliaga', 'Male', 6.7, 61, NULL, 'AB+', 'Filipino', 9),
(27, 'Bea', 'Castillo', 'Bautista', '2025-02-03', 'Aliaga', 'Female', 6.5, 64, NULL, 'AB+', 'Filipino', 9),
(28, 'Janelle', 'Castillo', 'Bautista', '2025-08-24', 'Aliaga', 'Female', 4, 53, NULL, 'B+', 'Filipino', 9),
(29, 'Joseph', 'Jimenez', 'Reyes', '2025-04-30', 'Cabanatuan City', 'Male', 6.1, 59, NULL, 'AB+', 'Filipino', 10),
(30, 'Shiela', 'Jimenez', 'Reyes', '2025-02-27', 'Cabanatuan City', 'Female', 6, 60, NULL, 'AB+', 'Filipino', 10),
(31, 'Patrick', 'Jimenez', 'Reyes', '2025-09-10', 'Cabanatuan City', 'Male', 5.9, 47, NULL, 'AB+', 'Filipino', 10),
(32, 'buloy', 'argon', 'Sarmiento', '2025-01-23', 'aliaga', 'Male', 7.5, 65, NULL, 'A+', 'Filipino', 11),
(33, 'angela', 'matias', 'Sarmiento', '2025-01-24', 'manila', 'Male', 8, 65, NULL, 'B+', 'Filipino', 12),
(34, 'Manok', 'susojo', 'palaka', '2025-01-02', 'manila', 'Male', 2.5, 46, NULL, 'B-', 'Filipino', 15);

-- --------------------------------------------------------

--
-- Table structure for table `infant_previous_records`
--

CREATE TABLE `infant_previous_records` (
  `id` int(11) NOT NULL,
  `infant_id` int(11) NOT NULL,
  `record_date` date DEFAULT curdate(),
  `previous_weight` decimal(5,2) DEFAULT NULL,
  `previous_height` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `growth_status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `infant_previous_records`
--

INSERT INTO `infant_previous_records` (`id`, `infant_id`, `record_date`, `previous_weight`, `previous_height`, `remarks`, `growth_status`) VALUES
(1, 12, '2025-10-23', 2.50, 46.00, '', 'Improving'),
(2, 10, '2025-10-24', 2.40, 45.00, '', 'Improving'),
(3, 4, '2025-10-24', 2.40, 4.20, '', 'Improving'),
(4, 25, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(5, 27, '2025-10-24', 2.40, 45.00, '', 'Improving'),
(6, 19, '2025-10-24', 2.40, 45.00, '', 'Improving'),
(7, 7, '2025-10-24', 2.40, 45.00, '', 'Improving'),
(8, 16, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(9, 11, '2025-10-24', 2.40, 45.00, '', 'Improving'),
(11, 17, '2025-10-24', 2.40, 45.00, '', 'Improving'),
(12, 28, '2025-10-24', 2.40, 45.00, '', 'Improving'),
(13, 29, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(14, 24, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(15, 6, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(16, 23, '2025-10-24', 2.40, 45.00, '', 'Improving'),
(18, 15, '2025-10-24', 2.40, 45.00, '', 'Improving'),
(19, 5, '2025-10-24', 2.40, 45.00, '', 'Improving'),
(20, 9, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(21, 26, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(22, 18, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(23, 31, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(24, 13, '2025-10-24', 2.40, 45.00, '', 'Improving'),
(25, 2, '2025-10-24', 3.00, 50.00, '', 'Improving'),
(26, 14, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(28, 30, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(29, 16, '2025-10-24', 6.90, 62.00, '', 'Needs Attention'),
(30, 31, '2025-10-24', 3.40, 50.00, '', 'Improving'),
(31, 10, '2025-10-24', 7.00, 72.00, '', 'Improving'),
(32, 32, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(33, 33, '2025-10-24', 2.50, 46.00, '', 'Improving'),
(34, 4, '2025-10-24', 11.00, 65.00, '', 'Improving'),
(35, 4, '2025-10-24', 7.00, 74.00, '', 'Improving'),
(36, 8, '2025-10-26', 2.50, 46.00, '', 'Improving'),
(37, 11, '2025-10-26', 5.60, 60.00, '', 'Improving'),
(38, 5, '2025-10-26', 6.00, 72.00, '', 'Maintained'),
(39, 8, '2025-10-28', 7.00, 65.00, '', 'Maintained'),
(40, 8, '2025-10-28', 7.00, 65.00, '', 'Maintained'),
(41, 8, '2025-10-28', 7.00, 65.00, '', 'Needs Attention'),
(42, 9, '2025-10-28', 2.50, 55.00, '', 'Needs Attention'),
(43, 10, '2025-10-28', 8.00, 72.00, '', 'Needs Attention'),
(44, 10, '2025-10-28', 5.90, 71.00, '', 'Improving');

-- --------------------------------------------------------

--
-- Table structure for table `laboratory`
--

CREATE TABLE `laboratory` (
  `id` int(11) NOT NULL,
  `plaintext` varchar(255) NOT NULL,
  `hashed` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laboratory`
--

INSERT INTO `laboratory` (`id`, `plaintext`, `hashed`) VALUES
(1, 'asdfas', '$2y$10$gO3VQCJ.gOjihWzeqel.rOK4ktc3L1ON5LU5I00yJ4/pn.kdm/3Ai'),
(2, 'asdfas', '$2y$10$GVPHVVAoIGCk3WBddTOLrOmrQ6iQdPsm005TKBqUX/cEK7tWneW/S'),
(3, 'justine', '$2y$10$bFGaToEpYbH2dSyYI8PzQ.DbFrLtdBCRQzjGif4VtHccVkVQ.vRSe'),
(4, 'karl', '$2y$10$pnYbze/vplzQngsIQydQgeYdv.8jqMgxMbBMZo8Wpbr74gMdRd3W6'),
(5, 'karl', '$2y$10$HaRSVMmrQqBOJkQcrdRuv.BxrkVp9gnV9SBV8QfoIkop8ed.EP9ue'),
(6, 'raven', '$2y$10$pcdZ5ag.dfa8jPYKJGnmFeZBtCAp3P33FQUG/Rvkfolt62wCIoTaW'),
(7, 'noeliza ann angeles', '$2y$10$nCaKyTMVC5MG9kMThWESJuAh7ipbzKLWoi8YMQcvhPy//G8xwZZDu');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs_del_edit`
--

CREATE TABLE `logs_del_edit` (
  `id` int(11) NOT NULL,
  `action` text NOT NULL,
  `user_ip` varchar(45) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs_del_edit`
--

INSERT INTO `logs_del_edit` (`id`, `action`, `user_ip`, `timestamp`) VALUES
(0, 'Updated parent record with ID 1', '::1', '2025-10-28 19:20:40');

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `purok` enum('Purok 1','Purok 2','Purok 3','Purok 4','Purok 5','Purok 6','Purok 7') NOT NULL,
  `Municipality` enum('Aliaga','Bongabon','Cabiao','Carranglan','Gabaldon','General Mamerto Natividad','General Tinio','Guimba','Jaen','Laur','Licab','Llanera','Lupao','Nampicuan','Pantabangan','Peñaranda','Quezon','Rizal','San Antonio','San Isidro','San Leonardo','Santa Rosa','Santo Domingo','Talavera','Talugtug','Zaragoza','Cabanatuan City','Gapan City','Science City of Muñoz','Palayan City','San Jose City') NOT NULL,
  `Province` enum('Aurora','Bataan','Bulacan','Nueva Ecija','Pampanga','Tarlac','Zambales') NOT NULL DEFAULT 'Nueva Ecija',
  `baranggay` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `barangay` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`id`, `first_name`, `last_name`, `email`, `phone`, `address`, `purok`, `Municipality`, `Province`, `baranggay`, `created_at`, `updated_at`, `barangay`) VALUES
(1, 'Rogeliako', 'Santos', 'Rogelia@gmail.com', '09774759342', 'Bibilat, Aliaga, Nueva Ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-23 06:28:37', '2025-10-28 11:46:23', 'Bibiclat'),
(2, 'Lourdes', 'Dela Cruz', 'Lourdes@gmail.com', '09695711178', 'Betes, Aliaga, Nueva Ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-23 06:30:54', '2025-10-23 06:30:54', 'Betes'),
(3, 'Eduarda', 'Villanueva', 'Eduarda@gmail.com', '09925094535', 'Bucot, Aliaga, Nueva Ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-23 06:33:15', '2025-10-23 06:33:15', 'Bucot'),
(4, 'Teresita', 'Ramos', 'Teresita@gmail.com', '09524430383', 'La Purisima, Aliaga, Nueva Ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-23 06:34:50', '2025-10-23 06:34:50', 'La Purisima'),
(5, 'Antonia', 'Garcia', 'Antonia@gmail.com', '09123245675', 'Macabucod, Aliaga, Nueva Ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-23 06:36:18', '2025-10-23 06:36:18', 'Macabucod'),
(6, 'Imelda', 'Navarro', 'Imelda@gmail.com', '09875645572', 'Magsaysay, Aliaga, Nueva Ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-23 06:37:41', '2025-10-23 06:37:41', 'Magsaysay'),
(8, 'Rowena', 'Mendoza', 'Rowena@gmail.com', '09886644556', 'Poblacion Centro, Aliaga, Nueva ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-23 06:40:40', '2025-10-23 06:40:40', 'Poblacion Centro'),
(9, 'Ramona', 'Bautista', 'Ramona@gmail.com', '09665544674', 'San Carlos, Aliaga, Nueva ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-23 06:42:34', '2025-10-23 06:42:34', 'San Carlos'),
(10, 'Carmelita', 'Reyes', 'Carmelita@gmail.com', '09113325678', 'Sunson, Aliaga, Nueva ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-23 06:43:53', '2025-10-23 06:43:53', 'Sunson'),
(11, 'andrew', 'villegas', 'andrew@gmail.com', '09778493395', 'zone 2, Bucot, Aliaga, Nueva Ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-24 06:35:31', '2025-10-24 06:35:31', 'Bucot'),
(12, 'angelito', 'cunanan', 'angelito@gmail', '09057081229', 'zone 2, Bibiclat, Aliaga, Nueva Ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-24 07:19:32', '2025-10-24 07:19:32', 'Bibiclat'),
(13, 'Justine', 'Sarmiento', 'justine@gmail.com', '0999232432', 'zone 2, Bibiclat, Aliaga, Nueva Ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-26 11:22:31', '2025-10-26 11:22:31', 'Bibiclat'),
(14, 'Noeliza', 'Bombio', 'Noelizaann@gmail.com', '09925094535', 'zone 4, Bibiclat, Aliaga, Nueva Ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-26 11:30:43', '2025-10-26 11:30:43', 'Bibiclat'),
(15, 'Rayver', 'Viernes', 'rayver@gmail.com', '09925094535', 'zone 1, Bibiclat, Aliaga, Nueva Ecija', 'Purok 1', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-26 11:35:44', '2025-10-26 11:35:44', 'Bibiclat'),
(16, 'Jessy', 'Bombio', 'jessy@gmail.com', '0999232432', 'Bibiclat, Purok 5, Aliaga, Nueva Ecija', 'Purok 5', 'Aliaga', 'Nueva Ecija', NULL, '2025-10-28 10:21:31', '2025-10-28 10:21:31', 'Bibiclat');

-- --------------------------------------------------------

--
-- Table structure for table `sms_queue`
--

CREATE TABLE `sms_queue` (
  `id` int(11) NOT NULL,
  `vacc_id` int(11) NOT NULL,
  `infant_id` int(11) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `next_dose_date` date DEFAULT NULL,
  `schedule_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `barangay` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_queue`
--

INSERT INTO `sms_queue` (`id`, `vacc_id`, `infant_id`, `phone`, `next_dose_date`, `schedule_time`, `created_at`, `barangay`) VALUES
(134, 45, 1, '09774759342', '2025-11-26', '08:31:00', '2025-10-26 13:37:49', 'Bibiclat');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_report_logs`
--

CREATE TABLE `tbl_report_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `run_type` enum('preview','csv','pdf') NOT NULL,
  `filters_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`filters_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_report_logs`
--

INSERT INTO `tbl_report_logs` (`id`, `user_id`, `run_type`, `filters_json`, `created_at`) VALUES
(1, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:32:10'),
(2, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:32:41'),
(3, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:32:50'),
(4, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:33:03'),
(5, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:33:10'),
(6, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:33:11'),
(7, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:33:12'),
(8, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:33:12'),
(9, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:33:13'),
(10, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:33:16'),
(11, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"barangay\"}', '2025-10-20 12:33:48'),
(12, 1, 'preview', '{\"barangays\":[\"Bibiclat\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"barangay\"}', '2025-10-20 12:33:50'),
(13, 1, 'pdf', '{\"barangays\":[\"Bibiclat\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"barangay\"}', '2025-10-20 12:33:55'),
(14, 1, 'csv', '{\"barangays\":[\"Bibiclat\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"barangay\"}', '2025-10-20 12:34:44'),
(15, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:35:12'),
(16, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"Pending\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:35:16'),
(17, 1, 'preview', '{\"barangays\":[],\"date_from\":\"2025-09-20\",\"date_to\":null,\"status\":\"Pending\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:35:41'),
(18, 1, 'preview', '{\"barangays\":[],\"date_from\":\"2025-09-20\",\"date_to\":\"2025-10-20\",\"status\":\"Pending\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:35:48'),
(19, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:36:06'),
(20, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"BCG\",\"group_by\":\"none\"}', '2025-10-20 12:36:08'),
(21, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"Hepatitis B (HepB)\",\"group_by\":\"none\"}', '2025-10-20 12:36:22'),
(22, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"Pentavalent (1st dose)\",\"group_by\":\"none\"}', '2025-10-20 12:36:28'),
(23, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"Hepatitis B (HepB)\",\"group_by\":\"none\"}', '2025-10-20 12:36:32'),
(24, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 12:46:46'),
(25, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"vaccine\"}', '2025-10-20 12:47:06'),
(26, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"barangay\"}', '2025-10-20 12:47:10'),
(27, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"vaccine\"}', '2025-10-20 12:47:20'),
(28, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-20 14:37:48'),
(29, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:39:53'),
(30, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:40:08'),
(31, 1, 'preview', '{\"barangays\":[\"Bibiclat\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:40:17'),
(32, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:40:24'),
(33, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"Pending\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:40:50'),
(34, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:40:54'),
(35, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"Pending\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:40:57'),
(36, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:41:04'),
(37, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:41:29'),
(38, 1, 'preview', '{\"barangays\":[\"Bibiclat\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:41:32'),
(39, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:41:41'),
(40, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"BCG\",\"group_by\":\"none\"}', '2025-10-21 09:41:57'),
(41, 1, 'csv', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"BCG\",\"group_by\":\"none\"}', '2025-10-21 09:42:11'),
(42, 1, 'pdf', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"BCG\",\"group_by\":\"none\"}', '2025-10-21 09:42:17'),
(43, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:42:43'),
(44, 1, 'csv', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:42:45'),
(45, 1, 'pdf', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:42:47'),
(46, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 09:47:13'),
(47, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 13:24:16'),
(48, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-21 15:28:45'),
(49, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 00:30:53'),
(50, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 01:26:07'),
(51, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 01:32:52'),
(52, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 01:32:55'),
(53, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 01:59:01'),
(54, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 04:04:28'),
(55, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:02:34'),
(56, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:03:13'),
(57, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:03:13'),
(58, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:03:14'),
(59, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:36:53'),
(60, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:37:24'),
(61, 1, 'preview', '{\"barangays\":[\"Bibiclat\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:37:37'),
(62, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:37:53'),
(63, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"BCG\",\"group_by\":\"none\"}', '2025-10-22 05:37:56'),
(64, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"Hepatitis B (HepB)\",\"group_by\":\"none\"}', '2025-10-22 05:37:58'),
(65, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:37:59'),
(66, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:38:02'),
(67, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:57:34'),
(68, 1, 'preview', '{\"barangays\":[],\"date_from\":\"2025-10-20\",\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:58:23'),
(69, 1, 'preview', '{\"barangays\":[],\"date_from\":\"2025-10-10\",\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:58:31'),
(70, 1, 'preview', '{\"barangays\":[],\"date_from\":\"2025-10-13\",\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:58:35'),
(71, 1, 'preview', '{\"barangays\":[],\"date_from\":\"2025-10-13\",\"date_to\":\"2025-10-17\",\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:58:39'),
(72, 1, 'preview', '{\"barangays\":[],\"date_from\":\"2025-10-13\",\"date_to\":\"2025-10-17\",\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:58:43'),
(73, 1, 'preview', '{\"barangays\":[],\"date_from\":\"2025-10-13\",\"date_to\":\"2025-10-17\",\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:59:13'),
(74, 1, 'preview', '{\"barangays\":[],\"date_from\":\"2025-10-13\",\"date_to\":\"2025-10-17\",\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:59:15'),
(75, 1, 'preview', '{\"barangays\":[],\"date_from\":\"2025-10-13\",\"date_to\":\"2025-10-17\",\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:59:16'),
(76, 1, 'preview', '{\"barangays\":[],\"date_from\":\"2025-10-13\",\"date_to\":\"2025-10-17\",\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 05:59:17'),
(77, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-22 06:52:29'),
(78, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-23 07:49:04'),
(79, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-23 10:07:43'),
(80, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-23 10:07:52'),
(81, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-23 10:08:17'),
(82, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-23 10:08:18'),
(83, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-23 10:31:29'),
(84, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 06:49:31'),
(85, 1, 'preview', '{\"barangays\":[\"Bibiclat\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 06:50:28'),
(86, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 06:51:48'),
(87, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 06:51:56'),
(88, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"Inactivated Polio Vaccine (1 dose)\",\"group_by\":\"none\"}', '2025-10-24 06:52:58'),
(89, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"BCG\",\"group_by\":\"none\"}', '2025-10-24 06:53:02'),
(90, 1, 'preview', '{\"barangays\":[\"Bucot\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":\"BCG\",\"group_by\":\"none\"}', '2025-10-24 06:53:05'),
(91, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 08:17:27'),
(92, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 08:17:32'),
(93, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"Pending\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 08:17:43'),
(94, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 08:17:46'),
(95, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"barangay\"}', '2025-10-24 08:17:56'),
(96, 1, 'preview', '{\"barangays\":[\"Betes\"],\"date_from\":null,\"date_to\":null,\"status\":\"Completed\",\"vaccine\":null,\"group_by\":\"status\"}', '2025-10-24 08:17:58'),
(97, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 08:27:26'),
(98, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 08:31:38'),
(99, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 08:31:38'),
(100, 1, 'preview', '{\"barangays\":[\"Bibiclat\"],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 08:31:40'),
(101, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:06:51'),
(102, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:07:00'),
(103, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:07:02'),
(104, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:07:04'),
(105, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:07:09'),
(106, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:07:11'),
(107, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:07:13'),
(108, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:07:14'),
(109, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:07:15'),
(110, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:07:17'),
(111, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:07:18'),
(112, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-24 13:07:20'),
(113, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-28 06:16:02'),
(114, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-28 06:18:32'),
(115, 1, 'preview', '{\"barangays\":[],\"date_from\":null,\"date_to\":null,\"status\":\"All\",\"vaccine\":null,\"group_by\":\"none\"}', '2025-10-28 06:32:30');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_vaccination_details`
--

CREATE TABLE `tbl_vaccination_details` (
  `id` int(11) NOT NULL,
  `infant_id` int(11) NOT NULL,
  `vaccine_name` varchar(255) NOT NULL,
  `stage` varchar(20) NOT NULL,
  `status` enum('Pending','Completed') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_vaccination_details`
--

INSERT INTO `tbl_vaccination_details` (`id`, `infant_id`, `vaccine_name`, `stage`, `status`, `created_at`, `updated_at`) VALUES
(2, 2, 'Oral Polio Vaccine (1st dose)', '1½ mo', 'Completed', '2025-10-23 08:06:38', '2025-10-24 04:32:53'),
(3, 4, 'Pentavalent (2nd dose)', '2½ mo', 'Completed', '2025-10-23 08:07:38', '2025-10-24 04:24:27'),
(4, 5, 'Pentavalent (1st dose)', '1½ mo', 'Completed', '2025-10-23 08:08:27', '2025-10-24 04:31:34'),
(5, 6, 'Pentavalent (1st dose)', '1½ mo', 'Completed', '2025-10-23 08:09:23', '2025-10-24 04:30:08'),
(6, 7, 'Oral Polio Vaccine (2nd dose)', '2½ mo', 'Completed', '2025-10-23 08:10:51', '2025-10-24 04:26:46'),
(8, 9, 'Pentavalent (1st dose)', '1½ mo', 'Completed', '2025-10-23 08:14:37', '2025-10-24 04:31:51'),
(9, 10, 'Pneumococcal Conjugate Vaccine (1st dose)', '1½ mo', 'Completed', '2025-10-23 08:15:35', '2025-10-24 04:21:17'),
(10, 11, 'Pentavalent (2nd dose)', '2½ mo', 'Completed', '2025-10-23 08:17:29', '2025-10-24 04:27:29'),
(11, 12, 'Pentavalent (1st dose)', '1½ mo', 'Completed', '2025-10-23 08:21:51', '2025-10-23 08:22:32'),
(12, 13, 'Pentavalent (3rd dose)', '3½ mo', 'Completed', '2025-10-23 08:23:32', '2025-10-24 04:32:42'),
(13, 14, 'Oral Polio Vaccine (2nd dose)', '2½ mo', 'Completed', '2025-10-23 08:24:20', '2025-10-24 04:33:04'),
(15, 15, 'Inactivated Polio Vaccine (1 dose)', '3½ mo', 'Completed', '2025-10-23 08:26:11', '2025-10-24 04:31:13'),
(16, 16, 'Pneumococcal Conjugate Vaccine (3rd dose)', '3½ mo', 'Completed', '2025-10-23 08:26:44', '2025-10-24 04:27:03'),
(17, 17, 'Measles, Mumps, Rubella (MMR 1st dose)', '9 mo', 'Completed', '2025-10-23 08:27:36', '2025-10-24 04:28:48'),
(18, 18, 'Oral Polio Vaccine (1st dose)', '1½ mo', 'Completed', '2025-10-23 08:28:03', '2025-10-24 04:32:14'),
(19, 19, 'Oral Polio Vaccine (1st dose)', '1½ mo', 'Completed', '2025-10-23 08:28:31', '2025-10-24 04:26:27'),
(23, 23, 'Pneumococcal Conjugate Vaccine (2nd dose)', '2½ mo', 'Completed', '2025-10-23 08:30:30', '2025-10-24 04:30:41'),
(24, 24, 'Inactivated Polio Vaccine (1 dose)', '3½ mo', 'Completed', '2025-10-23 08:31:00', '2025-10-24 04:29:49'),
(25, 25, 'Pneumococcal Conjugate Vaccine (2nd dose)', '2½ mo', 'Completed', '2025-10-23 08:31:31', '2025-10-24 04:25:04'),
(26, 26, 'Pneumococcal Conjugate Vaccine (2nd dose)', '2½ mo', 'Completed', '2025-10-23 08:31:59', '2025-10-24 04:32:03'),
(27, 27, 'Pneumococcal Conjugate Vaccine (1st dose)', '1½ mo', 'Completed', '2025-10-23 08:33:37', '2025-10-24 04:25:46'),
(28, 28, 'Pneumococcal Conjugate Vaccine (2nd dose)', '2½ mo', 'Completed', '2025-10-23 08:34:03', '2025-10-24 04:29:12'),
(29, 29, 'Pneumococcal Conjugate Vaccine (3rd dose)', '3½ mo', 'Completed', '2025-10-23 08:35:19', '2025-10-24 04:29:37'),
(30, 30, 'Measles, Mumps, Rubella (MMR 2nd dose)', '1 yr', 'Completed', '2025-10-23 08:35:47', '2025-10-24 04:33:41'),
(31, 31, 'Pneumococcal Conjugate Vaccine (3rd dose)', '3½ mo', 'Completed', '2025-10-23 08:36:16', '2025-10-24 04:32:30'),
(32, 16, 'BCG', 'Birth', 'Completed', '2025-10-24 04:36:01', '2025-10-24 04:37:14'),
(33, 31, 'BCG', 'Birth', 'Completed', '2025-10-24 04:47:10', '2025-10-24 04:50:14'),
(34, 10, 'BCG', 'Birth', 'Completed', '2025-10-24 05:43:03', '2025-10-24 05:44:09'),
(35, 32, 'BCG', 'Birth', 'Completed', '2025-10-24 06:40:09', '2025-10-24 06:44:14'),
(36, 33, 'BCG', 'Birth', 'Completed', '2025-10-24 07:27:56', '2025-10-24 07:29:19'),
(37, 4, 'Hepatitis B (HepB)', 'Birth', 'Completed', '2025-10-24 07:49:32', '2025-10-24 08:02:22'),
(38, 4, 'Oral Polio Vaccine (1st dose)', '1½ mo', 'Completed', '2025-10-24 07:50:17', '2025-10-24 07:59:46'),
(42, 11, 'BCG', 'Birth', 'Completed', '2025-10-26 13:31:02', '2025-10-26 14:38:23'),
(43, 8, 'Hepatitis B (HepB)', 'Birth', 'Completed', '2025-10-26 13:35:50', '2025-10-26 14:28:10'),
(44, 5, 'BCG', 'Birth', 'Completed', '2025-10-26 13:37:01', '2025-10-26 14:48:58'),
(45, 1, 'BCG', 'Birth', 'Pending', '2025-10-26 13:37:49', '2025-10-26 13:37:49'),
(46, 8, 'BCG', 'Birth', 'Completed', '2025-10-27 17:08:24', '2025-10-27 17:52:15'),
(47, 8, 'Pentavalent (1st dose)', '1½ mo', 'Completed', '2025-10-27 17:53:25', '2025-10-27 17:54:31'),
(48, 8, 'Oral Polio Vaccine (1st dose)', '1½ mo', 'Completed', '2025-10-28 07:14:07', '2025-10-28 07:15:06'),
(49, 9, 'BCG', 'Birth', 'Completed', '2025-10-28 07:18:36', '2025-10-28 07:19:28'),
(50, 10, 'Hepatitis B (HepB)', 'Birth', 'Completed', '2025-10-28 07:42:19', '2025-10-28 07:43:32'),
(51, 10, 'Pentavalent (1st dose)', '1½ mo', 'Completed', '2025-10-28 08:06:14', '2025-10-28 08:42:40');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_vaccination_schedule`
--

CREATE TABLE `tbl_vaccination_schedule` (
  `vacc_id` int(11) NOT NULL,
  `infant_id` int(11) NOT NULL,
  `infant_name` varchar(100) DEFAULT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `stage` varchar(20) DEFAULT NULL,
  `date_vaccination` date NOT NULL,
  `next_dose_date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `barangay` varchar(100) DEFAULT NULL,
  `vaccinatedby` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_vaccination_schedule`
--

INSERT INTO `tbl_vaccination_schedule` (`vacc_id`, `infant_id`, `infant_name`, `vaccine_name`, `stage`, `date_vaccination`, `next_dose_date`, `time`, `status`, `remarks`, `date_created`, `barangay`, `vaccinatedby`) VALUES
(2, 2, NULL, 'Oral Polio Vaccine (1st dose)', NULL, '2025-10-23', '2025-11-05', '08:00:00', 'Completed', '', '2025-10-23 08:06:38', 'Bibiclat', NULL),
(3, 4, NULL, 'Pentavalent (2nd dose)', NULL, '2025-10-23', '2025-11-10', '08:00:00', 'Completed', '', '2025-10-23 08:07:38', 'Bibiclat', NULL),
(4, 5, NULL, 'Pentavalent (1st dose)', NULL, '2025-10-23', '2025-11-01', '08:00:00', 'Completed', '', '2025-10-23 08:08:27', 'Betes', NULL),
(5, 6, NULL, 'Pentavalent (1st dose)', NULL, '2025-10-23', '2025-10-27', '13:00:00', 'Completed', '', '2025-10-23 08:09:23', 'Betes', NULL),
(6, 7, NULL, 'Oral Polio Vaccine (2nd dose)', NULL, '2025-10-23', '2025-11-17', '15:00:00', 'Completed', '', '2025-10-23 08:10:51', 'Betes', NULL),
(8, 9, NULL, 'Pentavalent (1st dose)', NULL, '2025-10-23', '2025-10-27', '08:00:00', 'Completed', '', '2025-10-23 08:14:37', 'Bucot', NULL),
(9, 10, NULL, 'Pneumococcal Conjugate Vaccine (1st dose)', NULL, '2025-10-23', '2026-10-01', '08:00:00', 'Completed', '', '2025-10-23 08:15:35', 'Bucot', NULL),
(10, 11, NULL, 'Pentavalent (2nd dose)', NULL, '2025-10-23', '2026-10-10', '11:00:00', 'Completed', '', '2025-10-23 08:17:29', 'La Purisima', NULL),
(11, 12, NULL, 'Pentavalent (1st dose)', NULL, '2025-10-23', '2025-10-22', '08:00:00', 'Completed', '', '2025-10-23 08:21:51', 'La Purisima', NULL),
(12, 13, NULL, 'Pentavalent (3rd dose)', NULL, '2025-10-23', '2025-12-10', '08:00:00', 'Completed', '', '2025-10-23 08:23:32', 'La Purisima', NULL),
(13, 14, NULL, 'Oral Polio Vaccine (2nd dose)', NULL, '2025-10-23', '2025-12-02', '08:00:00', 'Completed', '', '2025-10-23 08:24:20', 'Macabucod', NULL),
(15, 15, NULL, 'Inactivated Polio Vaccine (1 dose)', NULL, '2025-10-23', '2026-01-03', '08:00:00', 'Completed', '', '2025-10-23 08:26:11', 'Macabucod', NULL),
(16, 16, NULL, 'Pneumococcal Conjugate Vaccine (3rd dose)', NULL, '2025-10-23', '2025-11-05', '08:00:00', 'Completed', '', '2025-10-23 08:26:44', 'Macabucod', NULL),
(17, 17, NULL, 'Measles, Mumps, Rubella (MMR 1st dose)', NULL, '2025-10-23', '2025-11-20', '09:00:00', 'Completed', '', '2025-10-23 08:27:36', 'Magsaysay', NULL),
(18, 18, NULL, 'Oral Polio Vaccine (1st dose)', NULL, '2025-10-23', '2025-11-01', '08:00:00', 'Completed', '', '2025-10-23 08:28:03', 'Magsaysay', NULL),
(19, 19, NULL, 'Oral Polio Vaccine (1st dose)', NULL, '2025-10-23', '2025-11-27', '08:00:00', 'Completed', '', '2025-10-23 08:28:31', 'Magsaysay', NULL),
(23, 23, NULL, 'Pneumococcal Conjugate Vaccine (2nd dose)', NULL, '2025-10-23', '2026-02-02', '08:00:00', 'Completed', '', '2025-10-23 08:30:30', 'Poblacion Centro', NULL),
(24, 24, NULL, 'Inactivated Polio Vaccine (1 dose)', NULL, '2025-10-23', '2025-10-29', '08:00:00', 'Completed', '', '2025-10-23 08:31:00', 'Poblacion Centro', NULL),
(25, 25, NULL, 'Pneumococcal Conjugate Vaccine (2nd dose)', NULL, '2025-10-23', '2025-11-25', '14:00:00', 'Completed', '', '2025-10-23 08:31:31', 'Poblacion Centro', NULL),
(26, 26, NULL, 'Pneumococcal Conjugate Vaccine (2nd dose)', NULL, '2025-10-23', '2025-11-20', '11:30:00', 'Completed', '', '2025-10-23 08:31:59', 'San Carlos', NULL),
(27, 27, NULL, 'Pneumococcal Conjugate Vaccine (1st dose)', NULL, '2025-10-23', '2025-12-11', '13:00:00', 'Completed', '', '2025-10-23 08:33:37', 'San Carlos', NULL),
(28, 28, NULL, 'Pneumococcal Conjugate Vaccine (2nd dose)', NULL, '2025-10-23', '2025-11-02', '15:00:00', 'Completed', '', '2025-10-23 08:34:03', 'San Carlos', NULL),
(29, 29, NULL, 'Pneumococcal Conjugate Vaccine (3rd dose)', NULL, '2025-10-23', '2026-01-25', '13:00:00', 'Completed', '', '2025-10-23 08:35:19', 'Sunson', NULL),
(30, 30, NULL, 'Measles, Mumps, Rubella (MMR 2nd dose)', NULL, '2025-10-23', '2026-03-08', '09:00:00', 'Completed', '', '2025-10-23 08:35:47', 'Sunson', NULL),
(31, 31, NULL, 'Pneumococcal Conjugate Vaccine (3rd dose)', NULL, '2025-10-23', '2026-01-30', '09:00:00', 'Completed', '', '2025-10-23 08:36:16', 'Sunson', NULL),
(32, 16, NULL, 'BCG', NULL, '2025-10-24', '2025-11-24', '08:35:00', 'Completed', '', '2025-10-24 04:36:01', 'Macabucod', NULL),
(33, 31, NULL, 'BCG', NULL, '2025-10-24', '2025-11-24', '08:22:00', 'Completed', '', '2025-10-24 04:47:10', 'Sunson', NULL),
(34, 10, NULL, 'BCG', NULL, '2025-10-24', '2025-10-24', '08:42:00', 'Completed', '', '2025-10-24 05:43:03', 'Bucot', NULL),
(35, 32, NULL, 'BCG', NULL, '2025-10-24', '2025-11-24', '08:30:00', 'Completed', '', '2025-10-24 06:40:09', 'Bucot', NULL),
(36, 33, NULL, 'BCG', NULL, '2025-10-24', '2025-11-24', '08:30:00', 'Completed', '', '2025-10-24 07:27:56', 'Bibiclat', NULL),
(37, 4, NULL, 'Hepatitis B (HepB)', NULL, '2025-10-24', '2025-11-24', '08:48:00', 'Completed', '', '2025-10-24 07:49:32', 'Bibiclat', NULL),
(38, 4, NULL, 'Oral Polio Vaccine (1st dose)', NULL, '2025-10-24', '2025-11-24', '08:00:00', 'Completed', '', '2025-10-24 07:50:17', 'Bibiclat', NULL),
(42, 11, NULL, 'BCG', NULL, '2025-10-26', '2025-11-26', '08:30:00', 'Completed', '', '2025-10-26 13:31:02', 'La Purisima', NULL),
(43, 8, NULL, 'Hepatitis B (HepB)', NULL, '2025-10-26', '2026-11-26', '08:30:00', 'Completed', '', '2025-10-26 13:35:50', 'Bucot', NULL),
(44, 5, NULL, 'BCG', NULL, '2025-10-26', '2026-11-26', '08:30:00', 'Completed', '', '2025-10-26 13:37:01', 'Betes', NULL),
(45, 1, NULL, 'BCG', NULL, '2025-10-26', '2025-11-26', '08:31:00', 'Pending', '', '2025-10-26 13:37:49', 'Bibiclat', NULL),
(46, 8, NULL, 'BCG', NULL, '2025-10-28', '2025-11-28', '08:13:00', 'Completed', '', '2025-10-27 17:08:24', 'Bucot', 'Noeliza Angeles Bombio'),
(47, 8, NULL, 'Pentavalent (1st dose)', NULL, '2025-10-28', '2025-11-28', '08:53:00', 'Completed', '', '2025-10-27 17:53:25', 'Bucot', 'Justine argon Sarmiento'),
(48, 8, NULL, 'Oral Polio Vaccine (1st dose)', NULL, '2025-10-28', '2025-11-28', '08:14:00', 'Completed', '', '2025-10-28 07:14:07', 'Bucot', 'Justine argon Sarmiento'),
(49, 9, NULL, 'BCG', NULL, '2025-10-28', '2025-11-28', '08:21:00', 'Completed', '', '2025-10-28 07:18:36', 'Bucot', 'Administrator'),
(50, 10, NULL, 'Hepatitis B (HepB)', NULL, '2025-10-28', '2025-11-28', '20:42:00', 'Completed', '', '2025-10-28 07:42:19', 'Bucot', 'Administrator'),
(51, 10, NULL, 'Pentavalent (1st dose)', NULL, '2025-10-28', '2025-11-28', '08:08:00', 'Completed', '', '2025-10-28 08:06:14', 'Bucot', 'Administrator');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_vaccine_reference`
--

CREATE TABLE `tbl_vaccine_reference` (
  `id` int(11) NOT NULL,
  `vaccine_name` varchar(100) DEFAULT NULL,
  `disease_prevented` varchar(150) DEFAULT NULL,
  `age_stage` varchar(20) DEFAULT NULL,
  `at_birth` tinyint(1) DEFAULT 0,
  `one_half_month` tinyint(1) DEFAULT 0,
  `two_half_month` tinyint(1) DEFAULT 0,
  `three_half_month` tinyint(1) DEFAULT 0,
  `nine_month` tinyint(1) DEFAULT 0,
  `one_year` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_vaccine_reference`
--

INSERT INTO `tbl_vaccine_reference` (`id`, `vaccine_name`, `disease_prevented`, `age_stage`, `at_birth`, `one_half_month`, `two_half_month`, `three_half_month`, `nine_month`, `one_year`) VALUES
(1, 'BCG', 'Tuberculosis', 'Birth', 0, 0, 0, 0, 0, 0),
(2, 'Hepatitis B (HepB)', 'Hepatitis B', 'Birth', 0, 0, 0, 0, 0, 0),
(3, 'Pentavalent (1st dose)', 'Diphtheria, Pertussis, Tetanus, Hepatitis B, Haemophilus influenzae type B', '1½ mo', 0, 0, 0, 0, 0, 0),
(4, 'Oral Polio Vaccine (1st dose)', 'Poliomyelitis', '1½ mo', 0, 0, 0, 0, 0, 0),
(5, 'Pneumococcal Conjugate Vaccine (1st dose)', 'Pneumonia, Meningitis, Otitis Media', '1½ mo', 0, 0, 0, 0, 0, 0),
(6, 'Pentavalent (2nd dose)', 'Diphtheria, Pertussis, Tetanus, Hepatitis B, Haemophilus influenzae type B', '2½ mo', 0, 0, 0, 0, 0, 0),
(7, 'Oral Polio Vaccine (2nd dose)', 'Poliomyelitis', '2½ mo', 0, 0, 0, 0, 0, 0),
(8, 'Pneumococcal Conjugate Vaccine (2nd dose)', 'Pneumonia, Meningitis, Otitis Media', '2½ mo', 0, 0, 0, 0, 0, 0),
(9, 'Pentavalent (3rd dose)', 'Diphtheria, Pertussis, Tetanus, Hepatitis B, Haemophilus influenzae type B', '3½ mo', 0, 0, 0, 0, 0, 0),
(10, 'Oral Polio Vaccine (3rd dose)', 'Poliomyelitis', '3½ mo', 0, 0, 0, 0, 0, 0),
(11, 'Inactivated Polio Vaccine (1 dose)', 'Poliomyelitis', '3½ mo', 0, 0, 0, 0, 0, 0),
(12, 'Pneumococcal Conjugate Vaccine (3rd dose)', 'Pneumonia, Meningitis, Otitis Media', '3½ mo', 0, 0, 0, 0, 0, 0),
(13, 'Measles, Mumps, Rubella (MMR 1st dose)', 'Measles, Mumps, Rubella', '9 mo', 0, 0, 0, 0, 0, 0),
(14, 'Measles, Mumps, Rubella (MMR 2nd dose)', 'Measles, Mumps, Rubella', '1 yr', 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` date NOT NULL,
  `usersname` int(255) NOT NULL,
  `role` enum('admin','healthworker','parent') NOT NULL DEFAULT 'parent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `created_at`, `usersname`, `role`) VALUES
(1, 'admin@gmail.com', '$2y$10$Nvj2IJVcsZg/Ak54hQdzd.QdC5oEUpsZ3/QuxVROTNIcISCR/5PR2', 'Administrator', '2025-04-24', 0, 'admin'),
(2, 'healthworker@gmail.com', '$2y$10$k0N5uDn0pcBK6IkxJQtVfeQ2M1PVTXGqi5CHQejgIDPBmCjUxDgZu', 'Healthworker', '2025-04-24', 0, 'healthworker'),
(3, 'angelicacute@gmail.com', '$2y$10$.t41Y5PtxTl/r6AydOFa4OwNop9Zy91nB7CFW5MhApFimPcuhuX12', 'Angela Catacutan', '2025-04-24', 0, 'parent'),
(4, 'biancakes@gmail.com', '$2y$10$iC3JUZpGbDHmD28EHchW6uTYAf4FnJZ4dsT26Jn1ES2eGaAPZwO/S', 'Bianca Umali', '2025-04-24', 0, 'parent'),
(5, 'mariateressa@gmail.com', '$2y$10$A4mA6yacMxfbluuSypBug..KAirca9QNOVb6o/8ke/zJFnr5xjI/y', 'maria terresa', '2025-04-24', 0, 'parent'),
(6, 'zingapanraven@gmail.com', '$2y$10$FnP/LAAEHwTz83bk1EfmcOHjXvPmkCENsdwyU1fDrz4EsS.cuWsyK', 'Raven Zingapan', '2025-10-17', 0, 'parent'),
(7, 'karluntal@gmail', '$2y$10$8e0u6LMY6hK2X64i2G8TjubDsTzRmw3XbZz7UF1NRU0W4slb6xWoa', 'karl untal', '2025-10-18', 0, 'parent'),
(8, 'gusionkogmaw@gmail.com', '$2y$10$FAbJBBOo4ydHHfu8aCXmse8lj72XLV2BY0KDbW4eG/PzN.zZSG9g6', 'Gusion kogmaw', '2025-10-18', 0, 'parent'),
(9, 'nyxsarmiento@gmail.com', '$2y$10$XPSAoGwIO/LhVbmZxRTgyOu.R3ItfVL4E9qkk6VQxGUxXaRohNh/S', 'Nyx Sarmiento', '2025-10-19', 0, 'parent'),
(10, 'noelizaannb@gmail.com', '$2y$10$vhQDFTdXFxY7R6qXym0BZeyByKT7S1p3r.w2wTVqSvr/FQKsU7MfS', 'Noeliza Ann Bombio', '2025-10-20', 0, 'parent'),
(11, 'prince@gmail.com', '$2y$10$EA6pl6mbcl3/JlXHegIQmuDXsR9C3109lg7lJFLd.vSg6f1kgY03C', 'Prince Mert Nicolas', '2025-10-20', 0, 'parent'),
(12, 'kingkong@gmail.com', '$2y$10$SE30hGMMnUA0.SdqVG0rzO0Y9POxNkR/SXy1/J1mZgIOGURNFEEfO', 'king kong', '2025-10-20', 0, 'parent'),
(13, 'tralalelo@gmail.com', '$2y$10$IKIGkVRO.rPD.pouBnjPqOWKvMrqKidmFnbLaehu6EEckpygytXLO', 'trala lelo', '2025-10-20', 0, 'parent'),
(14, 'Queenmorgan@gmail.com', '$2y$10$SdcbTZCmHI8g6m4NWG8HI.wZc9uma7nlEAJFz.emEuoG6fPa.WADq', 'Queen Morgan', '2025-10-22', 0, 'parent'),
(15, 'Rogelia@gmail.com', '$2y$10$0xi5lZMYkLjVLMeJaBcPQeiTLFtPdAOCUKR96xr1CJX07c7OrpHou', 'Rogelia Santos', '2025-10-23', 0, 'parent'),
(16, 'Lourdes@gmail.com', '$2y$10$FDKlkETCOdSfruqWT8DAk.V9fkQTIne0eccfVHS6nqjqUqLHVITsO', 'Lourdes Dela Cruz', '2025-10-23', 0, 'parent'),
(17, 'Eduarda@gmail.com', '$2y$10$wKSDZNUxYY4LGSB6dv.2Xu7JI3nuZAHUgvltEcfkriXivTTqDiyQ.', 'Eduarda Villanueva', '2025-10-23', 0, 'parent'),
(18, 'Teresita@gmail.com', '$2y$10$kKxNHLBaZJ9ymUYprVyPve02F/.SFruM.rE2xJtKjhT6LoI/x6tCO', 'Teresita Ramos', '2025-10-23', 0, 'parent'),
(19, 'Antonia@gmail.com', '$2y$10$.2Synkbmrh1u5oS4SEige.SQHLD1LqdAkeoJmmyXpr.EGIqjy9l9y', 'Antonia Garcia', '2025-10-23', 0, 'parent'),
(20, 'Imelda@gmail.com', '$2y$10$H4.S.vnCjNYaXkXcg7rHs.Gc2Fgaaigfp1XWMlQiTgp4R7ySfOc/m', 'Imelda Navarro', '2025-10-23', 0, 'parent'),
(21, 'Benigna@gmail.com', '$2y$10$PjXGHxcFoIzOm326S8teduSP69ee4j7Xx5n3E6bpoAPApPjJGJpUe', 'Benigna Cruz', '2025-10-23', 0, 'parent'),
(22, 'Rowena@gmail.com', '$2y$10$uh7t7p0z.OIv2iAQW9jpl.Rq9/oHWpjttNaFccu7xe95CbGbCv1r2', 'Rowena Mendoza', '2025-10-23', 0, 'parent'),
(23, 'Ramona@gmail.com', '$2y$10$kVxgHTNiSay/AEAvjgzJy.gqAotKluQaAEMa9OTgsRjdgL7JvbJsq', 'Ramona Bautista', '2025-10-23', 0, 'parent'),
(24, 'Carmelita@gmail.com', '$2y$10$5.8wY02UFDO2RWTm6SmCEupBPDvpnTVKxyeAePfNKImUfgd/OZ4Fu', 'Carmelita Reyes', '2025-10-23', 0, 'parent'),
(25, 'andrew@gmail.com', '$2y$10$Y70NU0BS9DFKarD6rgQEhOvi5C9cFN1K.SEKSilaEKtjT2N847bsa', 'andrew villegas', '2025-10-24', 0, 'parent'),
(26, 'angelito@gmail', '$2y$10$PVv7fS2dySoKKSMQADzNzOQBcA3Lx7mVF3TkNHBlD3iWvu0eCrPwO', 'angelito cunanan', '2025-10-24', 0, 'parent'),
(27, 'justine@gmail.com', '$2y$10$zWr61Gqw0E.fgwmdoHC/2ebT9FklnFJq4.Uj/6dGnxq6f7cKOZ53y', 'Justine Sarmiento', '2025-10-26', 0, 'parent'),
(28, 'Noelizaann@gmail.com', '$2y$10$c/DL7ZciKySEmmei8ntnbuBbz39jUbz/YUl6a946ACqAsLuIir9Ti', 'Noeliza Bombio', '2025-10-26', 0, 'parent'),
(29, 'rayver@gmail.com', '$2y$10$KAPDBnHBHci69nHmC3pn3uIq9Kymy0xQ4uSwwqtwM8nptEXIBp7Ly', 'Rayver Viernes', '2025-10-26', 0, 'parent'),
(30, 'try@gmail.com', '$2y$10$tTagYjwoAtAq0LsGNdK0DeQqfkBHvgPJ9VeYZF8o3jwbEixG9nsm2', 'try', '2025-10-26', 0, 'healthworker'),
(46, 'sarmiento@gmail.com', '$2y$10$fhXISOZd2JjndR1ijW5qsej0mu0zdIRjYFwZhJDPjnsP9GdCA7JPe', 'Justine argon Sarmiento', '2025-10-28', 0, 'healthworker'),
(47, 'ann@gmail.com', '$2y$10$1v55fsnlrjdX07dqgdWsJeX9Sus6fs4BaMjxCTNPDn1sS8kbIbczS', 'Noeliza Angeles Bombio', '2025-10-28', 0, 'healthworker'),
(48, 'jessy@gmail.com', '$2y$10$0M2QdIvd309AWIuNXDdPvOcY3YHHWjzKreGGt2z42V9on9PbZNoie', 'Jessy Bombio', '2025-10-28', 0, 'parent');

-- --------------------------------------------------------

--
-- Table structure for table `user_logins`
--

CREATE TABLE `user_logins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `success` tinyint(1) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_logins`
--

INSERT INTO `user_logins` (`id`, `user_id`, `email`, `ip_address`, `success`, `reason`, `timestamp`) VALUES
(1, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-23 12:44:53'),
(2, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-23 14:26:03'),
(3, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-23 17:13:44'),
(4, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-23 18:06:28'),
(5, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-23 18:26:37'),
(6, 9, 'nyxsarmiento@gmail.com', '::1', 1, 'Login successful', '2025-10-23 18:49:41'),
(7, 15, 'Rogelia@gmail.com', '::1', 1, 'Login successful', '2025-10-23 18:53:32'),
(8, 15, 'Rogelia@gmail.com', '::1', 1, 'Login successful', '2025-10-23 19:44:00'),
(9, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-24 11:47:33'),
(10, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-24 12:20:23'),
(11, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-24 14:06:55'),
(12, 2, 'healthworker@gmail.com', '::1', 1, 'Login successful', '2025-10-24 14:11:56'),
(13, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-24 14:12:56'),
(14, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-24 14:33:53'),
(15, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-24 14:46:39'),
(16, 25, 'andrew@gmail.com', '::1', 1, 'Login successful', '2025-10-24 14:47:40'),
(17, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-24 15:18:14'),
(18, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-24 21:03:42'),
(19, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-26 19:14:31'),
(20, 30, 'try@gmail.com', '::1', 1, 'Login successful', '2025-10-26 22:58:53'),
(21, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-27 19:56:18'),
(22, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-27 21:23:13'),
(23, 46, 'sarmiento@gmail.com', '::1', 1, 'Login successful', '2025-10-28 00:37:16'),
(24, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-28 00:37:43'),
(25, 46, 'sarmiento@gmail.com', '::1', 1, 'Login successful', '2025-10-28 00:58:00'),
(26, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-28 01:04:05'),
(27, 47, 'ann@gmail.com', '::1', 1, 'Login successful', '2025-10-28 01:12:31'),
(28, 46, 'sarmiento@gmail.com', '::1', 1, 'Login successful', '2025-10-28 01:53:45'),
(29, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-28 14:14:33'),
(30, 46, 'sarmiento@gmail.com', '::1', 1, 'Login successful', '2025-10-28 14:37:36'),
(31, 1, 'admin@gmail.com', '::1', 1, 'Login successful', '2025-10-28 15:16:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity` (`entity_table`,`entity_id`),
  ADD KEY `idx_user` (`user_id`,`created_at`);

--
-- Indexes for table `growth_reference`
--
ALTER TABLE `growth_reference`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `healthworker`
--
ALTER TABLE `healthworker`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `infantinfo`
--
ALTER TABLE `infantinfo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_child_parent` (`parent_id`);

--
-- Indexes for table `infant_previous_records`
--
ALTER TABLE `infant_previous_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `infant_previous_records_ibfk_1` (`infant_id`);

--
-- Indexes for table `laboratory`
--
ALTER TABLE `laboratory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_queue`
--
ALTER TABLE `sms_queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_vacc` (`vacc_id`),
  ADD KEY `idx_infant` (`infant_id`);

--
-- Indexes for table `tbl_report_logs`
--
ALTER TABLE `tbl_report_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_logs_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `tbl_vaccination_details`
--
ALTER TABLE `tbl_vaccination_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tbl_vaccination_details_ibfk_1` (`infant_id`);

--
-- Indexes for table `tbl_vaccination_schedule`
--
ALTER TABLE `tbl_vaccination_schedule`
  ADD PRIMARY KEY (`vacc_id`),
  ADD KEY `tbl_vaccination_schedule_ibfk_1` (`infant_id`);

--
-- Indexes for table `tbl_vaccine_reference`
--
ALTER TABLE `tbl_vaccine_reference`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `growth_reference`
--
ALTER TABLE `growth_reference`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `healthworker`
--
ALTER TABLE `healthworker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `infantinfo`
--
ALTER TABLE `infantinfo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `infant_previous_records`
--
ALTER TABLE `infant_previous_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `laboratory`
--
ALTER TABLE `laboratory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sms_queue`
--
ALTER TABLE `sms_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `tbl_report_logs`
--
ALTER TABLE `tbl_report_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `tbl_vaccination_details`
--
ALTER TABLE `tbl_vaccination_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `tbl_vaccination_schedule`
--
ALTER TABLE `tbl_vaccination_schedule`
  MODIFY `vacc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `tbl_vaccine_reference`
--
ALTER TABLE `tbl_vaccine_reference`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `user_logins`
--
ALTER TABLE `user_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `healthworker`
--
ALTER TABLE `healthworker`
  ADD CONSTRAINT `healthworker_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `infantinfo`
--
ALTER TABLE `infantinfo`
  ADD CONSTRAINT `fk_child_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `infant_previous_records`
--
ALTER TABLE `infant_previous_records`
  ADD CONSTRAINT `infant_previous_records_ibfk_1` FOREIGN KEY (`infant_id`) REFERENCES `infantinfo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_report_logs`
--
ALTER TABLE `tbl_report_logs`
  ADD CONSTRAINT `fk_report_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_vaccination_details`
--
ALTER TABLE `tbl_vaccination_details`
  ADD CONSTRAINT `tbl_vaccination_details_ibfk_1` FOREIGN KEY (`infant_id`) REFERENCES `infantinfo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_vaccination_schedule`
--
ALTER TABLE `tbl_vaccination_schedule`
  ADD CONSTRAINT `tbl_vaccination_schedule_ibfk_1` FOREIGN KEY (`infant_id`) REFERENCES `infantinfo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
