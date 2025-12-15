-- Migration: Add temp_password columns to users table
-- Run this SQL once on your database to add the required columns for password reset feature

-- Add temp_password column
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `temp_password` VARCHAR(255) NULL AFTER `password_hash`;

-- Add temp_password_expires column  
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `temp_password_expires` TIMESTAMP NULL AFTER `temp_password`;

-- Add requires_password_change column
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `requires_password_change` TINYINT(1) DEFAULT 0 AFTER `temp_password_expires`;

-- For MariaDB versions that don't support IF NOT EXISTS, use these alternative queries:
-- (Run each one separately, ignore errors if column already exists)

-- ALTER TABLE `users` ADD COLUMN `temp_password` VARCHAR(255) NULL AFTER `password_hash`;
-- ALTER TABLE `users` ADD COLUMN `temp_password_expires` TIMESTAMP NULL AFTER `temp_password`;
-- ALTER TABLE `users` ADD COLUMN `requires_password_change` TINYINT(1) DEFAULT 0 AFTER `temp_password_expires`;
