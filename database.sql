-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 03, 2026 at 10:33 AM
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
-- Database: `hazinafunding`
--

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `donor_name` varchar(150) DEFAULT 'Anonymous',
  `donor_email` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `user_id`, `project_id`, `amount`, `donor_name`, `donor_email`, `message`, `status`, `created_at`) VALUES
(1, 1, 1, 20.00, 'Thony', 'tonnybash5@gmail.com', '10bale', 'completed', '2026-06-26 15:19:20'),
(2, 1, 1, 100.00, 'Thony', 'tonnybash5@gmail.com', 'Aider tous les enfants', 'completed', '2026-06-26 18:51:10'),
(3, 1, 1, 100.00, 'Anonymous', '', '', 'completed', '2026-06-27 10:11:35'),
(4, 1, 2, 50.00, 'Anonymous', '', '', 'completed', '2026-06-27 10:34:26'),
(5, 1, 1, 200.00, 'Anonymous', '', '', 'completed', '2026-06-27 11:29:08'),
(6, 1, 2, 50.00, 'Anonymous', '', '', 'completed', '2026-06-27 13:09:25'),
(7, 1, 1, 200.00, 'Anonymous', '', '', 'completed', '2026-06-27 13:10:14');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `goal_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `raised_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `description`, `goal_amount`, `raised_amount`, `image`, `category`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Aide aux deplacés de EST', 'Aide aux deplacés de EST Aide aux deplacés de EST Aide aux deplacés de EST ', 10000.00, 620.00, '/uploads/img_6a3eccac186c03.79762075.jpg', 'Money', 'active', '2026-06-23 13:43:02', '2026-06-27 13:10:14'),
(2, 'test', 'test ewfsefv vswvwsv sgsef test ewfsefv vswvwsv sgsef test ewfsefv vswvwsv sgsef', 3000.00, 100.00, '/uploads/8e48046fda3ed2fb388d195c0ffb948232ed8ad7_5071_cropped.jpg', 'test', 'active', '2026-06-27 10:21:55', '2026-06-27 13:14:51'),
(3, 'TEST RSTV', 'Contruction route EContruction route EContruction route EContruction route E', 1000.00, 0.00, 'assets/images/img_6a463e90735d92.00733618.jpg', 'Contruction route E', 'active', '2026-07-02 10:33:52', '2026-07-02 10:33:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `mail` varchar(150) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `adresse` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `mail`, `telephone`, `adresse`, `password`, `photo`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@hazina.com', '+00000000', 'Admin HQ', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'admin', '2026-06-19 12:44:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `mail` (`mail`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
