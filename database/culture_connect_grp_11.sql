-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 03:14 PM
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
-- Dumping data for table `listings`
--

INSERT INTO `listings` (`listing_id`, `sme_id`, `title`, `caption`, `description`, `price`, `status`, `created_at`, `updated_at`, `item_id`, `approved_by`) VALUES
(7, 11, 'Painting & Sculpture for Beginners', 'Explore your creativity with hands-on painting and sculpture sessions', 'Join our friendly beginner-friendly painting and sculpture classes held weekly at Brushstroke Studio. Whether you prefer watercolours, acrylics or working with clay, our experienced tutors guide you through every step. All materials provided. Suitable for ages 16 and above.', 25.00, 'active', '2026-04-09 09:36:45', '2026-04-09 11:48:14', 1, 6),
(8, 11, 'Guitar & Piano Lessons for All Levels', 'One-to-one and group music lessons tailored to your pace', 'Our music lessons cover guitar and piano for complete beginners through to improvers. Sessions are available one-to-one or in small groups. Our tutors are experienced performers who make learning music enjoyable and accessible for everyone in the Hertfordshire community.', 30.00, 'active', '2026-04-09 18:52:31', '2026-04-09 19:43:35', 2, 6),
(9, 11, 'Movement & Dance Expression Workshop', 'A creative movement workshop blending dance with artistic expression', 'This unique workshop combines movement, dance and visual art to help participants express themselves freely. No dance experience is required. Sessions run for 90 minutes and are open to adults of all fitness levels. A fantastic way to connect with your body and your community.', 20.00, 'active', '2026-04-09 18:55:06', '2026-04-09 19:43:21', 1, 6),
(10, 11, 'Creative Arts for Kids - Ages 6 to 12', 'A fun and imaginative art class designed specially for children aged 6 to 12', 'Creative Arts for Kids is a weekly after-school and weekend art class designed to spark imagination and build confidence in children aged 6 to 12. Sessions cover painting, drawing, collage, and simple sculpture using child-safe materials. Our friendly tutors create a warm, encouraging environment where every child can express themselves freely. No experience needed — just curiosity and enthusiasm! Parents are welcome to stay and watch. All materials are provided and sessions run for 60 minutes.', 15.00, 'active', '2026-04-09 18:57:41', '2026-04-10 11:04:32', 1, 6),
(11, 12, 'Community Stage - Live Theatre Night', 'Original community theatre performed by local Hertfordshire talent', 'Experience the magic of live theatre performed by members of our local community. Each production is written and directed by Hertfordshire residents and explores themes of culture, identity and belonging. Shows run on selected Friday and Saturday evenings. Book early to avoid disappointment.', 15.00, 'active', '2026-04-09 19:01:47', '2026-04-10 11:04:44', 3, 6),
(12, 12, 'Open Mic Night - Local Voices', 'A relaxed open mic evening celebrating local musicians and spoken word artists', 'Our monthly open mic nights are a celebration of local musical and spoken word talent. Whether you want to perform or simply enjoy the show, everyone is welcome. Doors open at 6:30pm with performances from 7pm. No booking required for audience members.', 10.00, 'active', '2026-04-09 19:06:32', '2026-04-10 11:04:58', 4, 6),
(13, 12, 'Youth Drama Showcase', 'A showcase of short plays written and performed by young Hertfordshire residents', 'Our Youth Drama Showcase gives young people aged 13 to 18 a platform to perform their own original short plays. The event runs twice a year and is free to watch. This listing covers workshop participation which includes rehearsal sessions, script development and the final performance.', 18.00, 'active', '2026-04-09 19:09:00', '2026-04-09 19:42:41', 3, 6),
(14, 13, 'Hertfordshire Historic Town Walk', 'A guided walk through the cultural and historical highlights of Hertfordshire', 'Join our expert guide for a 2-hour walking tour through the most historically significant sites in Hertfordshire. Learn about the area\'s rich cultural heritage, architecture and local stories that shaped the community. Tours depart every Saturday at 10am. Suitable for all ages.', 12.00, 'active', '2026-04-09 19:14:13', '2026-04-09 19:42:29', 5, 6),
(15, 13, 'Gallery Discovery Programme', 'Curated gallery visits and interactive cultural programs for the community', 'Our Gallery Discovery Programme offers curated visits to local museums and galleries with expert commentary. Each session includes an interactive element where participants can engage with exhibits, ask questions and learn about the cultural significance of the artworks and artefacts on display.', 8.00, 'active', '2026-04-09 19:16:47', '2026-04-09 19:42:19', 6, 6),
(16, 14, 'Event Photography & Videography Package', 'Professional photography and videography for community events and cultural occasions', 'PixelCraft Media offers full event photography and videography coverage for community gatherings, cultural festivals, performances and private events. Our package includes a pre-event consultation, full-day coverage, edited photo gallery delivery within 7 days and a highlight video reel.', 150.00, 'active', '2026-04-09 19:19:47', '2026-04-09 19:42:09', 7, 6),
(17, 14, 'Graphic Design & Marketing Materials', 'Custom graphic design for flyers, posters, social media and digital campaigns', 'Our graphic design service covers everything from event flyers and posters to social media graphics and full digital marketing campaigns. We work with local businesses, community groups and cultural organisations to create visually compelling materials that represent their identity and message.', 120.00, 'active', '2026-04-09 19:22:13', '2026-04-09 19:41:59', 8, 6),
(18, 14, 'Brand Identity Design Package', 'Full brand identity design including logo, colour palette and style guide', 'Our brand identity package is designed for new and growing businesses that need a strong visual identity. We deliver a custom logo, colour palette, typography selection and a brand style guide. Ideal for SMEs in the cultural and creative sector looking to establish a professional presence.', 200.00, 'active', '2026-04-09 19:24:24', '2026-04-09 19:41:48', 8, 6),
(19, 15, 'Original Hertfordshire Landscape Water colour', 'Hand-painted original water colour depicting iconic Hertfordshire landscapes', 'Each piece in this collection is an original hand-painted watercolour by our resident artist, capturing the natural beauty of the Hertfordshire landscape. Paintings are mounted and ready to frame. No two pieces are identical. Sizes range from A4 to A2. Please contact us to view available pieces before purchasing.', 45.00, 'active', '2026-04-09 19:26:59', '2026-04-09 19:41:37', 9, 6),
(20, 15, 'Hand-Thrown Ceramic Pottery Collection', 'Unique hand-thrown ceramic bowls, mugs and vases made in Hertfordshire', 'Our hand-thrown ceramic collection features bowls, mugs, plates and vases crafted individually by our resident potter. Each piece is glazed using natural pigments and fired in our studio kiln. Items are food safe and dishwasher friendly. Perfect as gifts or statement pieces for your home.', 35.00, 'active', '2026-04-09 19:29:14', '2026-04-09 19:41:29', 10, 6),
(21, 15, 'Limited Edition Hertfordshire Cultural Festival Poster', 'Collectible limited edition art print celebrating Hertfordshire\'s cultural calendar', 'These limited edition posters are designed exclusively by local artists to commemorate Hertfordshire\'s annual cultural festival. Each poster is A2 size, printed on 300gsm heavyweight paper with archival ink. Only 100 prints are produced per edition. Numbered and signed by the artist.', 18.00, 'active', '2026-04-09 19:31:52', '2026-04-09 19:41:19', 13, 6),
(22, 15, 'Handmade Leather & Recycled Paper Journal', 'Beautifully crafted artisan journals made from leather and recycled paper', 'Our artisan journals feature hand-stitched recycled paper pages bound in a soft leather cover sourced from local suppliers. Each journal is unique and comes in A5 size with 120 pages. Ideal for sketching, writing, journaling or as a thoughtful gift for creative individuals.', 22.00, 'active', '2026-04-09 19:34:13', '2026-04-09 19:41:10', 14, 6),
(23, 16, 'Hertfordshire Voices - Community Poetry Collection', 'An independently published anthology of poetry written by Hertfordshire residents', 'Hertfordshire Voices is our flagship poetry anthology featuring works from over 30 local writers. The collection explores themes of community, identity, nature and belonging through poetry in a variety of styles. Paperback, 180 pages. A portion of every sale supports our community writing workshops.', 12.00, 'active', '2026-04-09 19:37:24', '2026-04-10 11:05:13', 11, 6),
(24, 16, 'The Hertford Quarterly - Community Zine', 'A quarterly zine featuring local art, writing, interviews and cultural commentary', 'The Hertford Quarterly is our independently produced community zine published four times a year. Each issue is packed with original short stories, poetry, local artist interviews, photography and cultural commentary from Hertfordshire residents. A5 format, 48 pages, full colour. Subscribe or buy individual issues.', 8.00, 'active', '2026-04-09 19:39:15', '2026-04-10 11:05:26', 12, 6);

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

--
-- Dumping data for table `listing_images`
--

INSERT INTO `listing_images` (`image_id`, `listing_id`, `image_url`, `is_primary`, `created_at`) VALUES
(3, 7, '1775731005_Brushstroke Studio beginners' classes promotion.png', 1, '2026-04-09 09:36:45'),
(4, 7, '17757310050_Creative expression in the community studio.png', 0, '2026-04-09 09:36:45'),
(5, 8, '1775764351_guitar-lessons-1.jpg', 1, '2026-04-09 18:52:31'),
(6, 8, '17757643510_group-guitar-lessons.jpg', 0, '2026-04-09 18:52:31'),
(7, 8, '17757643511_adults-guitar-classes.jpeg', 0, '2026-04-09 18:52:31'),
(8, 9, '1775764506_Sara-Contemporary-Dance.jpg', 1, '2026-04-09 18:55:06'),
(9, 9, '17757645060_image_processing20250516-30-e0rut0.jpg', 0, '2026-04-09 18:55:06'),
(10, 10, '1775764661_1421fd7082c16ea5d468d2c6c5fc65dc5c1d0266.png', 1, '2026-04-09 18:57:41'),
(11, 10, '17757646610_images.jpeg', 0, '2026-04-09 18:57:41'),
(12, 11, '1775764907_images-2.jpeg', 1, '2026-04-09 19:01:47'),
(13, 11, '17757649070_06092021-Four-Four-07799-1.jpg', 0, '2026-04-09 19:01:47'),
(14, 12, '1775765192_open-Mic-night-The-Preston-Gate-e1728641015614.png', 1, '2026-04-09 19:06:32'),
(15, 13, '1775765340_youth-theatre-showcase-1920x947.jpg', 1, '2026-04-09 19:09:00'),
(16, 13, '17757653400_1765540190354.jpeg', 0, '2026-04-09 19:09:00'),
(17, 14, '1775765653_6628d755dc796-HD.jpg', 1, '2026-04-09 19:14:13'),
(18, 14, '17757656530_sherborne-abbey.webp', 0, '2026-04-09 19:14:13'),
(19, 15, '1775765807_12920RAQIB_SHAW_BALLADS_EAST_WEST_LS_DSC_0015.webp', 1, '2026-04-09 19:16:47'),
(20, 15, '17757658070_Members-Discovery-Raqib-Shaw-Square.webp', 0, '2026-04-09 19:16:47'),
(21, 16, '1775765987_Corporate-Event-Videography-London-UK.jpg.webp', 1, '2026-04-09 19:19:47'),
(22, 17, '1775766133_Graphic-Design_Marketing-Materials-1727226418806.png', 1, '2026-04-09 19:22:13'),
(23, 18, '1775766264_Brand-Identity-Image.png', 1, '2026-04-09 19:24:24'),
(24, 19, '1775766419_il_570xN.3233756463_j1tb.jpg', 1, '2026-04-09 19:26:59'),
(25, 20, '1775766554_chris-jenkins-pottery-collection.jpg', 1, '2026-04-09 19:29:15'),
(26, 20, '17757665550_DSCF6206.jpg', 0, '2026-04-09 19:29:15'),
(27, 21, '1775766712_MJF-Affiches-Ed-Limitee_01-1500-x-1500-01.jpg', 1, '2026-04-09 19:31:52'),
(28, 22, '1775766853_Leather_530x@2x.webp', 1, '2026-04-09 19:34:13'),
(29, 22, '17757668530_il_570xN.2881606934_bsl0.jpg.webp', 0, '2026-04-09 19:34:13'),
(30, 23, '1775767044_465feede-e7e7-4b8c-868b-d333bc277f6f_1_201_a.jpg', 1, '2026-04-09 19:37:24'),
(31, 23, '17757670440_images-3.jpeg', 0, '2026-04-09 19:37:24'),
(32, 24, '1775767155_images-4.jpeg', 1, '2026-04-09 19:39:15'),
(33, 24, '17757671550_images-5.jpeg', 0, '2026-04-09 19:39:15');

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
-- Dumping data for table `listing_requests`
--

INSERT INTO `listing_requests` (`approval_id`, `listing_id`, `user_id`, `decision`, `comment`, `decided_at`) VALUES
(3, 7, 6, 'approved', 'Approved', '2026-04-09 11:48:14'),
(4, 24, 6, 'approved', '', '2026-04-09 19:40:38'),
(5, 23, 6, 'approved', 'approved', '2026-04-09 19:40:57'),
(6, 22, 6, 'approved', 'approved', '2026-04-09 19:41:10'),
(7, 21, 6, 'approved', 'approved', '2026-04-09 19:41:19'),
(8, 20, 6, 'approved', 'approved', '2026-04-09 19:41:29'),
(9, 19, 6, 'approved', 'approved', '2026-04-09 19:41:37'),
(10, 18, 6, 'approved', 'approved', '2026-04-09 19:41:48'),
(11, 17, 6, 'approved', 'approved', '2026-04-09 19:41:59'),
(12, 16, 6, 'approved', 'approved', '2026-04-09 19:42:09'),
(13, 15, 6, 'approved', 'approved', '2026-04-09 19:42:19'),
(14, 14, 6, 'approved', 'approved', '2026-04-09 19:42:29'),
(15, 13, 6, 'approved', 'approved', '2026-04-09 19:42:41'),
(16, 12, 6, 'approved', 'approved', '2026-04-09 19:42:53'),
(17, 11, 6, 'approved', '', '2026-04-09 19:42:58'),
(18, 10, 6, 'approved', 'approved', '2026-04-09 19:43:09'),
(19, 9, 6, 'approved', 'approved', '2026-04-09 19:43:21'),
(20, 8, 6, 'approved', 'approved', '2026-04-09 19:43:35'),
(21, 24, 6, 'approved', '', '2026-04-09 22:30:17');

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
-- Table structure for table `listing_votes`
--

CREATE TABLE `listing_votes` (
  `vote_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `vote_type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listing_votes`
--

INSERT INTO `listing_votes` (`vote_id`, `user_id`, `listing_id`, `vote_type`, `created_at`) VALUES
(1, 8, 16, 'like', '2026-04-10 10:28:42'),
(2, 9, 16, 'like', '2026-04-10 10:28:42'),
(3, 11, 17, 'like', '2026-04-10 10:28:42'),
(4, 12, 17, 'dislike', '2026-04-10 10:28:42'),
(5, 13, 18, 'like', '2026-04-10 10:28:42');

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
(14, 7, 'Artisan Stationery', 'Handmade notebooks'),
(15, 8, 'Cultural Recipe Book', 'A collection of traditional recipes from Hertfordshire cultural communities.'),
(16, 8, 'Artisan Cultural Spice Kit', 'Curated set of spices used in traditional cultural cooking.'),
(17, 8, 'Cultural Snack Box', 'A selection of traditional snacks and treats from various cultural backgrounds.'),
(18, 9, 'Podcast Production Session', 'Professional podcast recording and editing session for community voices.'),
(19, 9, 'Community Newsletter Design', 'Design and layout of a community newsletter or digital publication.'),
(20, 9, 'Cultural Documentary Filming', 'Short documentary filming and editing service for community events.');

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
(7, 2, 'Cultural Merchandise'),
(8, 2, 'Cultural Food & Drink'),
(9, 1, 'Community Media');

-- --------------------------------------------------------

--
-- Stand-in structure for view `resident_product_service_interest`
-- (See below for the actual view)
--
CREATE TABLE `resident_product_service_interest` (
`product_name` varchar(150)
,`listing_title` varchar(150)
,`price` decimal(10,2)
,`total_likes` decimal(22,0)
,`total_dislikes` decimal(22,0)
,`ranking` decimal(56,0)
);

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
(13, 23, 'Council', 'Member', '2026-02-23', 'Other', 'Hatfield', '+44 7000000006', 'AL10 9NA', '2026-04-08 11:31:45', 1),
(14, 34, 'Marcus', 'Osei', '1990-03-15', 'Male', '12 Oak Street, Hatfield', '+44 7700 100010', 'AL10 9NA', '2026-04-10 09:25:47', 1),
(15, 35, 'Aisha', 'Patel', '1995-07-22', 'Female', '45 Elm Avenue, Hatfield', '+44 7700 100011', 'AL10 9SB', '2026-04-10 09:27:46', 2),
(16, 36, 'Daniel', 'Mensah', '1988-11-30', 'Male', '8 Birch Road, Hatfield', '+44 7700 100012', 'AL10 0ED', '2026-04-10 09:29:29', 3),
(17, 37, 'Fatima', 'Ali', '1992-05-18', 'Female', '23 Cedar Lane, Hatfield', '+44 7700 100013', 'AL10 0WB', '2026-04-10 09:31:31', 4),
(18, 38, 'James', 'Adeyemi', '1985-04-09', 'Male', '67 Maple Close, Hatfield', '+44 7700 100014', 'AL10 0JT', '2026-04-10 09:33:34', 5),
(19, 39, 'Priya', 'Sharma', '1998-01-14', 'Female', '31 Willow Way, Hatfield', '+44 7700 100015', 'AL10 8HG', '2026-04-10 09:35:33', 6),
(20, 40, 'Sarah', 'Nkrumah', '1993-12-08', 'Female', '14 Pine Street, Hatfield', '+44 7700 100016', 'AL10 9NA', '2026-04-10 09:36:58', 1),
(21, 41, 'David', 'Kofi', '1987-06-25', 'Male', '55 Ash Grove, Hatfield', '+44 7700 100017', 'AL10 9SB', '2026-04-10 09:38:53', 2),
(22, 42, 'Tariq', 'Hussain', '1997-06-14', 'Male', '9 Hazel Court, Hatfield', '+44 7700 200001', 'AL10 9NA', '2026-04-10 09:45:08', 1),
(23, 43, 'Siobhan', 'Oconnor', '1993-11-28', 'Female', '27 Poplar Drive, Hatfield', '+44 7700 200002', 'AL10 0ED', '2026-04-10 09:46:55', 3),
(24, 44, 'Samuel', 'Tally', '2001-03-05', 'Male', '43 Sycamore Road, Hatfield', '+44 7700 200003', 'AL10 0WB', '2026-04-10 09:49:09', 4);

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
  `booking_date` date NOT NULL,
  `status` enum('confirmed','declined','cancelled','pending') NOT NULL DEFAULT 'pending',
  `listing_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_bookings`
--

INSERT INTO `service_bookings` (`booking_id`, `user_id`, `booking_date`, `status`, `listing_id`, `created_at`) VALUES
(1, 8, '2026-04-12', 'confirmed', 7, '2026-04-10 08:00:00'),
(2, 8, '2026-04-14', 'confirmed', 15, '2026-04-10 08:05:00'),
(3, 9, '2026-04-13', 'confirmed', 9, '2026-04-10 08:30:00'),
(4, 9, '2026-04-19', 'pending', 14, '2026-04-10 08:35:00'),
(5, 11, '2026-04-15', 'confirmed', 8, '2026-04-10 09:00:00'),
(6, 11, '2026-04-18', 'confirmed', 11, '2026-04-10 09:05:00'),
(7, 12, '2026-04-16', 'confirmed', 10, '2026-04-10 09:30:00'),
(8, 12, '2026-04-17', 'cancelled', 12, '2026-04-10 09:35:00'),
(9, 13, '2026-04-20', 'confirmed', 13, '2026-04-10 10:00:00'),
(10, 13, '2026-04-21', 'confirmed', 20, '2026-04-10 10:05:00');

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
(3, 'Harmony Wellbeing Centre', 'pending', '2026-04-06 09:54:55', 1, 'Harmony Wellbeing Centre is a community-focused organisation dedicated to improving the mental, physical, and emotional wellbeing of local residents. We offer a range of therapeutic services including art therapy, music therapy, mindfulness workshops, and community support groups. Our mission is to foster a sense of belonging and cultural connection through inclusive and accessible wellbeing programmes tailored to the diverse needs of our community.', '+44 7700 900123', 10, 3),
(11, 'Brushstroke Studio', 'approved', '2026-04-09 08:36:23', 1, 'Brushstroke Studio is a vibrant creative hub in Hertfordshire offering art classes, music lessons, and movement workshops for all ages. We believe creativity is for everyone and provide a welcoming space for beginners and experienced artists alike.', '+44 7700 100001', 24, 1),
(12, 'Hatfield Theatre Collective', 'approved', '2026-04-09 08:37:51', 2, 'Hatfield Theatre Collective is a community-driven performing arts organisation staging live theatre, concerts and open mic nights. We celebrate local talent and bring the community together through the power of performance.', '+44 7700 100002', 25, 2),
(13, 'Hertfordshire Heritage Tours', 'approved', '2026-04-09 08:40:06', 3, 'Hertfordshire Heritage Tours offers immersive guided cultural walks and museum programmes that bring local history to life. Our expert guides lead residents and visitors through the rich cultural landscape of Hertfordshire.', '+44 7700 100003', 26, 3),
(14, 'PixelCraft Media', 'approved', '2026-04-09 08:41:59', 4, 'PixelCraft Media is a full-service creative agency specialising in photography, videography, graphic design and brand identity. We help local businesses and cultural organisations tell their stories through compelling visual content.', '+44 7700 100004', 27, 4),
(15, 'Hatfield Handmade Co.', 'approved', '2026-04-09 08:43:32', 5, 'Hatfield Handmade Co. is a collective of local artisans producing original artwork, handcrafted ceramics, cultural merchandise and artisan stationery. Every piece is made by hand and rooted in the cultural identity of Hertfordshire.', '+44 7700 100005', 28, 5),
(16, 'Hertford Ink Publishing', 'approved', '2026-04-09 08:45:03', 6, 'Hertford Ink Publishing is an independent publisher celebrating local voices through poetry collections, community zines, magazines and creative writing resources. We champion literary culture and support emerging writers across Hertfordshire.', '+44 7700 100006', 29, 6);

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
(6, '$2y$10$01mLimPT3Pddrd30S/gtEuweOQzUYFV6fUxOYXNVK.ItevTDMTv26', '2026-04-09 21:47:15', 'approved', 'Council Administrator', 'admin@cultureconnect.com'),
(8, '$2y$10$NdvqqVsckEpxpJfLVEokXe437ra6Nye/3TZycQoCDR/8uWAWnFWRa', '2026-04-04 18:16:05', 'approved', 'Resident', 'nike@gmail.com'),
(9, '$2y$10$dyS/3x2E0F5qFN3gM.tbv.SJg83R6Q8N93hBx.9CtiIO5j9psQd/C', '2026-04-09 21:27:48', 'approved', 'Resident', 'josephine@gmail.com'),
(10, '$2y$10$pD9RVt2XH2HZmJ/dQwJkI.DO1t0Wh0xEssZCFLjUW0g0CUIjIHlA6', '2026-04-08 11:47:21', 'approved', 'SME', 'info@harmonywellbeing.com'),
(11, '$2y$10$pEGsw9izjII4FX3gzzaJ4.QSDLc70c6nGub9/1IU78TLgASSZUsf.', '2026-04-07 08:16:06', 'approved', 'Resident', 'victor@gmail.com'),
(12, '$2y$10$aSQiHxr71NTJT0.ohlXxDezWv08wpyxyjnP1tR9q7tgxhcBsX.Uai', '2026-04-07 08:19:30', 'approved', 'Resident', 'jake@gmail.com'),
(13, '$2y$10$L680iiTY/OYAE9v./tg99O4N1T/ao6GKU/AnYCR6KNO4jLIimJKHu', '2026-04-08 11:28:32', 'approved', 'Resident', 'habeeb@gmail.com'),
(23, '$2y$10$UDiUYY47oKHijEltMpEDRusNoO93tFpEhQ389rybJJHXwzuNwcMiG', '2026-04-08 11:37:39', 'approved', 'Council Member', 'Cmember@cultureconnect.com'),
(24, '$2y$10$ViJxAEtd.j9tpIUKfzKPw.5pPGEFm4c5JlfxBXha4GgFbGeWXTzvC', '2026-04-09 21:28:44', 'approved', 'SME', 'hello@brushstrokestudio.com'),
(25, '$2y$10$eLhuVYx4TBSMRuld/k4THuwZ1XC2EhQG4fUCshbrKOlnRdgqz5Hva', '2026-04-09 18:58:45', 'approved', 'SME', 'info@hatfieldtheatre.com'),
(26, '$2y$10$AUciDVzSGaUL7jdXeUdkM.DhFyvuMxkxFa1J8xurcFsZV8qHyg14m', '2026-04-09 19:10:10', 'approved', 'SME', 'tours@hertheritagetours.com'),
(27, '$2y$10$.dWO5U3z4wW4FGMV32g3COS5QZbspNds4t8z853sYSp1ErZcuvvOO', '2026-04-09 19:17:47', 'approved', 'SME', 'studio@pixelcraftmedia.com'),
(28, '$2y$10$LT2xJjdlHYINbJNC5PaEuup2dqUpNQMvIxct0ueW3FJRfYgObhWtK', '2026-04-09 19:25:00', 'approved', 'SME', 'shop@hatfieldhandmade.com'),
(29, '$2y$10$HrsNx52kvUjSBIemM/6gcuaWiVz5LhBgpQMJz0pf4LxY4W1cPPC5m', '2026-04-09 19:35:19', 'approved', 'SME', 'press@hertfordink.com'),
(34, '$2y$10$sCH/qU1xp9OQjvBqIRP1FuasIU/k/tRQ5N5JV3wSQtVllyC68uQ.q', '2026-04-10 09:25:47', 'approved', 'Resident', 'marcus.osei@gmail.com'),
(35, '$2y$10$GSRl4qkAShFvWuyWsmdcyeB8OrG230vd7jUf9tCrV2vnbp8WlpIp6', '2026-04-10 09:27:46', 'approved', 'Resident', 'aisha.patel@gmail.com'),
(36, '$2y$10$35HzVBsn/LVPwMcI8XATwuhxDN/FaK.pa2ukr3nuS5ZBekQ0.09S.', '2026-04-10 09:29:29', 'rejected', 'Resident', 'daniel.mensah@gmail.com'),
(37, '$2y$10$/myEQxh6qX8hmvTsl.WEAel6s3SJrAe356dW3XO3cf71hAQsnfAwu', '2026-04-10 09:31:31', 'approved', 'Resident', 'fatima.ali@gmail.com'),
(38, '$2y$10$nz.w8p6IC/kDyXnGilKz8.PMMYUUFaIR1090MA2KvcvPYOhbgTgZS', '2026-04-10 09:33:34', 'approved', 'Resident', 'james.adeyemi@gmail.com'),
(39, '$2y$10$XpD6gNkKqGcC44.J1Z7Rs.kai6q6JkjmSEFH.zgHlNoam7EvfHGlC', '2026-04-10 09:35:33', 'approved', 'Resident', 'priya.sharma@gmail.com'),
(40, '$2y$10$ywh2.r3LbuUVd/VzOQ/6wej1RKXB.EcUVpGFE0H4ToW2KfGAHfmvi', '2026-04-10 09:36:58', 'approved', 'Resident', 'sarah.nkrumah@gmail.com'),
(41, '$2y$10$cu9oDMLdhSIgMNc1rdFB2.dTIOaAlut0tK2ksA2ReCG.3BriB2bla', '2026-04-10 09:38:53', 'approved', 'Resident', 'david.kofi@gmail.com'),
(42, '$2y$10$t1KGemGc.6azqpROkQ/YmOT24z5XhhjDM5csnmACFRieI.zkEKbka', '2026-04-10 09:45:08', 'pending', 'Resident', 'tariq.hussain@gmail.com'),
(43, '$2y$10$HufOb6B1aOSL2XRrwCV7Ze7NxNE1LX1oduX1pzbGIPlwNxJ1WHE9S', '2026-04-10 09:46:55', 'pending', 'Resident', 'siobhan.oconnor@gmail.com'),
(44, '$2y$10$x1vCCutsYAkIALta/IzB/OwuVjieM6HfMTtUV/BLNot5kjkgfRCwm', '2026-04-10 09:49:09', 'pending', 'Resident', 'samuel.tally@gmail.com'),
(45, '$2y$10$1N6upPakt9SvA2705zNUF.tx5VF5hfjzlabv4BXhFgb7C1/6jcXNy', '2026-04-10 09:52:08', 'pending', 'SME', 'info@africanheritagecrafts.co.uk'),
(46, '$2y$10$yzbl51n2Sa4whe2ggwRicuhjxExcYsKm3DoGNfDfIlClcVsYbZ6v6', '2026-04-10 09:54:48', 'pending', 'SME', 'hello@hertsmusicschool.co.uk'),
(47, '$2y$10$EdYx3Tr2i3A.w0ayszXgDu/KgyWJbGdyzBns8BLuenmvpInRtm1xu', '2026-04-10 09:57:07', 'pending', 'SME', 'contact@communityfilmherts.co.uk');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `enforce_document_verification_before_approval` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    DECLARE verified_docs INT;

    IF NEW.account_status = 'approved' AND OLD.account_status != 'approved' THEN
        SELECT COUNT(*) INTO verified_docs
        FROM user_documents
        WHERE user_id = NEW.user_id
          AND verification_status = 'approved';

        IF verified_docs = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'User cannot be approved without at least one verified document';
        END IF;
    END IF;
END
$$
DELIMITER ;

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
(1, 8, 'Bank_Statement', '../uploads/verification_documents/1775330165_bank_statement.pdf', 'approved', '2026-04-04 18:16:05'),
(2, 9, 'Bank_Statement', '../uploads/verification_documents/1775418264_bank_statement.pdf', 'approved', '2026-04-05 18:44:24'),
(3, 10, 'Bank_Statement', '../uploads/verification_documents/1775472895_bank_statement.pdf', 'approved', '2026-04-06 09:54:55'),
(4, 11, 'Bank_Statement', '../uploads/verification_documents/1775553366_bank_statement.pdf', 'approved', '2026-04-07 08:16:06'),
(5, 12, 'Bank_Statement', '../uploads/verification_documents/1775553570_bank_statement.pdf', 'approved', '2026-04-07 08:19:30'),
(6, 13, 'Bank_Statement', '../uploads/verification_documents/1775553662_bank_statement.pdf', 'approved', '2026-04-07 08:21:02'),
(7, 23, 'Bank_Statement', '../uploads/verification_documents/1775647905_bank_statement.pdf', 'approved', '2026-04-08 11:31:45');

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
-- Structure for view `resident_product_service_interest`
--
DROP TABLE IF EXISTS `resident_product_service_interest`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `resident_product_service_interest`  AS SELECT `ps`.`item_name` AS `product_name`, `l`.`title` AS `listing_title`, `l`.`price` AS `price`, coalesce(`vote_score`.`total_likes`,0) AS `total_likes`, coalesce(`vote_score`.`total_dislikes`,0) AS `total_dislikes`, sum(coalesce(`vote_score`.`score`,0) + coalesce(`review_score`.`score`,0) + coalesce(`booking_score`.`score`,0)) AS `ranking` FROM ((((`listings` `l` join `product_service` `ps` on(`l`.`item_id` = `ps`.`item_id`)) left join (select `listing_votes`.`listing_id` AS `listing_id`,sum(case when `listing_votes`.`vote_type` = 'like' then 1 else 0 end) AS `total_likes`,sum(case when `listing_votes`.`vote_type` = 'dislike' then 1 else 0 end) AS `total_dislikes`,sum(case when `listing_votes`.`vote_type` = 'like' then 1 when `listing_votes`.`vote_type` = 'dislike' then -1 else 0 end) AS `score` from `listing_votes` group by `listing_votes`.`listing_id`) `vote_score` on(`vote_score`.`listing_id` = `l`.`listing_id`)) left join (select `product_service_reviews`.`listing_id` AS `listing_id`,sum(`product_service_reviews`.`rating`) AS `score` from `product_service_reviews` group by `product_service_reviews`.`listing_id`) `review_score` on(`review_score`.`listing_id` = `l`.`listing_id`)) left join (select `service_bookings`.`listing_id` AS `listing_id`,count(0) AS `score` from `service_bookings` where `service_bookings`.`status` = 'confirmed' and `service_bookings`.`listing_id` is not null group by `service_bookings`.`listing_id`) `booking_score` on(`booking_score`.`listing_id` = `l`.`listing_id`)) GROUP BY `ps`.`item_name`, `l`.`title`, `l`.`price`, `vote_score`.`total_likes`, `vote_score`.`total_dislikes` ORDER BY sum(coalesce(`vote_score`.`score`,0) + coalesce(`review_score`.`score`,0) + coalesce(`booking_score`.`score`,0)) DESC ;

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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sme_listings_overview`  AS SELECT `s`.`business_name` AS `business_name`, `l`.`title` AS `listing_title`, `ps`.`item_name` AS `item_name`, `psc`.`category_name` AS `category_name`, `l`.`price` AS `price`, `l`.`status` AS `status` FROM ((((`sme_profiles` `s` join `listings` `l` on(`s`.`sme_id` = `l`.`sme_id`)) join `product_service` `ps` on(`l`.`item_id` = `ps`.`item_id`)) join `product_service_subcategories` `pss` on(`ps`.`subcategory_id` = `pss`.`subcategory_id`)) join `product_service_categories` `psc` on(`pss`.`category_id` = `psc`.`category_id`)) ;

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
-- Indexes for table `listing_votes`
--
ALTER TABLE `listing_votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD UNIQUE KEY `unique_user_listing` (`user_id`,`listing_id`),
  ADD KEY `listing_id` (`listing_id`);

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
  MODIFY `listing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `listing_images`
--
ALTER TABLE `listing_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `listing_requests`
--
ALTER TABLE `listing_requests`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `listing_votes`
--
ALTER TABLE `listing_votes`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `product_service`
--
ALTER TABLE `product_service`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
  MODIFY `subcategory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `resident_profiles`
--
ALTER TABLE `resident_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `sme_profiles`
--
ALTER TABLE `sme_profiles`
  MODIFY `sme_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `user_documents`
--
ALTER TABLE `user_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
-- Constraints for table `listing_votes`
--
ALTER TABLE `listing_votes`
  ADD CONSTRAINT `listing_votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `listing_votes_ibfk_2` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_booking_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE ON UPDATE CASCADE,
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
