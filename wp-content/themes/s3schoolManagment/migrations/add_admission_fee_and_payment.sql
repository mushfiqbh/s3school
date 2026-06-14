/* =============================================
|     Admission Fee and Payment Tracking       |
|            Migration Script                   |
============================================== */

-- Create admission fee table
DROP TABLE IF EXISTS `ct_admission_fee_promoted`;
CREATE TABLE `ct_admission_fee_promoted` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_class_fee` (`class`),
  CONSTRAINT `fk_class_fee` FOREIGN KEY (`class`) REFERENCES `ct_class` (`classid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add payment status field to ct_online_application if it doesn't exist
SET @stmt = IF(
    NOT EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ct_online_application' AND COLUMN_NAME='payment_status'
    ),
    'ALTER TABLE ct_online_application ADD COLUMN payment_status ENUM(''Pending'',''Paid'',''Partial'') NOT NULL DEFAULT ''Pending'' AFTER approve_status;',
    'SELECT "Column payment_status already exists in ct_online_application";'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add payment_amount field to ct_online_application if it doesn't exist
SET @stmt = IF(
    NOT EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ct_online_application' AND COLUMN_NAME='payment_amount'
    ),
    'ALTER TABLE ct_online_application ADD COLUMN payment_amount decimal(10,2) DEFAULT NULL AFTER payment_status;',
    'SELECT "Column payment_amount already exists in ct_online_application";'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add payment_date field to ct_online_application if it doesn't exist
SET @stmt = IF(
    NOT EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ct_online_application' AND COLUMN_NAME='payment_date'
    ),
    'ALTER TABLE ct_online_application ADD COLUMN payment_date timestamp NULL DEFAULT NULL AFTER payment_amount;',
    'SELECT "Column payment_date already exists in ct_online_application";'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Insert sample admission fees (adjust amounts as needed)
INSERT IGNORE INTO `ct_admission_fee_promoted` (`class`, `amount`, `is_active`) 
SELECT classid, 5000.00, 1 FROM ct_class;

COMMIT;
