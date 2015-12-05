DROP TABLE IF EXISTS `ontologies`;

CREATE TABLE `ontologies` (
  `ontology_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `ontology_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ontology_url` text COLLATE utf8mb4_unicode_ci,
  `ontology_rootid` bigint(10) DEFAULT NULL,
  `ontology_addeddate` datetime NOT NULL,
  `ontology_lastparsed` datetime DEFAULT NULL,
  `ontology_status` enum('active','hidden','inactive') CHARACTER SET latin1 NOT NULL DEFAULT 'active',
  PRIMARY KEY (`ontology_id`),
  KEY `ontology_rootid` (`ontology_rootid`),
  KEY `ontology_addeddate` (`ontology_addeddate`),
  KEY `ontology_lastparsed` (`ontology_lastparsed`),
  KEY `ontology_status` (`ontology_status`),
  KEY `ontology_name` (`ontology_name`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
