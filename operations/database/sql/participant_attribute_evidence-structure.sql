DROP TABLE IF EXISTS `participant_attribute_evidence`;

CREATE TABLE `participant_attribute_evidence` (
  `participant_attribute_evidence_id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `participant_attribute_evidence_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `participant_attribute_evidence_addeddate` datetime NOT NULL,
  `participant_attribute_evidence_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`participant_attribute_evidence_id`),
  KEY `participant_attribute_evidence_name` (`participant_attribute_evidence_name`(191)),
  KEY `participant_attribute_evidence_addeddate` (`participant_attribute_evidence_addeddate`),
  KEY `participant_attribute_evidence_status` (`participant_attribute_evidence_status`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
