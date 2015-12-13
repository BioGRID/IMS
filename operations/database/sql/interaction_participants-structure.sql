DROP TABLE IF EXISTS `interaction_participants`;

CREATE TABLE `interaction_participants` (
  `interaction_participant_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `interaction_id` bigint(10) NOT NULL,
  `participant_id` bigint(10) NOT NULL,
  `participant_role_id` bigint(10) NOT NULL,
  `interaction_participant_addeddate` datetime NOT NULL,
  `interaction_participant_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`interaction_participant_id`),
  KEY `interaction_id` (`interaction_id`),
  KEY `participant_id` (`participant_id`),
  KEY `participant_role_id` (`participant_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
