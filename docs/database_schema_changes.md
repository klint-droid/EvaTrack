# Database Schema Remediation & Full Corrected Schema Documentation (`klint`)

**Target Database:** `klint` (MySQL `8.0.44`, `InnoDB`, `utf8mb4` / `utf8mb4_0900_ai_ci`)  
**Generated SQL File:** [corrected_full_schema.sql](file:///c:/CAPSTONE/all/corrected_full_schema.sql) (also mirrored at [EvaTrack/docs/corrected_full_schema.sql](file:///c:/CAPSTONE/EvaTrack/docs/corrected_full_schema.sql))  
**Companion Audit Report:** [database_audit_report.md](file:///c:/CAPSTONE/EvaTrack/docs/database_audit_report.md)  
**Scope:** Complete 111 Base Tables + 2 SQL Views across **EvaTrack**, **ResQ / ResQperation**, and **BIDRCS**

---

## 1. Executive Summary of Schema Changes

The corrected full schema (`corrected_full_schema.sql`) preserves 100% of existing tables, columns, and cross-system compatibility across **EvaTrack**, **ResQ / ResQperation**, and **BIDRCS** while remediating every structural, referential, indexing, and collation defect identified in the Senior DBA Audit (`DB-001` through `DB-014`).

| Category | Original Live Schema | Corrected Schema (`corrected_full_schema.sql`) | Audit Finding(s) |
| :--- | :--- | :--- | :--- |
| **Total Base Tables** | 111 tables | 111 tables (all preserved) | — |
| **Total SQL Views** | 2 views (`DEFINER=root@localhost`) | 2 portable views (`SQL SECURITY INVOKER`) | Section 1.E |
| **Tables with `PRIMARY KEY`** | 84 / 111 (27 missing PKs, 2 using only `UNIQUE`) | **111 / 111 (100%)** | `DB-001` |
| **Integer PKs with `AUTO_INCREMENT`** | Missing on 20 integer PK tables | **Restored on all 20 transactional integer PKs** | `DB-002` |
| **Foreign Key Constraints** | 17 FKs across 7 tables | **112 FKs across 53 tables** | `DB-003` |
| **Character Set & Collation** | Split: 87 `utf8mb4_0900_ai_ci` vs 24 `utf8mb4_unicode_ci` | **100% unified on `utf8mb4_0900_ai_ci`** | `DB-011` |
| **`UNIQUE` Constraints** | Missing on `personal_access_tokens.token` & lookup keys | **Added 23 `UNIQUE` constraints** | `DB-009`, `DB-012` |
| **Secondary & Composite Indexes** | Missing on core joins, filters, and telemetry tables | **Added 115+ targeted B-Tree & composite indexes** | `DB-008`, `DB-010`, `DB-012` |
| **Redundant Duplicate Indexes** | 4 redundant left-prefix single-column indexes | **Removed 4 redundant indexes** | `DB-013` |

---

## 2. Detailed Breakdown of Changes by Category

### 2.1 Character Set & Collation Standardization (`DB-011`)
- **Problem:** 87 tables used `utf8mb4_0900_ai_ci` while 24 tables (`addresses`, `barangays`, `cities`, `provinces`, `regions`, `zipcodes`, `civil_statuses`, `education_levels`, `genders`, `occupations`, `relations`, `religions`, `vulnerable_groups`, `csv_uploads`, `data_sources`, `import_logs`, `households`, `household_members`, `member_vulnerable_groups`, `evacuation_centers`, `evacuation_records`, `evacuated_members`, `disaster_types`, `severity_levels`) used `utf8mb4_unicode_ci`. Joining string keys across these collations prevented index usage or raised `Illegal mix of collations` errors.
- **Change Made:** Every table definition and character column (`VARCHAR`, `CHAR`, `TEXT`, `MEDIUMTEXT`, `LONGTEXT`, `ENUM`) is standardized to `CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci`.

---

### 2.2 Primary Keys Added to All 29 Deficient Tables (`DB-001`)
InnoDB requires an explicit `PRIMARY KEY` to avoid hidden 6-byte row-ID contention, enable row-based replication, and allow foreign key references.

#### A. 27 Tables that Previously Had No `PRIMARY KEY`
| Table Name | Primary Key Added | Notes |
| :--- | :--- | :--- |
| `_archive_orphan_household_disasters` | `PRIMARY KEY (household_disaster_id)` | Preserves archive row identity |
| `_archive_orphan_household_status_logs` | `PRIMARY KEY (status_log_id)` | Preserves archive row identity |
| `affected_areas` | `PRIMARY KEY (affected_area_id)` | Also enabled `AUTO_INCREMENT` |
| `analytics_job_status` | `PRIMARY KEY (status_id)` | Lookup table PK |
| `cache` | `PRIMARY KEY (`key`)` | Standard Laravel cache PK |
| `cache_locks` | `PRIMARY KEY (`key`)` | Standard Laravel cache lock PK |
| `csv_uploads` | `PRIMARY KEY (id)` | Also enabled `AUTO_INCREMENT` |
| `data_sources` | `PRIMARY KEY (id)` | Also enabled `AUTO_INCREMENT` |
| `device_tracking_logs` | `PRIMARY KEY (tracking_id)` | Also enabled `AUTO_INCREMENT` |
| `disaster_broadcasts` | `PRIMARY KEY (broadcast_id)` | Also enabled `AUTO_INCREMENT` |
| `education_levels` | `PRIMARY KEY (education_level_id)` | Lookup table PK (referenced by `household_members`) |
| `field_report_categories` | `PRIMARY KEY (category_id)` | Lookup table PK (referenced by `responder_field_reports`) |
| `import_logs` | `PRIMARY KEY (id)` | Also enabled `AUTO_INCREMENT` |
| `incident_archives` | `PRIMARY KEY (archive_id)` | Also enabled `AUTO_INCREMENT` |
| `job_batches` | `PRIMARY KEY (id)` | Standard Laravel queue batch PK |
| `map_tile_metadata` | `PRIMARY KEY (tile_id)` | Also enabled `AUTO_INCREMENT` |
| `occupations` | `PRIMARY KEY (occuaption_id)` | Lookup table PK (referenced by `household_members`) |
| `responder_check_ins` | `PRIMARY KEY (check_in_id)` | Also enabled `AUTO_INCREMENT` |
| `responder_communication_logs` | `PRIMARY KEY (communication_id)` | Also enabled `AUTO_INCREMENT` |
| `responder_field_report_details` | `PRIMARY KEY (detail_id)` | Also enabled `AUTO_INCREMENT` |
| `responder_field_reports` | `PRIMARY KEY (report_id)` | Also enabled `AUTO_INCREMENT` |
| `responder_location_logs` | `PRIMARY KEY (log_id)` | Also enabled `AUTO_INCREMENT` |
| `responder_routes` | `PRIMARY KEY (route_id)` | Also enabled `AUTO_INCREMENT` |
| `responders` | `PRIMARY KEY (responder_id)` | UUID/string PK (`varchar(255)`) |
| `route_coordinates` | `PRIMARY KEY (coordinate_id)` | Also enabled `AUTO_INCREMENT` |
| `situation_reports` | `PRIMARY KEY (sit_rep_id)` | Also enabled `AUTO_INCREMENT` |
| `zipcodes` | `PRIMARY KEY (zipcode_id)` | Lookup table PK (referenced by `cities`) |

#### B. 2 Tables Promoted from `UNIQUE KEY` to `PRIMARY KEY`
| Table Name | Original Definition | Corrected Definition |
| :--- | :--- | :--- |
| `rescue_teams` | `UNIQUE KEY team_id (team_id)` (no explicit PK) | `PRIMARY KEY (team_id)` |
| `responder_assignments` | `UNIQUE KEY assignment_id (assignment_id)` (no explicit PK) | `PRIMARY KEY (assignment_id)` |

---

### 2.3 `AUTO_INCREMENT` Restored on 20 Integer Primary Keys (`DB-002`)
The following 20 transactional/operational tables had integer primary keys without `AUTO_INCREMENT`, which caused `INSERT` statements omitting the ID column to fail with `Field '...' doesn't have a default value` or duplicate key `0`:

1. `addresses.address_id` (`int NOT NULL AUTO_INCREMENT`)
2. `affected_areas.affected_area_id` (`int NOT NULL AUTO_INCREMENT`)
3. `analytics_job_logs.job_id` (`int NOT NULL AUTO_INCREMENT`)
4. `csv_uploads.id` (`bigint unsigned NOT NULL AUTO_INCREMENT`)
5. `data_sources.id` (`bigint unsigned NOT NULL AUTO_INCREMENT`)
6. `device_tokens.id` (`bigint unsigned NOT NULL AUTO_INCREMENT`)
7. `device_tracking_logs.tracking_id` (`bigint NOT NULL AUTO_INCREMENT`)
8. `disaster_broadcasts.broadcast_id` (`int NOT NULL AUTO_INCREMENT`)
9. `disaster_event_types.event_type_id` (`int NOT NULL AUTO_INCREMENT`)
10. `import_logs.id` (`bigint unsigned NOT NULL AUTO_INCREMENT`)
11. `incident_archives.archive_id` (`int NOT NULL AUTO_INCREMENT`)
12. `map_tile_metadata.tile_id` (`int NOT NULL AUTO_INCREMENT`)
13. `responder_check_ins.check_in_id` (`int NOT NULL AUTO_INCREMENT`)
14. `responder_communication_logs.communication_id` (`int NOT NULL AUTO_INCREMENT`)
15. `responder_field_report_details.detail_id` (`int NOT NULL AUTO_INCREMENT`)
16. `responder_field_reports.report_id` (`int NOT NULL AUTO_INCREMENT`)
17. `responder_location_logs.log_id` (`bigint NOT NULL AUTO_INCREMENT`)
18. `responder_routes.route_id` (`int NOT NULL AUTO_INCREMENT`)
19. `route_coordinates.coordinate_id` (`bigint NOT NULL AUTO_INCREMENT`)
20. `situation_reports.sit_rep_id` (`int NOT NULL AUTO_INCREMENT`)

*(Static enumerated lookup tables with small fixed IDs like `roles`, `genders`, `civil_statuses`, `resource_request_status`, etc. remain explicit integer IDs so seed IDs are deterministic.)*

---

### 2.4 Data Type, Signedness, and Length Alignment (`DB-011`)
To allow foreign key enforcement and prevent silent string truncation or type-mismatch join penalties:

| Table & Column | Original Type | Corrected Type | Reason |
| :--- | :--- | :--- | :--- |
| `analytics.barangay_id` | `bigint unsigned` | `int` | Aligns with `barangays.barangay_id` (`int`) |
| `barangay_boundaries.barangay_id` | `bigint unsigned` | `int` | Aligns with `barangays.barangay_id` (`int`) |
| `responder_routes.responder_id` | `varchar(255)` | `varchar(255)` | Matches `responders.responder_id` (`varchar(255)`) |
| `responder_field_reports.assignment_id` | `varchar(255)` | `int` | Aligns with `responder_assignments.assignment_id` (`int`) |
| `responder_field_reports.status_log_id` | `varchar(255)` | `int` | Aligns with `household_status_logs.status_log_id` (`int`) |
| `responder_field_report_members.status_id` | `varchar(255)` | `int` | Aligns with `member_statuses.status_id` (`int`) |
| `event_center_history.evacuation_center_id` | `varchar(50)` | `varchar(255)` | Aligns with `evacuation_centers.evacuation_center_id` (`varchar(255)`) |
| `user_notifications.user_id` | `varchar(36)` | `varchar(255)` | Aligns with `users.user_id` (`varchar(255)`) |
| `landing_inquiries.handled_by_user_id` | `varchar(80)` | `varchar(255)` | Aligns with `users.user_id` (`varchar(255)`) |
| `civil_statuses.status_label` | `varchar(20)` | `varchar(100)` | Prevents truncation of labels like `"Legally Separated"` |
| `education_levels.education_level_label` | `varchar(20)` | `varchar(100)` | Prevents truncation of labels like `"Post-Graduate / Doctorate"` |
| `vulnerable_groups.vulnerable_group_label` | `varchar(20)` | `varchar(100)` | Prevents truncation of labels like `"Person with Disability (PWD)"` |
| `occupations.occupation_id` | *(Missing — typo `occuaption_id`)* | `int GENERATED ALWAYS AS (occuaption_id) VIRTUAL` | Adds virtual generated column `occupation_id` alongside `occuaption_id` so both old BIDRCS code and corrected queries work seamlessly |

---

### 2.5 `UNIQUE` Constraints Added (`DB-009`, `DB-012`)
1. **Authentication Token Integrity (`DB-009`)**:
   - `personal_access_tokens`: Added `UNIQUE KEY personal_access_tokens_token_unique (token)` and `KEY personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id)`.
2. **Lookup Code Uniqueness**:
   - `roles (role_key)`
   - `accommodation_types (type_key)`
   - `analytics_job_status (status_key)`
   - `center_issue_categories (category_key)`
   - `center_issue_report_statuses (status_key)`
   - `resource_request_status (status_key)`
   - `disaster_types (type_code)`
   - `severity_levels (severity_key)`
   - `notification_channels (channel_key)`
   - `notification_statuses (status_key)`
   - `recurrence_types (type_key)`
   - `education_levels (education_level_key)`
   - `field_report_categories (category_key)`
   - `regions (region_code)`
   - `provinces (province_code)`
   - `cities (city_code)`
   - `barangays (barangay_code)`
3. **Operational & Junction Uniqueness**:
   - `failed_jobs (uuid)`
   - `map_tile_metadata (zoom_level, tile_x, tile_y)`
   - `evacuated_members (evacuation_id, member_id)` — prevents duplicate check-in of the same household member into the same evacuation record.
   - `member_vulnerable_groups (member_id, vulnerable_group_id)` — prevents duplicate vulnerability tags per member.
   - `notification_recipients (notification_id, household_id)` — prevents duplicate broadcast dispatch rows per household.

---

### 2.6 Performance & Foreign Key Indexing (`DB-008`, `DB-010`, `DB-012`, `DB-013`)

#### A. Critical High-Traffic Indexes Added
- **`users`**: `idx_users_role_active (role_id, is_active)`, `idx_users_assigned_center (assigned_evacuation_center_id)`, `idx_users_household_id (household_id)`, `idx_users_mobile_number (mobile_number)` (accelerates login, RBAC filtering, and SMS OTP lookup).
- **`evacuation_centers`**: `idx_evac_centers_status (status_id)`, `idx_evac_centers_address (address_id)`, `idx_evac_centers_lead (camp_lead_user_id)`, `idx_evac_centers_deleted_at (deleted_at)`.
- **`disaster_events`**: `idx_disaster_events_active_dates (is_active, start_datetime, end_datetime)`, `idx_disaster_events_type (disaster_type_id)`, `idx_disaster_events_severity (severity_id)`.
- **`addresses`**: `idx_addresses_barangay (barangay_id)`, `idx_addresses_city (city_id)`, `idx_addresses_province (province_id)`, `idx_addresses_region (region_id)`, `idx_addresses_coords (latitude, longitude)`.
- **`households` & `household_members`**: `idx_households_head_member (household_head_member_id)`, `idx_households_deleted_at (deleted_at)`, `idx_hm_Lookup_composite (household_id, deleted_at, is_active)`, `idx_hm_gender (gender_id)`, `idx_hm_civil_status (civil_status_id)`, `idx_hm_relation (relation_to_head_id)`, `idx_hm_education (education_level_id)`, `idx_hm_occupation (occupation_id)`, `idx_hm_religion (religion_id)`.
- **`accommodation_units` & `unit_allocations`**: `idx_acc_units_center_status (evacuation_center_id, status_id, deleted_at)`, `idx_unit_alloc_unit_status (unit_id, status_id)`, `idx_unit_alloc_evac_record (evacuation_record_id)`.
- **`evacuation_records`**: `idx_evac_records_center_event (evacuation_center_id, disaster_event_id, check_out_time)`, `idx_evac_records_household (household_id, check_out_time)`.
- **`center_issue_reports` & `resource_requests`**: `idx_cir_center_status (evacuation_center_id, status_id, reported_at)`, `idx_rr_center_status (evacuation_center_id, status_id, requested_at)`.
- **`responder_location_logs`, `device_tracking_logs`, `route_coordinates`**: Added composite time-series and sequence indexes (`idx_rll_responder_time (responder_id, recorded_at)`, `idx_dtl_user_time (user_id, recorded_at)`, `idx_rc_route_seq (route_id, sequence_order)`).

#### B. Redundant Duplicate Indexes Removed (`DB-013`)
1. `password_reset_requests`: Dropped `password_reset_requests_user_id_index (user_id)` — covered by `idx_prr_user_status_expires (user_id, status, expires_at)`.
2. `remembered_devices`: Dropped `remembered_devices_user_id_index (user_id)` — covered by `idx_rd_user_selector_exp (user_id, device_selector, expires_at)`.
3. `web_push_deliveries`: Dropped `web_push_deliveries_subscription_id_index (subscription_id)` — covered by `idx_wpd_sub_status (subscription_id, status)`.
4. `evacuated_members`: Dropped `idx_evac_members_lookup (evacuation_id, member_id)` — replaced by `UNIQUE KEY uq_evacuated_members_evac_member (evacuation_id, member_id)`.

---

### 2.7 Foreign Key Relationships Expanded (`DB-003`)
Expanded from 17 FKs (on 7 tables) to **112 Foreign Keys across 53 tables**, enforcing referential integrity across all 3 subsystems:
- **Geography Hierarchy**: `provinces` → `regions`, `cities` → `provinces` & `zipcodes`, `barangays` → `cities`, `addresses` → `regions`/`provinces`/`cities`/`barangays`, `barangay_boundaries` → `barangays`.
- **Identity & RBAC**: `users` → `roles`, `evacuation_centers`, `households`; `role_module_permissions` → `roles`; `password_reset_requests`, `remembered_devices`, `web_push_subscriptions`, `user_notifications`, `device_tokens` → `users`.
- **Households & Demographics**: `households` → `addresses`, `users`; `household_members` → `households`, `genders`, `civil_statuses`, `relations`, `religions`, `education_levels`, `occupations`; `member_vulnerable_groups` → `household_members`, `vulnerable_groups`; `member_vital_signs` → `household_members`, `users`; `member_locations` → `household_members`, `location_types`.
- **Evacuation Centers & Shelters**: `evacuation_centers` → `addresses`, `center_statuses`, `users`; `center_contacts` → `evacuation_centers`; `accommodation_units` → `evacuation_centers`, `accommodation_types`, `unit_statuses`, `genders`; `unit_allocations` → `accommodation_units`, `evacuation_records`, `allocation_statuses`, `users`; `evacuation_records` → `households`, `evacuation_centers`, `disaster_events`, `users`; `evacuated_members` → `evacuation_records`, `household_members`, `evacuation_statuses`, `accommodation_units`, `users`.
- **Requests, Issues & Supplies**: `center_issue_reports` → `evacuation_centers`, `center_issue_categories`, `center_issue_report_statuses`, `users`; `resource_requests` → `evacuation_centers`, `disaster_events`, `resource_request_status`, `users`; `request_items` → `resource_requests`, `supply_categories`; `supply_distributions` → `evacuation_records`, `household_members`, `supply_categories`, `users`.
- **Disasters & Notifications**: `disaster_events` → `disaster_types`, `severity_levels`, `users`; `disaster_event_types` → `disaster_events`, `disaster_types`, `severity_levels`; `affected_areas` → `disaster_events`, `cities`, `barangays`; `event_center_history` → `disaster_events`, `evacuation_centers`, `users`; `notifications` → `disaster_events`, `notification_channels`, `notification_statuses`, `recurrence_types`, `users`; `notification_recipients` → `notifications`, `households`, `notification_statuses`.
- **ResQ Dispatch & Telemetry**: `responders` → `users`, `responder_types`, `responder_statuses`, `rescue_teams`; `rescue_teams` → `team_statuses`, `responders`; `responder_assignments` → `household_status_logs`, `responders`, `rescue_teams`, `assignment_statuses`, `users`; `responder_check_ins`, `responder_location_logs`, `responder_routes` → `responders`, `responder_assignments`; `route_coordinates` → `responder_routes`; `responder_field_reports` → `responders`, `responder_assignments`, `household_status_logs`, `field_report_categories`, `users`.
- **BIDRCS Import Pipeline**: `csv_uploads` → `data_sources`, `barangays`; `import_logs` → `csv_uploads`.

---

### 2.8 View Security & Portability Remediation
Both views (`v_member_status_monitor` and `v_household_status_board`) previously hardcoded:
```sql
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW ...
```
and prefixed table names with `` `klint`. ``.  
In `corrected_full_schema.sql`, both views now use:
```sql
CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW ...
```
with database-agnostic table references so the schema can be imported into any MySQL environment (`klint`, staging, CI, or Docker) without `root@localhost` definer errors.

