-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2026 at 02:46 PM
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
-- Database: `culture_connect_grp_11`
--

-- --------------------------------------------------------

--
-- Table structure for table `areas`
--

CREATE TABLE `areas` (
  `area_id` int(11) NOT NULL,
  `area_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `postcode` varchar(20) NOT NULL,
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
  `sme_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `caption` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `item_id` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `listings`
--
DELIMITER $$
CREATE TRIGGER `restrict_listing_approval` BEFORE UPDATE ON `listings` FOR EACH ROW BEGIN
    DECLARE user_role VARCHAR(50);

    -- Only check when approving
    IF NEW.status = 'active' AND OLD.status != 'active' THEN

        -- Get role of approver
        SELECT role INTO user_role
        FROM users
        WHERE user_id = NEW.approved_by;

        -- Restrict roles
        IF user_role NOT IN ('Council Administrator', 'Council Member') THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Only council members or admins can approve listings';
        END IF;

    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `listing_images`
--

CREATE TABLE `listing_images` (
  `image_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
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
  `comment` text NOT NULL,
  `decided_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `listing_requests`
--
DELIMITER $$
CREATE TRIGGER `sync_listing_on_request_decision` AFTER INSERT ON `listing_requests` FOR EACH ROW BEGIN
    IF NEW.decision = 'approved' THEN
        UPDATE `listings`
        SET `status` = 'active', `approved_by` = NEW.user_id
        WHERE `listing_id` = NEW.listing_id;
    ELSEIF NEW.decision = 'rejected' THEN
        UPDATE `listings`
        SET `status` = 'inactive'
        WHERE `listing_id` = NEW.listing_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `poll`
--

CREATE TABLE `poll` (
  `poll_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `poll`
--
DELIMITER $$
CREATE TRIGGER `restrict_poll_creation` BEFORE INSERT ON `poll` FOR EACH ROW BEGIN
    DECLARE user_role VARCHAR(50);

    -- Get the role of the user creating the poll
    SELECT role INTO user_role
    FROM users
    WHERE user_id = NEW.created_by;

    -- Check if role is allowed
    IF user_role NOT IN ('Council Administrator', 'Council Member') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Only council members or admins can create polls';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

CREATE TABLE `poll_options` (
  `option_id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Stand-in structure for view `product_interest_ranking`
-- (See below for the actual view)
--
CREATE TABLE `product_interest_ranking` (
`product_name` varchar(150)
,`price` decimal(10,2)
,`total_score` bigint(21)
,`ranking_position` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `product_service`
--

CREATE TABLE `product_service` (
  `item_id` int(11) NOT NULL,
  `subcategory_id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `description` text NOT NULL
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
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `product_service_reviews`
--
DELIMITER $$
CREATE TRIGGER `check_rating_before_insert` BEFORE INSERT ON `product_service_reviews` FOR EACH ROW IF NEW.rating < 1 OR NEW.rating > 10 THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Rating must be between 1 and 10';
END IF
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `product_service_subcategories`
--

CREATE TABLE `product_service_subcategories` (
  `subcategory_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
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
-- Table structure for table `resident_profiles`
--

CREATE TABLE `resident_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Non-binary','Transgender','Genderqueer','Genderfluid','Agender','Intersex','Other','Prefer not to say') DEFAULT 'Prefer not to say',
  `address` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `postcode` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `area_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resident_profiles`
--

INSERT INTO `resident_profiles` (`profile_id`, `user_id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `address`, `phone`, `postcode`, `created_at`, `area_id`) VALUES
(6, 6, 'Council', 'Admin', '2026-03-02', 'Prefer not to say', 'Hatfield', '+44 7700000000', 'AL10 9NA', '2026-04-04 13:41:33', 1),
(8, 8, 'Oyenike', 'Alade', '2022-03-13', 'Female', 'Hatfield', '+44 7000000002', 'AL10 0ED', '2026-04-04 18:16:05', 3),
(9, 9, 'Josephine', 'Abioye', '2025-10-02', 'Female', 'Hatfield', '+44 7000000003', 'AL10 0ED', '2026-04-05 18:44:24', 3),
(10, 11, 'Victor', 'Ikekhua', '2019-03-20', 'Male', 'Hatfield', '+44 70000000001', 'AL10 0WB', '2026-04-07 08:16:06', 4),
(11, 12, 'Kenneth', 'Onyeabor', '2021-10-21', 'Male', 'Hatfield', '+44 7000000004', 'AL10 0JT', '2026-04-07 08:19:30', 5),
(12, 13, 'Habeeblahi', 'Hameed', '2020-02-19', 'Male', 'Hatfield', '+44 7000000005', 'AL10 9SB', '2026-04-07 08:21:02', 2),
(13, 23, 'Council', 'Member', '2026-02-23', 'Other', 'Hatfield', '+44 7000000006', 'AL10 9NA', '2026-04-08 11:31:45', 1);

--
-- Triggers `resident_profiles`
--
DELIMITER $$
CREATE TRIGGER `set_area_id_before_insert` BEFORE INSERT ON `resident_profiles` FOR EACH ROW BEGIN
  DECLARE matched_area_id INT;

  SELECT area_id INTO matched_area_id
  FROM areas
  WHERE postcode = NEW.postcode
  LIMIT 1;

  IF matched_area_id IS NULL THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Invalid postcode: no matching area';
  ELSE
    SET NEW.area_id = matched_area_id;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `set_area_id_before_update` BEFORE UPDATE ON `resident_profiles` FOR EACH ROW BEGIN
  DECLARE matched_area_id INT;

  SELECT area_id INTO matched_area_id
  FROM areas
  WHERE postcode = NEW.postcode
  LIMIT 1;

  SET NEW.area_id = matched_area_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `resident_with_area`
-- (See below for the actual view)
--
CREATE TABLE `resident_with_area` (
`profile_id` int(11)
,`user_id` int(11)
,`email_address` varchar(100)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`postcode` varchar(10)
,`area_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `service_bookings`
--

CREATE TABLE `service_bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `status` enum('confirmed','declined','cancelled','pending') NOT NULL DEFAULT 'pending',
  `listing_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `sme_listings_overview`
-- (See below for the actual view)
--
CREATE TABLE `sme_listings_overview` (
`business_name` varchar(150)
,`listing_title` varchar(150)
,`item_name` varchar(150)
,`category_name` varchar(50)
,`price` decimal(10,2)
,`status` enum('active','inactive','pending')
);

-- --------------------------------------------------------

--
-- Table structure for table `sme_profiles`
--

CREATE TABLE `sme_profiles` (
  `sme_id` int(11) NOT NULL,
  `business_name` varchar(150) NOT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `area_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subcategory_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sme_profiles`
--

INSERT INTO `sme_profiles` (`sme_id`, `business_name`, `approval_status`, `created_at`, `area_id`, `description`, `phone`, `user_id`, `subcategory_id`) VALUES
(3, 'Harmony Wellbeing Centre', 'pending', '2026-04-06 09:54:55', 1, 'Harmony Wellbeing Centre is a community-focused organisation dedicated to improving the mental, physical, and emotional wellbeing of local residents. We offer a range of therapeutic services including art therapy, music therapy, mindfulness workshops, and community support groups. Our mission is to foster a sense of belonging and cultural connection through inclusive and accessible wellbeing programmes tailored to the diverse needs of our community.', '+44 7700 900123', 10, 3);

-- --------------------------------------------------------

--
-- Stand-in structure for view `sme_with_area`
-- (See below for the actual view)
--
CREATE TABLE `sme_with_area` (
`sme_id` int(11)
,`user_id` int(11)
,`email_address` varchar(100)
,`business_name` varchar(150)
,`approval_status` enum('pending','approved','rejected')
,`area_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `role` varchar(50) NOT NULL,
  `email_address` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `password_hash`, `last_login`, `account_status`, `role`, `email_address`) VALUES
(6, '$2y$10$01mLimPT3Pddrd30S/gtEuweOQzUYFV6fUxOYXNVK.ItevTDMTv26', '2026-04-08 10:37:44', 'approved', 'Council Administrator', 'admin@cultureconnect.com'),
(8, '$2y$10$NdvqqVsckEpxpJfLVEokXe437ra6Nye/3TZycQoCDR/8uWAWnFWRa', '2026-04-04 18:16:05', 'approved', 'Resident', 'nike@gmail.com'),
(9, '$2y$10$dyS/3x2E0F5qFN3gM.tbv.SJg83R6Q8N93hBx.9CtiIO5j9psQd/C', '2026-04-06 09:38:40', 'pending', 'Resident', 'josephine@gmail.com'),
(10, '$2y$10$pD9RVt2XH2HZmJ/dQwJkI.DO1t0Wh0xEssZCFLjUW0g0CUIjIHlA6', '2026-04-08 11:47:21', 'approved', 'SME', 'info@harmonywellbeing.com'),
(11, '$2y$10$pEGsw9izjII4FX3gzzaJ4.QSDLc70c6nGub9/1IU78TLgASSZUsf.', '2026-04-07 08:16:06', 'approved', 'Resident', 'victor@gmail.com'),
(12, '$2y$10$aSQiHxr71NTJT0.ohlXxDezWv08wpyxyjnP1tR9q7tgxhcBsX.Uai', '2026-04-07 08:19:30', 'approved', 'Resident', 'jake@gmail.com'),
(13, '$2y$10$L680iiTY/OYAE9v./tg99O4N1T/ao6GKU/AnYCR6KNO4jLIimJKHu', '2026-04-08 11:28:32', 'approved', 'Resident', 'habeeb@gmail.com'),
(23, '$2y$10$UDiUYY47oKHijEltMpEDRusNoO93tFpEhQ389rybJJHXwzuNwcMiG', '2026-04-08 11:37:39', 'approved', 'Council Member', 'Cmember@cultureconnect.com');

-- --------------------------------------------------------

--
-- Table structure for table `user_documents`
--

CREATE TABLE `user_documents` (
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type` enum('Bank_Statement','Driver_License','Utility_Bill') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `verification_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_documents`
--

INSERT INTO `user_documents` (`document_id`, `user_id`, `document_type`, `file_path`, `verification_status`, `uploaded_at`) VALUES
(1, 8, 'Bank_Statement', '../uploads/verification_documents/1775330165_bank_statement.pdf', 'pending', '2026-04-04 18:16:05'),
(2, 9, 'Bank_Statement', '../uploads/verification_documents/1775418264_bank_statement.pdf', 'pending', '2026-04-05 18:44:24'),
(3, 10, 'Bank_Statement', '../uploads/verification_documents/1775472895_bank_statement.pdf', 'pending', '2026-04-06 09:54:55'),
(4, 11, 'Bank_Statement', '../uploads/verification_documents/1775553366_bank_statement.pdf', 'pending', '2026-04-07 08:16:06'),
(5, 12, 'Bank_Statement', '../uploads/verification_documents/1775553570_bank_statement.pdf', 'pending', '2026-04-07 08:19:30'),
(6, 13, 'Bank_Statement', '../uploads/verification_documents/1775553662_bank_statement.pdf', 'pending', '2026-04-07 08:21:02'),
(7, 23, 'Bank_Statement', '../uploads/verification_documents/1775647905_bank_statement.pdf', 'pending', '2026-04-08 11:31:45');

-- --------------------------------------------------------

--
-- Table structure for table `user_registration_requests`
--

CREATE TABLE `user_registration_requests` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `decision` enum('approved','rejected','pending') NOT NULL DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `reviewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `document_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_registration_requests`
--

INSERT INTO `user_registration_requests` (`review_id`, `user_id`, `admin_id`, `decision`, `comments`, `reviewed_at`, `document_id`) VALUES
(1, 9, 6, 'approved', '', '2026-04-06 09:15:48', 2),
(2, 9, 6, 'approved', '', '2026-04-06 09:16:14', 2),
(3, 8, 6, 'rejected', 'testing', '2026-04-06 09:17:47', 1),
(4, 8, 6, 'approved', '', '2026-04-06 09:18:56', 1),
(5, 9, 6, 'approved', '', '2026-04-06 09:19:10', 2),
(6, 13, 6, 'approved', '', '2026-04-08 10:40:22', 6),
(7, 12, 23, 'approved', '', '2026-04-08 11:46:05', 5),
(8, 11, 23, 'rejected', 'testing', '2026-04-08 11:46:25', 4),
(9, 11, 23, 'approved', '', '2026-04-08 11:46:39', 4);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`role`) VALUES
('Council Administrator'),
('Council Member'),
('Resident'),
('SME');

-- --------------------------------------------------------

--
-- Structure for view `product_interest_ranking`
--
DROP TABLE IF EXISTS `product_interest_ranking`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `product_interest_ranking`  AS SELECT `ps`.`item_name` AS `product_name`, `l`.`price` AS `price`, count(`pv`.`vote_id`) AS `total_score`, rank() over ( order by count(`pv`.`vote_id`) desc,`l`.`price`) AS `ranking_position` FROM (((`product_service` `ps` join `listings` `l` on(`ps`.`item_id` = `l`.`item_id`)) left join `poll_options` `po` on(`l`.`listing_id` = `po`.`listing_id`)) left join `poll_votes` `pv` on(`po`.`option_id` = `pv`.`option_id`)) GROUP BY `ps`.`item_name`, `l`.`price` ;

-- --------------------------------------------------------

--
-- Structure for view `resident_with_area`
--
DROP TABLE IF EXISTS `resident_with_area`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `resident_with_area`  AS SELECT `r`.`profile_id` AS `profile_id`, `r`.`user_id` AS `user_id`, `u`.`email_address` AS `email_address`, `r`.`first_name` AS `first_name`, `r`.`last_name` AS `last_name`, `r`.`postcode` AS `postcode`, `a`.`area_name` AS `area_name` FROM ((`resident_profiles` `r` join `users` `u` on(`r`.`user_id` = `u`.`user_id`)) join `areas` `a` on(`r`.`area_id` = `a`.`area_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `sme_listings_overview`
--
DROP TABLE IF EXISTS `sme_listings_overview`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sme_listings_overview`  AS SELECT `s`.`business_name` AS `business_name`, `l`.`title` AS `listing_title`, `ps`.`item_name` AS `item_name`, `psc`.`category_name` AS `category_name`, `l`.`price` AS `price`, `l`.`status` AS `status` FROM ((((`sme_profiles` `s` left join `listings` `l` on(`s`.`sme_id` = `l`.`sme_id`)) left join `product_service` `ps` on(`l`.`item_id` = `ps`.`item_id`)) left join `product_service_subcategories` `pss` on(`ps`.`subcategory_id` = `pss`.`subcategory_id`)) left join `product_service_categories` `psc` on(`pss`.`category_id` = `psc`.`category_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `sme_with_area`
--
DROP TABLE IF EXISTS `sme_with_area`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sme_with_area`  AS SELECT `s`.`sme_id` AS `sme_id`, `s`.`user_id` AS `user_id`, `u`.`email_address` AS `email_address`, `s`.`business_name` AS `business_name`, `s`.`approval_status` AS `approval_status`, `a`.`area_name` AS `area_name` FROM ((`sme_profiles` `s` join `users` `u` on(`s`.`user_id` = `u`.`user_id`)) join `areas` `a` on(`s`.`area_id` = `a`.`area_id`)) ;

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
  ADD KEY `fk_listing_item` (`item_id`),
  ADD KEY `fk_approved_user` (`approved_by`),
  ADD KEY `sme_id` (`sme_id`);

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
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
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
  ADD UNIQUE KEY `user_id_2` (`user_id`),
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
-- Indexes for table `resident_profiles`
--
ALTER TABLE `resident_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `fk_resident_area` (`area_id`);

--
-- Indexes for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_booking_user` (`user_id`),
  ADD KEY `fk_booking_item` (`item_id`),
  ADD KEY `listing_id` (`listing_id`);

--
-- Indexes for table `sme_profiles`
--
ALTER TABLE `sme_profiles`
  ADD PRIMARY KEY (`sme_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `fk_sme_area` (`area_id`),
  ADD KEY `fk_sme_subcategory` (`subcategory_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email_address` (`email_address`),
  ADD KEY `fk_users_role` (`role`);

--
-- Indexes for table `user_documents`
--
ALTER TABLE `user_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD UNIQUE KEY `file_path` (`file_path`),
  ADD KEY `user_id` (`user_id`);

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
  ADD PRIMARY KEY (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `area_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `listings`
--
ALTER TABLE `listings`
  MODIFY `listing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
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
-- AUTO_INCREMENT for table `product_service`
--
ALTER TABLE `product_service`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `product_service_categories`
--
ALTER TABLE `product_service_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_service_reviews`
--
ALTER TABLE `product_service_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_service_subcategories`
--
ALTER TABLE `product_service_subcategories`
  MODIFY `subcategory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `resident_profiles`
--
ALTER TABLE `resident_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `sme_profiles`
--
ALTER TABLE `sme_profiles`
  MODIFY `sme_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_documents`
--
ALTER TABLE `user_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_registration_requests`
--
ALTER TABLE `user_registration_requests`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `listings`
--
ALTER TABLE `listings`
  ADD CONSTRAINT `fk_approved_user` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_listing_item` FOREIGN KEY (`item_id`) REFERENCES `product_service` (`item_id`),
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
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitems_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orderitems_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `resident_profiles`
--
ALTER TABLE `resident_profiles`
  ADD CONSTRAINT `fk_resident_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`area_id`),
  ADD CONSTRAINT `resident_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD CONSTRAINT `fk_booking_item` FOREIGN KEY (`item_id`) REFERENCES `product_service` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sme_profiles`
--
ALTER TABLE `sme_profiles`
  ADD CONSTRAINT `fk_sme_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `product_service_subcategories` (`subcategory_id`),
  ADD CONSTRAINT `fk_sme_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role`) REFERENCES `user_roles` (`role`);

--
-- Constraints for table `user_documents`
--
ALTER TABLE `user_documents`
  ADD CONSTRAINT `user_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

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
