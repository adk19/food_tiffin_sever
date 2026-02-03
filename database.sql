-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 20, 2026 at 03:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

CREATE DATABASE IF NOT EXISTS tiffin
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE tiffin;

--
-- Database: `tiffin`
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

START TRANSACTION;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `reset_token` VARCHAR(64) NULL DEFAULT NULL,
  `reset_token_expires` DATETIME NULL DEFAULT NULL,
  `last_password_change` TIMESTAMP NULL DEFAULT NULL
  `created_at` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`full_name`, `email`, `password`, `created_at`) VALUES
('admin', 'admin@gmail.com', '$2y$12$FXmfTyykBxSqKfcLnt1QD.WCnrA1gd3N5f21GJ10oZeNA9g5F7v1C', 1759062956);

-- password: admin123

-- ========================================================

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(40) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) NULL DEFAULT NULL,
  `reset_token_expires` datetime NULL DEFAULT NULL,
  `address` varchar(255) NOT NULL DEFAULT '',
  `created_at` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(120) NOT NULL,
  `detail` varchar(255) NOT NULL DEFAULT '',
  `image` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`name`, `detail`, `image`) VALUES
('Vegetables (Sabji)', 'Different types of vegetable curries and dry dishes (Paneer, Potato, Brinjal, Mix Veg).', 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758891301/traditional-indian-soup-lentils-indian-dhal-spicy-curry-bowl-spices-herbs-rustic-black-wooden-table_1_1_evyvyx.jpg'),
('Lentils (Dal)', 'Protein-rich lentil dishes like Toor, Moong, Masoor, Dal Fry, Dal Tadka.', 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758891252/indian-dhal-spicy-curry-bowl-spices-herbs-rustic-black-wooden-table_1_rfwx54.jpg'),
('Breads (Roti / Naan / Paratha)', 'Indian flatbreads such as Roti, Butter Roti, Naan, Paratha, Puri.', 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758891456/basket-breads-black-surface_tvbrwg.jpg'),
('Rice / Biryani', 'Rice-based meals like Plain Rice, Jeera Rice, Pulao, Veg Biryani, Khichdi, Curd Rice.', 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758891455/plate-biryani-with-bunch-food-it_k3yjp3.jpg'),
('Salads', 'Fresh and healthy sides including Green Salad, Onion Salad, Sprout Salad, Fruit Salad.', 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758891396/top-view-tasty-salad-with-vegetables_qmh0zf.jpg'),
('Sweets / Desserts', 'Traditional Indian desserts like Gulab Jamun, Kheer, Halwa, Jalebi, Ice Cream.', 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758891009/vertical-high-angle-shot-traditional-indian-sweet-made-wheat-flour-jaggery_qd7j8g.jpg'),
('Beverages / Cold Drinks', 'Refreshing drinks such as Buttermilk, Lassi, Lemon Water, Juice, Soft Drinks.', 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758891354/close-up-alcohol-cocktails-glasses-blue-lagoon-cocktail-decorated-with-lemon-glass-cocktail-with-whiskey-wooden-stand_q0likt.jpg'),
('Snacks / Side Items', 'Extra sides like Papad, Pickle, Chutney, Farsan, Bhajiya.', 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758891253/half-top-view-different-snacks-crackers-nuts-crisps-dark-grey-surface-snack-crisp-cracker-salt_1_ef9gm1.jpg');

-- ========================================================

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `category_id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `detail` varchar(255) NOT NULL DEFAULT '',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) NOT NULL DEFAULT '',
  CONSTRAINT `fk_menu_category`
    FOREIGN KEY (`category_id`)
    REFERENCES `categories`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`category_id`, `name`, `detail`, `price`, `discount`, `image`) VALUES
(8, 'Samosa', 'Crispy fried pastry stuffed with spiced potatoes & peas.', 20.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758906468/samsa-samosas-with-meat_vxasjm.jpg'),
(8, 'Kachori', 'Round deep-fried snack filled with lentils or spicy mix.', 25.00, 0, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758906624/chole-bhature-banana-leaf_qhkon4.jpg'),
(8, 'Pakora / Bhajiya', 'Fritters made with gram flour batter (onion, potato, or mix veg).', 30.00, 5, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758906899/images_mlgrxy.jpg'),
(8, 'Papad', 'Thin crispy wafer made from lentil flour (fried or roasted).', 10.00, 0, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758907064/360_F_55231952_mE2OfUMAO3d4fkG92NYKNaWtj2cEdVgF_hmxrwd.jpg'),
(8, 'Pickle (Achar)', 'Spicy preserved condiment (mango, lemon, mixed veg).', 15.00, 0, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758907167/mango-pickle-recipe_yhszg9.jpg'),
(8, 'Chutney', 'Fresh dips like mint, tamarind, garlic, or coconut chutney.', 10.00, 0, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758907216/images_2_jwvy0v.jpg'),
(8, 'Raita', 'Curd-based side dish with boondi, cucumber, or onion.', 25.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758907295/images_3_mt9md9.jpg'),
(7, 'Lassi (Sweet / Salted)', 'Thick curd drink, sweet or salted, very cooling.', 40.00, 15, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759091873/20180511-salt-lassi-0061-500x500_lozbt7.jpg'),
(7, 'Orange Juice', 'Freshly squeezed orange juice.', 50.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759092175/Orangejuice_qe0i6i.jpg'),
(7, 'Pineapple Juice', 'Tropical tangy pineapple juice.', 50.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759092249/How-to-Juice-a-Pineapple-Square-500x500_r4nddx.jpg'),
(7, 'Sprite / 7Up', 'Lemon-flavored carbonated drink.', 35.00, 5, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759092308/main-qimg-70fc3c8f6dc1bb63e766b559216c86f4-lq_mgigsv.jpg'),
(6, 'Rasgulla', 'Spongy cottage cheese balls in light syrup.', 35.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759092456/Featured-Image-500x500_kq9vdk.jpg'),
(6, 'Kaju Katli', 'Cashew nut diamond-shaped sweet.', 80.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759092529/Kaju-Katli_bza3li.avif'),
(6, 'Kheer (Rice Pudding)', 'Rice cooked in milk with sugar, nuts & cardamom.', 50.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759092606/Kheer-Indian-Rice-Pudding-Featured-500x375_jb5teg.webp'),
(5, 'Onion Salad', 'Fresh sliced onions with lemon & salt.', 20.00, 5, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759092691/onion-salad-recipe-1_ulcfe0.webp'),
(5, 'Tomato Salad', 'Juicy tomato slices with salt & pepper.', 20.00, 5, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759092834/The-Best-Tomato-Salad_0-SQ_fxmvyc.webp'),
(5, 'Fruit Salad', 'Seasonal fruits served fresh.', 60.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759092892/SummerFruitSalad-2_juhj0f.webp'),
(4, 'Veg Pulao', 'Fragrant rice cooked with vegetables & mild spices.', 90.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093155/pulao-recipe_vuejj3.jpg'),
(4, 'Veg Biryani', 'Layered rice & vegetables cooked with spices.', 100.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093229/veg-biryani-recipe-500x500_nmqitb.jpg'),
(3, 'Plain Paratha', 'Layered paratha cooked with ghee.', 25.00, 5, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093367/Whole-wheat-Paratha_fg8qwe.jpg'),
(3, 'Butter Naan', 'Naan brushed with butter.', 35.00, 5, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093425/images_4_wafzav.jpg'),
(3, 'Lachha Paratha', 'Multi-layered crispy paratha.', 40.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093495/53318868_gct3pj.gif'),
(2, 'Dal Fry', 'Toor dal cooked with onion, tomato & masala.', 80.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093651/dal-fry-500x500_rdiogk.jpg'),
(1, 'Bhindi Masala', 'Ladyfinger cooked with onion & tomato masala.', 80.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093798/Bhindi-Masala-2_rt5dg9.jpg'),
(1, 'Paneer Butter Masala', 'Cottage cheese in rich tomato gravy.', 150.00, 15, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093853/dhaba-style-paneer-butter-masala-1641643202_fp4row.jpg'),
(1, 'Malai Kofta', 'Soft paneer & potato balls in creamy gravy.', 180.00, 15, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093910/Best-Malai-Kofta-recipe-500x500_ghih7b.jpg'),
(1, 'Methi Malai Matar', 'Fenugreek leaves & peas in creamy curry.', 160.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093988/methi-matar-malai-recipe_bh8b8s.jpg'),
(8, 'Dokla', 'Extra sides like Papad, Pickle, Chutney, Farsan, Bhajiya.', 35.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1758906980/images_1_ngcqsj.jpg'),
(7, 'Buttermilk (Chaas)', 'Refreshing yogurt-based drink with cumin & spices.', 25.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759091829/Two-Homemade-Masala-Chaas-Recipes-H2_lqh7bu.webp'),
(6, 'Gulab Jamun', 'Soft fried balls soaked in sugar syrup.', 40.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759092401/63799510_etizlp.avif'),
(5, 'Cucumber Salad', 'Chilled cucumber slices with chaat masala.', 25.00, 5, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759092801/Cucumber-Tomato-Salad-SpendWithPennies-3_irwzsi.jpg'),
(4, 'Plain Steamed Rice', 'Soft boiled white rice.', 40.00, 5, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093087/1200x1200-boiled-fluffy-rice-500x500_b9frrs.jpg'),
(3, 'Tawa Roti (Phulka)', 'Soft whole wheat roti cooked on tawa.', 10.00, 0, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093329/Phulka-7-1_qmgfjv.webp'),
(2, 'Dal Tadka', 'Yellow dal tempered with ghee, garlic & spices.', 70.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093592/images_5_rpcsfu.jpg'),
(1, 'Aloo Sabji', 'Simple potato curry with Indian spices.', 60.00, 10, 'https://res.cloudinary.com/dmruu3c4i/image/upload/v1759093735/rajasthani-dahi-ke-aloo-sabji-recipe_hqj2gl.jpg');

-- ========================================================

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `item_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','confirmed','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_id` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_item` FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orderitems_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orderitems_menuitem` FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(40) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('unread','read','replied') NOT NULL DEFAULT 'unread',
  `created_at` int(11) NOT NULL,
  CONSTRAINT `fk_contact_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;