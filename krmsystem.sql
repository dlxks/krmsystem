-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 20, 2025 at 02:43 AM
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
-- Database: `krmsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `location` varchar(255) NOT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `license_image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `password`, `email`, `phone`, `location`, `profile_photo_path`, `license_image_path`) VALUES
(1, 'admin', '$2y$10$6xrkpe.ieuBM27SFk6hKlu2nuoViDXhnuruhcE15IdTKJLDkmDqHe', 'admin@gmail.com', '09751563360', 'nia road 1', '6853e1a443fcb_death13.png', '6853e1ab75082_VI.png');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `make` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `year` int(11) NOT NULL,
  `color` varchar(255) NOT NULL,
  `engine` varchar(255) NOT NULL,
  `transmission` varchar(255) NOT NULL,
  `fuel_economy` varchar(255) NOT NULL,
  `seating_capacity` varchar(255) NOT NULL,
  `safety_features` text NOT NULL,
  `additional_features` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `status` enum('available','rented','maintenance','unavailable') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `make`, `model`, `year`, `color`, `engine`, `transmission`, `fuel_economy`, `seating_capacity`, `safety_features`, `additional_features`, `price`, `image_path`, `status`) VALUES
(13, 'Mitsubishi', 'Mirage G4', 2023, 'Silver', '1.2-liter MIVEC DOHC 3-cylinder', 'Continuously Variable Transmission (CVT)', '37 combined mpg', '5', 'Anti-lock Braking System, Front SRS airbags, 3P ELR with Pretensioner front seat belts, 3P ELR x2 and Lapbelt x1 rear seat belts', 'Cruise control, ECO indicator, Bluetooth wireless technology', 2500.00, 'mitsubishi-mirage.avif', 'rented'),
(14, 'Toyota', 'Hiace', 2023, 'White', '3.0L Diesel Engine (2,982 cc), 4-cylinder, 16-valve DOHC', '5-speed Manual', '12.5 km/L', '15', 'Dual front airbags (driver and passenger), Anti-Lock Breaking Lock System, Front seatbelts are 3-point ELR (Extended-Length Retractor) with pretensioners. Rear seatbelts are 2-point NR (Non-Retractable)', 'Air conditioning, power steering, power windows, folding rear seats, adjustable seats', 2500.00, 'toyota-hiace.webp', 'available'),
(15, 'Toyota', 'Innova', 2022, 'Red Mica Metallic', '1GD-FTV (diesel)', '6-speed Automatic', '15.5 km/L', '8', 'Driver + Front Passenger + Knee Airbags, Vehicle Stability Control (VSC), Hill-Start Assist Control (HSA), Brake Assist + EBD', 'Multi-Reflector Halogen Headlights, Reverse Camera, Apple CarPlay + Android Auto', 2500.00, 'toyota-innova-2022.webp', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `driver_license_number` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `messenger_name` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `driver_license_image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `password`, `driver_license_number`, `address`, `messenger_name`, `phone_number`, `profile_photo_path`, `driver_license_image_path`) VALUES
(7, 'jan tagalog', 'jantagalog05@gmail.com', '', '09239948849', 'nia roadd', 'jan', '09164475475', NULL, NULL),
(8, 'karlo', 'admin@barangay.gov', '', '123456789872', 'sangab', 'jan', '09164452633', NULL, NULL),
(9, 'Corregidor', 'ahhhhhhhhh@gmail.com', '', '6746734874', 'nia road sanjuan', 'mia roads', '09456123789', NULL, NULL),
(10, 'Juan', 'juan@gmail.com', '', 'asd122', '123ligtong', 'Juan JJ', '09123456789', NULL, NULL),
(11, 'Jei Gonzales', 'jei.gon@gmail.com', '', '123asd', '123 Ligtong, Rosario, Cavite', 'Jei Gonzales', '09231124456', NULL, NULL),
(12, 'JJ Joestar', 'jj@gmail.com', '', '1334asf', '123 Ligtong', 'JJ Joestar', '09123456789', NULL, NULL),
(13, 'aa', 'aa@gmail.com', '', '1214bmws', 'da3', 'Aa', '09089596523', NULL, NULL),
(14, 'ab', 'ab@gmail.com', '', '1412bmwg', 'tan', 'Ab', '09293548763', NULL, NULL),
(15, 'arcylunas', 'arcy@gmail.com', '', '1214basd', 'DA', 'arcy', '09089596523', NULL, NULL),
(16, 'janessa mariel samonte cruz', 'janessa@gmail.com', '', '1214asd', 'gentri, cavite', 'Janessa Cruz', '09391187259', NULL, NULL),
(17, '', '', '$2y$10$viMzluSdbA2aDJJNGQ5xPe6Eod4N10w6xU2B5Fi0HqGOQCDFTpB4O', '', 'asd', '', '', NULL, NULL),
(18, 'Tristan', 'sangangbayant@gmail.com', '$2y$10$uNdvV9IVBphMCgpsD6C.JO6PHJw0gXdZmxkZVMdxQ4vtn2Lmwl1s6', 'asd', 'harasan indang', 'tristan', '09072203267', NULL, NULL),
(20, 'tristan', 'asd@asd.com', '$2y$10$/SqMWqaEKQNb4Da2H3/QVes5oWJF33/mboRoTOGRzhgJriaEtiYtm', 'asddd', 'asdasdad', 'asd', '09123142345', NULL, NULL),
(22, 'Tristan', 'sangangbayanst@gmail.com', '', 'asdasd', 'harasan indang', 'tristan s', '09558076388', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `car_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comments` text NOT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `reservation_id`, `customer_id`, `name`, `phone_number`, `car_id`, `rating`, `comments`, `status`, `created_at`) VALUES
(2, 0, 0, 'yuki', '09164475181', NULL, 5, '                       aaa ', 'pending', '2025-06-11 03:03:51'),
(3, 0, 0, 'john', '09201898949', 13, 4, '                        nice car', 'pending', '2025-06-18 07:01:23'),
(4, 0, 0, 'jj', '09201898944', 14, 5, '                        ', 'pending', '2025-06-18 07:01:57'),
(7, 39, 20, 'Tristan', '09072203267', 14, 4, 'good', 'completed', '2025-06-19 22:42:59'),
(8, 39, 20, '', '', 14, 0, '', 'pending', '2025-06-20 00:43:13'),
(9, 40, 20, '', '', 15, 0, '', 'pending', '2025-06-20 00:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `status` enum('reserved','pending','completed','cancelled','in-route') NOT NULL,
  `pickup_date` date NOT NULL,
  `return_date` date NOT NULL,
  `passenger_count` int(11) NOT NULL,
  `accommodations` text NOT NULL,
  `special_requests` text NOT NULL,
  `pickup_location` varchar(255) NOT NULL,
  `estimated_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `car_id`, `customer_id`, `status`, `pickup_date`, `return_date`, `passenger_count`, `accommodations`, `special_requests`, `pickup_location`, `estimated_price`, `created_at`) VALUES
(24, 14, 11, 'completed', '2025-06-18', '2025-06-21', 5, '', '', 'Rosario, Cavite', 0.00, '2025-06-18 04:54:41'),
(26, 13, 12, 'in-route', '2025-06-18', '2025-06-21', 6, '', '', 'Rosario, Cavite', 0.00, '2025-06-18 06:11:58'),
(27, 13, 13, 'reserved', '2025-06-20', '2025-06-22', 4, '', 'child seat', 'gentri', 0.00, '2025-06-18 06:39:08'),
(28, 14, 14, 'completed', '2025-06-19', '2025-06-21', 6, 'in town', 'entertainment system', 'sm tanza', 0.00, '2025-06-18 06:43:27'),
(29, 15, 15, 'completed', '2025-06-20', '2025-06-25', 4, '', 'child seat', 'rosario, cavite', 0.00, '2025-06-18 06:55:53'),
(39, 14, 20, 'completed', '2025-06-20', '2025-06-21', 10, 'asd test', 'test', 'indang', 2500.00, '2025-06-19 22:16:43'),
(40, 15, 20, 'completed', '2025-06-20', '2025-06-21', 4, '3', '3', 'asd', 2500.00, '2025-06-20 00:42:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD UNIQUE KEY `driver_license_number` (`driver_license_number`),
  ADD UNIQUE KEY `driver_license_number_2` (`driver_license_number`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservations_ibfk_1` (`car_id`),
  ADD KEY `reservations_ibfk_2` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
