DROP TABLE IF EXISTS `participant_roles`;

CREATE TABLE `participant_roles` (
  `participant_role_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `participant_role_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `participant_role_addeddate` datetime NOT NULL,
  `participant_role_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`participant_role_id`),
  KEY `participant_role_addeddate` (`participant_role_addeddate`),
  KEY `participant_role_status` (`participant_role_status`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
