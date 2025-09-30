-- Drop existing tables if they exist
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS promotion_clicks;
DROP TABLE IF EXISTS reward_settings;
DROP TABLE IF EXISTS rewards;
DROP TABLE IF EXISTS promotion_stats;
DROP TABLE IF EXISTS promotions;
DROP TABLE IF EXISTS server_settings;
DROP TABLE IF EXISTS servers;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS migrations;

-- Create roles table
CREATE TABLE `roles` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `display_name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `level` TINYINT(1) UNSIGNED NOT NULL COMMENT '1=super_admin, 2=admin, 3=server_owner, 4=reviewer, 5=user',
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO `roles` (`name`, `display_name`, `description`, `level`, `created_at`, `updated_at`) VALUES
('super_admin', 'Super Administrator', 'Full system access with all permissions', 1, NOW(), NOW()),
('admin', 'Administrator', 'System administrator with user and server management', 2, NOW(), NOW()),
('server_owner', 'Server Owner', 'Can manage their own servers and promotion settings', 3, NOW(), NOW()),
('reviewer', 'Reviewer', 'Can review and approve server applications and promotions', 4, NOW(), NOW()),
('user', 'User', 'Basic user with promotion participation rights', 5, NOW(), NOW());

-- Create users table
CREATE TABLE `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `line_id` VARCHAR(50) DEFAULT NULL,
  `discord_id` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
  `email_verified_at` DATETIME DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `failed_login_attempts` TINYINT(3) UNSIGNED DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `password_reset_token` VARCHAR(255) DEFAULT NULL,
  `password_reset_expires` DATETIME DEFAULT NULL,
  `email_verification_token` VARCHAR(255) DEFAULT NULL,
  `two_factor_secret` VARCHAR(100) DEFAULT NULL,
  `two_factor_enabled` BOOLEAN DEFAULT FALSE,
  `preferences` JSON DEFAULT NULL,
  `metadata` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  KEY `deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_roles table
CREATE TABLE `user_roles` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `role_id` INT(11) UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_role` (`user_id`, `role_id`),
  KEY `user_id` (`user_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_sessions table
CREATE TABLE `user_sessions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `session_token` VARCHAR(64) NOT NULL,
  `refresh_token` VARCHAR(64) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `refresh_expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session_token` (`session_token`),
  UNIQUE KEY `unique_refresh_token` (`refresh_token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert test users with password 'password'
-- Password hash for 'password' using bcrypt
INSERT INTO `users` (`username`, `email`, `password_hash`, `first_name`, `last_name`, `status`, `email_verified_at`, `created_at`, `updated_at`) VALUES
('admin', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'active', NOW(), NOW(), NOW()),
('testuser', 'user@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', 'active', NOW(), NOW(), NOW());

-- Assign roles to users
INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`)
SELECT u.id, r.id, NOW()
FROM users u, roles r
WHERE u.username = 'admin' AND r.name = 'super_admin';

INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`)
SELECT u.id, r.id, NOW()
FROM users u, roles r
WHERE u.username = 'testuser' AND r.name = 'user';