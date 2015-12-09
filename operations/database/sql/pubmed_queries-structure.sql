DROP TABLE IF EXISTS `pubmed_queries`;

CREATE TABLE `pubmed_queries` (
  `pubmed_query_id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(10) NOT NULL DEFAULT '0',
  `pubmed_query_value` text CHARACTER SET latin1 NOT NULL,
  `pubmed_query_translated` text CHARACTER SET latin1 NOT NULL,
  `pubmed_query_total` bigint(10) NOT NULL,
  `pubmed_query_newlyadded` bigint(10) NOT NULL,
  `pubmed_query_added_date` date NOT NULL DEFAULT '0000-00-00',
  `pubmed_query_last_run` datetime DEFAULT NULL,
  `pubmed_query_type` enum('manual','automatic') CHARACTER SET latin1 NOT NULL DEFAULT 'manual',
  `pubmed_query_status` enum('active','inactive') CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`pubmed_query_id`),
  KEY `project_id` (`project_id`),
  KEY `pubmed_query_added_date` (`pubmed_query_added_date`),
  KEY `pubmed_query_last_run` (`pubmed_query_last_run`),
  KEY `pubmed_query_type` (`pubmed_query_type`),
  KEY `pubmed_query_total` (`pubmed_query_total`),
  KEY `pubmed_query_newlyadded` (`pubmed_query_newlyadded`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
