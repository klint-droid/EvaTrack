-- ============================================================================
-- KLINT UNIFIED DATABASE — COMPLETE CORRECTED SCHEMA (MySQL 8.0+ / InnoDB)
-- Subsystems Supported: EvaTrack, ResQ / ResQperation, and BIDRCS
-- Total Objects: 111 Base Tables + 2 Analytical Views
-- Character Set: utf8mb4 | Collation: utf8mb4_0900_ai_ci
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ----------------------------------------------------------------------------
-- Table: `_archive_orphan_household_disasters`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `_archive_orphan_household_disasters`;
CREATE TABLE `_archive_orphan_household_disasters` (
  `household_disaster_id` int NOT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `disaster_id` varchar(255) DEFAULT NULL,
  `initial_status_id` int DEFAULT NULL,
  `current_status_id` int DEFAULT NULL,
  `last_status_source` varchar(50) DEFAULT NULL,
  `last_status_notes` text,
  `last_reported_by_user_id` varchar(255) DEFAULT NULL,
  `last_responder_id` int DEFAULT NULL,
  `last_device_token_id` bigint DEFAULT NULL,
  `last_latitude` decimal(10,7) DEFAULT NULL,
  `last_longitude` decimal(10,7) DEFAULT NULL,
  `last_battery_level` int DEFAULT NULL,
  `last_reported_at` datetime DEFAULT NULL,
  `priority_level` varchar(30) DEFAULT NULL,
  `needs_dispatch` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`household_disaster_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `_archive_orphan_household_status_logs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `_archive_orphan_household_status_logs`;
CREATE TABLE `_archive_orphan_household_status_logs` (
  `status_log_id` int NOT NULL DEFAULT '0',
  `disaster_id` varchar(255) DEFAULT NULL,
  `household_id` varchar(255) NOT NULL,
  `status_id` int NOT NULL,
  `source` varchar(50) NOT NULL,
  `submitted_by_user_id` varchar(255) DEFAULT NULL,
  `responder_id` int DEFAULT NULL,
  `device_token_id` bigint DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_label` varchar(255) DEFAULT NULL,
  `location_accuracy_m` decimal(8,2) DEFAULT NULL,
  `battery_level` int DEFAULT NULL,
  `signal_strength` int DEFAULT NULL,
  `notes` text,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by_user_id` varchar(255) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`status_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `accommodation_types`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `accommodation_types`;
CREATE TABLE `accommodation_types` (
  `type_id` int NOT NULL AUTO_INCREMENT,
  `type_key` varchar(50) DEFAULT NULL,
  `type_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_accommodation_types_key` (`type_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `accommodation_units`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `accommodation_units`;
CREATE TABLE `accommodation_units` (
  `unit_id` int NOT NULL AUTO_INCREMENT,
  `center_id` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `type_id` int DEFAULT NULL,
  `max_capacity` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`unit_id`),
  KEY `idx_accomm_units_center_deleted` (`center_id`, `deleted_at`),
  KEY `idx_accomm_units_type` (`type_id`),
  CONSTRAINT `fk_accomm_units_center` FOREIGN KEY (`center_id`) REFERENCES `evacuation_centers` (`evacuation_center_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_accomm_units_type` FOREIGN KEY (`type_id`) REFERENCES `accommodation_types` (`type_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `addresses`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `addresses`;
CREATE TABLE `addresses` (
  `address_id` int NOT NULL AUTO_INCREMENT,
  `street_address` int DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `barangay_id` int DEFAULT NULL,
  `barangay_name` varchar(255) DEFAULT NULL,
  `sitio_id` int DEFAULT NULL,
  `purok_sitio` varchar(255) DEFAULT NULL,
  `house_number` varchar(255) DEFAULT NULL,
  `purok_id` int DEFAULT NULL,
  `zipcode_id` int DEFAULT NULL,
  `zip_code` varchar(50) DEFAULT NULL,
  `full_address` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_apartment` tinyint(1) DEFAULT NULL,
  `building_name` varchar(120) DEFAULT NULL,
  `floor_number` varchar(30) DEFAULT NULL,
  `unit_number` varchar(80) DEFAULT NULL,
  `room_number` varchar(80) DEFAULT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`address_id`),
  KEY `idx_addresses_barangay` (`barangay_id`),
  KEY `idx_addresses_sitio` (`sitio_id`),
  KEY `idx_addresses_purok` (`purok_id`),
  KEY `idx_addresses_zipcode` (`zipcode_id`),
  CONSTRAINT `fk_addresses_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_addresses_sitio` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`sitio_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_addresses_purok` FOREIGN KEY (`purok_id`) REFERENCES `puroks` (`purok_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_addresses_zipcode` FOREIGN KEY (`zipcode_id`) REFERENCES `zipcodes` (`zipcode_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `affected_areas`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `affected_areas`;
CREATE TABLE `affected_areas` (
  `affected_area_id` int NOT NULL AUTO_INCREMENT,
  `disaster_id` varchar(255) DEFAULT NULL,
  `confirmed_by_admin_id` varchar(255) DEFAULT NULL,
  `severity_id` int DEFAULT NULL,
  `purok_id` int DEFAULT NULL,
  `sitio_id` int DEFAULT NULL,
  `barangay_id` int DEFAULT NULL,
  `confirmed_by_hq` tinyint(1) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`affected_area_id`),
  KEY `idx_affected_areas_disaster` (`disaster_id`),
  KEY `idx_affected_areas_purok` (`purok_id`),
  KEY `idx_affected_areas_sitio` (`sitio_id`),
  KEY `idx_affected_areas_barangay` (`barangay_id`),
  KEY `idx_affected_areas_severity` (`severity_id`),
  CONSTRAINT `fk_affected_areas_disaster` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_affected_areas_severity` FOREIGN KEY (`severity_id`) REFERENCES `severity_levels` (`severity_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_affected_areas_purok` FOREIGN KEY (`purok_id`) REFERENCES `puroks` (`purok_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_affected_areas_sitio` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`sitio_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_affected_areas_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `analytics`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE `analytics` (
  `analytic_id` varchar(255) NOT NULL,
  `evacuation_event_id` varchar(255) DEFAULT NULL,
  `evacuation_center_id` varchar(255) DEFAULT NULL,
  `barangay_id` int DEFAULT NULL,
  `purok_sitio` varchar(150) DEFAULT NULL,
  `record_period` date DEFAULT NULL,
  `total_households` int unsigned NOT NULL DEFAULT '0',
  `snapshot_type` varchar(50) DEFAULT NULL,
  `total_population` int DEFAULT NULL,
  `total_males` int unsigned NOT NULL DEFAULT '0',
  `total_females` int unsigned NOT NULL DEFAULT '0',
  `total_pwd` int unsigned NOT NULL DEFAULT '0',
  `total_seniors` int unsigned NOT NULL DEFAULT '0',
  `total_children` int unsigned NOT NULL DEFAULT '0',
  `total_adults` int unsigned NOT NULL DEFAULT '0',
  `total_pregnant` int unsigned NOT NULL DEFAULT '0',
  `total_evacuees` int unsigned NOT NULL DEFAULT '0',
  `children_count` int DEFAULT NULL,
  `adult_count` int DEFAULT NULL,
  `elderly_count` int DEFAULT NULL,
  `pwd_count` int DEFAULT NULL,
  `pregnant_count` int DEFAULT NULL,
  `male_count` int DEFAULT NULL,
  `female_count` int DEFAULT NULL,
  `recorded_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`analytic_id`),
  KEY `idx_analytics_event_center_period` (`evacuation_event_id`, `evacuation_center_id`, `record_period`),
  KEY `idx_analytics_barangay` (`barangay_id`),
  CONSTRAINT `fk_analytics_event` FOREIGN KEY (`evacuation_event_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_analytics_center` FOREIGN KEY (`evacuation_center_id`) REFERENCES `evacuation_centers` (`evacuation_center_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_analytics_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `analytics_job_logs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `analytics_job_logs`;
CREATE TABLE `analytics_job_logs` (
  `job_id` int NOT NULL AUTO_INCREMENT,
  `status_id` int NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `message` text,
  PRIMARY KEY (`job_id`),
  KEY `idx_analytics_job_logs_status` (`status_id`, `started_at`),
  CONSTRAINT `fk_analytics_job_logs_status` FOREIGN KEY (`status_id`) REFERENCES `analytics_job_status` (`status_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `analytics_job_status`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `analytics_job_status`;
CREATE TABLE `analytics_job_status` (
  `status_id` int NOT NULL,
  `status_key` varchar(50) DEFAULT NULL,
  `status_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_analytics_job_status_key` (`status_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `audit_logs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `audit_log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) DEFAULT NULL,
  `role_key` varchar(50) DEFAULT NULL,
  `module` varchar(80) NOT NULL,
  `action` varchar(80) NOT NULL,
  `reference_table` varchar(80) DEFAULT NULL,
  `reference_id` varchar(255) DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`audit_log_id`),
  KEY `idx_audit_logs_created` (`created_at`),
  KEY `idx_audit_logs_user_created` (`user_id`, `created_at`),
  KEY `idx_audit_logs_module_action` (`module`, `action`),
  KEY `idx_audit_logs_reference` (`reference_table`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `barangay_boundaries`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `barangay_boundaries`;
CREATE TABLE `barangay_boundaries` (
  `psgc_code` varchar(12) NOT NULL,
  `barangay_id` int DEFAULT NULL,
  `barangay_code` varchar(12) DEFAULT NULL,
  `barangay_name` varchar(100) NOT NULL,
  `city_name` varchar(100) NOT NULL,
  `geometry_json` longtext NOT NULL,
  `source_url` varchar(500) NOT NULL,
  `source_note` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`psgc_code`),
  KEY `barangay_boundaries_barangay_id_index` (`barangay_id`),
  KEY `barangay_boundaries_barangay_code_index` (`barangay_code`),
  CONSTRAINT `fk_barangay_boundaries_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `barangays`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `barangays`;
CREATE TABLE `barangays` (
  `barangay_id` int NOT NULL,
  `barangay_code` varchar(20) DEFAULT NULL,
  `barangay_name` varchar(100) DEFAULT NULL,
  `city_id` int NOT NULL,
  PRIMARY KEY (`barangay_id`),
  UNIQUE KEY `uk_barangays_code` (`barangay_code`),
  KEY `idx_barangays_city` (`city_id`),
  CONSTRAINT `fk_barangays_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `cache`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `cache_locks`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `cached_routes`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cached_routes`;
CREATE TABLE `cached_routes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int NOT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `evac_center_id` varchar(255) DEFAULT NULL,
  `target_key` varchar(300) NOT NULL,
  `profile` enum('auto','pedestrian') NOT NULL,
  `polyline6` longtext,
  `steps` json DEFAULT NULL,
  `distance_m` int unsigned DEFAULT NULL,
  `duration_s` int unsigned DEFAULT NULL,
  `status` enum('ready','failed') NOT NULL DEFAULT 'failed',
  `failure_reason` varchar(255) DEFAULT NULL,
  `computed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cached_routes_team_target_profile_unique` (`team_id`,`target_key`,`profile`),
  KEY `cached_routes_household_id_foreign` (`household_id`),
  KEY `cached_routes_evac_center_id_foreign` (`evac_center_id`),
  KEY `cached_routes_team_status_profile_index` (`team_id`,`status`,`profile`),
  CONSTRAINT `cached_routes_evac_center_id_foreign` FOREIGN KEY (`evac_center_id`) REFERENCES `evacuation_centers` (`evacuation_center_id`) ON DELETE CASCADE,
  CONSTRAINT `cached_routes_household_id_foreign` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE,
  CONSTRAINT `cached_routes_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `rescue_teams` (`team_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `center_issue_categories`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `center_issue_categories`;
CREATE TABLE `center_issue_categories` (
  `category_id` int NOT NULL,
  `category_key` varchar(50) DEFAULT NULL,
  `category_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uk_center_issue_categories_key` (`category_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `center_issue_report_statuses`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `center_issue_report_statuses`;
CREATE TABLE `center_issue_report_statuses` (
  `status_id` int NOT NULL,
  `status_key` varchar(50) DEFAULT NULL,
  `status_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_center_issue_report_statuses_key` (`status_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `center_issue_reports`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `center_issue_reports`;
CREATE TABLE `center_issue_reports` (
  `report_id` varchar(255) NOT NULL,
  `evacuation_center_id` varchar(255) DEFAULT NULL,
  `reported_by` varchar(255) DEFAULT NULL,
  `handled_by` varchar(255) DEFAULT NULL,
  `category_id` int NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `description` text,
  `severity_id` int DEFAULT NULL,
  `status_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `idx_cir_status_created` (`status_id`, `created_at`),
  KEY `idx_cir_center_status` (`evacuation_center_id`, `status_id`),
  KEY `idx_cir_category` (`category_id`),
  KEY `idx_cir_severity` (`severity_id`),
  KEY `idx_cir_reported_by` (`reported_by`),
  KEY `idx_cir_handled_by` (`handled_by`),
  CONSTRAINT `fk_cir_center` FOREIGN KEY (`evacuation_center_id`) REFERENCES `evacuation_centers` (`evacuation_center_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cir_reported_by` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cir_handled_by` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cir_category` FOREIGN KEY (`category_id`) REFERENCES `center_issue_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cir_severity` FOREIGN KEY (`severity_id`) REFERENCES `severity_levels` (`severity_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cir_status` FOREIGN KEY (`status_id`) REFERENCES `center_issue_report_statuses` (`status_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `cities`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cities`;
CREATE TABLE `cities` (
  `city_id` int NOT NULL,
  `city_code` varchar(20) DEFAULT NULL,
  `city_name` varchar(100) DEFAULT NULL,
  `province_id` int NOT NULL,
  PRIMARY KEY (`city_id`),
  UNIQUE KEY `uk_cities_code` (`city_code`),
  KEY `idx_cities_province` (`province_id`),
  CONSTRAINT `fk_cities_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`province_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `civil_statuses`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `civil_statuses`;
CREATE TABLE `civil_statuses` (
  `status_id` int NOT NULL AUTO_INCREMENT,
  `status_key` varchar(20) DEFAULT NULL,
  `status_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_status_key` (`status_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `csv_uploads`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `csv_uploads`;
CREATE TABLE `csv_uploads` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `data_source_id` bigint NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `total_records` int DEFAULT NULL,
  `successful_records` int DEFAULT NULL,
  `failed_records` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_csv_uploads_source` (`data_source_id`),
  CONSTRAINT `fk_csv_uploads_source` FOREIGN KEY (`data_source_id`) REFERENCES `data_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `data_sources`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `data_sources`;
CREATE TABLE `data_sources` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `type` varchar(20) DEFAULT NULL,
  `uploaded_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_data_sources_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_data_sources_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `device_tokens`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `device_tokens`;
CREATE TABLE `device_tokens` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `device_uuid` varchar(150) DEFAULT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `member_id` varchar(255) DEFAULT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `platform` varchar(30) DEFAULT NULL,
  `app_role` varchar(30) DEFAULT NULL,
  `push_provider` varchar(50) DEFAULT NULL,
  `player_id` varchar(255) DEFAULT NULL,
  `expo_push_token` varchar(255) DEFAULT NULL,
  `battery_level` int DEFAULT NULL,
  `signal_strength` int DEFAULT NULL,
  `location_permission_status` varchar(30) NOT NULL DEFAULT 'unknown',
  `notification_permission_status` varchar(30) NOT NULL DEFAULT 'unknown',
  `last_latitude` decimal(10,7) DEFAULT NULL,
  `last_longitude` decimal(10,7) DEFAULT NULL,
  `last_location_label` varchar(255) DEFAULT NULL,
  `last_location_accuracy_m` decimal(8,2) DEFAULT NULL,
  `last_location_at` datetime DEFAULT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `logged_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `team_id` int DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `selection_token_id` bigint unsigned DEFAULT NULL,
  `owner_selected_at` timestamp NULL DEFAULT NULL,
  `push_token` varchar(255) DEFAULT NULL,
  `one_signal_user_id` varchar(255) DEFAULT NULL,
  `responder_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resq_device_uuid_lookup` (`device_uuid`),
  KEY `resq_household_device_lookup` (`household_id`,`device_uuid`),
  KEY `resq_team_user_lookup` (`team_id`,`user_id`),
  KEY `idx_device_tokens_member` (`member_id`),
  KEY `idx_device_tokens_user` (`user_id`),
  KEY `idx_device_tokens_responder` (`responder_id`),
  CONSTRAINT `fk_device_tokens_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_device_tokens_member` FOREIGN KEY (`member_id`) REFERENCES `household_members` (`member_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_device_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_device_tokens_team` FOREIGN KEY (`team_id`) REFERENCES `rescue_teams` (`team_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_device_tokens_responder` FOREIGN KEY (`responder_id`) REFERENCES `responders` (`responder_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `device_tracking_logs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `device_tracking_logs`;
CREATE TABLE `device_tracking_logs` (
  `tracking_id` int NOT NULL AUTO_INCREMENT,
  `device_token_id` bigint DEFAULT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `member_id` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_label` varchar(255) DEFAULT NULL,
  `accuracy_m` decimal(8,2) DEFAULT NULL,
  `location_source` varchar(50) DEFAULT NULL,
  `is_allowed_location` tinyint(1) NOT NULL DEFAULT '1',
  `battery_level` int DEFAULT NULL,
  `signal_strength` int DEFAULT NULL,
  `logged_at` datetime NOT NULL,
  PRIMARY KEY (`tracking_id`),
  KEY `idx_dev_tracking_token_time` (`device_token_id`, `logged_at`),
  KEY `idx_dev_tracking_household` (`household_id`, `logged_at`),
  KEY `idx_dev_tracking_member` (`member_id`, `logged_at`),
  CONSTRAINT `fk_dev_tracking_token` FOREIGN KEY (`device_token_id`) REFERENCES `device_tokens` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_dev_tracking_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_dev_tracking_member` FOREIGN KEY (`member_id`) REFERENCES `household_members` (`member_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `disaster_broadcasts`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `disaster_broadcasts`;
CREATE TABLE `disaster_broadcasts` (
  `broadcast_id` int NOT NULL AUTO_INCREMENT,
  `broadcast_title` varchar(150) DEFAULT NULL,
  `disaster_id` varchar(255) DEFAULT NULL,
  `sent_by_admin_id` varchar(255) DEFAULT NULL,
  `severity_id` int DEFAULT NULL,
  `scope_type` varchar(50) DEFAULT NULL,
  `target_purok_id` int DEFAULT NULL,
  `target_area_id` int DEFAULT NULL,
  `message` text,
  `allowed_statuses` varchar(255) DEFAULT NULL,
  `channel` varchar(50) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'sent',
  `notification_id` int DEFAULT NULL,
  `weather_log_id` int DEFAULT NULL,
  `sent_at` datetime NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `target_area_label` varchar(150) DEFAULT NULL,
  `direct_impact_puroks_json` json DEFAULT NULL,
  `allowed_statuses_json` json DEFAULT NULL,
  `recipient_count` int DEFAULT NULL,
  `push_status` varchar(50) DEFAULT NULL,
  `sms_status` varchar(50) DEFAULT NULL,
  `status_options_json` text,
  `push_provider_ids_json` json DEFAULT NULL,
  `push_errors_json` json DEFAULT NULL,
  `push_accepted_count` int unsigned DEFAULT NULL,
  `onesignal_id` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`broadcast_id`),
  KEY `idx_disaster_broadcasts_event` (`disaster_id`, `sent_at`),
  KEY `idx_disaster_broadcasts_notif` (`notification_id`),
  KEY `idx_disaster_broadcasts_weather` (`weather_log_id`),
  CONSTRAINT `fk_disaster_broadcasts_event` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_disaster_broadcasts_severity` FOREIGN KEY (`severity_id`) REFERENCES `severity_levels` (`severity_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_disaster_broadcasts_notif` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notif_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_disaster_broadcasts_weather` FOREIGN KEY (`weather_log_id`) REFERENCES `weather_logs` (`weather_log_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `disaster_event_types`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `disaster_event_types`;
CREATE TABLE `disaster_event_types` (
  `event_type_id` int NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) DEFAULT NULL,
  `type_id` int NOT NULL,
  PRIMARY KEY (`event_type_id`),
  UNIQUE KEY `uk_disaster_event_types_pair` (`event_id`, `type_id`),
  KEY `idx_disaster_event_types_type` (`type_id`),
  CONSTRAINT `fk_det_event` FOREIGN KEY (`event_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_det_type` FOREIGN KEY (`type_id`) REFERENCES `disaster_types` (`type_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `disaster_events`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `disaster_events`;
CREATE TABLE `disaster_events` (
  `event_id` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `type_id` int NOT NULL,
  `severity_level_id` int DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  KEY `idx_disaster_events_active` (`deleted_at`, `ended_at`, `started_at`),
  KEY `idx_disaster_events_type` (`type_id`),
  KEY `idx_disaster_events_severity` (`severity_level_id`),
  CONSTRAINT `fk_disaster_events_type` FOREIGN KEY (`type_id`) REFERENCES `disaster_types` (`type_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_disaster_events_severity` FOREIGN KEY (`severity_level_id`) REFERENCES `severity_levels` (`severity_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `disaster_types`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `disaster_types`;
CREATE TABLE `disaster_types` (
  `type_id` int NOT NULL,
  `type_code` varchar(20) DEFAULT NULL,
  `type_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_disaster_types_code` (`type_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `dispatch_statuses`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `dispatch_statuses`;
CREATE TABLE `dispatch_statuses` (
  `status_key` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT '1',
  `allow_create` tinyint(1) NOT NULL DEFAULT '0',
  `allow_mobile` tinyint(1) NOT NULL DEFAULT '0',
  `dashboard_group` varchar(30) NOT NULL DEFAULT 'other',
  `is_alias` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status_code` varchar(20) NOT NULL,
  PRIMARY KEY (`status_key`),
  UNIQUE KEY `dispatch_statuses_status_code_unique` (`status_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `dispatch_trips`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `dispatch_trips`;
CREATE TABLE `dispatch_trips` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dispatch_id` int NOT NULL,
  `team_id` int NOT NULL,
  `device_id` bigint NOT NULL,
  `profile` enum('auto','pedestrian') NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `distance_m` int unsigned DEFAULT NULL,
  `planned_distance_m` int unsigned DEFAULT NULL,
  `moving_s` int unsigned NOT NULL DEFAULT '0',
  `stopped_s` int unsigned NOT NULL DEFAULT '0',
  `avg_speed_ms` decimal(8,3) DEFAULT NULL,
  `status` enum('recording','completed','abandoned') NOT NULL DEFAULT 'recording',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dispatch_trips_device_id_foreign` (`device_id`),
  KEY `dispatch_trips_dispatch_started_index` (`dispatch_id`,`started_at`),
  KEY `dispatch_trips_team_started_index` (`team_id`,`started_at`),
  CONSTRAINT `dispatch_trips_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device_tokens` (`id`),
  CONSTRAINT `dispatch_trips_dispatch_id_foreign` FOREIGN KEY (`dispatch_id`) REFERENCES `responder_assignments` (`assignment_id`),
  CONSTRAINT `dispatch_trips_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `rescue_teams` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `education_levels`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `education_levels`;
CREATE TABLE `education_levels` (
  `education_level_id` int NOT NULL,
  `education_level_key` varchar(20) DEFAULT NULL,
  `education_level_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`education_level_id`),
  UNIQUE KEY `uk_education_levels_key` (`education_level_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `evacuated_members`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `evacuated_members`;
CREATE TABLE `evacuated_members` (
  `evacuated_member_id` int NOT NULL AUTO_INCREMENT,
  `evacuation_id` int NOT NULL,
  `member_id` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`evacuated_member_id`),
  UNIQUE KEY `uk_evac_members_evac_member` (`evacuation_id`, `member_id`),
  KEY `idx_evac_members_member` (`member_id`),
  CONSTRAINT `fk_evac_members_record` FOREIGN KEY (`evacuation_id`) REFERENCES `evacuation_records` (`evacuation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_evac_members_member` FOREIGN KEY (`member_id`) REFERENCES `household_members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `evacuation_centers`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `evacuation_centers`;
CREATE TABLE `evacuation_centers` (
  `evacuation_center_id` varchar(255) NOT NULL,
  `current_event_id` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `center_type` varchar(80) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `capacity` int DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `current_occupancy` int DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `osm_address` varchar(255) DEFAULT NULL,
  `notes` text,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`evacuation_center_id`),
  KEY `idx_evac_centers_status` (`deleted_at`, `status`),
  KEY `idx_evac_centers_event` (`current_event_id`),
  CONSTRAINT `fk_evac_centers_event` FOREIGN KEY (`current_event_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `evacuation_records`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `evacuation_records`;
CREATE TABLE `evacuation_records` (
  `evacuation_id` int NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) DEFAULT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `center_id` varchar(255) DEFAULT NULL,
  `household_status_id` int DEFAULT NULL,
  `evacuated_count` int DEFAULT NULL,
  `method` enum('qr','manual') DEFAULT NULL,
  `verified_by` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`evacuation_id`),
  KEY `idx_evac_records_status_center` (`center_id`,`household_status_id`,`household_id`),
  KEY `idx_evac_records_event_household` (`event_id`, `household_id`),
  KEY `idx_evac_records_household` (`household_id`),
  KEY `idx_evac_records_status` (`household_status_id`),
  KEY `idx_evac_records_verified_by` (`verified_by`),
  CONSTRAINT `fk_evac_records_event` FOREIGN KEY (`event_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_evac_records_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_evac_records_center` FOREIGN KEY (`center_id`) REFERENCES `evacuation_centers` (`evacuation_center_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_evac_records_status` FOREIGN KEY (`household_status_id`) REFERENCES `household_statuses` (`status_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_evac_records_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `event_center_history`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `event_center_history`;
CREATE TABLE `event_center_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) NOT NULL,
  `evacuation_center_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_center_unique` (`event_id`,`evacuation_center_id`),
  KEY `idx_event_center_history_center` (`evacuation_center_id`),
  CONSTRAINT `fk_ech_event` FOREIGN KEY (`event_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ech_center` FOREIGN KEY (`evacuation_center_id`) REFERENCES `evacuation_centers` (`evacuation_center_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `failed_jobs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) DEFAULT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `field_report_categories`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `field_report_categories`;
CREATE TABLE `field_report_categories` (
  `category_id` int NOT NULL,
  `category_key` varchar(50) DEFAULT NULL,
  `category_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uk_field_report_categories_key` (`category_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `genders`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `genders`;
CREATE TABLE `genders` (
  `gender_id` int NOT NULL AUTO_INCREMENT,
  `gender_key` varchar(20) DEFAULT NULL,
  `gender_label` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`gender_id`),
  UNIQUE KEY `uk_gender_key` (`gender_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `geotagged_locations`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `geotagged_locations`;
CREATE TABLE `geotagged_locations` (
  `location_id` int NOT NULL AUTO_INCREMENT,
  `household_id` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_label` varchar(255) DEFAULT NULL,
  `accuracy_m` decimal(8,2) DEFAULT NULL,
  `geotag_source` varchar(50) NOT NULL DEFAULT 'household_mobile',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_by_user_id` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`location_id`),
  KEY `idx_geo_household` (`household_id`),
  KEY `idx_geo_verified_by` (`verified_by_user_id`),
  CONSTRAINT `fk_geo_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_geo_verified_by` FOREIGN KEY (`verified_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `household_disasters`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `household_disasters`;
CREATE TABLE `household_disasters` (
  `household_disaster_id` int NOT NULL AUTO_INCREMENT,
  `household_id` varchar(255) DEFAULT NULL,
  `disaster_id` varchar(255) DEFAULT NULL,
  `initial_status_id` int DEFAULT NULL,
  `current_status_id` int DEFAULT NULL,
  `last_status_source` varchar(50) DEFAULT NULL,
  `last_status_notes` text,
  `last_reported_by_user_id` varchar(255) DEFAULT NULL,
  `last_responder_id` int DEFAULT NULL,
  `last_device_token_id` bigint DEFAULT NULL,
  `last_latitude` decimal(10,7) DEFAULT NULL,
  `last_longitude` decimal(10,7) DEFAULT NULL,
  `last_battery_level` int DEFAULT NULL,
  `last_reported_at` datetime DEFAULT NULL,
  `priority_level` varchar(30) DEFAULT NULL,
  `needs_dispatch` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`household_disaster_id`),
  UNIQUE KEY `uk_hd_event_household` (`disaster_id`,`household_id`),
  KEY `idx_hd_dispatch` (`disaster_id`,`needs_dispatch`),
  KEY `fk_hd_household` (`household_id`),
  KEY `fk_hd_initial_status` (`initial_status_id`),
  KEY `fk_hd_status` (`current_status_id`),
  CONSTRAINT `fk_hd_event` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`),
  CONSTRAINT `fk_hd_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`),
  CONSTRAINT `fk_hd_initial_status` FOREIGN KEY (`initial_status_id`) REFERENCES `household_statuses` (`status_id`),
  CONSTRAINT `fk_hd_status` FOREIGN KEY (`current_status_id`) REFERENCES `household_statuses` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `household_members`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `household_members`;
CREATE TABLE `household_members` (
  `member_id` varchar(255) NOT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `birth_date` date NOT NULL,
  `gender_id` int DEFAULT NULL,
  `relationship_id` int DEFAULT NULL,
  `is_household_head` tinyint(1) NOT NULL DEFAULT '0',
  `can_report_for_household` tinyint(1) NOT NULL DEFAULT '0',
  `contact_number` varchar(50) DEFAULT NULL,
  `civil_status_id` int DEFAULT NULL,
  `occupation` int DEFAULT NULL,
  `education_level_id` int DEFAULT NULL,
  `is_graduate` tinyint(1) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_pwd` tinyint(1) NOT NULL DEFAULT '0',
  `is_senior` tinyint(1) NOT NULL DEFAULT '0',
  `is_pregnant` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sex` varchar(1) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `relation` varchar(50) DEFAULT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `education_level` varchar(100) DEFAULT NULL,
  `special_needs` varchar(50) DEFAULT NULL,
  `legacy_member_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`member_id`),
  KEY `idx_hm_household` (`household_id`),
  KEY `idx_hm_relationship` (`relationship_id`),
  KEY `idx_hm_head` (`household_id`,`is_household_head`),
  KEY `idx_hm_gender` (`gender_id`),
  KEY `idx_hm_civil_status` (`civil_status_id`),
  KEY `idx_hm_education` (`education_level_id`),
  KEY `idx_hm_occupation` (`occupation`),
  KEY `idx_hm_deleted` (`deleted_at`),
  CONSTRAINT `fk_hm_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hm_gender` FOREIGN KEY (`gender_id`) REFERENCES `genders` (`gender_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hm_relationship` FOREIGN KEY (`relationship_id`) REFERENCES `relationships` (`relationship_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hm_civil_status` FOREIGN KEY (`civil_status_id`) REFERENCES `civil_statuses` (`status_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hm_occupation` FOREIGN KEY (`occupation`) REFERENCES `occupations` (`occuaption_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hm_education` FOREIGN KEY (`education_level_id`) REFERENCES `education_levels` (`education_level_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `household_status_logs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `household_status_logs`;
CREATE TABLE `household_status_logs` (
  `status_log_id` int NOT NULL AUTO_INCREMENT,
  `disaster_id` varchar(255) DEFAULT NULL,
  `household_id` varchar(255) NOT NULL,
  `status_id` int NOT NULL,
  `source` varchar(50) NOT NULL,
  `submitted_by_user_id` varchar(255) DEFAULT NULL,
  `responder_id` int DEFAULT NULL,
  `device_token_id` bigint DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_label` varchar(255) DEFAULT NULL,
  `location_accuracy_m` decimal(8,2) DEFAULT NULL,
  `battery_level` int DEFAULT NULL,
  `signal_strength` int DEFAULT NULL,
  `notes` text,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by_user_id` varchar(255) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`status_log_id`),
  KEY `idx_hsl_event_household` (`disaster_id`,`household_id`,`created_at`),
  KEY `fk_hsl_status` (`status_id`),
  KEY `idx_hsl_household` (`household_id`, `created_at`),
  KEY `idx_hsl_responder` (`responder_id`),
  CONSTRAINT `fk_hsl_status` FOREIGN KEY (`status_id`) REFERENCES `household_statuses` (`status_id`),
  CONSTRAINT `fk_hsl_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hsl_event` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `household_statuses`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `household_statuses`;
CREATE TABLE `household_statuses` (
  `status_id` int NOT NULL,
  `status_key` varchar(50) DEFAULT NULL,
  `status_label` varchar(100) DEFAULT NULL,
  `severity_rank` tinyint unsigned NOT NULL DEFAULT '0',
  `requires_rescue` tinyint(1) NOT NULL DEFAULT '0',
  `color_hex` varchar(7) DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '999',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `safety_basis` varchar(6) DEFAULT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_household_status_key` (`status_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `household_trusted_pins`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `household_trusted_pins`;
CREATE TABLE `household_trusted_pins` (
  `household_id` varchar(255) NOT NULL,
  `pin_hash` varchar(255) NOT NULL,
  `updated_by_user_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`household_id`),
  CONSTRAINT `fk_htp_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `households`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `households`;
CREATE TABLE `households` (
  `household_id` varchar(255) NOT NULL,
  `household_code` varchar(255) DEFAULT NULL,
  `household_number` varchar(255) DEFAULT NULL,
  `household_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `member_count` int unsigned NOT NULL DEFAULT '0',
  `created_by` varchar(255) DEFAULT NULL,
  `address_id` int DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `emergency_contact` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `login_user_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`household_id`),
  UNIQUE KEY `uk_households_code` (`household_code`),
  KEY `idx_households_address` (`address_id`),
  KEY `idx_households_created_by` (`created_by`),
  KEY `idx_households_login_user` (`login_user_id`),
  KEY `idx_households_deleted` (`deleted_at`),
  CONSTRAINT `fk_households_address` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`address_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_households_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_households_login_user` FOREIGN KEY (`login_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `hq_field_reports`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `hq_field_reports`;
CREATE TABLE `hq_field_reports` (
  `hq_report_id` int NOT NULL AUTO_INCREMENT,
  `disaster_id` varchar(255) DEFAULT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `source` varchar(20) DEFAULT NULL,
  `linked_report_id` int NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`hq_report_id`),
  KEY `idx_hqfr_event` (`disaster_id`,`household_id`),
  KEY `idx_hqfr_linked_report` (`linked_report_id`),
  KEY `idx_hqfr_household` (`household_id`),
  CONSTRAINT `fk_hqfr_event` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hqfr_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hqfr_linked_report` FOREIGN KEY (`linked_report_id`) REFERENCES `responder_field_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `import_logs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `import_logs`;
CREATE TABLE `import_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `data_source_id` bigint NOT NULL,
  `row_num` int DEFAULT NULL,
  `row_number` int unsigned DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `error_message` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_import_logs_source_status` (`data_source_id`, `status`),
  CONSTRAINT `fk_import_logs_source` FOREIGN KEY (`data_source_id`) REFERENCES `data_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `incident_archives`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `incident_archives`;
CREATE TABLE `incident_archives` (
  `archive_id` int NOT NULL AUTO_INCREMENT,
  `archive_type` varchar(50) DEFAULT NULL,
  `disaster_id` varchar(255) DEFAULT NULL,
  `reference_table` varchar(80) DEFAULT NULL,
  `reference_id` varchar(255) DEFAULT NULL,
  `archived_by_admin_id` varchar(255) DEFAULT NULL,
  `archive_note` text,
  `archived_at` datetime NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`archive_id`),
  KEY `idx_incident_archives_disaster` (`disaster_id`, `archive_type`),
  KEY `idx_incident_archives_ref` (`reference_table`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `inquiries`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `inquiries`;
CREATE TABLE `inquiries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `organization` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `job_batches`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `jobs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) DEFAULT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`, `reserved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `landing_inquiries`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `landing_inquiries`;
CREATE TABLE `landing_inquiries` (
  `inquiry_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `organization` varchar(150) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'new',
  `source_page` varchar(80) NOT NULL DEFAULT 'landing_page',
  `ip_address` varchar(80) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `handled_by_user_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`inquiry_id`),
  KEY `idx_landing_inquiries_status` (`status`, `created_at`),
  KEY `idx_landing_inquiries_handler` (`handled_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `map_tile_metadata`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `map_tile_metadata`;
CREATE TABLE `map_tile_metadata` (
  `tile_id` int NOT NULL AUTO_INCREMENT,
  `zoom_level` int NOT NULL,
  `tile_x` int NOT NULL,
  `tile_y` int NOT NULL,
  `tile_url` varchar(500) DEFAULT NULL,
  `cached_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`tile_id`),
  UNIQUE KEY `uk_map_tile_zxy` (`zoom_level`, `tile_x`, `tile_y`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `mapping_hazard_layers`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `mapping_hazard_layers`;
CREATE TABLE `mapping_hazard_layers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `barangay_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `source` varchar(255) NOT NULL,
  `source_url` varchar(255) DEFAULT NULL,
  `assessed_at` date NOT NULL,
  `geojson` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mapping_hazard_layers_barangay_id_index` (`barangay_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `member_disaster_statuses`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `member_disaster_statuses`;
CREATE TABLE `member_disaster_statuses` (
  `member_status_id` bigint NOT NULL AUTO_INCREMENT,
  `disaster_id` varchar(255) NOT NULL,
  `household_id` varchar(255) NOT NULL,
  `member_id` varchar(255) NOT NULL,
  `status_id` int NOT NULL DEFAULT '1',
  `previous_status_id` int DEFAULT NULL,
  `report_source` enum('self','household_head','proxy_member','responder','hq','sms','system') NOT NULL DEFAULT 'system',
  `reported_by_user_id` varchar(255) DEFAULT NULL,
  `reported_by_member_id` varchar(255) DEFAULT NULL,
  `responder_id` int DEFAULT NULL,
  `device_token_id` bigint DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_label` varchar(255) DEFAULT NULL,
  `location_accuracy_m` decimal(8,2) DEFAULT NULL,
  `battery_level` int DEFAULT NULL,
  `signal_strength` int DEFAULT NULL,
  `evacuation_center_id` varchar(255) DEFAULT NULL,
  `needs_rescue` tinyint(1) NOT NULL DEFAULT '0',
  `severity_rank` tinyint unsigned NOT NULL DEFAULT '0',
  `priority_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'low',
  `notes` text,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_by_user_id` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `reported_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`member_status_id`),
  UNIQUE KEY `uk_mds_event_member` (`disaster_id`,`member_id`),
  KEY `idx_mds_household` (`disaster_id`,`household_id`),
  KEY `idx_mds_status` (`disaster_id`,`status_id`),
  KEY `idx_mds_rescue` (`disaster_id`,`needs_rescue`,`severity_rank`),
  KEY `idx_mds_member` (`member_id`),
  KEY `idx_mds_reported_at` (`reported_at`),
  KEY `idx_mds_status_fk` (`status_id`),
  KEY `idx_mds_prev_status_fk` (`previous_status_id`),
  KEY `fk_mds_household` (`household_id`),
  CONSTRAINT `fk_mds_event` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`),
  CONSTRAINT `fk_mds_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`),
  CONSTRAINT `fk_mds_member` FOREIGN KEY (`member_id`) REFERENCES `household_members` (`member_id`),
  CONSTRAINT `fk_mds_prev_status` FOREIGN KEY (`previous_status_id`) REFERENCES `member_statuses` (`status_id`),
  CONSTRAINT `fk_mds_status` FOREIGN KEY (`status_id`) REFERENCES `member_statuses` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `member_status_logs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `member_status_logs`;
CREATE TABLE `member_status_logs` (
  `member_status_log_id` bigint NOT NULL AUTO_INCREMENT,
  `disaster_id` varchar(255) NOT NULL,
  `household_id` varchar(255) NOT NULL,
  `member_id` varchar(255) NOT NULL,
  `from_status_id` int DEFAULT NULL,
  `to_status_id` int NOT NULL,
  `report_source` varchar(30) NOT NULL,
  `reported_by_user_id` varchar(255) DEFAULT NULL,
  `reported_by_member_id` varchar(255) DEFAULT NULL,
  `responder_id` int DEFAULT NULL,
  `device_token_id` bigint DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_label` varchar(255) DEFAULT NULL,
  `location_accuracy_m` decimal(8,2) DEFAULT NULL,
  `battery_level` int DEFAULT NULL,
  `signal_strength` int DEFAULT NULL,
  `notes` text,
  `reported_at` datetime DEFAULT NULL,
  `logged_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`member_status_log_id`),
  KEY `idx_msl_member_time` (`member_id`,`logged_at`),
  KEY `idx_msl_event` (`disaster_id`,`logged_at`),
  KEY `idx_msl_household` (`household_id`,`logged_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `member_statuses`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `member_statuses`;
CREATE TABLE `member_statuses` (
  `status_id` int NOT NULL AUTO_INCREMENT,
  `status_key` varchar(50) NOT NULL,
  `status_label` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `severity_rank` tinyint unsigned NOT NULL DEFAULT '0',
  `requires_rescue` tinyint(1) NOT NULL DEFAULT '0',
  `is_terminal` tinyint(1) NOT NULL DEFAULT '0',
  `color_hex` varchar(7) DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '999',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `counts_as_death` tinyint(1) DEFAULT NULL,
  `counts_as_injury` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_member_status_key` (`status_key`),
  KEY `idx_member_status_severity` (`severity_rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `member_vulnerable_groups`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `member_vulnerable_groups`;
CREATE TABLE `member_vulnerable_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` varchar(255) DEFAULT NULL,
  `vulnerable_group_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mvg_member_group` (`member_id`, `vulnerable_group_id`),
  KEY `idx_mvg_group` (`vulnerable_group_id`),
  CONSTRAINT `fk_mvg_member` FOREIGN KEY (`member_id`) REFERENCES `household_members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mvg_group` FOREIGN KEY (`vulnerable_group_id`) REFERENCES `vulnerable_groups` (`vulnerable_group_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `migrations`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) DEFAULT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `notification_channels`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `notification_channels`;
CREATE TABLE `notification_channels` (
  `channel_id` int NOT NULL,
  `channel_key` varchar(50) DEFAULT NULL,
  `channel_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`channel_id`),
  UNIQUE KEY `uk_notification_channels_key` (`channel_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `notification_logs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `notification_logs`;
CREATE TABLE `notification_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `notification_id` int NOT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `channel_id` int NOT NULL,
  `status_id` int NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `retry_count` int DEFAULT NULL,
  `external_message_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_notif_logs_notif` (`notification_id`),
  KEY `idx_notif_logs_household` (`household_id`, `status_id`),
  KEY `idx_notif_logs_channel` (`channel_id`),
  KEY `idx_notif_logs_status` (`status_id`),
  CONSTRAINT `fk_notif_logs_notif` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notif_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notif_logs_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_notif_logs_channel` FOREIGN KEY (`channel_id`) REFERENCES `notification_channels` (`channel_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_notif_logs_status` FOREIGN KEY (`status_id`) REFERENCES `notification_statuses` (`status_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `notification_recipients`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `notification_recipients`;
CREATE TABLE `notification_recipients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notification_id` int NOT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_notif_recip_notif_hh` (`notification_id`, `household_id`),
  KEY `idx_notif_recip_household` (`household_id`),
  CONSTRAINT `fk_notif_recip_notif` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notif_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notif_recip_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `notification_statuses`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `notification_statuses`;
CREATE TABLE `notification_statuses` (
  `status_id` int NOT NULL,
  `status_key` varchar(50) DEFAULT NULL,
  `status_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_notification_statuses_key` (`status_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `notifications`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notif_id` int NOT NULL AUTO_INCREMENT,
  `message` text,
  `sent_by` varchar(255) DEFAULT NULL,
  `evacuation_event_id` varchar(255) DEFAULT NULL,
  `evacuation_center_id` varchar(255) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT NULL,
  `recurrence_type_id` int DEFAULT NULL,
  `recurrence_end_at` datetime DEFAULT NULL,
  `last_sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `channel` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `target_filter` varchar(50) DEFAULT NULL,
  `source_key` varchar(120) DEFAULT NULL,
  `onesignal_id` varchar(36) DEFAULT NULL,
  `onesignal_ids_json` json DEFAULT NULL,
  `feed_payload_json` json DEFAULT NULL,
  `advisory_source` varchar(16) DEFAULT NULL,
  `advisory_fingerprint` varchar(64) DEFAULT NULL,
  `advisory_title` varchar(255) DEFAULT NULL,
  `advisory_source_url` text,
  PRIMARY KEY (`notif_id`),
  UNIQUE KEY `resq_advisory_fingerprint_unique` (`advisory_fingerprint`),
  UNIQUE KEY `resq_notification_source_unique` (`source_key`),
  KEY `idx_notifications_sched` (`status`, `scheduled_at`),
  KEY `idx_notifications_event` (`evacuation_event_id`, `created_at`),
  KEY `idx_notifications_center` (`evacuation_center_id`, `created_at`),
  KEY `idx_notifications_sent_by` (`sent_by`),
  KEY `idx_notifications_recurrence` (`recurrence_type_id`),
  CONSTRAINT `fk_notifications_sender` FOREIGN KEY (`sent_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_notifications_event` FOREIGN KEY (`evacuation_event_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_notifications_center` FOREIGN KEY (`evacuation_center_id`) REFERENCES `evacuation_centers` (`evacuation_center_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_notifications_recurrence` FOREIGN KEY (`recurrence_type_id`) REFERENCES `recurrence_types` (`type_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `occupations`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `occupations`;
CREATE TABLE `occupations` (
  `occuaption_id` int NOT NULL,
  `occupation_id` int GENERATED ALWAYS AS (`occuaption_id`) VIRTUAL COMMENT 'Clean alias for misspelled occuaption_id',
  `occupation_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`occuaption_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `offline_packs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `offline_packs`;
CREATE TABLE `offline_packs` (
  `version` varchar(64) NOT NULL,
  `bbox` json NOT NULL,
  `style_url` varchar(500) NOT NULL,
  `size_bytes` bigint unsigned NOT NULL,
  `checksum` char(64) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pack_format` varchar(32) NOT NULL DEFAULT 'mbtiles_zip',
  PRIMARY KEY (`version`),
  KEY `offline_packs_active_version_index` (`is_active`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `operational_sequences`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `operational_sequences`;
CREATE TABLE `operational_sequences` (
  `sequence_key` varchar(255) NOT NULL,
  `next_value` bigint unsigned NOT NULL,
  `sequence_prefix` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`sequence_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `password_reset_requests`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `password_reset_requests`;
CREATE TABLE `password_reset_requests` (
  `request_id` char(36) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `login_value` varchar(255) DEFAULT NULL,
  `verification_method` enum('previous_password','security_questions','sms_otp') NOT NULL,
  `token_hash` varchar(255) DEFAULT NULL,
  `failed_attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` timestamp NOT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `consumed_at` timestamp NULL DEFAULT NULL,
  `requested_ip` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `otp_subject_key` char(64) DEFAULT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `code_hash` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  UNIQUE KEY `password_reset_requests_token_hash_unique` (`token_hash`),
  UNIQUE KEY `resq_otp_subject_unique` (`otp_subject_key`),
  KEY `password_reset_requests_user_id_consumed_at_index` (`user_id`,`consumed_at`),
  KEY `password_reset_requests_expires_at_consumed_at_index` (`expires_at`,`consumed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `password_reset_tokens`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `personal_access_tokens`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_id` varchar(255) DEFAULT NULL,
  `tokenable_type` varchar(255) DEFAULT NULL,
  `name` text NOT NULL,
  `token` varchar(64) DEFAULT NULL,
  `abilities` text,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `property_damage_levels`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `property_damage_levels`;
CREATE TABLE `property_damage_levels` (
  `damage_key` varchar(80) NOT NULL,
  `label` varchar(255) NOT NULL,
  `sort_order` int unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`damage_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `provinces`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `provinces`;
CREATE TABLE `provinces` (
  `province_id` int NOT NULL,
  `province_code` varchar(20) DEFAULT NULL,
  `province_name` varchar(100) DEFAULT NULL,
  `region_id` int NOT NULL,
  PRIMARY KEY (`province_id`),
  UNIQUE KEY `uk_provinces_code` (`province_code`),
  KEY `idx_provinces_region` (`region_id`),
  CONSTRAINT `fk_provinces_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`region_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `puroks`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `puroks`;
CREATE TABLE `puroks` (
  `purok_id` int NOT NULL,
  `purok_name` varchar(100) DEFAULT NULL,
  `sitio_id` int NOT NULL,
  PRIMARY KEY (`purok_id`),
  KEY `idx_puroks_sitio` (`sitio_id`),
  CONSTRAINT `fk_puroks_sitio` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`sitio_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `realtime_outbox`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `realtime_outbox`;
CREATE TABLE `realtime_outbox` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `topics` text NOT NULL,
  `available_at` int unsigned NOT NULL,
  `claim_token` char(36) DEFAULT NULL,
  `claimed_until` int unsigned DEFAULT NULL,
  `attempts` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `delivered_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `realtime_outbox_pending` (`delivered_at`,`available_at`,`id`),
  KEY `realtime_outbox_claim_token_index` (`claim_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `recurrence_types`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `recurrence_types`;
CREATE TABLE `recurrence_types` (
  `type_id` int NOT NULL AUTO_INCREMENT,
  `type_key` varchar(50) DEFAULT NULL,
  `type_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_recurrence_types_key` (`type_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `regions`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `regions`;
CREATE TABLE `regions` (
  `region_id` int NOT NULL,
  `region_code` varchar(20) DEFAULT NULL,
  `region_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`region_id`),
  UNIQUE KEY `uk_regions_code` (`region_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `relationships`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `relationships`;
CREATE TABLE `relationships` (
  `relationship_id` int NOT NULL AUTO_INCREMENT,
  `relationship_key` varchar(50) DEFAULT NULL,
  `relationship_label` varchar(100) DEFAULT NULL,
  `is_head` tinyint(1) NOT NULL DEFAULT '0',
  `is_immediate_family` tinyint(1) NOT NULL DEFAULT '0',
  `can_report_for_household` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` smallint unsigned NOT NULL DEFAULT '999',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`relationship_id`),
  UNIQUE KEY `uk_relationship_key` (`relationship_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `remembered_devices`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `remembered_devices`;
CREATE TABLE `remembered_devices` (
  `remembered_device_id` char(36) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `device_name` varchar(150) DEFAULT NULL,
  `platform` varchar(30) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`remembered_device_id`),
  UNIQUE KEY `remembered_devices_token_hash_unique` (`token_hash`),
  KEY `remembered_devices_user_id_revoked_at_index` (`user_id`,`revoked_at`),
  KEY `remembered_devices_expires_at_revoked_at_index` (`expires_at`,`revoked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `report_idempotency`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `report_idempotency`;
CREATE TABLE `report_idempotency` (
  `scope` char(64) NOT NULL,
  `request_hash` char(64) NOT NULL,
  `response_status` smallint unsigned DEFAULT NULL,
  `response_body` longtext,
  `content_type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`scope`),
  KEY `report_idempotency_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `request_validations`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `request_validations`;
CREATE TABLE `request_validations` (
  `validation_id` int NOT NULL AUTO_INCREMENT,
  `request_id` varchar(255) NOT NULL,
  `validation_status` varchar(30) NOT NULL,
  `validator_user_id` varchar(255) DEFAULT NULL,
  `validation_notes` text,
  `missing_information` text,
  `duplicate_request_id` varchar(255) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`validation_id`),
  KEY `idx_req_validations_request` (`request_id`),
  KEY `idx_req_validations_validator` (`validator_user_id`),
  CONSTRAINT `fk_req_validations_request` FOREIGN KEY (`request_id`) REFERENCES `resource_requests` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_req_validations_validator` FOREIGN KEY (`validator_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `rescue_priority_settings`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `rescue_priority_settings`;
CREATE TABLE `rescue_priority_settings` (
  `version` bigint unsigned NOT NULL AUTO_INCREMENT,
  `impact_weight` tinyint unsigned NOT NULL,
  `vulnerability_weight` tinyint unsigned NOT NULL,
  `unreported_weight` tinyint unsigned NOT NULL,
  `no_contact_weight` tinyint unsigned NOT NULL,
  `updated_by_user_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `rescue_teams`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `rescue_teams`;
CREATE TABLE `rescue_teams` (
  `team_id` int NOT NULL,
  `team_code` varchar(50) DEFAULT NULL,
  `team_name` varchar(100) DEFAULT NULL,
  `team_type` varchar(80) DEFAULT NULL,
  `assigned_purok_id` int DEFAULT NULL,
  `leader_responder_id` int DEFAULT NULL,
  `duty_status` varchar(30) NOT NULL DEFAULT 'available',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `base_name` varchar(120) DEFAULT NULL,
  `base_latitude` decimal(10,7) DEFAULT NULL,
  `base_longitude` decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (`team_id`),
  UNIQUE KEY `uk_rescue_teams_code` (`team_code`),
  KEY `idx_rescue_teams_purok` (`assigned_purok_id`),
  KEY `idx_rescue_teams_leader` (`leader_responder_id`),
  CONSTRAINT `fk_rescue_teams_purok` FOREIGN KEY (`assigned_purok_id`) REFERENCES `puroks` (`purok_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `resource_request_status`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `resource_request_status`;
CREATE TABLE `resource_request_status` (
  `status_id` int NOT NULL,
  `status_key` varchar(50) DEFAULT NULL,
  `status_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_resource_request_status_key` (`status_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `resource_requests`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `resource_requests`;
CREATE TABLE `resource_requests` (
  `request_id` varchar(255) NOT NULL,
  `request_source` varchar(80) DEFAULT NULL,
  `source_reference` varchar(120) DEFAULT NULL,
  `request_category` varchar(50) DEFAULT NULL,
  `evacuation_center_id` varchar(255) DEFAULT NULL,
  `requested_by` varchar(255) DEFAULT NULL,
  `handled_by` varchar(255) DEFAULT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `item_name` varchar(150) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `description` text,
  `status_id` int DEFAULT NULL,
  `validation_status` varchar(30) NOT NULL DEFAULT 'needs_validation',
  `validation_notes` text,
  `validated_by_user_id` varchar(255) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `released_for_tracking_at` datetime DEFAULT NULL,
  `tracking_reference` varchar(120) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `idx_resource_req_status_created` (`status_id`,`created_at`),
  KEY `idx_rr_center_created` (`evacuation_center_id`, `created_at`),
  KEY `idx_rr_requested_by` (`requested_by`),
  KEY `idx_rr_handled_by` (`handled_by`),
  KEY `idx_rr_validated_by` (`validated_by_user_id`),
  CONSTRAINT `fk_rr_center` FOREIGN KEY (`evacuation_center_id`) REFERENCES `evacuation_centers` (`evacuation_center_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rr_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rr_handled_by` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rr_status` FOREIGN KEY (`status_id`) REFERENCES `resource_request_status` (`status_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rr_validated_by` FOREIGN KEY (`validated_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `responder_assignments`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `responder_assignments`;
CREATE TABLE `responder_assignments` (
  `assignment_id` int NOT NULL,
  `assignment_code` varchar(80) DEFAULT NULL,
  `responder_id` int NOT NULL,
  `team_id` int DEFAULT NULL,
  `disaster_id` varchar(255) DEFAULT NULL,
  `affected_area_id` int DEFAULT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `status_log_id` int DEFAULT NULL,
  `assigned_area` varchar(150) DEFAULT NULL,
  `route_notes` text,
  `priority_level` varchar(30) DEFAULT NULL,
  `dispatch_notes` text,
  `created_by_admin_id` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `assigned_at` datetime NOT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `en_route_at` datetime DEFAULT NULL,
  `arrived_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `outcome_notes` text,
  `updated_at` datetime DEFAULT NULL,
  `responder_count` smallint unsigned NOT NULL DEFAULT '1',
  `dispatch_type` varchar(30) NOT NULL DEFAULT 'rescue',
  PRIMARY KEY (`assignment_id`),
  KEY `responder_assignments_dispatch_type_index` (`dispatch_type`),
  UNIQUE KEY `uk_responder_assignments_code` (`assignment_code`),
  KEY `idx_ra_disaster_status` (`disaster_id`, `status`),
  KEY `idx_ra_responder` (`responder_id`, `status`),
  KEY `idx_ra_team` (`team_id`, `status`),
  KEY `idx_ra_household` (`household_id`),
  KEY `idx_ra_status` (`status`),
  CONSTRAINT `fk_ra_responder` FOREIGN KEY (`responder_id`) REFERENCES `responders` (`responder_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ra_team` FOREIGN KEY (`team_id`) REFERENCES `rescue_teams` (`team_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ra_disaster` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ra_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ra_status` FOREIGN KEY (`status`) REFERENCES `dispatch_statuses` (`status_key`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `responder_check_ins`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `responder_check_ins`;
CREATE TABLE `responder_check_ins` (
  `check_in_id` int NOT NULL AUTO_INCREMENT,
  `responder_id` int NOT NULL,
  `disaster_id` varchar(255) DEFAULT NULL,
  `team_id` int DEFAULT NULL,
  `affected_area_id` int DEFAULT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `member_id` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `check_in_method` varchar(10) DEFAULT NULL,
  `status_id` int DEFAULT NULL,
  `notes` text,
  `checked_in_at` datetime NOT NULL,
  `checked_out_at` datetime DEFAULT NULL,
  `verified_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`check_in_id`),
  KEY `idx_rci_disaster_responder` (`disaster_id`, `responder_id`),
  KEY `idx_rci_household` (`household_id`),
  KEY `idx_rci_team` (`team_id`),
  CONSTRAINT `fk_rci_responder` FOREIGN KEY (`responder_id`) REFERENCES `responders` (`responder_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rci_disaster` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rci_team` FOREIGN KEY (`team_id`) REFERENCES `rescue_teams` (`team_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rci_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `responder_communication_logs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `responder_communication_logs`;
CREATE TABLE `responder_communication_logs` (
  `communication_id` int NOT NULL AUTO_INCREMENT,
  `responder_id` int NOT NULL,
  `team_id` int DEFAULT NULL,
  `team_name` varchar(100) DEFAULT NULL,
  `disaster_id` varchar(255) DEFAULT NULL,
  `message` text,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`communication_id`),
  KEY `idx_rcl_disaster_time` (`disaster_id`, `timestamp`),
  KEY `idx_rcl_team_time` (`team_id`, `timestamp`),
  KEY `idx_rcl_responder_time` (`responder_id`, `timestamp`),
  CONSTRAINT `fk_rcl_responder` FOREIGN KEY (`responder_id`) REFERENCES `responders` (`responder_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rcl_team` FOREIGN KEY (`team_id`) REFERENCES `rescue_teams` (`team_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rcl_disaster` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `responder_field_report_details`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `responder_field_report_details`;
CREATE TABLE `responder_field_report_details` (
  `detail_id` int NOT NULL AUTO_INCREMENT,
  `report_id` int NOT NULL,
  `category_id` int NOT NULL,
  `value` int DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`detail_id`),
  KEY `idx_rfrd_report` (`report_id`),
  KEY `idx_rfrd_category` (`category_id`),
  CONSTRAINT `fk_rfrd_report` FOREIGN KEY (`report_id`) REFERENCES `responder_field_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rfrd_category` FOREIGN KEY (`category_id`) REFERENCES `field_report_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `responder_field_report_members`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `responder_field_report_members`;
CREATE TABLE `responder_field_report_members` (
  `detail_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` int NOT NULL,
  `household_id` varchar(255) NOT NULL,
  `disaster_id` varchar(255) NOT NULL,
  `member_id` varchar(255) NOT NULL,
  `status_id` int DEFAULT NULL,
  `status_key` varchar(50) NOT NULL,
  `assessed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`detail_id`),
  UNIQUE KEY `rf_report_member_unique` (`report_id`,`member_id`),
  KEY `rf_member_event_household_idx` (`disaster_id`,`household_id`),
  KEY `idx_rfrm_member` (`member_id`),
  KEY `idx_rfrm_status` (`status_id`),
  CONSTRAINT `fk_rfrm_report` FOREIGN KEY (`report_id`) REFERENCES `responder_field_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rfrm_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rfrm_disaster` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rfrm_member` FOREIGN KEY (`member_id`) REFERENCES `household_members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rfrm_status` FOREIGN KEY (`status_id`) REFERENCES `member_statuses` (`status_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `responder_field_reports`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `responder_field_reports`;
CREATE TABLE `responder_field_reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `responder_id` int NOT NULL,
  `disaster_id` varchar(255) DEFAULT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL,
  `assignment_id` int DEFAULT NULL,
  `submitted_by_user_id` varchar(255) DEFAULT NULL,
  `status_log_id` int DEFAULT NULL,
  `incident_type` varchar(80) DEFAULT NULL,
  `incident_type_other` varchar(255) DEFAULT NULL,
  `property_damage` varchar(80) DEFAULT NULL,
  `death_count` int unsigned DEFAULT '0',
  `injured_count` int unsigned DEFAULT '0',
  `idempotency_key` varchar(100) DEFAULT NULL,
  `payload_hash` varchar(64) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `uk_rfr_idempotency` (`idempotency_key`),
  KEY `idx_rfr_disaster_household` (`disaster_id`, `household_id`),
  KEY `idx_rfr_responder` (`responder_id`, `created_at`),
  KEY `idx_rfr_assignment` (`assignment_id`),
  CONSTRAINT `fk_rfr_responder` FOREIGN KEY (`responder_id`) REFERENCES `responders` (`responder_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rfr_disaster` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rfr_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rfr_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `responder_assignments` (`assignment_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `responder_location_logs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `responder_location_logs`;
CREATE TABLE `responder_location_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `responder_id` int NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `battery_level` int DEFAULT NULL,
  `signal_strength` int DEFAULT NULL,
  `logged_at` datetime NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_rll_responder_time` (`responder_id`, `logged_at`),
  CONSTRAINT `fk_rll_responder` FOREIGN KEY (`responder_id`) REFERENCES `responders` (`responder_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `responder_routes`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `responder_routes`;
CREATE TABLE `responder_routes` (
  `route_id` int NOT NULL AUTO_INCREMENT,
  `assignment_id` int NOT NULL,
  `route_name` varchar(150) DEFAULT NULL,
  `route_status` varchar(30) NOT NULL DEFAULT 'planned',
  `start_latitude` decimal(10,7) DEFAULT NULL,
  `start_longitude` decimal(10,7) DEFAULT NULL,
  `end_latitude` decimal(10,7) DEFAULT NULL,
  `end_longitude` decimal(10,7) DEFAULT NULL,
  `estimated_distance_km` decimal(8,2) DEFAULT NULL,
  `estimated_duration_min` int DEFAULT NULL,
  `route_polyline` mediumtext,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `responder_id` int DEFAULT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `navigation_steps` json DEFAULT NULL,
  PRIMARY KEY (`route_id`),
  KEY `idx_rr_assignment` (`assignment_id`),
  KEY `idx_rr_responder` (`responder_id`),
  KEY `idx_rr_household` (`household_id`),
  CONSTRAINT `fk_rr_route_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `responder_assignments` (`assignment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rr_route_responder` FOREIGN KEY (`responder_id`) REFERENCES `responders` (`responder_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rr_route_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `responders`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `responders`;
CREATE TABLE `responders` (
  `responder_id` int NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `responder_code` varchar(80) DEFAULT NULL,
  `created_by_admin_id` varchar(255) DEFAULT NULL,
  `team_id` int DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_number` varchar(30) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `address` text,
  `skills` text,
  `training_notes` text,
  `certification_reference` varchar(150) DEFAULT NULL,
  `equipment_notes` text,
  `is_validated` tinyint(1) DEFAULT NULL,
  `is_deployed` tinyint(1) DEFAULT NULL,
  `duty_status` varchar(30) NOT NULL DEFAULT 'off_duty',
  `last_active_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`responder_id`),
  UNIQUE KEY `uk_responders_code` (`responder_code`),
  KEY `idx_responders_user` (`user_id`),
  KEY `idx_responders_team` (`team_id`, `duty_status`),
  KEY `idx_responders_username` (`username`),
  CONSTRAINT `fk_responders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_responders_team` FOREIGN KEY (`team_id`) REFERENCES `rescue_teams` (`team_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `roles`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `role_id` int NOT NULL,
  `role_key` varchar(50) DEFAULT NULL,
  `role_name` varchar(100) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `base_role_key` varchar(80) DEFAULT NULL,
  `web_modules` json DEFAULT NULL,
  `can_manage_personnel` tinyint(1) DEFAULT NULL,
  `can_approve_requests` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uk_roles_role_key` (`role_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `route_coordinates`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `route_coordinates`;
CREATE TABLE `route_coordinates` (
  `coordinate_id` int NOT NULL AUTO_INCREMENT,
  `route_id` int NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `sequence_order` int NOT NULL,
  `recorded_at` datetime DEFAULT NULL,
  `accuracy_m` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`coordinate_id`),
  KEY `idx_route_coords_route_seq` (`route_id`, `sequence_order`),
  CONSTRAINT `fk_route_coords_route` FOREIGN KEY (`route_id`) REFERENCES `responder_routes` (`route_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `sessions`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `severity_levels`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `severity_levels`;
CREATE TABLE `severity_levels` (
  `severity_id` int NOT NULL,
  `severity_key` varchar(50) DEFAULT NULL,
  `severity_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`severity_id`),
  UNIQUE KEY `uk_severity_levels_key` (`severity_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `sitios`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `sitios`;
CREATE TABLE `sitios` (
  `sitio_id` int NOT NULL,
  `sitio_name` varchar(100) DEFAULT NULL,
  `barangay_id` int NOT NULL,
  PRIMARY KEY (`sitio_id`),
  KEY `idx_sitios_barangay` (`barangay_id`),
  CONSTRAINT `fk_sitios_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `situation_reports`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `situation_reports`;
CREATE TABLE `situation_reports` (
  `sit_rep_id` int NOT NULL AUTO_INCREMENT,
  `report_number` varchar(80) DEFAULT NULL,
  `disaster_id` varchar(255) DEFAULT NULL,
  `created_by_admin_id` varchar(255) DEFAULT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `summary` text,
  `report_status` varchar(30) NOT NULL DEFAULT 'draft',
  `reviewed_by_user_id` varchar(255) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `escalated_to` varchar(150) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT NULL,
  `generated_at` datetime NOT NULL,
  `archived_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sit_rep_id`),
  UNIQUE KEY `uk_sitrep_number` (`report_number`),
  KEY `idx_sitrep_disaster` (`disaster_id`, `generated_at`),
  KEY `idx_sitrep_household` (`household_id`),
  CONSTRAINT `fk_sitrep_disaster` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sitrep_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `status_reminders`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `status_reminders`;
CREATE TABLE `status_reminders` (
  `reminder_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) NOT NULL,
  `household_id` varchar(255) NOT NULL,
  `member_id` varchar(255) NOT NULL,
  `attempt` tinyint unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `scheduled_at` timestamp NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `stopped_at` timestamp NULL DEFAULT NULL,
  `member_delivery` varchar(30) DEFAULT NULL,
  `household_delivery` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`reminder_id`),
  UNIQUE KEY `status_reminders_event_member_attempt_unique` (`event_id`,`member_id`,`attempt`),
  KEY `status_reminders_due_index` (`status`,`scheduled_at`),
  KEY `status_reminders_household_index` (`event_id`,`household_id`,`status`),
  CONSTRAINT `fk_status_reminders_event` FOREIGN KEY (`event_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_status_reminders_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_status_reminders_member` FOREIGN KEY (`member_id`) REFERENCES `household_members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `sync_events`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `sync_events`;
CREATE TABLE `sync_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_event_id` char(36) NOT NULL,
  `device_id` bigint NOT NULL,
  `type` varchar(40) NOT NULL,
  `status` enum('received','processed','failed') NOT NULL DEFAULT 'received',
  `error_message` varchar(255) DEFAULT NULL,
  `received_at` timestamp NOT NULL,
  `result_json` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sync_events_client_event_id_unique` (`client_event_id`),
  KEY `sync_events_device_received_at_index` (`device_id`,`received_at`),
  CONSTRAINT `sync_events_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `device_tokens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `trip_points`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `trip_points`;
CREATE TABLE `trip_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` bigint unsigned NOT NULL,
  `lat` decimal(10,7) NOT NULL,
  `lng` decimal(10,7) NOT NULL,
  `accuracy` decimal(8,2) DEFAULT NULL,
  `speed` decimal(8,3) DEFAULT NULL,
  `heading` decimal(6,2) DEFAULT NULL,
  `recorded_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_points_trip_recorded_at_index` (`trip_id`,`recorded_at`),
  CONSTRAINT `trip_points_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `dispatch_trips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `trusted_households`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `trusted_households`;
CREATE TABLE `trusted_households` (
  `connection_id` varchar(80) NOT NULL,
  `requesting_household_id` varchar(255) NOT NULL,
  `trusted_household_id` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `validation_status` varchar(30) NOT NULL DEFAULT 'pending',
  `member_relationships` json DEFAULT NULL,
  `created_by_user_id` varchar(255) DEFAULT NULL,
  `validated_by_user_id` varchar(255) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`connection_id`),
  KEY `idx_trusted_households_requesting` (`requesting_household_id`),
  KEY `idx_trusted_households_trusted` (`trusted_household_id`),
  KEY `idx_trusted_households_status` (`validation_status`),
  CONSTRAINT `fk_trusted_hh_requesting` FOREIGN KEY (`requesting_household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_trusted_hh_trusted` FOREIGN KEY (`trusted_household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `unit_allocations`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `unit_allocations`;
CREATE TABLE `unit_allocations` (
  `allocation_id` int NOT NULL AUTO_INCREMENT,
  `evacuation_id` int NOT NULL,
  `unit_id` int NOT NULL,
  `assigned_by` varchar(255) DEFAULT NULL,
  `selected_by_resident` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`allocation_id`),
  KEY `idx_unit_alloc_evac` (`evacuation_id`),
  KEY `idx_unit_alloc_unit` (`unit_id`),
  KEY `idx_unit_alloc_assigned_by` (`assigned_by`),
  CONSTRAINT `fk_unit_alloc_evac` FOREIGN KEY (`evacuation_id`) REFERENCES `evacuation_records` (`evacuation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_unit_alloc_unit` FOREIGN KEY (`unit_id`) REFERENCES `accommodation_units` (`unit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_unit_alloc_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `user_notifications`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `user_notifications`;
CREATE TABLE `user_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'general',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` json DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_notifications_user_id` (`user_id`),
  KEY `idx_user_notifications_read_at` (`read_at`),
  CONSTRAINT `fk_user_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `users`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `assigned_center_id` varchar(255) DEFAULT NULL,
  `household_id` varchar(255) DEFAULT NULL,
  `member_id` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `temp_password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `security_question_1` varchar(100) DEFAULT NULL,
  `security_answer_1` varchar(255) DEFAULT NULL,
  `security_question_2` varchar(100) DEFAULT NULL,
  `security_answer_2` varchar(255) DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uk_users_member` (`member_id`),
  KEY `idx_users_household` (`household_id`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_contact` (`contact_number`),
  KEY `idx_users_username` (`username`),
  KEY `idx_users_assigned_center` (`assigned_center_id`),
  KEY `idx_users_deleted` (`deleted_at`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_assigned_center` FOREIGN KEY (`assigned_center_id`) REFERENCES `evacuation_centers` (`evacuation_center_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_member` FOREIGN KEY (`member_id`) REFERENCES `household_members` (`member_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `vulnerable_groups`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `vulnerable_groups`;
CREATE TABLE `vulnerable_groups` (
  `vulnerable_group_id` int NOT NULL AUTO_INCREMENT,
  `vulnerable_group_key` varchar(20) DEFAULT NULL,
  `vulnerable_group_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`vulnerable_group_id`),
  UNIQUE KEY `uk_vulnerable_group_key` (`vulnerable_group_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `weather_logs`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `weather_logs`;
CREATE TABLE `weather_logs` (
  `weather_log_id` int NOT NULL AUTO_INCREMENT,
  `disaster_id` varchar(255) DEFAULT NULL,
  `source_name` varchar(80) NOT NULL,
  `source_url` varchar(255) DEFAULT NULL,
  `condition_name` varchar(80) DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `rainfall_mm` decimal(8,2) DEFAULT NULL,
  `wind_speed` decimal(8,2) DEFAULT NULL,
  `wind_direction` varchar(50) DEFAULT NULL,
  `humidity` int DEFAULT NULL,
  `advisory_title` varchar(255) DEFAULT NULL,
  `advisory_text` text,
  `raw_payload` json DEFAULT NULL,
  `observed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`weather_log_id`),
  KEY `weather_logs_latest_idx` (`observed_at`,`created_at`),
  KEY `weather_logs_event_latest_idx` (`disaster_id`,`observed_at`,`created_at`),
  CONSTRAINT `fk_weather_logs_event` FOREIGN KEY (`disaster_id`) REFERENCES `disaster_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `web_push_deliveries`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `web_push_deliveries`;
CREATE TABLE `web_push_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint unsigned NOT NULL,
  `notice_hash` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `web_push_deliveries_subscription_id_notice_hash_unique` (`subscription_id`,`notice_hash`),
  CONSTRAINT `fk_web_push_deliveries_sub` FOREIGN KEY (`subscription_id`) REFERENCES `web_push_subscriptions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `web_push_subscriptions`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `web_push_subscriptions`;
CREATE TABLE `web_push_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL,
  `token_id` bigint unsigned DEFAULT NULL,
  `endpoint_hash` varchar(64) NOT NULL,
  `subscription` text NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `push_after` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `web_push_subscriptions_endpoint_hash_unique` (`endpoint_hash`),
  KEY `web_push_subscriptions_user_id_index` (`user_id`),
  KEY `web_push_subscriptions_token_id_index` (`token_id`),
  CONSTRAINT `fk_web_push_subs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- Table: `zipcodes`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `zipcodes`;
CREATE TABLE `zipcodes` (
  `zipcode_id` int NOT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `city_id` int NOT NULL,
  PRIMARY KEY (`zipcode_id`),
  KEY `idx_zipcodes_city` (`city_id`),
  CONSTRAINT `fk_zipcodes_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================================
-- ANALYTICAL & MONITORING VIEWS (SQL SECURITY INVOKER)
-- ============================================================================

DROP VIEW IF EXISTS `v_member_status_monitor`;
CREATE SQL SECURITY INVOKER VIEW `v_member_status_monitor` AS
SELECT
  `mds`.`member_status_id` AS `member_status_id`,
  `mds`.`disaster_id` AS `disaster_id`,
  `de`.`name` AS `event_name`,
  `de`.`started_at` AS `event_started_at`,
  `de`.`ended_at` AS `event_ended_at`,
  (`de`.`ended_at` IS NULL) AS `event_is_active`,
  `h`.`household_id` AS `household_id`,
  `h`.`household_code` AS `household_code`,
  `h`.`household_number` AS `household_number`,
  `h`.`household_name` AS `household_name`,
  `h`.`contact_number` AS `household_contact`,
  `hm`.`member_id` AS `member_id`,
  TRIM(CONCAT_WS(' ', `hm`.`first_name`, `hm`.`middle_name`, `hm`.`last_name`)) AS `member_name`,
  TIMESTAMPDIFF(YEAR, `hm`.`birth_date`, CURDATE()) AS `age`,
  `g`.`gender_label` AS `gender`,
  `r`.`relationship_label` AS `relationship_label`,
  `hm`.`is_household_head` AS `is_household_head`,
  `hm`.`is_pwd` AS `is_pwd`,
  `hm`.`is_senior` AS `is_senior`,
  `hm`.`is_pregnant` AS `is_pregnant`,
  `u`.`user_id` AS `account_user_id`,
  `u`.`username` AS `account_username`,
  `u`.`email` AS `account_email`,
  (`u`.`user_id` IS NOT NULL) AS `has_account`,
  `mds`.`status_id` AS `status_id`,
  `ms`.`status_key` AS `status_key`,
  `ms`.`status_label` AS `status_label`,
  `ms`.`color_hex` AS `status_color`,
  `mds`.`severity_rank` AS `severity_rank`,
  `mds`.`needs_rescue` AS `needs_rescue`,
  `mds`.`priority_level` AS `priority_level`,
  `prev`.`status_label` AS `previous_status_label`,
  `mds`.`report_source` AS `report_source`,
  `mds`.`reported_by_user_id` AS `reported_by_user_id`,
  `mds`.`reported_by_member_id` AS `reported_by_member_id`,
  TRIM(CONCAT_WS(' ', `rep`.`first_name`, `rep`.`last_name`)) AS `reported_by_name`,
  `mds`.`latitude` AS `latitude`,
  `mds`.`longitude` AS `longitude`,
  `mds`.`location_label` AS `location_label`,
  `mds`.`location_accuracy_m` AS `location_accuracy_m`,
  `mds`.`battery_level` AS `battery_level`,
  `mds`.`signal_strength` AS `signal_strength`,
  `mds`.`evacuation_center_id` AS `evacuation_center_id`,
  `mds`.`notes` AS `notes`,
  `mds`.`is_verified` AS `is_verified`,
  `mds`.`verified_at` AS `verified_at`,
  `mds`.`reported_at` AS `reported_at`,
  `mds`.`updated_at` AS `updated_at`,
  TIMESTAMPDIFF(MINUTE, `mds`.`reported_at`, NOW()) AS `minutes_since_report`
FROM `member_disaster_statuses` `mds`
JOIN `household_members` `hm` ON (`hm`.`member_id` = `mds`.`member_id`)
JOIN `households` `h` ON (`h`.`household_id` = `mds`.`household_id`)
JOIN `member_statuses` `ms` ON (`ms`.`status_id` = `mds`.`status_id`)
JOIN `disaster_events` `de` ON (`de`.`event_id` = `mds`.`disaster_id`)
LEFT JOIN `member_statuses` `prev` ON (`prev`.`status_id` = `mds`.`previous_status_id`)
LEFT JOIN `relationships` `r` ON (`r`.`relationship_id` = `hm`.`relationship_id`)
LEFT JOIN `genders` `g` ON (`g`.`gender_id` = `hm`.`gender_id`)
LEFT JOIN `users` `u` ON (`u`.`member_id` = `hm`.`member_id` AND `u`.`deleted_at` IS NULL)
LEFT JOIN `household_members` `rep` ON (`rep`.`member_id` = `mds`.`reported_by_member_id`)
WHERE `hm`.`deleted_at` IS NULL
  AND `h`.`deleted_at` IS NULL;

DROP VIEW IF EXISTS `v_household_status_board`;
CREATE SQL SECURITY INVOKER VIEW `v_household_status_board` AS
SELECT
  `mds`.`disaster_id` AS `disaster_id`,
  `h`.`household_id` AS `household_id`,
  `h`.`household_code` AS `household_code`,
  `h`.`household_name` AS `household_name`,
  `hs`.`status_id` AS `household_status_id`,
  `hs`.`status_label` AS `household_status_label`,
  `hs`.`color_hex` AS `household_status_color`,
  COUNT(0) AS `total_members`,
  SUM(`ms`.`status_key` = 'unknown') AS `unreported`,
  SUM(`ms`.`status_key` IN ('safe', 'safe_at_home', 'evacuated')) AS `accounted_safe`,
  SUM(`ms`.`requires_rescue` = 1) AS `needs_rescue_count`,
  SUM(`ms`.`status_key` = 'missing') AS `missing_count`,
  SUM(`ms`.`status_key` = 'injured') AS `injured_count`,
  SUM(`ms`.`status_key` = 'deceased') AS `deceased_count`,
  MAX(`mds`.`severity_rank`) AS `worst_severity`,
  MAX(`mds`.`reported_at`) AS `last_reported_at`,
  MAX(`mds`.`latitude`) AS `latitude`,
  MAX(`mds`.`longitude`) AS `longitude`
FROM `member_disaster_statuses` `mds`
JOIN `household_members` `hm` ON (`hm`.`member_id` = `mds`.`member_id` AND `hm`.`deleted_at` IS NULL)
JOIN `households` `h` ON (`h`.`household_id` = `mds`.`household_id`)
JOIN `member_statuses` `ms` ON (`ms`.`status_id` = `mds`.`status_id`)
LEFT JOIN `household_disasters` `hd` ON (`hd`.`disaster_id` = `mds`.`disaster_id` AND `hd`.`household_id` = `mds`.`household_id`)
LEFT JOIN `household_statuses` `hs` ON (`hs`.`status_id` = `hd`.`current_status_id`)
GROUP BY
  `mds`.`disaster_id`,
  `h`.`household_id`,
  `h`.`household_code`,
  `h`.`household_name`,
  `hs`.`status_id`,
  `hs`.`status_label`,
  `hs`.`color_hex`;

SET FOREIGN_KEY_CHECKS = 1;
