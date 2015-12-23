DROP TABLE IF EXISTS `participant_attributes`;

CREATE TABLE `participant_attributes` (
  `participant_attribute_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `participant_id` bigint(10) NOT NULL,
  `attribute_id` bigint(10) NOT NULL,
  `participant_attribute_parent` bigint(10) NOT NULL,
  `user_id` bigint(10) NOT NULL,
  `participant_attribute_evidence` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `participant_attribute_evidence_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `participant_attribute_evidence_method` enum('inferred','experimental','unknown') COLLATE utf8mb4_unicode_ci NOT NULL,
  `participant_attribute_evidence_id` bigint(10) NOT NULL,
  `participant_attribute_addeddate` datetime NOT NULL,
  `participant_attribute_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`participant_attribute_id`),
  KEY `participant_id` (`participant_id`),
  KEY `attribute_id` (`attribute_id`),
  KEY `participant_attribute_parent` (`participant_attribute_parent`),
  KEY `user_id` (`user_id`),
  KEY `participant_attribute_addeddate` (`participant_attribute_addeddate`),
  KEY `participant_attribute_status` (`participant_attribute_status`),
  KEY `participant_attribute_evidence` (`participant_attribute_evidence`(191)),
  KEY `participant_attribute_evidence_text` (`participant_attribute_evidence_text`(191)),
  KEY `participant_attribute_evidence_method` (`participant_attribute_evidence_method`),
  KEY `participant_attribute_evidence_id` (`participant_attribute_evidence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
