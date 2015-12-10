DROP TABLE IF EXISTS `attribute_type_categories`;

CREATE TABLE `attribute_type_categories` (
  `attribute_type_category_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `attribute_type_category_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attribute_type_category_addeddate` datetime NOT NULL,
  `attribute_type_category_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`attribute_type_category_id`),
  KEY `attribute_type_category_name` (`attribute_type_category_name`(191)),
  KEY `attribute_type_category_addeddate` (`attribute_type_category_addeddate`),
  KEY `attribute_type_category_status` (`attribute_type_category_status`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
