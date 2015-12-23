DROP TABLE IF EXISTS `projects`;

CREATE TABLE `projects` (
  `project_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_fullname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attribute_type_id` bigint(10) NOT NULL,
  `project_state` enum('public','private') COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_addeddate` datetime NOT NULL,
  `project_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`project_id`),
  KEY `project_name` (`project_name`(191)),
  KEY `project_fullname` (`project_fullname`(191)),
  KEY `attribute_type_id` (`attribute_type_id`),
  KEY `project_state` (`project_state`),
  KEY `project_addeddate` (`project_addeddate`),
  KEY `project_status` (`project_status`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;