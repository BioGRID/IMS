DROP TABLE IF EXISTS `interactions`;

CREATE TABLE `interactions` (
  `interaction_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `dataset_id` bigint(10) NOT NULL,
  `interaction_type_id` bigint(10) NOT NULL,
  `interaction_state` enum('normal','error','temporary') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`interaction_id`),
  KEY `dataset_id` (`dataset_id`),
  KEY `interaction_type_id` (`interaction_type_id`),
  KEY `interaction_state` (`interaction_state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
