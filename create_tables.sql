-- Create messages table
CREATE TABLE IF NOT EXISTS `messages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) NOT NULL,
  `to_address` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `intro` text DEFAULT NULL,
  `raw_json` json DEFAULT NULL,
  `created_at_api` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `messages_message_id_unique` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create otp_codes table
CREATE TABLE IF NOT EXISTS `otp_codes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) NOT NULL,
  `to_address` varchar(255) NOT NULL,
  `otp` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `otp_codes_message_id_unique` (`message_id`),
  KEY `otp_codes_to_address_index` (`to_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
