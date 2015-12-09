DROP TABLE IF EXISTS `prepub`;

CREATE TABLE `prepub` (
  `prepub_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `prepub_title` text COLLATE utf8mb4_unicode_ci,
  `prepub_abstract` mediumtext COLLATE utf8mb4_unicode_ci,
  `prepub_author_short` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prepub_author_full` text COLLATE utf8mb4_unicode_ci,
  `prepub_date` date DEFAULT NULL,
  `prepub_affiliation` text COLLATE utf8mb4_unicode_ci,
  `prepub_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `prepub_pubmed_id` bigint(10) NOT NULL,
  `prepub_status` enum('active','inactive','published') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `prepub_addeddate` datetime NOT NULL,
  `prepub_lastupdated` datetime DEFAULT NULL,
  PRIMARY KEY (`prepub_id`),
  KEY `prepub_author_short` (`prepub_author_short`(191)),
  KEY `prepub_date` (`prepub_date`),
  KEY `prepub_pubmed_id` (`prepub_pubmed_id`),
  KEY `prepub_status` (`prepub_status`),
  KEY `prepub_addeddate` (`prepub_addeddate`),
  KEY `prepub_lastupdated` (`prepub_lastupdated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
