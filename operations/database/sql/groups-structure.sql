DROP TABLE IF EXISTS `groups`;

CREATE TABLE `groups` (
  `group_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_description` text COLLATE utf8mb4_unicode_ci,
  `group_default_organism_id` bigint(10) NOT NULL,
  `group_addeddate` datetime NOT NULL,
  `group_status` enum('public','private') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  PRIMARY KEY (`group_id`),
  KEY `group_name` (`group_name`(191)),
  KEY `group_default_organism_id` (`group_default_organism_id`),
  KEY `group_addeddate` (`group_addeddate`),
  KEY `group_status` (`group_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
