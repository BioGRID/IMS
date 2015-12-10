DROP TABLE IF EXISTS `interaction_attributes`;

CREATE TABLE `interaction_attributes` (
  `interaction_attribute_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `interaction_id` bigint(10) NOT NULL,
  `attribute_id` bigint(10) NOT NULL,
  `attribute_id_parent` bigint(10) NOT NULL,
  `interaction_attribute_addeddate` datetime NOT NULL,
  `interaction_attribute_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`interaction_attribute_id`),
  KEY `interaction_id` (`interaction_id`),
  KEY `attribute_id_parent` (`attribute_id_parent`),
  KEY `attribute_id` (`attribute_id`),
  KEY `interaction_attribute_addeddate` (`interaction_attribute_addeddate`),
  KEY `interaction_attribute_status` (`interaction_attribute_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
