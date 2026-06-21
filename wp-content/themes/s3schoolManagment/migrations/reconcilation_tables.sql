-- ==============================================================
-- Reconciliation Tables for Payment Mismatch Management
-- Detects & resolves duplicate payments, wrong-student payments,
-- overpayments, and unidentified PayStation transactions.
-- ==============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- -----------------------------------------------------------
-- 1. Payment Mismatch — stores detected payment issues
-- Types: DUPLICATE_PAYMENT | PAID_WRONG_STUDENT | OVERPAYMENT
--        UNIDENTIFIED_PAYMENT | BANK_RECONCILIATION_ERROR
-- Status: PENDING | CLAIMED | APPROVED | REJECTED | TRANSFERRED
--         REFUNDED | CLOSED
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ct_payment_mismatch` (
  `mismatch_id`        bigint(20) NOT NULL AUTO_INCREMENT,
  `payment_id`         varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Reference to paystation_transactions.payment_id',
  `collection_info_id` int(11) DEFAULT NULL COMMENT 'Reference to ct_student_fee_collection_info.id',
  `student_id`         bigint(20) DEFAULT NULL COMMENT 'Affected student',
  `mismatch_type`      varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'DUPLICATE_PAYMENT|PAID_WRONG_STUDENT|OVERPAYMENT|UNIDENTIFIED_PAYMENT|BANK_RECONCILIATION_ERROR',
  `amount`             decimal(10,2) NOT NULL DEFAULT '0.00',
  `description`        text COLLATE utf8mb4_unicode_ci,
  `status`             varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT 'PENDING|CLAIMED|APPROVED|REJECTED|TRANSFERRED|REFUNDED|CLOSED',
  `detected_at`        datetime DEFAULT NULL,
  `detected_by`        int(11) DEFAULT NULL,
  `resolved_at`        datetime DEFAULT NULL,
  `created_at`         datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mismatch_id`),
  KEY `payment_id` (`payment_id`),
  KEY `student_id` (`student_id`),
  KEY `mismatch_type` (`mismatch_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 2. Mismatch Claim — students claim ownership of wrong payments
-- Status: PENDING | APPROVED | REJECTED
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ct_mismatch_claim` (
  `claim_id`           bigint(20) NOT NULL AUTO_INCREMENT,
  `mismatch_id`        bigint(20) NOT NULL COMMENT 'FK to ct_payment_mismatch',
  `claim_student_id`   bigint(20) NOT NULL COMMENT 'Student submitting the claim',
  `claim_reason`       text COLLATE utf8mb4_unicode_ci,
  `evidence_file_url`  varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status`             varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT 'PENDING|APPROVED|REJECTED',
  `submitted_at`       datetime DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by`        int(11) DEFAULT NULL,
  `reviewed_at`        datetime DEFAULT NULL,
  `review_notes`       text COLLATE utf8mb4_unicode_ci,
  `created_at`         datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`claim_id`),
  KEY `mismatch_id` (`mismatch_id`),
  KEY `claim_student_id` (`claim_student_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 3. Payment Transfer — approved fund transfers between students
-- Status: PENDING | COMPLETED | FAILED | REVERSED
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ct_payment_transfer` (
  `transfer_id`        bigint(20) NOT NULL AUTO_INCREMENT,
  `source_student_id`  bigint(20) NOT NULL COMMENT 'Student whose payment is being transferred',
  `target_student_id`  bigint(20) NOT NULL COMMENT 'Student receiving the payment',
  `source_payment_id`  varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Original PayStation payment_id',
  `amount`             decimal(10,2) NOT NULL DEFAULT '0.00',
  `approved_claim_id`  bigint(20) DEFAULT NULL COMMENT 'FK to ct_mismatch_claim',
  `transfer_date`      datetime DEFAULT NULL,
  `status`             varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT 'PENDING|COMPLETED|FAILED|REVERSED',
  `created_by`         int(11) DEFAULT NULL,
  `notes`              text COLLATE utf8mb4_unicode_ci,
  `created_at`         datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`transfer_id`),
  KEY `source_student_id` (`source_student_id`),
  KEY `target_student_id` (`target_student_id`),
  KEY `approved_claim_id` (`approved_claim_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
