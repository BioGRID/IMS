DROP TABLE IF EXISTS `pubmed_mappings`;

CREATE TABLE `pubmed_mappings` (
  `pubmed_mapping_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `external_database_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_database_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_database_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pubmed_id` bigint(10) NOT NULL,
  PRIMARY KEY (`pubmed_mapping_id`),
  KEY `external_database_id` (`external_database_id`(191)),
  KEY `external_database_name` (`external_database_name`(191)),
  KEY `pubmed_id` (`pubmed_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

