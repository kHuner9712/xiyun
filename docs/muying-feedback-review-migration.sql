-- ============================================================
-- 妈妈说/用户反馈 — 审核流迁移
-- 作用：为 sxo_muying_feedback 表增加审核字段，将"提交即展示"改为"审核后展示"
-- 执行时机：在 muying-final-migration.sql 和 muying-enhancement-migration.sql 之后执行
-- 幂等性：使用 information_schema 检查字段是否存在，可重复执行
-- 兼容：MySQL 5.7.44+
-- ============================================================

SET @dbname = DATABASE();
SET @tablename = 'sxo_muying_feedback';

-- 1. review_status 字段
SET @colname = 'review_status';
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME=@colname;
SET @sql = IF(@col_exists=0, 'ALTER TABLE `sxo_muying_feedback` ADD COLUMN `review_status` char(20) NOT NULL DEFAULT ''pending'' COMMENT ''审核状态(pending待审核/approved已通过/rejected已驳回)'' AFTER `contact`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. review_remark 字段
SET @colname = 'review_remark';
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME=@colname;
SET @sql = IF(@col_exists=0, 'ALTER TABLE `sxo_muying_feedback` ADD COLUMN `review_remark` varchar(255) NOT NULL DEFAULT '''' COMMENT ''审核备注'' AFTER `review_status`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. review_admin_id 字段
SET @colname = 'review_admin_id';
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME=@colname;
SET @sql = IF(@col_exists=0, 'ALTER TABLE `sxo_muying_feedback` ADD COLUMN `review_admin_id` int unsigned NOT NULL DEFAULT 0 COMMENT ''审核管理员ID'' AFTER `review_remark`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. review_time 字段
SET @colname = 'review_time';
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME=@colname;
SET @sql = IF(@col_exists=0, 'ALTER TABLE `sxo_muying_feedback` ADD COLUMN `review_time` int unsigned NOT NULL DEFAULT 0 COMMENT ''审核时间'' AFTER `review_admin_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. 审核状态索引
SET @indexname = 'idx_review_status';
SELECT COUNT(*) INTO @index_exists FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND INDEX_NAME=@indexname;
SET @sql = IF(@index_exists=0, 'ALTER TABLE `sxo_muying_feedback` ADD KEY `idx_review_status` (`review_status`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. 更新已有数据：已启用的标记为 approved，已禁用的标记为 rejected
UPDATE `sxo_muying_feedback` SET `review_status` = 'approved', `review_time` = `upd_time` WHERE `review_status` = 'pending' AND `is_enable` = 1 AND `is_delete_time` = 0;
UPDATE `sxo_muying_feedback` SET `review_status` = 'rejected', `review_time` = `upd_time` WHERE `review_status` = 'pending' AND `is_enable` = 0 AND `is_delete_time` = 0;

-- 回滚
-- ALTER TABLE `sxo_muying_feedback` DROP COLUMN `review_status`, DROP COLUMN `review_remark`, DROP COLUMN `review_admin_id`, DROP COLUMN `review_time`;
-- ALTER TABLE `sxo_muying_feedback` DROP INDEX `idx_review_status`;
