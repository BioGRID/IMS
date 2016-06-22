DROP TABLE IF EXISTS `attribute_type_ontologies`;

CREATE TABLE `attribute_type_ontologies` (
  `attribute_type_ontology_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `attribute_type_id` bigint(10) NOT NULL,
  `ontology_id` bigint(10) NOT NULL,
  `attribute_type_ontology_option` enum('term','qualifier','both') COLLATE utf8mb4_unicode_ci NOT NULL,
  `attribute_type_ontology_selected` tinyint(1) NOT NULL,
  `attribute_type_ontology_organism` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attribute_type_ontology_addeddate` datetime NOT NULL,
  `attribute_type_ontology_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`attribute_type_ontology_id`),
  KEY `attribute_type_id` (`attribute_type_id`),
  KEY `ontology_id` (`ontology_id`),
  KEY `attribute_type_ontology_option` (`attribute_type_ontology_option`),
  KEY `attribute_type_ontology_addeddate` (`attribute_type_ontology_addeddate`),
  KEY `attribute_type_ontology_status` (`attribute_type_ontology_status`),
  KEY `attribute_type_ontology_selected` (`attribute_type_ontology_selected`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
