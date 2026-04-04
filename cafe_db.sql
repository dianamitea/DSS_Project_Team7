SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
;

-- Database: `cafe_db`
-- Table structure for table `categories`

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `categories`

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Breads'),
(2, 'Cakes'),
(3, 'Coffee'),
(4, 'Cold Drinks'),
(5, 'Pastries'),
(6, 'Sandwiches'),
(7, 'Specialty Coffee'),
(8, 'Tarts'),
(9, 'Tea');




-- Table structure for table `orders`

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `orders`

INSERT INTO `orders` (`id`, `user_id`, `total_price`, `status`, `created_at`) VALUES
(5, 1, 12.25, 'completed', '2026-03-28 18:46:57'),
(6, 2, 9.50, 'completed', '2026-03-30 17:46:57'),
(7, 1, 16.75, '', '2026-04-01 17:46:57'),
(8, 3, 8.00, 'completed', '2026-04-02 17:46:57'),
(9, 2, 21.50, 'pending', '2026-04-03 17:46:57'),
(10, NULL, 11.00, 'pending', '2026-04-03 18:46:45'),
(11, NULL, 6.00, 'pending', '2026-04-03 19:41:10'),
(12, NULL, 11.00, 'pending', '2026-04-03 19:46:19'),
(13, NULL, 6.00, 'pending', '2026-04-03 20:52:46'),
(14, NULL, 6.00, 'pending', '2026-04-03 23:14:36');

-- Table structure for table `products`

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `products`

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image_url`) VALUES
(1, 3, 'Espresso', 'Strong and classic.', 2.50, 'espresso.jpg'),
(2, 3, 'Cappuccino', 'Creamy with foam.', 3.50, 'cappuccino.jpg'),
(3, 2, 'Chocolate Cake', 'Rich dark chocolate.', 4.00, 'cake.jpg'),
(4, 6, 'Club Sandwich', 'Triple-decker with grilled chicken, crispy bacon, lettuce, tomato and mayo on toasted white bread.', 6.50, 'club_sandwich.jpg'),
(5, 6, 'Tuna Melt', 'Creamy tuna salad with melted cheddar on sourdough, served warm.', 5.75, 'tuna_melt.jpg'),
(6, 6, 'Veggie Wrap', 'Grilled peppers, hummus, cucumber, spinach and feta rolled in a whole-wheat tortilla.', 5.25, 'veggie_wrap.jpg'),
(7, 6, 'BLT Panini', 'Bacon, fresh lettuce and sun-dried tomatoes pressed in a ciabatta roll.', 5.50, 'blt_panini.jpg'),
(8, 9, 'Green Tea', 'Delicate Japanese Sencha green tea, light and refreshing.', 3.00, 'green_tea.jpg'),
(9, 9, 'Earl Grey', 'Classic black tea blended with bergamot oil, served with a lemon slice.', 3.00, 'earl_grey.jpg'),
(10, 9, 'Chamomile Herbal Tea', 'Soothing caffeine-free chamomile blossoms, perfect any time of day.', 3.25, 'chamomile_tea..jpg'),
(11, 9, 'Chai Latte', 'Spiced black tea with cinnamon, cardamom and steamed milk.', 4.25, 'chai_latte.jpg'),
(12, 9, 'Matcha Latte', 'Ceremonial-grade matcha whisked with frothy oat milk.', 4.75, 'matcha_latte.jpg'),
(13, 4, 'Iced Latte', 'Double espresso poured over ice with your choice of milk.', 4.50, 'iced_latte.jpg'),
(14, 4, 'Mango Smoothie', 'Fresh mango blended with banana, orange juice and a hint of ginger.', 5.00, 'mango_smootie.jpg'),
(15, 4, 'Strawberry Lemonade', 'House-made lemonade infused with fresh strawberry purée.', 4.00, 'strawberry_lemonade.jpg'),
(16, 4, 'Cold Brew Coffee', 'Slow-steeped for 18 hours, smooth with low acidity. Served over ice.', 4.75, 'cold_brew.jpg'),
(17, 4, 'Sparkling Water', 'Chilled sparkling mineral water with a slice of lemon or lime.', 2.50, 'sparkling_water.jpg'),
(19, 7, 'Flat White', 'Ristretto espresso with a silky microfoam – the barista\s choice."', 4.00, 'flat_white.jpg'),
(20, 7, 'Caramel Macchiato', 'Vanilla syrup, steamed milk, espresso and a drizzle of caramel sauce.', 5.00, 'caramel_macchiato.jpg'),
(21, 7, 'Hazelnut Mocha', 'Espresso blended with rich chocolate sauce and hazelnut syrup, topped with whipped cream.', 5.25, 'hazelnut_mocha.jpg'),
(22, 5, 'Blueberry Muffin', 'Freshly baked muffin bursting with juicy blueberries and a golden crumble top.', 3.50, 'blueberry_muffin.jpg'),
(23, 5, 'Croissant', 'Buttery, flaky all-butter croissant baked fresh every morning.', 3.25, 'croissant.jpg'),
(24, 8, 'Lemon Tart', 'A delicate lemon tart with a crisp, buttery crust filled with smooth, tangy lemon cream—perfectly balanced between sweet and refreshing', 6.50, 'lemon_tart.jpg'),
(25, 1, 'Sourdough Bread', 'A rustic sourdough loaf with a crisp, golden-brown crust and a soft, airy interior—rich in flavor with a subtle tang, handcrafted for a perfect balance of texture and depth.', 4.00, 'sourdough_bread.jpg)');




-- Table structure for table `reservations`

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `res_date` date NOT NULL,
  `res_time` time NOT NULL,
  `guests` int(11) NOT NULL,
  `status` enum('confirmed','cancelled') DEFAULT 'confirmed',
  `notes` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `res_date`, `res_time`, `guests`, `status`, `notes`) VALUES
(1, 1, '2026-04-04', '12:00:00', 2, 'confirmed', NULL),
(2, 2, '2026-04-05', '19:30:00', 4, 'confirmed', NULL),
(3, 3, '2026-04-06', '13:00:00', 3, '', NULL),
(4, 1, '2026-04-08', '20:00:00', 2, '', NULL),
(5, 2, '2026-04-10', '18:00:00', 6, 'confirmed', NULL),
(6, 10, '2026-04-14', '13:00:00', 2, 'confirmed', 'Testy Test');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'client1', 'client1@example.com', '123456', '2026-04-03 17:40:22'),
(2, 'client2', 'client2@example.com', '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW', '2026-04-03 17:40:22'),
(3, 'client3', 'client3@example.com', '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW', '2026-04-03 17:40:22'),
(10, 'didyboom', 'dianamitea16@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$QzRVaURjaU96eWdBMnNOOA$CyhCa5agzdhc/yanMTkHxlOUdTqP8T0OdsXZoEZrf3g', '2026-04-04 13:47:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
