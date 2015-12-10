DROP TABLE IF EXISTS `attribute_types`;

CREATE TABLE `attribute_types` (
  `attribute_type_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `attribute_type_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attribute_type_category_id` bigint(10) NOT NULL,
  `attribute_type_shortcode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attribute_type_addeddate` datetime NOT NULL,
  `attribute_type_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`attribute_type_id`),
  KEY `attribute_type_name` (`attribute_type_name`(191)),
  KEY `attribute_type_category_id` (`attribute_type_category_id`),
  KEY `attribute_type_shortcode` (`attribute_type_shortcode`),
  KEY `attribute_type_addeddate` (`attribute_type_addeddate`),
  KEY `attribute_type_status` (`attribute_type_status`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
