-- Common settings for all tables
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET COLLATION_CONNECTION = utf8mb4_general_ci;

-- Genres table - stores product categories
CREATE TABLE `genres` (
`genre_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`genre_name` VARCHAR(100) NOT NULL,
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Members table - stores user account information
CREATE TABLE `members` (
`member_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`last_name` VARCHAR(100) NOT NULL,
`first_name` VARCHAR(100) NOT NULL,
`last_name_kana` VARCHAR(100) NOT NULL DEFAULT '',
`first_name_kana` VARCHAR(100) NOT NULL DEFAULT '',
`postal_code` CHAR(8) NOT NULL,
`address` VARCHAR(255) NOT NULL,
`phone_number` VARCHAR(20) NOT NULL,
`email` VARCHAR(255) NOT NULL UNIQUE,
`password` VARCHAR(255) NOT NULL,
`withdrawal_status` BOOLEAN NOT NULL DEFAULT 0,
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Shipping addresses table - stores multiple delivery addresses per member
CREATE TABLE `shipping_addresses` (
`shipping_address_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`member_id` INT UNSIGNED NOT NULL,
`postal_code` CHAR(8) NOT NULL,
`address` VARCHAR(255) NOT NULL,
`phone_number` VARCHAR(20) NOT NULL,
`recipient` VARCHAR(100) NOT NULL,
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Products table - stores product information
CREATE TABLE `products` (
`product_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`genre_id` INT UNSIGNED NOT NULL,
`product_name` VARCHAR(255) NOT NULL,
`product_image` VARCHAR(255) NOT NULL,
`description` TEXT NOT NULL,
`sales_status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
`price_without_tax` DECIMAL(10,0) NOT NULL,
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (`genre_id`) REFERENCES `genres`(`genre_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Cart items table - temporary storage for items before order
CREATE TABLE `cart_items` (
`cart_item_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`member_id` INT UNSIGNED NOT NULL,
`product_id` INT UNSIGNED NOT NULL,
`quantity` INT UNSIGNED NOT NULL CHECK (`quantity` > 0),
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Orders table - stores order header information
CREATE TABLE `orders` (
`order_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`member_id` INT UNSIGNED NOT NULL,
`shipping_recipient` VARCHAR(100) NOT NULL,
`shipping_postal_code` CHAR(8) NOT NULL,
`shipping_address` VARCHAR(255) NOT NULL,
`shipping_phone_number` VARCHAR(20) NOT NULL,
`billing_amount` DECIMAL(10,0) NOT NULL,
`payment_method` TINYINT UNSIGNED NOT NULL,
`order_status` ENUM('new', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'new',
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Order items table - stores order detail information
CREATE TABLE `order_items` (
`order_item_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`order_id` INT UNSIGNED NOT NULL,
`product_id` INT UNSIGNED NOT NULL,
`purchase_price_including_tax` DECIMAL(10,0) NOT NULL,
`quantity` INT UNSIGNED NOT NULL CHECK (`quantity` > 0),
`production_status` ENUM('not_started', 'in_progress', 'completed', 'preparing_shipment', 'shipped') NOT NULL DEFAULT
'not_started',
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Administrators table - stores admin user information
CREATE TABLE `administrators` (
`administrator_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`email` VARCHAR(255) NOT NULL UNIQUE,
`password` VARCHAR(255) NOT NULL,
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;