-- --------------------------------------------------------
-- Table structure for table `payment_transfer_log`
-- 
-- Stores detailed audit log of all payment transfers and
-- student claims, preserving both source and target data.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `payment_transfer_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `transfer_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `payment_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `source_student_id` bigint(20) DEFAULT NULL,
  `source_student_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_student_roll` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_student_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_student_section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_student_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_student_facilities` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_student_data_raw` longtext COLLATE utf8mb4_unicode_ci,
  `target_student_id` bigint(20) DEFAULT NULL,
  `target_student_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_student_roll` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_student_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_student_section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_student_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_student_facilities` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_student_data_raw` longtext COLLATE utf8mb4_unicode_ci,
  `fee_data_snapshot` longtext COLLATE utf8mb4_unicode_ci,
  `note` text COLLATE utf8mb4_unicode_ci,
  `claimed_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claimed_wrong_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claimed_wrong_section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claimed_wrong_roll` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claimed_wrong_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `performed_by` bigint(20) DEFAULT NULL,
  `performed_by_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_id` (`payment_id`),
  KEY `source_student_id` (`source_student_id`),
  KEY `target_student_id` (`target_student_id`),
  KEY `transfer_type` (`transfer_type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
