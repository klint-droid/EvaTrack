SET FOREIGN_KEY_CHECKS=0;

-- 1. Clean up redundant columns in household_members
ALTER TABLE `household_members` 
  DROP COLUMN `name`,
  DROP COLUMN `sex`,
  DROP COLUMN `gender`,
  DROP COLUMN `age`,
  DROP COLUMN `relation`,
  DROP COLUMN `civil_status`,
  DROP COLUMN `special_needs`,
  DROP COLUMN `education_level`;

-- 2. Clean up duplicate metric columns in analytics
ALTER TABLE `analytics`
  DROP COLUMN `total_males`,
  DROP COLUMN `total_females`,
  DROP COLUMN `total_pwd`,
  DROP COLUMN `total_seniors`,
  DROP COLUMN `total_children`,
  DROP COLUMN `total_adults`,
  DROP COLUMN `total_pregnant`,
  DROP COLUMN `total_household`;

-- 3. Clean up redundant text column in addresses
ALTER TABLE `addresses`
  DROP COLUMN `barangay_name`;

-- 4. Drop redundant center_occupancies table
DROP TABLE IF EXISTS `center_occupancies`;

-- 5. Add high-performance composite indexes
ALTER TABLE `evacuated_members` ADD INDEX `idx_evac_members_lookup` (`evacuation_id`, `member_id`);
ALTER TABLE `evacuation_records` ADD INDEX `idx_evac_records_status_center` (`center_id`, `household_status_id`, `household_id`);
ALTER TABLE `resource_requests` ADD INDEX `idx_resource_req_status_created` (`status_id`, `created_at`);

SET FOREIGN_KEY_CHECKS=1;
