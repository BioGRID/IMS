DROP TABLE IF EXISTS `group_datasets`;

CREATE TABLE `group_datasets` (
  `group_dataset_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `group_id` bigint(10) NOT NULL,
  `dataset_id` bigint(10) NOT NULL,
  `group_dataset_addeddate` datetime NOT NULL,
  `group_dataset_status` enum('normal','high','error','processed','low') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`group_dataset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
