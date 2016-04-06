DROP TABLE IF EXISTS `interaction_types`;

CREATE TABLE `interaction_types` (
  `interaction_type_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `interaction_type_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interaction_type_desc` text COLLATE utf8mb4_unicode_ci,
  `interaction_type_columns` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `interaction_type_fields` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `interaction_type_dateadded` datetime NOT NULL,
  `interaction_type_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`interaction_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;