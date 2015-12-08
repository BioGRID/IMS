DROP TABLE IF EXISTS `ontology_relationships`;

CREATE TABLE `ontology_relationships` (
  `ontology_relationship_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `ontology_term_id` bigint(10) NOT NULL,
  `ontology_parent_id` bigint(10) DEFAULT NULL,
  `ontology_relationship_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ontology_relationship_addeddate` datetime NOT NULL,
  `ontology_relationship_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`ontology_relationship_id`),
  KEY `ontology_term_id` (`ontology_term_id`),
  KEY `ontology_parent_id` (`ontology_parent_id`),
  KEY `ontology_relationship_type` (`ontology_relationship_type`(191)),
  KEY `ontology_relationship_addeddate` (`ontology_relationship_addeddate`),
  KEY `ontology_relationship_status` (`ontology_relationship_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
