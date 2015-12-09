DROP TABLE IF EXISTS `pubmed`;

CREATE TABLE `pubmed` (
  `pubmed_id` bigint(10) NOT NULL,
  `pubmed_title` text COLLATE utf8mb4_unicode_ci,
  `pubmed_abstract` mediumtext COLLATE utf8mb4_unicode_ci,
  `pubmed_fulltext` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `pubmed_author_short` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pubmed_author_full` text COLLATE utf8mb4_unicode_ci,
  `pubmed_volume` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pubmed_issue` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pubmed_date` date DEFAULT NULL,
  `pubmed_journal` text COLLATE utf8mb4_unicode_ci,
  `pubmed_journal_short` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pubmed_pagination` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pubmed_affiliation` text COLLATE utf8mb4_unicode_ci,
  `pubmed_meshterms` text COLLATE utf8mb4_unicode_ci,
  `pubmed_pmcid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pubmed_status` enum('active','inactive','retracted','erratum') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `pubmed_addeddate` datetime NOT NULL,
  `pubmed_lastupdated` datetime DEFAULT NULL,
  `pubmed_isannotated` tinyint(1) NOT NULL,
  PRIMARY KEY (`pubmed_id`),
  KEY `pubmed_author_short` (`pubmed_author_short`(191)),
  KEY `pubmed_date` (`pubmed_date`),
  KEY `pubmed_journal_short` (`pubmed_journal_short`(191)),
  KEY `pubmed_pmcid` (`pubmed_pmcid`(191)),
  KEY `pubmed_status` (`pubmed_status`),
  KEY `pubmed_addeddate` (`pubmed_addeddate`),
  KEY `pubmed_lastupdated` (`pubmed_lastupdated`),
  KEY `pubmed_isannotated` (`pubmed_isannotated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

