CREATE TABLE `product` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `picture_id` BIGINT UNSIGNED NULL,
  `manual_id` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_picture_id` (`picture_id`),
  KEY `idx_product_manual_id` (`manual_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
