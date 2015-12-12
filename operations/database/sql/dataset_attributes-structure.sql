DROP TABLE IF EXISTS `dataset_attributes`;

CREATE TABLE `dataset_attributes` (
  `dataset_attribute_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `dataset_id` bigint(10) NOT NULL,
  `attribute_id` bigint(10) NOT NULL,
  `dataset_attribute_parent` bigint(10) NOT NULL,
  `dataset_attribute_addeddate` datetime NOT NULL,
  `dataset_attribute_status` enum('active','inactive') CHARACTER SET latin1 NOT NULL DEFAULT 'active',
  PRIMARY KEY (`dataset_attribute_id`),
  KEY `interaction_id` (`dataset_id`),
  KEY `dataset_attribute_parent` (`dataset_attribute_parent`),
  KEY `attribute_id` (`attribute_id`),
  KEY `interaction_attribute_addeddate` (`dataset_attribute_addeddate`),
  KEY `interaction_attribute_status` (`dataset_attribute_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;