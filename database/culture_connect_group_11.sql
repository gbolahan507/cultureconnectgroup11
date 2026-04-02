-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2026 at 10:28 PM
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
-- Database: `culture_connect_group_11`
--

-- --------------------------------------------------------

--
-- Table structure for table `areas`
--

CREATE TABLE `areas` (
  `area_id` int(11) NOT NULL,
  `area_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `postcode` varchar(20) DEFAULT NULL,
  `latitude` decimal(9,6) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `areas`
--

INSERT INTO `areas` (`area_id`, `area_name`, `description`, `postcode`, `latitude`, `longitude`) VALUES
(1, 'Hertfordshire North', 'Famous for visual arts, including painting workshops, sculpture classes, and handcrafted ceramics by local artists', 'AL10 9NA', 51.763200, -0.238100),
(2, 'Hertfordshire South', 'Famous for music and performing arts, known for community theatre productions, live music concerts, and spoken word events', 'AL10 9SB', 51.748700, -0.240300),
(3, 'Hertfordshire East', 'Famous for creative media services including photography studios, videography services, and digital graphic design', 'AL10 0ED', 51.759900, -0.225600),
(4, 'Hertfordshire West', 'Famous for literature and publishing, known for independent books, poetry readings, magazines, and creative writing communities', 'AL10 0WB', 51.761500, -0.257400),
(5, 'Hertfordshire Town Centre', 'Known for cultural markets featuring handmade goods, artisan stationery, posters, and locally produced creative merchandise', 'AL10 0JT', 51.760600, -0.242300),
(6, 'Hertfordshire Central', 'Famous for sports and recreation, known for community sporting activities, cultural sporting events, and fitness programs', 'AL10 8HG', 51.765400, -0.220700);

-- --------------------------------------------------------

--
-- Table structure for table `listings`
--

CREATE TABLE `listings` (
  `listing_id` int(11) NOT NULL,
  `sme_id` int(11) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(10) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listings`
--

INSERT INTO `listings` (`listing_id`, `sme_id`, `title`, `description`, `category_id`, `price`, `type`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Handcrafted Adire Textile Wrap', 'Authentic hand-dyed Adire fabric made using traditional Yoruba techniques, suitable for fashion and home décor.', 2, 65.00, 'product', 'approved', '2026-03-30 15:06:50', '2026-03-30 17:06:50'),
(2, 2, 'Afrobeat & Cultural Dance Classes', 'Weekly Afrobeat and traditional African dance sessions for all skill levels in a welcoming studio environment.', 1, 25.00, 'service', 'approved', '2026-03-30 15:10:44', '2026-03-30 15:10:44'),
(3, 1, 'Hand-Carved Wooden Masks', 'Decorative wooden masks inspired by West African heritage, ideal for interior design and cultural collections.', 2, 120.00, 'product', 'pending', '2026-03-30 15:20:23', '2026-03-30 15:20:23'),
(4, 2, 'Children’s Cultural Dance Workshop', 'Interactive dance workshops for children aged 6–12 focusing on rhythm, movement, and cultural storytelling.', 1, 18.00, 'service', 'approved', '2026-03-30 15:27:50', '2026-03-30 15:27:50');

-- --------------------------------------------------------

--
-- Table structure for table `listing_images`
--

CREATE TABLE `listing_images` (
  `image_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `listing_requests`
--

CREATE TABLE `listing_requests` (
  `approval_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `decision` enum('approved','rejected') NOT NULL,
  `comment` text DEFAULT NULL,
  `decided_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listing_requests`
--

INSERT INTO `listing_requests` (`approval_id`, `listing_id`, `user_id`, `decision`, `comment`, `decided_at`) VALUES
(1, 3, 3, 'approved', 'Culturally valuable product with strong artisan appeal', '2026-03-22 19:08:56');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `total_amount`, `status`, `created_at`) VALUES
(1, 4, 65.00, 'completed', '2026-03-30 19:09:16'),
(2, 5, 25.00, 'processing', '2026-03-30 19:09:16');

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `order_id`, `listing_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 65.00),
(2, 2, 2, 1, 25.00);

-- --------------------------------------------------------

--
-- Table structure for table `poll`
--

CREATE TABLE `poll` (
  `poll_id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `poll`
--

INSERT INTO `poll` (`poll_id`, `title`, `description`, `start_date`, `end_date`, `created_by`) VALUES
(1, 'Favourite Cultural Experience', 'Vote for the most engaging cultural activity in your area', NULL, NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

CREATE TABLE `poll_options` (
  `option_id` int(11) NOT NULL,
  `poll_id` int(11) DEFAULT NULL,
  `listing_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `poll_options`
--

INSERT INTO `poll_options` (`option_id`, `poll_id`, `listing_id`) VALUES
(3, 1, 2),
(4, 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `poll_votes`
--

CREATE TABLE `poll_votes` (
  `vote_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `poll_votes`
--

INSERT INTO `poll_votes` (`vote_id`, `user_id`, `poll_id`, `option_id`) VALUES
(5, 4, 1, 3),
(6, 5, 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `product_service`
--

CREATE TABLE `product_service` (
  `item_id` int(11) NOT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_service`
--

INSERT INTO `product_service` (`item_id`, `subcategory_id`, `item_name`, `description`) VALUES
(1, 1, 'Art Classes', 'Painting, sculpture, ceramics'),
(2, 1, 'Music Lessons', 'Guitar, piano, vocals'),
(3, 2, 'Live Theatre Performances', 'Community theatre'),
(4, 2, 'Concerts & Open Mic Nights', 'Music and spoken word'),
(5, 3, 'Guided Cultural Tours', 'Heritage walks'),
(6, 3, 'Museum & Gallery Programs', 'Exhibitions'),
(7, 4, 'Photography & Videography', 'Event shoots'),
(8, 4, 'Graphic & Digital Design', 'Branding'),
(9, 5, 'Original Artwork', 'Paintings and prints'),
(10, 5, 'Handcrafted Ceramics', 'Pottery'),
(11, 6, 'Independently Published Books', 'Fiction and poetry'),
(12, 6, 'Zines & Magazines', 'Creative publications'),
(13, 7, 'Limited Edition Posters', 'Event artwork'),
(14, 7, 'Artisan Stationery', 'Handmade notebooks');

-- --------------------------------------------------------

--
-- Table structure for table `product_service_categories`
--

CREATE TABLE `product_service_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_service_categories`
--

INSERT INTO `product_service_categories` (`category_id`, `category_name`) VALUES
(1, 'Service'),
(2, 'Product');

-- --------------------------------------------------------

--
-- Table structure for table `product_service_reviews`
--

CREATE TABLE `product_service_reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_service_reviews`
--

INSERT INTO `product_service_reviews` (`review_id`, `user_id`, `listing_id`, `rating`, `comment`, `created_at`) VALUES
(1, 4, 1, 5, 'Absolutely beautiful craftsmanship and quality fabric!', '2026-03-30 17:09:33'),
(2, 5, 2, 4, 'Great energy in the class, very welcoming atmosphere.', '2026-03-30 18:10:27');

-- --------------------------------------------------------

--
-- Table structure for table `product_service_subcategories`
--

CREATE TABLE `product_service_subcategories` (
  `subcategory_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `subcategory_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_service_subcategories`
--

INSERT INTO `product_service_subcategories` (`subcategory_id`, `category_id`, `subcategory_name`) VALUES
(1, 1, 'Creative Workshops & Learning'),
(2, 1, 'Performing Arts & Events'),
(3, 1, 'Cultural Experiences'),
(4, 1, 'Creative Services'),
(5, 2, 'Art & Handmade Goods'),
(6, 2, 'Literary & Media Products'),
(7, 2, 'Cultural Merchandise');

-- --------------------------------------------------------

--
-- Table structure for table `service_bookings`
--

CREATE TABLE `service_bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `booking_date` date DEFAULT NULL,
  `status` enum('confirmed','declined','cancelled') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_bookings`
--

INSERT INTO `service_bookings` (`booking_id`, `user_id`, `item_id`, `booking_date`, `status`) VALUES
(0, 4, 2, '2026-04-10', 'confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `sme_profiles`
--

CREATE TABLE `sme_profiles` (
  `sme_id` int(11) NOT NULL,
  `business_name` varchar(150) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `approval_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `area_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sme_profiles`
--

INSERT INTO `sme_profiles` (`sme_id`, `business_name`, `category`, `approval_status`, `created_at`, `area_id`) VALUES
(1, 'Heritage Hands Studio', 'Arts & Crafts', 'approved', '2026-03-30 19:05:46', 1),
(2, 'Rhythm Roots Dance Academy', 'Dance', 'approved', '2026-03-30 22:05:46', 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role_id`, `email_address`, `password_hash`, `last_login`, `account_status`) VALUES
(1, 2, 'info@heritagehands.co.uk', 'hashed_pw', '2026-03-30 14:04:27', 'approved'),
(2, 2, 'contact@rhythmrootsdance.co.uk', 'hashed_pw', '2026-03-30 14:20:10', 'approved'),
(3, 3, 'council.officer@hertscouncil.gov.uk', 'hashed_pw', '2026-03-30 19:04:24', 'approved'),
(4, 1, 'amelia.clarke@email.com', 'hashed_pw', '2026-03-30 19:04:24', 'approved'),
(5, 1, 'james.wilson@email.com', 'hashed_pw', '2026-03-30 19:04:24', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `user_documents`
--

CREATE TABLE `user_documents` (
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type` enum('Bank_Statement','Driver_License','Utility_Bill') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `verification_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `resident_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Non-binary','Transgender','Genderqueer','Genderfluid','Agender','Intersex','Other','Prefer not to say') DEFAULT 'Prefer not to say',
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `postcode` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`profile_id`, `user_id`, `first_name`, `last_name`, `email_address`, `date_of_birth`, `gender`, `address`, `phone`, `postcode`, `created_at`) VALUES
(1, 1, 'Kwame', 'Mensah', 'info@heritagehands.co.uk', NULL, 'Prefer not to say', NULL, '07700111222', NULL, '2026-03-30 19:05:10'),
(2, 2, 'Aisha', 'Rahman', 'contact@rhythmrootsdance.co.uk', NULL, 'Prefer not to say', NULL, '07700333444', NULL, '2026-03-30 19:05:10'),
(3, 3, 'Daniel', 'Thompson', 'council.officer@hertscouncil.gov.uk', NULL, 'Prefer not to say', NULL, '07700555666', NULL, '2026-03-30 19:05:10'),
(4, 4, 'Amelia', 'Clarke', 'amelia.clarke@email.com', NULL, 'Prefer not to say', NULL, '07700777888', NULL, '2026-03-30 19:05:10'),
(5, 5, 'James', 'Wilson', 'james.wilson@email.com', NULL, 'Prefer not to say', NULL, '07700999000', NULL, '2026-03-30 19:05:10');

-- --------------------------------------------------------

--
-- Table structure for table `user_registration_requests`
--

CREATE TABLE `user_registration_requests` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `decision` enum('approved','rejected') NOT NULL,
  `comments` text DEFAULT NULL,
  `reviewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `document_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`role_id`, `role_name`) VALUES
(1, 'Resident'),
(2, 'SME'),
(3, 'Council Member'),
(4, 'Council Administrator');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`area_id`);

--
-- Indexes for table `listings`
--
ALTER TABLE `listings`
  ADD PRIMARY KEY (`listing_id`),
  ADD KEY `fk_listings_product_service_categories` (`category_id`),
  ADD KEY `fk_listings_sme` (`sme_id`);

--
-- Indexes for table `listing_images`
--
ALTER TABLE `listing_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `listing_id` (`listing_id`);

--
-- Indexes for table `listing_requests`
--
ALTER TABLE `listing_requests`
  ADD PRIMARY KEY (`approval_id`),
  ADD KEY `listing_id` (`listing_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_orders_user` (`user_id`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_orderitem_order` (`order_id`),
  ADD KEY `fk_orderitem_listing` (`listing_id`);

--
-- Indexes for table `poll`
--
ALTER TABLE `poll`
  ADD PRIMARY KEY (`poll_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `poll_id` (`poll_id`),
  ADD KEY `listing_id` (`listing_id`);

--
-- Indexes for table `poll_votes`
--
ALTER TABLE `poll_votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`poll_id`),
  ADD KEY `fk_vote_poll` (`poll_id`),
  ADD KEY `fk_vote_option` (`option_id`);

--
-- Indexes for table `product_service`
--
ALTER TABLE `product_service`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `subcategory_id` (`subcategory_id`);

--
-- Indexes for table `product_service_categories`
--
ALTER TABLE `product_service_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `product_service_reviews`
--
ALTER TABLE `product_service_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `listing_id` (`listing_id`);

--
-- Indexes for table `product_service_subcategories`
--
ALTER TABLE `product_service_subcategories`
  ADD PRIMARY KEY (`subcategory_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_booking_user` (`user_id`),
  ADD KEY `fk_booking_item` (`item_id`);

--
-- Indexes for table `sme_profiles`
--
ALTER TABLE `sme_profiles`
  ADD PRIMARY KEY (`sme_id`),
  ADD KEY `fk_sme_area` (`area_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email_address`),
  ADD UNIQUE KEY `email_address` (`email_address`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_documents`
--
ALTER TABLE `user_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD UNIQUE KEY `file_path` (`file_path`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `Email_address` (`email_address`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `user_registration_requests`
--
ALTER TABLE `user_registration_requests`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `fk_review_document` (`document_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `area_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `listing_images`
--
ALTER TABLE `listing_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `listing_requests`
--
ALTER TABLE `listing_requests`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `poll`
--
ALTER TABLE `poll`
  MODIFY `poll_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `poll_options`
--
ALTER TABLE `poll_options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `poll_votes`
--
ALTER TABLE `poll_votes`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_service_reviews`
--
ALTER TABLE `product_service_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sme_profiles`
--
ALTER TABLE `sme_profiles`
  MODIFY `sme_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_documents`
--
ALTER TABLE `user_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_registration_requests`
--
ALTER TABLE `user_registration_requests`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `listings`
--
ALTER TABLE `listings`
  ADD CONSTRAINT `fk_listings_product_service_categories` FOREIGN KEY (`category_id`) REFERENCES `product_service_categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_listings_sme` FOREIGN KEY (`sme_id`) REFERENCES `sme_profiles` (`sme_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `listing_images`
--
ALTER TABLE `listing_images`
  ADD CONSTRAINT `fk_listing_images_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `listing_requests`
--
ALTER TABLE `listing_requests`
  ADD CONSTRAINT `listing_requests_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `listing_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `fk_orderitem_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `poll`
--
ALTER TABLE `poll`
  ADD CONSTRAINT `poll_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD CONSTRAINT `poll_options_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `poll` (`poll_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `poll_options_ibfk_2` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `poll_votes`
--
ALTER TABLE `poll_votes`
  ADD CONSTRAINT `fk_vote_option` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`option_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vote_poll` FOREIGN KEY (`poll_id`) REFERENCES `poll` (`poll_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vote_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_service`
--
ALTER TABLE `product_service`
  ADD CONSTRAINT `product_service_ibfk_1` FOREIGN KEY (`subcategory_id`) REFERENCES `product_service_subcategories` (`subcategory_id`);

--
-- Constraints for table `product_service_reviews`
--
ALTER TABLE `product_service_reviews`
  ADD CONSTRAINT `fk_reviews_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_service_subcategories`
--
ALTER TABLE `product_service_subcategories`
  ADD CONSTRAINT `product_service_subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_service_categories` (`category_id`);

--
-- Constraints for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD CONSTRAINT `fk_booking_item` FOREIGN KEY (`item_id`) REFERENCES `product_service` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sme_profiles`
--
ALTER TABLE `sme_profiles`
  ADD CONSTRAINT `fk_sme_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`area_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`);

--
-- Constraints for table `user_documents`
--
ALTER TABLE `user_documents`
  ADD CONSTRAINT `user_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `user_registration_requests`
--
ALTER TABLE `user_registration_requests`
  ADD CONSTRAINT `fk_review_document` FOREIGN KEY (`document_id`) REFERENCES `user_documents` (`document_id`),
  ADD CONSTRAINT `user_registration_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `user_registration_requests_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
