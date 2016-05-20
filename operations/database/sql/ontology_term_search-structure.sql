DROP TABLE IF EXISTS `ontology_term_search`;

CREATE TABLE IF NOT EXISTS `ontology_term_search` (
  `ontology_term_id` bigint(10) NOT NULL,
  `ontology_term_official_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ontology_term_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ontology_term_synonyms` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ontology_id` bigint(10) NOT NULL,
  `ontology_term_childcount` bigint(10) NOT NULL,
  KEY `ontology_term_id` (`ontology_term_id`),
  KEY `ontology_term_official_id` (`ontology_term_official_id`(191)),
  KEY `ontology_id` (`ontology_id`),
  KEY `ontology_term_childcount` (`ontology_term_childcount`),
  FULLTEXT KEY `ontology_term_name` (`ontology_term_name`),
  FULLTEXT KEY `ontology_term_synonyms` (`ontology_term_synonyms`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
