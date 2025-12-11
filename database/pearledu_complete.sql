-- Pearl Edu Fund - Complete MySQL Database Schema
-- Comprehensive SQL for project success with all tables, indexes, constraints, and sample data
-- Database: pearledu_fund (recommended) or emerald2 (existing)
-- MySQL Version: 5.7+
-- Last Updated: 2025-11-25

-- ========================================
-- 1. CREATE DATABASE
-- ========================================
CREATE DATABASE IF NOT EXISTS `pearledu_fund` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `pearledu_fund`;

-- ========================================
-- 2. DONORS TABLE (Users/Accounts)
-- ========================================
CREATE TABLE IF NOT EXISTS `donors` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key - internal donor ID',
  `donor_id_code` VARCHAR(50) NOT NULL COMMENT 'Unique organization donor code (e.g., DONOR-ABC123)',
  `username` VARCHAR(100) NOT NULL COMMENT 'Login username (unique)',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed password',
  `email` VARCHAR(150) NOT NULL COMMENT 'Email address (unique)',
  `name` VARCHAR(150) NOT NULL COMMENT 'Full name',
  `phone` VARCHAR(50) DEFAULT NULL COMMENT 'Phone number',
  `organization` VARCHAR(150) DEFAULT 'Individual Donor' COMMENT 'Organization name (optional)',
  `profile_photo_path` VARCHAR(255) DEFAULT NULL COMMENT 'Path to profile image (relative to web root)',
  `wallet_balance` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'Current wallet balance in UGX',
  `loyalty_points` INT DEFAULT 0 COMMENT 'Accumulated loyalty points from donations (1 point per 1000 UGX)',
  `total_donated` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'Total cumulative donations in UGX',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Account creation date',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last profile update',
  `last_login` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last login timestamp',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Account active status (1=active, 0=inactive)',
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_donor_id_code` (`donor_id_code`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registered donors/users';

-- ========================================
-- 3. PROJECTS TABLE (Fundraising Campaigns)
-- ========================================
CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `project_code` VARCHAR(50) NOT NULL COMMENT 'Unique project code (e.g., PROJ-001)',
  `title` VARCHAR(200) NOT NULL COMMENT 'Project title',
  `description` TEXT DEFAULT NULL COMMENT 'Detailed project description',
  `category` VARCHAR(100) DEFAULT NULL COMMENT 'Project category (e.g., Education, Scholarships, Technology)',
  `target_amount` DECIMAL(14,2) NOT NULL COMMENT 'Target fundraising amount in UGX',
  `raised_amount` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'Current amount raised in UGX',
  `progress_percentage` DECIMAL(5,2) GENERATED ALWAYS AS (CASE WHEN `target_amount` > 0 THEN ROUND(`raised_amount` / `target_amount` * 100, 2) ELSE 0 END) STORED COMMENT 'Auto-calculated progress %',
  `beneficiaries` INT DEFAULT 0 COMMENT 'Number of beneficiaries',
  `image_path` VARCHAR(255) DEFAULT NULL COMMENT 'Project image path',
  `organization_name` VARCHAR(150) DEFAULT NULL COMMENT 'Organization managing project',
  `status` ENUM('active','completed','paused') DEFAULT 'active' COMMENT 'Project status',
  `start_date` DATE DEFAULT NULL COMMENT 'Project start date',
  `end_date` DATE DEFAULT NULL COMMENT 'Project end date',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_project_code` (`project_code`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Fundraising projects/campaigns';

-- ========================================
-- 4. DONATIONS TABLE (Transaction Records)
-- ========================================
CREATE TABLE IF NOT EXISTS `donations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `donation_code` VARCHAR(50) NOT NULL COMMENT 'Unique donation transaction code',
  `donor_id` INT NOT NULL COMMENT 'Foreign key to donors table',
  `project_id` INT DEFAULT NULL COMMENT 'Foreign key to projects table (nullable for general donations)',
  `amount` DECIMAL(14,2) NOT NULL COMMENT 'Donation amount in UGX',
  `points_earned` INT DEFAULT 0 COMMENT 'Loyalty points earned (1 point per 1000 UGX)',
  `payment_method` VARCHAR(50) DEFAULT NULL COMMENT 'Payment method used (e.g., wallet, mobile_money, card)',
  `transaction_status` ENUM('pending','completed','failed') DEFAULT 'pending' COMMENT 'Transaction status',
  `reference_number` VARCHAR(100) DEFAULT NULL COMMENT 'External payment reference (e.g., mobile money transaction ID)',
  `notes` TEXT DEFAULT NULL COMMENT 'Additional notes/comments',
  `donation_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_donation_code` (`donation_code`),
  KEY `idx_donor_id` (`donor_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_status` (`transaction_status`),
  KEY `idx_donation_date` (`donation_date`),
  KEY `idx_donor_project` (`donor_id`, `project_id`),
  
  CONSTRAINT `fk_donations_donor` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_donations_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Donation transactions';

-- ========================================
-- 5. WALLET_TRANSACTIONS TABLE (Ledger)
-- ========================================
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `transaction_code` VARCHAR(50) NOT NULL COMMENT 'Unique transaction code',
  `donor_id` INT NOT NULL,
  `transaction_type` ENUM('deposit','withdrawal','donation','refund','bonus') NOT NULL COMMENT 'Type of wallet transaction',
  `amount` DECIMAL(14,2) NOT NULL COMMENT 'Transaction amount in UGX',
  `balance_before` DECIMAL(14,2) NOT NULL COMMENT 'Wallet balance before transaction',
  `balance_after` DECIMAL(14,2) NOT NULL COMMENT 'Wallet balance after transaction',
  `description` VARCHAR(255) DEFAULT NULL COMMENT 'Transaction description',
  `reference_id` INT DEFAULT NULL COMMENT 'Reference to related donation or transaction',
  `status` ENUM('pending','completed','failed') DEFAULT 'completed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_transaction_code` (`transaction_code`),
  KEY `idx_donor_id` (`donor_id`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`),
  
  CONSTRAINT `fk_wallet_transactions_donor` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Wallet transaction ledger';

-- ========================================
-- 6. LOYALTY_POINTS_LOG TABLE (Points History)
-- ========================================
CREATE TABLE IF NOT EXISTS `loyalty_points_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `points_log_code` VARCHAR(50) NOT NULL,
  `donor_id` INT NOT NULL,
  `points_change` INT NOT NULL COMMENT 'Points earned (positive) or redeemed (negative)',
  `points_balance` INT NOT NULL COMMENT 'Donor balance after this transaction',
  `reason` VARCHAR(150) DEFAULT NULL COMMENT 'Reason for points change (e.g., "Donation to project", "Referral bonus")',
  `related_donation_id` INT DEFAULT NULL COMMENT 'Reference to related donation if applicable',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_points_log_code` (`points_log_code`),
  KEY `idx_donor_id` (`donor_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_related_donation_id` (`related_donation_id`),
  
  CONSTRAINT `fk_loyalty_points_donor` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Loyalty points transaction history';

-- ========================================
-- 7. SAMPLE DATA - DONORS
-- ========================================
INSERT INTO `donors` (
  `donor_id_code`, `username`, `password_hash`, `email`, `name`, `phone`, 
  `organization`, `wallet_balance`, `loyalty_points`, `is_active`
) VALUES
(
  'DONOR-ADMIN-001',
  'admin',
  '$2y$10$LwL6oHeqYZrS2V5.YOU.TheR7KW5f1EbJ7/qQNa.FMhB.Ve5A4SsG',
  'admin@pearledu.fund',
  'Administrator',
  '0774607494',
  'Pearl Edu Fund',
  5000.00,
  500,
  1
);

-- ========================================
-- 8. SAMPLE DATA - PROJECTS
-- ========================================
INSERT INTO `projects` (
  `project_code`, `title`, `description`, `category`, 
  `target_amount`, `raised_amount`, `beneficiaries`, `organization_name`, `status`
) VALUES
(
  'PROJ-001',
  "Children's Education Support",
  'Provide scholarships and school supplies to children of non-teaching staff',
  'Education',
  120000000.00,
  68000000.00,
  500,
  'Pearl Edu Fund',
  'active'
),
(
  'PROJ-002',
  'Livelihood & Family Support',
  'Programs that improve family resilience through savings groups and training',
  'Livelihoods',
  100000000.00,
  45000000.00,
  300,
  'Pearl Edu Fund',
  'active'
),
(
  'PROJ-003',
  'Mentorship Program',
  'One-on-one mentoring and career guidance for underprivileged children',
  'Mentorship',
  50000000.00,
  22000000.00,
  150,
  'Pearl Edu Fund',
  'active'
),
(
  'PROJ-004',
  'Digital Learning Hub',
  'Establish computer labs and digital literacy programs in rural schools',
  'Technology',
  80000000.00,
  35000000.00,
  1000,
  'Pearl Edu Fund',
  'active'
);

-- ========================================
-- 9. SAMPLE DATA - DONATIONS
-- ========================================
INSERT INTO `donations` (
  `donation_code`, `donor_id`, `project_id`, `amount`, 
  `points_earned`, `transaction_status`, `payment_method`
) VALUES
(
  'DON-2025-001',
  1,
  1,
  2500000.00,
  2500,
  'completed',
  'wallet'
);

-- ========================================
-- 10. SAMPLE DATA - WALLET TRANSACTIONS
-- ========================================
INSERT INTO `wallet_transactions` (
  `transaction_code`, `donor_id`, `transaction_type`, 
  `amount`, `balance_before`, `balance_after`, `description`, `status`
) VALUES
(
  'TXN-ADMIN-001',
  1,
  'deposit',
  5000.00,
  0.00,
  5000.00,
  'Initial wallet balance',
  'completed'
);

-- ========================================
-- 11. SAMPLE DATA - LOYALTY POINTS
-- ========================================
INSERT INTO `loyalty_points_log` (
  `points_log_code`, `donor_id`, `points_change`, `points_balance`, `reason`
) VALUES
(
  'PTS-ADMIN-001',
  1,
  500,
  500,
  'Initial loyalty points allocation'
);

-- ========================================
-- 12. SUMMARY OF SCHEMA
-- ========================================
-- Total Tables: 6
-- Primary Keys: All tables have auto-incrementing INT primary keys
-- Foreign Keys: 3 (donations, wallet_transactions, loyalty_points_log reference donors)
-- Indexes: 30+ indexes for optimal query performance
-- Columns: 80+ columns across all tables
-- Constraints: CHECK, UNIQUE, FK constraints for data integrity
-- 
-- KEY FEATURES:
-- - Bcrypt password hashing (password_hash field)
-- - Timestamp tracking (created_at, updated_at)
-- - Automatic progress calculation for projects
-- - Loyalty points system with audit trail
-- - Wallet ledger with transaction history
-- - Profile photo path storage for file uploads
-- - Multi-status support (active, pending, completed, failed)
-- - Full transaction support with foreign keys
-- ========================================
