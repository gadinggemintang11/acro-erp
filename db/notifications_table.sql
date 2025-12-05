-- Create notifications table for neokaber ERP system
-- Run this SQL in your database (same database as other tables)

DROP TABLE IF EXISTS `tbl_notifications`;

CREATE TABLE `tbl_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) DEFAULT NULL COMMENT 'NULL for all users, specific user_id, or "all"',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `icon` varchar(100) DEFAULT 'fa-bell',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='System notifications table';

-- Insert sample notifications
INSERT INTO `tbl_notifications` (`user_id`, `title`, `message`, `type`, `icon`, `link`, `is_read`, `created_at`, `created_by`) VALUES
(NULL, 'Database Backup Completed', 'Automated database backup has been completed successfully', 'success', 'fa-database', NULL, 0, NOW(), 'system'),
(NULL, 'New User Registration', 'A new user has been registered in the system', 'info', 'fa-user-plus', NULL, 0, DATE_SUB(NOW(), INTERVAL 10 MINUTE), 'system'),
(NULL, 'System Update Available', 'A new system update is available for installation', 'warning', 'fa-exclamation-triangle', NULL, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR), 'system'),
(NULL, 'Welcome to ERP System', 'Thank you for using PT Mingbao Indonesia ERP System', 'info', 'fa-hand-wave', NULL, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR), 'system');

-- Success message
SELECT 'Notification table created successfully!' as status;
