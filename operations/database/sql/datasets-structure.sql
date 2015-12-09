DROP TABLE IF EXISTS `datasets`;

CREATE TABLE `datasets` (
  `dataset_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `dataset_source_id` bigint(10) NOT NULL,
  `dataset_type_id` bigint(10) NOT NULL,
  `dataset_source_id_replacement` bigint(10) NOT NULL,
  `dataset_type_id_replacement` bigint(10) NOT NULL,
  `dataset_availability` enum('public','private','website-only') COLLATE utf8mb4_unicode_ci NOT NULL,
  `dataset_addeddate` datetime NOT NULL,
  `dataset_status` enum('active','inactive','replaced') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`dataset_id`),
  KEY `dataset_source_id` (`dataset_source_id`),
  KEY `dataset_type_id` (`dataset_type_id`),
  KEY `dataset_availability` (`dataset_availability`),
  KEY `dataset_addeddate` (`dataset_addeddate`),
  KEY `dataset_status` (`dataset_status`),
  KEY `dataset_source_id_replacement` (`dataset_source_id_replacement`),
  KEY `dataset_type_id_replacement` (`dataset_type_id_replacement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;