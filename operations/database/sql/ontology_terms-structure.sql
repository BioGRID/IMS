DROP TABLE IF EXISTS `ontology_terms`;

CREATE TABLE `ontology_terms` (
  `ontology_term_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `ontology_term_official_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ontology_term_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ontology_term_desc` text COLLATE utf8mb4_unicode_ci,
  `ontology_term_synonyms` text COLLATE utf8mb4_unicode_ci,
  `ontology_term_replacement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ontology_term_subsets` text COLLATE utf8mb4_unicode_ci,
  `ontology_term_preferred_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ontology_id` bigint(10) NOT NULL,
  `ontology_term_addeddate` datetime NOT NULL,
  `ontology_term_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `ontology_term_childcount` bigint(10) NOT NULL,
  `ontology_term_parent` text COLLATE utf8mb4_unicode_ci,
  `ontology_term_isroot` tinyint(1) NOT NULL DEFAULT '0',
  `ontology_term_count` bigint(10) NOT NULL,
  PRIMARY KEY (`ontology_term_id`),
  KEY `ontology_term_official_id` (`ontology_term_official_id`(191)),
  KEY `ontology_term_name` (`ontology_term_name`(191)),
  KEY `ontology_term_replacement` (`ontology_term_replacement`(191)),
  KEY `ontology_term_preferred_name` (`ontology_term_preferred_name`(191)),
  KEY `ontology_id` (`ontology_id`),
  KEY `ontology_term_addeddate` (`ontology_term_addeddate`),
  KEY `ontology_term_status` (`ontology_term_status`),
  KEY `ontology_term_childcount` (`ontology_term_childcount`),
  KEY `ontology_term_count` (`ontology_term_count`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
