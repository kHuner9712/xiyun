-- ============================================================
-- 孕禧一期：隐私数据管理 - 后台权限菜单注册迁移
-- 将 Muyingprivacy 控制器注册到 sxo_power 表
-- 幂等性：使用 INSERT IGNORE，可重复执行
-- 兼容：MySQL 5.7.44+
-- ============================================================

-- 1. 隐私数据管理（二级菜单，挂在孕禧运营 700 下）
INSERT IGNORE INTO `sxo_power` (`id`, `pid`, `name`, `control`, `action`, `url`, `sort`, `is_show`, `icon`, `add_time`, `upd_time`) VALUES
(770, 700, '隐私数据管理', 'Muyingprivacy', 'Index', '', 8, 1, '', UNIX_TIMESTAMP(), 0),
(771, 770, '用户数据查询', 'Muyingprivacy', 'Search', '', 0, 0, '', UNIX_TIMESTAMP(), 0),
(772, 770, '数据匿名化', 'Muyingprivacy', 'Anonymize', '', 1, 0, '', UNIX_TIMESTAMP(), 0);

-- 2. 将权限分配给超级管理员角色（role_id=1）
INSERT IGNORE INTO `sxo_role_power` (`role_id`, `power_id`, `add_time`) VALUES
(1, 770, UNIX_TIMESTAMP()),
(1, 771, UNIX_TIMESTAMP()),
(1, 772, UNIX_TIMESTAMP());
