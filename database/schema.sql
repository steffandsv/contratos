SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('ADMIN','GESTOR_CENTRAL','FISCAL','VISUALIZADOR') NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `document` VARCHAR(20) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contracts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `number` VARCHAR(50) NOT NULL,
  `detailed_number` VARCHAR(50),
  `modality_code` VARCHAR(50),
  `modality_name` VARCHAR(100),
  `fiscal_name_raw` VARCHAR(255),
  `exercise` INT,
  `legal_basis` VARCHAR(255),
  `procedure_number` VARCHAR(100),
  `supplier_id` INT,
  `value_total` DECIMAL(15,2),
  `date_start` DATE,
  `date_end_current` DATE,
  `description_short` VARCHAR(255),
  `description_full` TEXT,
  `type_code` VARCHAR(50),
  `rateio_code` VARCHAR(50),
  `has_renewal` TINYINT(1) DEFAULT 1,
  `max_renewals` INT,
  `status_phase` ENUM('EM_ELABORACAO','VIGENTE','EM_PRORROGACAO','EM_ENCERRAMENTO','ENCERRADO','RESCINDIDO','ANULADO') DEFAULT 'VIGENTE',
  `status_risk` ENUM('TRANQUILO','PLANEJAR','AGIR','CRITICO','IRREGULAR') DEFAULT 'TRANQUILO',
  `next_action_text` VARCHAR(255),
  `next_action_deadline` DATE,
  `manager_user_id` INT,
  `created_by_user_id` INT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
  FOREIGN KEY (`manager_user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contract_responsibles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `contract_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `role_in_contract` ENUM('FISCAL','GESTOR_SETORIAL') NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`contract_id`) REFERENCES `contracts`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `contract_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT,
  `send_channel` ENUM('EMAIL','APP') NOT NULL,
  `scheduled_for` DATETIME NOT NULL,
  `sent_at` DATETIME,
  `status` ENUM('PENDENTE','ENVIADA','ERRO') DEFAULT 'PENDENTE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`contract_id`) REFERENCES `contracts`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `value` TEXT,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
