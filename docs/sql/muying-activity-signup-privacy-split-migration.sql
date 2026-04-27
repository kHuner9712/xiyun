-- ============================================================
-- 活动报名隐私授权拆分迁移脚本
-- 日期：2026-04-26
-- 说明：在 ActivitySignup 表增加 profile_sync_agreed 和 profile_sync_agreed_time 字段
-- 幂等：使用 information_schema 判断字段是否已存在
-- ============================================================

SET @table_prefix = (SELECT `value` FROM `sxo_config` WHERE `only_tag` = 'common_database_table_prefix' LIMIT 1);
SET @table_name = CONCAT(IFNULL(@table_prefix, 'sxo_'), 'activity_signup');

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @table_name AND COLUMN_NAME = 'profile_sync_agreed');
SET @sql = IF(@col_exists = 0,
    CONCAT('ALTER TABLE `', @table_name, '` ADD COLUMN `profile_sync_agreed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''画像同步授权 0=未同意 1=已同意'' AFTER `privacy_version`'),
    'SELECT ''profile_sync_agreed already exists, skipping'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @table_name AND COLUMN_NAME = 'profile_sync_agreed_time');
SET @sql2 = IF(@col_exists2 = 0,
    CONCAT('ALTER TABLE `', @table_name, '` ADD COLUMN `profile_sync_agreed_time` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''画像同步授权时间'' AFTER `profile_sync_agreed`'),
    'SELECT ''profile_sync_agreed_time already exists, skipping'''
);
PREPARE stmt FROM @sql2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 历史数据处理：已有 privacy_agreed_time 的记录，默认 profile_sync_agreed = 1（按旧逻辑已同步画像）
SET @hist_sql = CONCAT('UPDATE `', @table_name, '` SET `profile_sync_agreed` = 1, `profile_sync_agreed_time` = `privacy_agreed_time` WHERE `privacy_agreed_time` > 0 AND `profile_sync_agreed` = 0');
PREPARE stmt FROM @hist_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
