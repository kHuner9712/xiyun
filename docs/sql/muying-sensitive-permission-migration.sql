-- ============================================================
-- [MUYING-二开] 敏感数据查看/导出权限迁移
-- 作用：注册 muyingsensitive 权限到后台权限表，控制敏感数据查看和导出
-- 兼容：MySQL 5.7.44+
-- 幂等性：使用 INSERT IGNORE，可重复执行
-- 执行时机：在 muying-compliance-center-migration.sql 之后执行
-- 重要：使用 id=780-783，避免与合规中心 id=770-775 冲突
-- 回滚：见文件末尾
-- ============================================================

-- ============================================================
-- 一、敏感数据权限注册
-- ============================================================

INSERT IGNORE INTO `sxo_power` (`id`, `pid`, `name`, `control`, `action`, `url`, `sort`, `is_show`, `icon`, `add_time`, `upd_time`) VALUES
(780, 700, '敏感数据管理', 'Muyingsensitive', 'Index', '', 9, 0, '', UNIX_TIMESTAMP(), 0),
(781, 780, '查看敏感数据', 'Muyingsensitive', 'View', '', 0, 0, '', UNIX_TIMESTAMP(), 0),
(782, 780, '导出敏感数据', 'Muyingsensitive', 'Export', '', 1, 0, '', UNIX_TIMESTAMP(), 0);

-- ============================================================
-- 二、默认角色权限（role_id=1 为超级管理员，默认授予敏感数据权限）
-- 其他角色需手动授权
-- ============================================================

INSERT IGNORE INTO `sxo_role_power` (`role_id`, `power_id`, `add_time`) VALUES
(1, 780, UNIX_TIMESTAMP()),
(1, 781, UNIX_TIMESTAMP()),
(1, 782, UNIX_TIMESTAMP());

-- ============================================================
-- 验证查询
-- ============================================================
-- SELECT id, pid, name, control, action FROM sxo_power WHERE id IN (780, 781, 782) ORDER BY id;
-- SELECT rp.role_id, p.name, p.control, p.action FROM sxo_role_power rp JOIN sxo_power p ON rp.power_id = p.id WHERE p.control = 'Muyingsensitive';

-- ============================================================
-- 回滚
-- ============================================================
-- DELETE FROM sxo_role_power WHERE power_id IN (780, 781, 782);
-- DELETE FROM sxo_power WHERE id IN (780, 781, 782);
