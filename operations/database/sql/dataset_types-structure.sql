DROP TABLE IF EXISTS `dataset_types`;

CREATE TABLE `dataset_types` (
  `dataset_type_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `dataset_type_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dataset_type_desc` text COLLATE utf8mb4_unicode_ci,
  `dataset_type_dateadded` datetime NOT NULL,
  `dataset_type_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`dataset_type_id`),
  KEY `dataset_type_name` (`dataset_type_name`(191)),
  KEY `dataset_type_dateadded` (`dataset_type_dateadded`),
  KEY `dataset_type_status` (`dataset_type_status`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
