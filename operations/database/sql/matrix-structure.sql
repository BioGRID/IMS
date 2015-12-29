DROP TABLE IF EXISTS `matrix`;

CREATE TABLE `matrix` (
  `interaction_id` bigint(10) NOT NULL,
  `interaction_details` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `dataset_id` bigint(10) NOT NULL,
  `interaction_type_id` bigint(10) NOT NULL,
  `interaction_type_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interaction_state` enum('normal','error','temporary') COLLATE utf8mb4_unicode_ci NOT NULL,
  `modification_type` enum('ACTIVATED','DISABLED','MODIFIED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `history_operation_id` bigint(10) NOT NULL,
  `history_operation_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `history_comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`interaction_id`),
  KEY `dataset_id` (`dataset_id`),
  KEY `interaction_type_id` (`interaction_type_id`),
  KEY `interaction_state` (`interaction_state`),
  KEY `history_operation_id` (`history_operation_id`),
  KEY `interaction_type_name` (`interaction_type_name`(191)),
  KEY `modification_type` (`modification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
