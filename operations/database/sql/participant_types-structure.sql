DROP TABLE IF EXISTS `participant_types`;

CREATE TABLE `participant_types` (
  `participant_type_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `participant_type_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `participant_type_addeddate` datetime NOT NULL,
  `participant_type_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`participant_type_id`),
  KEY `participant_type_name` (`participant_type_name`(191)),
  KEY `participant_type_addeddate` (`participant_type_addeddate`),
  KEY `participant_type_status` (`participant_type_status`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

