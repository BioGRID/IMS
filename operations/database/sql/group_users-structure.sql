DROP TABLE IF EXISTS `group_users`;

CREATE TABLE `group_users` (
  `group_user_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `group_id` bigint(10) NOT NULL,
  `user_id` bigint(10) NOT NULL,
  `group_user_addeddate` datetime NOT NULL,
  `group_user_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`group_user_id`),
  KEY `group_id` (`group_id`),
  KEY `user_id` (`user_id`),
  KEY `group_user_addeddate` (`group_user_addeddate`),
  KEY `group_user_status` (`group_user_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
