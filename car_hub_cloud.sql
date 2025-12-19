
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


--
-- Database: `car_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

DROP TABLE IF EXISTS `cars`;
CREATE TABLE IF NOT EXISTS `cars` (
  `car_id` int NOT NULL AUTO_INCREMENT,
  `seller_id` int NOT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `mileage` int DEFAULT NULL,
  `condition` enum('new','used','refurbished') COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_type` enum('sedan','suv','coupe','hatchback','truck','van','convertible','wagon') COLLATE utf8mb4_unicode_ci NOT NULL,
  `doors` int DEFAULT NULL,
  `transmission` enum('manual','automatic','semi-automatic') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fuel_type` enum('petrol','diesel','electric','hybrid') COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `engine_size` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('available','pending','sold','removed') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `approved_by` int DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approval_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`car_id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_car_seller` (`seller_id`),
  KEY `idx_car_status` (`status`),
  KEY `idx_car_approval_status` (`approval_status`),
  KEY `idx_car_brand_model` (`brand`,`model`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`car_id`, `seller_id`, `brand`, `model`, `year`, `price`, `mileage`, `condition`, `body_type`, `doors`, `transmission`, `fuel_type`, `color`, `engine_size`, `description`, `status`, `approved_by`, `approval_status`, `approval_date`, `created_at`, `updated_at`) VALUES
(1, 6, 'Benz', 'C300', 2024, 12345.00, 0, 'new', 'truck', NULL, 'automatic', 'electric', 'Blue', '1.5', 'Wazaaaaahhhhhhhh', 'sold', 10, 'approved', '2025-12-12 20:51:03', '2025-12-10 00:39:44', '2025-12-12 20:53:47'),
(2, 6, 'Porsche', '911 Carrera', 2018, 950000.00, 38000, 'used', 'coupe', NULL, 'automatic', 'petrol', 'Jet Black Metallic', '3.0L Twin-Turbo Flat-6', 'Well-maintained Porsche 911 Carrera in excellent condition. Powered by a 3.0L twin-turbo flat-six engine delivering smooth yet powerful performance. The car features a premium leather interior, adaptive suspension, rear-engine balance, and Porsche Stability Management for confident handling.\r\n\r\nEquipped with automatic transmission, responsive steering, and a refined exhaust note. Driven responsibly with full service history available. Ideal for both daily driving and spirited performance.', 'available', 10, 'approved', '2025-12-13 08:15:10', '2025-12-12 21:02:01', '2025-12-14 19:18:06'),
(3, 6, 'Porsche', '911 Carrera', 2018, 950000.00, 38000, 'used', 'coupe', NULL, 'automatic', 'petrol', 'Jet Black Metallic', '3.0L Twin-Turbo Flat-6', 'Well-maintained Porsche 911 Carrera in excellent condition. Powered by a 3.0L twin-turbo flat-six engine delivering smooth yet powerful performance. The car features a premium leather interior, adaptive suspension, rear-engine balance, and Porsche Stability Management for confident handling.\r\n\r\nEquipped with automatic transmission, responsive steering, and a refined exhaust note. Driven responsibly with full service history available. Ideal for both daily driving and spirited performance.', 'available', 10, 'approved', '2025-12-13 08:15:09', '2025-12-12 21:20:00', '2025-12-14 19:18:06'),
(4, 6, 'Porsche', '911 Carrera', 2018, 950000.00, 38000, 'used', 'coupe', NULL, 'automatic', 'petrol', 'Jet Black Metallic', '3.0L Twin-Turbo Flat-6', 'Well-maintained Porsche 911 Carrera in excellent condition. Powered by a 3.0L twin-turbo flat-six engine delivering smooth yet powerful performance. The car features a premium leather interior, adaptive suspension, rear-engine balance, and Porsche Stability Management for confident handling.\r\n\r\nEquipped with automatic transmission, responsive steering, and a refined exhaust note. Driven responsibly with full service history available. Ideal for both daily driving and spirited performance.', 'sold', 10, 'approved', '2025-12-13 08:15:08', '2025-12-12 21:20:14', '2025-12-14 19:18:41'),
(5, 6, 'Porsche', '911 Carrera', 2018, 950000.00, 38000, 'used', 'coupe', NULL, 'automatic', 'petrol', 'Jet Black Metallic', '3.0L Twin-Turbo Flat-6', 'Well-maintained Porsche 911 Carrera in excellent condition. Powered by a 3.0L twin-turbo flat-six engine delivering smooth yet powerful performance. The car features a premium leather interior, adaptive suspension, rear-engine balance, and Porsche Stability Management for confident handling.\r\n\r\nEquipped with automatic transmission, responsive steering, and a refined exhaust note. Driven responsibly with full service history available. Ideal for both daily driving and spirited performance.', 'sold', 10, 'approved', '2025-12-12 21:21:30', '2025-12-12 21:20:23', '2025-12-12 21:27:17'),
(6, 13, 'Nobert Bwuoy', 'qqq', 2000, 20000.00, 12, 'new', 'sedan', NULL, 'automatic', 'petrol', 'White', '1.8L', 'qwedewqw', 'available', 10, 'approved', '2025-12-13 08:27:11', '2025-12-13 08:12:27', '2025-12-13 08:27:11');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE IF NOT EXISTS `cart_items` (
  `cart_item_id` int NOT NULL AUTO_INCREMENT,
  `cart_id` int NOT NULL,
  `item_type` enum('car','spare_part') COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_item_id`),
  UNIQUE KEY `unique_cart_item` (`cart_id`,`item_type`,`item_id`),
  KEY `idx_cart_item` (`cart_id`),
  KEY `idx_item_reference` (`item_type`,`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

DROP TABLE IF EXISTS `email_verifications`;
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `code` varchar(6) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_code` (`code`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `code`, `expires_at`, `is_used`, `created_at`) VALUES
(3, 6, '283813', '2025-12-06 19:59:02', 1, '2025-12-06 19:58:12'),
(4, 7, '371177', '2025-12-10 23:52:39', 1, '2025-12-10 23:51:53'),
(5, 11, '138190', '2025-12-13 20:28:26', 0, '2025-12-12 19:28:26'),
(6, 12, '088852', '2025-12-14 08:08:33', 0, '2025-12-13 08:08:33'),
(7, 13, '869496', '2025-12-13 08:10:15', 1, '2025-12-13 08:09:15');

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
CREATE TABLE IF NOT EXISTS `images` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `imageable_type` enum('car','spare_part','user') COLLATE utf8mb4_unicode_ci NOT NULL,
  `imageable_id` int NOT NULL,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `display_order` int DEFAULT '0',
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `idx_imageable` (`imageable_type`,`imageable_id`),
  KEY `idx_primary_image` (`imageable_type`,`imageable_id`,`is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `buyer_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` enum('pending_payment','paid','pending','processing','shipped','delivered','cancelled','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_payment',
  `shipping_address` text COLLATE utf8mb4_unicode_ci,
  `shipping_city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  KEY `idx_order_buyer` (`buyer_id`),
  KEY `idx_order_status` (`order_status`),
  KEY `idx_order_date` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `buyer_id`, `total_amount`, `order_status`, `shipping_address`, `shipping_city`, `shipping_country`, `created_at`, `updated_at`) VALUES
(1, 10, 12345.00, 'pending_payment', 'acccra', 'lol', 'Ghana', '2025-12-12 20:53:47', '2025-12-12 20:53:47'),
(2, 7, 950000.00, 'pending_payment', 'pop', 'asdfg', 'Ghana', '2025-12-12 21:27:17', '2025-12-12 21:27:17'),
(6, 13, 2468.00, 'pending_payment', 'Wazaah', 'Wazaah', 'Ghana', '2025-12-13 08:35:55', '2025-12-13 08:35:55'),
(7, 13, 2468.00, 'pending_payment', 'Wazaah', 'Wazaah', 'Ghana', '2025-12-13 08:36:39', '2025-12-13 08:36:39'),
(8, 13, 2468.00, 'pending_payment', 'Wazaah', 'Wazaah', 'Ghana', '2025-12-13 08:47:32', '2025-12-13 08:47:32'),
(9, 13, 1234.00, 'pending_payment', 'Wazaah', 'Wazaah', 'Ghana', '2025-12-13 08:53:02', '2025-12-13 08:53:02'),
(10, 13, 1234.00, 'paid', 'Wazaah', 'Wazaah', 'Ghana', '2025-12-13 09:12:52', '2025-12-13 09:27:00'),
(11, 13, 1234.00, 'paid', 'Wazaah', 'Wazaah', 'Ghana', '2025-12-13 09:28:03', '2025-12-13 09:28:11'),
(13, 13, 950000.00, 'paid', 'Wazaah', 'Wazaah', 'Ghana', '2025-12-14 19:18:41', '2025-12-14 19:18:54');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `item_type` enum('car','spare_part') COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `idx_order_item` (`order_id`),
  KEY `idx_order_seller` (`seller_id`),
  KEY `idx_item_reference` (`item_type`,`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `seller_id`, `item_type`, `item_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 1, 6, 'car', 1, 1, 12345.00, 12345.00),
(2, 2, 6, 'car', 5, 1, 950000.00, 950000.00),
(6, 6, 6, 'spare_part', 1, 1, 1234.00, 1234.00),
(7, 6, 6, 'spare_part', 2, 1, 1234.00, 1234.00),
(8, 7, 6, 'spare_part', 1, 1, 1234.00, 1234.00),
(9, 7, 6, 'spare_part', 2, 1, 1234.00, 1234.00),
(10, 8, 6, 'spare_part', 1, 1, 1234.00, 1234.00),
(11, 8, 6, 'spare_part', 2, 1, 1234.00, 1234.00),
(12, 9, 6, 'spare_part', 2, 1, 1234.00, 1234.00),
(13, 10, 6, 'spare_part', 2, 1, 1234.00, 1234.00),
(14, 11, 6, 'spare_part', 1, 1, 1234.00, 1234.00),
(16, 13, 6, 'car', 4, 1, 950000.00, 950000.00);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `code` varchar(6) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_code` (`code`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `reviewer_id` int NOT NULL,
  `reviewee_id` int NOT NULL,
  `order_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `unique_review_per_order` (`reviewer_id`,`order_id`),
  KEY `idx_review_reviewer` (`reviewer_id`),
  KEY `idx_review_reviewee` (`reviewee_id`),
  KEY `idx_review_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shopping_cart`
--

DROP TABLE IF EXISTS `shopping_cart`;
CREATE TABLE IF NOT EXISTS `shopping_cart` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_cart_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shopping_cart`
--

INSERT INTO `shopping_cart` (`cart_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 7, '2025-12-11 00:11:28', '2025-12-11 00:11:28'),
(2, 6, '2025-12-11 22:27:35', '2025-12-11 22:27:35'),
(3, 10, '2025-12-12 20:51:17', '2025-12-12 20:51:17'),
(4, 13, '2025-12-13 08:10:59', '2025-12-13 08:10:59');

-- --------------------------------------------------------

--
-- Table structure for table `spare_parts`
--

DROP TABLE IF EXISTS `spare_parts`;
CREATE TABLE IF NOT EXISTS `spare_parts` (
  `spare_part_id` int NOT NULL AUTO_INCREMENT,
  `seller_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('engine','transmission','brakes','suspension','electrical','body','interior','exhaust','cooling','fuel_system','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `compatible_brands` text COLLATE utf8mb4_unicode_ci,
  `compatible_models` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `condition` enum('new','used','refurbished') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('available','out_of_stock','removed') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `approved_by` int DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approval_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`spare_part_id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_spare_part_seller` (`seller_id`),
  KEY `idx_spare_part_status` (`status`),
  KEY `idx_spare_part_category` (`category`),
  KEY `idx_spare_part_approval_status` (`approval_status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `spare_parts`
--

INSERT INTO `spare_parts` (`spare_part_id`, `seller_id`, `name`, `category`, `compatible_brands`, `compatible_models`, `price`, `condition`, `quantity`, `description`, `status`, `approved_by`, `approval_status`, `approval_date`, `created_at`, `updated_at`) VALUES
(1, 6, 'Transmission', 'transmission', 'Benz', '2017', 1234.00, 'new', 8, 'Wazaah', 'available', 10, 'approved', '2025-12-13 08:27:33', '2025-12-10 00:50:10', '2025-12-13 09:28:03'),
(2, 6, 'Transmission', 'transmission', 'Benz', '2017', 1234.00, 'new', 118, 'Wazaah', 'available', 10, 'approved', '2025-12-13 08:27:32', '2025-12-10 00:52:25', '2025-12-13 09:12:52');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `paystack_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'NGN',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('pending','successful','failed','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `transaction_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metadata` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `paystack_reference` (`paystack_reference`),
  KEY `idx_transaction_order` (`order_id`),
  KEY `idx_transaction_user` (`user_id`),
  KEY `idx_transaction_reference` (`paystack_reference`),
  KEY `idx_transaction_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('buyer','seller','admin','buyer_seller') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'buyer',
  `profile_image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_user_email` (`email`),
  KEY `idx_user_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `first_name`, `last_name`, `phone_number`, `role`, `profile_image_url`, `address`, `city`, `country`, `is_active`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 'admin@carhub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', '+233206178455', 'admin', NULL, NULL, NULL, NULL, 1, 1, '2025-12-06 14:26:33', '2025-12-12 12:08:57'),
(6, 'opurchase57@gmail.com', '$2y$10$N.00X/oT2dJIVIU.11/PHO9iVuPmBtdjH9zlFT4uQIj7OBhlxk4jm', 'Test', 'User', '0207739027', 'seller', NULL, NULL, NULL, NULL, 1, 1, '2025-12-06 19:58:12', '2025-12-10 00:02:34'),
(7, 'amoatengmichelle@gmail.com', '$2y$10$LuM1TAX37Xhv4ndYButPo.DflF2v/tFLI50GaW4qYhXwnzyL5df2O', 'Michelle', 'Amoateng', '0206178455', 'buyer', NULL, NULL, NULL, NULL, 1, 1, '2025-12-10 23:51:53', '2025-12-10 23:59:54'),
(10, 'amaamoateng4@gmail.com', '$2y$10$KP04XIPqKMvOREx5o.WxpuBiJ6EEg5iFPQxiXtV5zxF83dEv95c9m', 'Ama', 'Admin', '0206178455', 'admin', NULL, NULL, NULL, NULL, 1, 1, '2025-12-12 12:35:46', '2025-12-12 13:12:27'),
(11, 'prince.baah@ashesi.edu.gh', '$2y$10$LnU9JqPKFc940QXmKQG2qekY7X7pi0Wmsu.mCmNSnCqM8Xs1eDCTG', 'Prince', 'Mensah', '0593420602', 'buyer', NULL, NULL, NULL, NULL, 1, 0, '2025-12-12 19:28:26', '2025-12-12 19:28:26'),
(12, 'clarkkent@gmail.com', '$2y$10$1/ln1TvQtf29FGLZLdtD1OOtwZHRrqu3Kht2tzyKgHk7H6o8GNlom', 'Test', 'User', '0207739027', 'buyer_seller', NULL, NULL, NULL, NULL, 1, 0, '2025-12-13 08:08:33', '2025-12-13 08:08:33'),
(13, 'warmspeaker1@gmail.com', '$2y$10$//RFKpTDgwexsCyp6fThceVfbm4dLgazBkcaZj/2GQHRaJILOrJnu', 'Test', 'User', '0207739027', 'buyer', NULL, NULL, NULL, NULL, 1, 1, '2025-12-13 08:09:15', '2025-12-13 08:15:24');

-- --------------------------------------------------------

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cars_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `shopping_cart` (`cart_id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `spare_parts`
--
ALTER TABLE `spare_parts`
  ADD CONSTRAINT `spare_parts_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `spare_parts_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

