DROP TABLE IF EXISTS `participants`;

CREATE TABLE `participants` (
  `participant_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `participant_value` bigint(10) NOT NULL,
  `participant_type_id` bigint(10) DEFAULT NULL,
  `participant_addeddate` datetime NOT NULL,
  `participant_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`participant_id`),
  KEY `participant_type_id` (`participant_type_id`),
  KEY `participant_value` (`participant_value`),
  KEY `participant_addeddate` (`participant_addeddate`),
  KEY `participant_status` (`participant_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
