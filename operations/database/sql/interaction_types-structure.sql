
DROP TABLE IF EXISTS `interaction_types`;

CREATE TABLE `interaction_types` (
  `interaction_type_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `interaction_type_name` varchar(255) NOT NULL,
  `interaction_type_desc` text,
  `interaction_type_dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `interaction_type_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`interaction_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;