DROP TABLE IF EXISTS `attributes`;

CREATE TABLE `attributes` (
  `attribute_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `attribute_value` text NOT NULL,
  `attribute_type_id` bigint(10) NOT NULL,
  `attribute_addeddate` datetime NOT NULL,
  `attribute_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`attribute_id`),
  KEY `attribute_type_id` (`attribute_type_id`),
  KEY `attribute_addeddate` (`attribute_addeddate`),
  KEY `attribute_status` (`attribute_status`),
  KEY `attribute_value` (`attribute_value`(50))
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
