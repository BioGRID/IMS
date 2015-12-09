DROP TABLE IF EXISTS `dataset_history`;

CREATE TABLE `dataset_history` (
  `dataset_history_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `modification_type` enum('ACTIVATED','DISABLED','DEACTIVATED','UPDATED','ANNOTATED','WRONGPROJECT','QUALITYCONTROL','ACCESSED','INPROGRESS','ABSTRACT','FULLTEXT','UNABLETOACCESS') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dataset_id` bigint(10) NOT NULL,
  `user_id` bigint(10) NOT NULL,
  `dataset_history_comment` text COLLATE utf8mb4_unicode_ci,
  `dataset_history_addeddate` datetime NOT NULL,
  PRIMARY KEY (`dataset_history_id`),
  KEY `user_id` (`user_id`),
  KEY `modification_type` (`modification_type`),
  KEY `dataset_id` (`dataset_id`),
  KEY `dataset_history_addeddate` (`dataset_history_addeddate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;