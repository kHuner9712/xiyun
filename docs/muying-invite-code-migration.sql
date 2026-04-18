-- ============================================================
-- [已废弃] 请使用 docs/muying-final-migration.sql
-- 本文件内容已合并到最终迁移脚本 B1 + C1 + C2 段，不要直接执行
-- ============================================================
-- 邀请码字段迁移脚本 v2
-- 说明: 用户表增加 invite_code 字段和唯一索引，补齐老用户邀请码
-- 依赖: 先执行 muying-migration.sql (建表)
-- 执行顺序: ADD COLUMN → 补邀请码 → ADD UNIQUE INDEX
-- 回滚方案见文末
-- ============================================================

-- ============================================================
-- 0. 执行前检查
-- ============================================================
-- 检查字段是否已存在（如果已存在则跳过步骤1）
-- SELECT COUNT(*) FROM information_schema.COLUMNS
--   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sxo_user' AND COLUMN_NAME = 'invite_code';
-- 预期结果: 0

-- 检查当前用户数和空邀请码数
-- SELECT COUNT(*) AS total_users FROM `sxo_user`;
-- SELECT COUNT(*) AS empty_code_users FROM `sxo_user` WHERE `invite_code` = '' OR `invite_code` IS NULL;

-- ============================================================
-- 1. 用户表增加 invite_code 字段（允许空字符串，不加唯一索引）
-- ============================================================
ALTER TABLE `sxo_user`
ADD COLUMN `invite_code` char(8) NOT NULL DEFAULT '' COMMENT '邀请码' AFTER `baby_birthday`;

-- ============================================================
-- 2. 为已有用户逐行生成唯一邀请码
--    使用存储过程：逐行遍历，每行生成随机码，确认唯一后写入
--    不使用批量生成，避免碰撞
-- ============================================================
DELIMITER //

DROP PROCEDURE IF EXISTS `muying_fill_invite_code`//

CREATE PROCEDURE `muying_fill_invite_code`()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_id INT;
    DECLARE v_code CHAR(8);
    DECLARE v_exists INT;
    DECLARE v_attempts INT;
    DECLARE cur CURSOR FOR SELECT `id` FROM `sxo_user` WHERE `invite_code` = '' OR `invite_code` IS NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_id;
        IF done THEN
            LEAVE read_loop;
        END IF;

        SET v_exists = 1;
        SET v_attempts = 0;
        WHILE v_exists > 0 AND v_attempts < 50 DO
            SET v_code = UPPER(SUBSTRING(MD5(CONCAT(RAND(), UNIX_TIMESTAMP(), v_id, v_attempts)), 1, 8));
            SELECT COUNT(*) INTO v_exists FROM `sxo_user` WHERE `invite_code` = v_code;
            SET v_attempts = v_attempts + 1;
        END WHILE;

        IF v_exists = 0 THEN
            UPDATE `sxo_user` SET `invite_code` = v_code WHERE `id` = v_id;
        END IF;
    END LOOP;
    CLOSE cur;
END//

DELIMITER ;

CALL `muying_fill_invite_code`();

DROP PROCEDURE IF EXISTS `muying_fill_invite_code`;

-- ============================================================
-- 3. 执行后校验：确认无空邀请码
-- ============================================================
-- SELECT COUNT(*) AS empty_code_users FROM `sxo_user` WHERE `invite_code` = '' OR `invite_code` IS NULL;
-- 预期结果: 0

-- SELECT COUNT(*) AS total_users, COUNT(DISTINCT invite_code) AS distinct_codes FROM `sxo_user`;
-- 预期: total_users == distinct_codes

-- ============================================================
-- 4. 确认无空值后，添加唯一索引
-- ============================================================
ALTER TABLE `sxo_user` ADD UNIQUE INDEX `uk_invite_code` (`invite_code`);

-- ============================================================
-- 5. 最终校验
-- ============================================================
-- SHOW COLUMNS FROM `sxo_user` LIKE 'invite_code';
-- SHOW INDEX FROM `sxo_user` WHERE Key_name = 'uk_invite_code';

-- ============================================================
-- 回滚方案
-- ============================================================
-- ALTER TABLE `sxo_user` DROP INDEX `uk_invite_code`;
-- ALTER TABLE `sxo_user` DROP COLUMN `invite_code`;
