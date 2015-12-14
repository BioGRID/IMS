DROP TABLE IF EXISTS `unknown_participants`;

CREATE TABLE `unknown_participants` (
  `unknown_participant_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `unknown_participant_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `participant_type_id` bigint(10) NOT NULL,
  `organism_id` bigint(10) NOT NULL,
  `unknown_participant_replacement_participant_id` bigint(10) NOT NULL,
  `unknown_participant_addeddate` datetime NOT NULL,
  `unknown_participant_status` enum('active','inactive','replaced') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`unknown_participant_id`),
  KEY `unknown_participant_value` (`unknown_participant_value`(191)),
  KEY `participant_type_id` (`participant_type_id`),
  KEY `organism_id` (`organism_id`),
  KEY `unknown_participant_addeddate` (`unknown_participant_addeddate`),
  KEY `unknown_participant_status` (`unknown_participant_status`),
  KEY `unknown_participant_replacement_participant_id` (`unknown_participant_replacement_participant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
