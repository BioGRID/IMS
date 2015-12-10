DROP TABLE IF EXISTS `history`;

CREATE TABLE `history` (
  `history_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `modification_type` enum('ACTIVATED','DISABLED','MODIFIED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `interaction_id` bigint(10) NOT NULL,
  `user_id` bigint(10) NOT NULL,
  `history_comment` text CHARACTER SET latin1 NOT NULL,
  `history_operation_id` bigint(10) NOT NULL,
  `history_addeddate` datetime NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `interaction_id` (`interaction_id`),
  KEY `user_id` (`user_id`),
  KEY `history_operation_id` (`history_operation_id`),
  KEY `history_addeddate` (`history_addeddate`),
  KEY `operation_id` (`history_operation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
