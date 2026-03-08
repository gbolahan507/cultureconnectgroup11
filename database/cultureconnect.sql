-- CultureConnect Database
-- Group 11

-- Create database
CREATE DATABASE IF NOT EXISTS cultureconnect;
USE cultureconnect;

-- =====================
-- TABLE: areas
-- =====================
CREATE TABLE areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================
-- TABLE: residents
-- =====================
CREATE TABLE residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    area_id INT,
    age_group VARCHAR(20),
    gender VARCHAR(20),
    interests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL
);

-- =====================
-- TABLE: platforms
-- =====================
CREATE TABLE platforms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================
-- TABLE: products
-- =====================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50),
    availability ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (platform_id) REFERENCES platforms(id) ON DELETE CASCADE
);

-- =====================
-- TABLE: votes
-- =====================
CREATE TABLE votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT,
    product_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- =====================
-- SAMPLE DATA: areas
-- =====================
INSERT INTO areas (name) VALUES
('North London'),
('South London'),
('East London'),
('West London'),
('Central London');

-- =====================
-- SAMPLE DATA: platforms
-- =====================
INSERT INTO platforms (name, description) VALUES
('African Arts Hub', 'A platform showcasing traditional and contemporary African art'),
('Local Craft Market', 'Handmade crafts from local artisans'),
('Cultural Kitchen', 'Authentic cultural food experiences and cooking classes'),
('Heritage Dance Academy', 'Traditional dance classes from various cultures'),
('World Music Collective', 'Live music events and instrument workshops');

-- =====================
-- SAMPLE DATA: products
-- =====================
INSERT INTO products (platform_id, name, description, price, category) VALUES
-- African Arts Hub products
(1, 'Handmade Pottery', 'Traditional African pottery with modern designs', 75.00, 'Art'),
(1, 'Wooden Sculpture', 'Hand-carved wooden sculptures', 250.00, 'Art'),
(1, 'Beaded Jewelry Set', 'Colorful beaded necklace and bracelet set', 45.00, 'Accessories'),
(1, 'Canvas Painting', 'Original African landscape painting', 180.00, 'Art'),

-- Local Craft Market products
(2, 'Handwoven Basket', 'Traditional woven storage basket', 35.00, 'Crafts'),
(2, 'Leather Bag', 'Handcrafted leather shoulder bag', 120.00, 'Accessories'),
(2, 'Ceramic Vase', 'Hand-painted decorative vase', 55.00, 'Crafts'),

-- Cultural Kitchen products
(3, 'Cooking Class - Caribbean', '3-hour Caribbean cooking experience', 65.00, 'Experience'),
(3, 'Cooking Class - West African', '3-hour West African cooking experience', 65.00, 'Experience'),
(3, 'Spice Box Collection', 'Set of 10 authentic cultural spices', 40.00, 'Food'),

-- Heritage Dance Academy products
(4, 'African Dance Workshop', '2-hour group dance session', 30.00, 'Experience'),
(4, 'Private Dance Lesson', '1-hour one-on-one dance instruction', 80.00, 'Experience'),
(4, 'Monthly Dance Pass', 'Unlimited classes for one month', 150.00, 'Experience'),

-- World Music Collective products
(5, 'Drumming Workshop', '2-hour djembe drumming class', 45.00, 'Experience'),
(5, 'Live Concert Ticket', 'Entry to monthly world music concert', 25.00, 'Event'),
(5, 'Instrument Rental - Weekly', 'Rent a traditional instrument for a week', 50.00, 'Service');

-- =====================
-- SAMPLE DATA: residents
-- =====================
INSERT INTO residents (name, email, password, area_id, age_group, gender, interests) VALUES
('John Smith', 'john@example.com', 'password123', 1, '26-35', 'Male', 'Art, Music'),
('Sarah Johnson', 'sarah@example.com', 'password123', 2, '18-25', 'Female', 'Dance, Food'),
('Michael Brown', 'michael@example.com', 'password123', 3, '36-45', 'Male', 'Crafts, Art'),
('Emily Davis', 'emily@example.com', 'password123', 1, '26-35', 'Female', 'Music, Experience'),
('David Wilson', 'david@example.com', 'password123', 4, '46-55', 'Male', 'Food, Crafts');

-- =====================
-- SAMPLE DATA: votes
-- =====================
INSERT INTO votes (resident_id, product_id) VALUES
(1, 1),  -- John voted for Handmade Pottery
(1, 4),  -- John voted for Canvas Painting
(1, 14), -- John voted for Drumming Workshop
(2, 11), -- Sarah voted for African Dance Workshop
(2, 8),  -- Sarah voted for Cooking Class - Caribbean
(2, 15), -- Sarah voted for Live Concert Ticket
(3, 5),  -- Michael voted for Handwoven Basket
(3, 1),  -- Michael voted for Handmade Pottery
(3, 7),  -- Michael voted for Ceramic Vase
(4, 14), -- Emily voted for Drumming Workshop
(4, 15), -- Emily voted for Live Concert Ticket
(4, 12), -- Emily voted for Private Dance Lesson
(5, 8),  -- David voted for Cooking Class - Caribbean
(5, 10), -- David voted for Spice Box Collection
(5, 6);  -- David voted for Leather Bag
