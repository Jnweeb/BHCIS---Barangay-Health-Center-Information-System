-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 03:43 PM
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
-- Database: `bhcis`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `status` enum('Pending','Completed','Cancelled') DEFAULT 'Pending',
  `service` enum('Childcare','Maternal','Dental','Checkups','Other') NOT NULL DEFAULT 'Other'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `appointment_date`, `status`, `service`) VALUES
(1, 1, '2025-11-15 13:30:00', 'Pending', 'Dental'),
(3, 2, '2025-11-21 10:01:00', 'Pending', 'Maternal'),
(4, 7, '2025-11-16 13:31:00', 'Pending', 'Maternal'),
(5, 4, '2025-11-25 13:31:00', 'Cancelled', 'Checkups'),
(6, 6, '2025-11-23 14:04:00', 'Completed', 'Childcare'),
(7, 5, '2025-11-29 11:30:00', 'Pending', 'Checkups'),
(8, 5, '2025-11-27 07:35:00', 'Pending', 'Dental'),
(9, 3, '2025-11-26 07:42:00', 'Pending', 'Checkups');

-- --------------------------------------------------------

--
-- Table structure for table `immunizations`
--

CREATE TABLE `immunizations` (
  `immunization_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `vaccine` varchar(100) NOT NULL,
  `dose` varchar(50) NOT NULL,
  `date_administered` date NOT NULL,
  `status` enum('Pending','Completed','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `immunizations`
--

INSERT INTO `immunizations` (`immunization_id`, `patient_id`, `vaccine`, `dose`, `date_administered`, `status`) VALUES
(1, 1, 'AlphaOmega', '10mg', '2025-11-13', 'Completed'),
(3, 5, 'AntiFlu', '10ml', '2025-11-15', 'Completed'),
(4, 6, 'AntiFlu', '', '2025-11-20', 'Pending'),
(5, 4, 'AlphaOmega', '', '2025-11-25', 'Pending'),
(6, 2, 'AntiFlu', '', '2025-11-29', 'Pending'),
(7, 1, 'AntiFlu', '', '2025-11-29', 'Completed');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Available','Low Stock','Out of Stock') DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`item_id`, `item_name`, `category`, `quantity`, `unit`, `expiry_date`, `status`) VALUES
(1, 'Neozep', 'Medicine', 5, 'packs', '2026-05-25', 'Available'),
(3, 'Salbutamol', 'Medicine', 10, 'tablets', '2026-08-29', 'Available'),
(4, 'lagundi', 'Medicine', 6, 'bottles', '2025-02-26', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `health_service` enum('childcare','maternal','dental','checkups','other') DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `date_registered` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `fullname`, `birthdate`, `health_service`, `gender`, `address`, `contact`, `date_registered`) VALUES
(1, 'Jan Lloyd Blanco', '2004-02-29', 'dental', 'Male', 'Tinambacan Norte', '09452485647', '2025-11-13 07:19:46'),
(2, 'Ashlee Contacto', '2004-11-25', 'maternal', 'Female', 'Malajog', '09999999090', '2025-11-13 07:53:30'),
(3, 'Virginia Blanco', '1974-12-20', 'checkups', 'Female', 'Tinambacan Norte', '09151735318', '2025-11-13 07:57:34'),
(4, 'Sotero Blanco', '1973-01-01', 'checkups', 'Male', 'Tinambacan Norte', '099989800099', '2025-11-13 08:01:21'),
(5, 'Jamie Ann Valley', '2006-05-11', 'checkups', 'Female', 'Tinambacan Norte', '099999999999999', '2025-11-13 08:02:00'),
(6, 'Trisha Kate Valley', '2006-11-11', 'childcare', 'Female', 'Tinambacan Norte', '099989800099', '2025-11-13 08:02:32'),
(7, 'John Jeff Dublin', '2003-12-01', 'maternal', 'Male', 'Tinambacan Sur', '0999989879898', '2025-11-13 10:50:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('Admin','HealthWorker','Midwife','Nurse') DEFAULT 'HealthWorker',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `fullname`, `username`, `password`, `role`, `created_at`, `status`) VALUES
(2, 'Administrator', 'admin', '$2y$10$w8SCwNHD0GWfN.a0VflzruupCH0OzGsbZp2IzuyqovUOt6QVsEAbC', 'Admin', '2025-11-12 08:45:48', 'active'),
(3, 'Jan Lloyd Blanco', 'BlancoJanlloyd', '$2y$10$LYg0pJLV0fqaHqCnR/epR.9Pgt67tQ6UArGCfAWB0u2TGo93Gqlgi', 'HealthWorker', '2025-11-13 08:25:45', 'active'),
(4, 'Ashlee Contacto', 'Lovely', '$2y$10$k4Ap7ACANQs.Q6SpjkoOA.OJAWZukITgo3KXpFmjLC3.mpEH25ppq', 'Nurse', '2025-11-13 13:01:10', 'active'),
(5, 'Sotero Blanco', '22-01644', '$2y$10$EuZNoQVdZHugERmAdcSikeiGRQ2iwit/eAW8n2uK4CPSNSS1Nm.w.', 'HealthWorker', '2025-11-24 16:07:45', 'active'),
(6, 'Jamie Ann Valley', 'Jamie', '$2y$10$dx/yQmLsZ5NC3GSxrrCcIOrH577FI5jMYy2v0WZ./K8CJvSdjCAFO', 'Nurse', '2025-11-25 09:07:46', 'active'),
(7, 'Virginia Blanco', 'Virginia', '$2y$10$dk8VYrgzKQ9lQ3s6vIbD5u4l01yB7uJusTkhuwm2jm0XLEwd8SV7.', 'Nurse', '2025-11-25 09:08:05', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `immunizations`
--
ALTER TABLE `immunizations`
  ADD PRIMARY KEY (`immunization_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `immunizations`
--
ALTER TABLE `immunizations`
  MODIFY `immunization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `immunizations`
--
ALTER TABLE `immunizations`
  ADD CONSTRAINT `immunizations_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
