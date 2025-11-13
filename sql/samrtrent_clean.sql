-- phpMyAdmin SQL Dump clean version
-- Removed: all properties, messages, assignments, payments, activity logs
-- Kept: database structure and user data only

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Database: `samrtrent`

-- --------------------------------------------------------
-- Struktura tabeli dla tabeli `activity_logs`
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `meta` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktura tabeli dla tabeli `assignments`
CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `status` enum('pending','confirmed','ended') NOT NULL DEFAULT 'confirmed',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktura tabeli dla tabeli `maintenance_reports`
CREATE TABLE `maintenance_reports` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `reported_by` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktura tabeli dla tabeli `messages`
CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `body` text NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `read_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktura tabeli dla tabeli `payments`
CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('due','paid','overdue') NOT NULL DEFAULT 'due',
  `paid_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktura tabeli dla tabeli `properties`
CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `city` varchar(120) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktura tabeli dla tabeli `rentals`
CREATE TABLE `rentals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktura tabeli dla tabeli `site_settings`
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktura tabeli dla tabeli `support_tickets`
CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktura tabeli dla tabeli `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users` (zachowani użytkownicy)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(3, 'Mr_G', 'MATEUSZ.GWADERA109@GMAIL.COM', '$2y$10$kUVciV769F3UA2ypNECQQe7ZdhSKbHN4bUaMvBmte2VRJZe3ifxoO', 'admin', '2025-11-09 14:06:21'),
(4, 'Mateusz', 'jan@gmail.com', '$2y$10$Um2MCEh1yIe1aSkCmrFPh.PHWZdeJY3LBe1bXSYbftesVa1F6ZEtq', 'user', '2025-11-13 13:45:43');

-- --------------------------------------------------------
-- Indeksy dla zrzutów tabel

ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `actor_id` (`actor_id`),
  ADD KEY `created_at` (`created_at`);

ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `tenant_id` (`tenant_id`);

ALTER TABLE `maintenance_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `property_id` (`property_id`);

ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_user_id` (`from_user_id`),
  ADD KEY `to_user_id` (`to_user_id`);

ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`);

ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

ALTER TABLE `rentals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `property_id` (`property_id`);

ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

-- AUTO_INCREMENT for dumped tables
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `maintenance_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `rentals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- Constraints for dumped tables
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_fk_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_fk_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `maintenance_reports`
  ADD CONSTRAINT `maintenance_fk_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `maintenance_fk_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `payments`
  ADD CONSTRAINT `payments_fk_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;

ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `rentals`
  ADD CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rentals_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;