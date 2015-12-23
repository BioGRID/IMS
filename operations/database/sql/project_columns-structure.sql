DROP TABLE IF EXISTS `project_columns`;

CREATE TABLE `project_columns` (
  `project_column_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `project_column_title` varchar(255) NOT NULL,
  `attribute_type_id` bigint(10) NOT NULL,
  `project_column_rank` bigint(10) NOT NULL,
  `project_column_addeddate` datetime NOT NULL,
  `project_column_status` enum('active','inactive') NOT NULL,
  `project_id` bigint(10) NOT NULL,
  PRIMARY KEY (`project_column_id`),
  KEY `project_column_title` (`project_column_title`),
  KEY `attribute_type_id` (`attribute_type_id`),
  KEY `project_column_addeddate` (`project_column_addeddate`),
  KEY `project_column_status` (`project_column_status`),
  KEY `project_id` (`project_id`),
  KEY `project_column_rank` (`project_column_rank`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;