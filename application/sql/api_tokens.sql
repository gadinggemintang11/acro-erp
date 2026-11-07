-- Tabel token untuk REST API mobile approval (aplikasi Android ERP Acropoly).
-- Aditif: tidak mengubah tabel existing. Jalankan sekali di tiap environment.
CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `device_name` VARCHAR(150) DEFAULT NULL,
  `fcm_token` VARCHAR(512) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
