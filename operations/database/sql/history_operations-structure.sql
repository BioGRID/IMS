DROP TABLE IF EXISTS `history_operations`;

CREATE TABLE `history_operations` (
  `history_operation_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `history_operation_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `history_operation_addeddate` datetime NOT NULL,
  `history_operation_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`history_operation_id`),
  KEY `operation_name` (`history_operation_name`(191)),
  KEY `operation_addeddate` (`history_operation_addeddate`),
  KEY `operation_status` (`history_operation_status`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

