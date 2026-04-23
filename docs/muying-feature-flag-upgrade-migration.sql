-- ============================================================
-- 功能开关体系升级迁移
-- 作用：1) 补充缺失的 feature_feedback_enabled 配置项
--       2) 新增未来扩展功能开关（coupon/points/membership 等）
--       3) 确保前后端配置来源统一
-- 执行时机：在 muying-feature-switch-migration.sql 之后执行
-- 幂等性：使用 ON DUPLICATE KEY UPDATE，可重复执行
-- 兼容：MySQL 5.7.44+
-- ============================================================

-- 1. 补充缺失的 feature_feedback_enabled（一期核心功能，默认开启）
INSERT INTO `sxo_config` (`value`, `name`, `describe`, `error_tips`, `type`, `only_tag`, `upd_time`)
VALUES ('1', '用户反馈开关', '控制用户反馈/妈妈说功能是否开放', '请选择是否开启', 'admin', 'feature_feedback_enabled', UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `describe`=VALUES(`describe`), `upd_time`=UNIX_TIMESTAMP();

-- 2. 新增未来扩展功能开关（默认关闭，后台可配置开启）
INSERT INTO `sxo_config` (`value`, `name`, `describe`, `error_tips`, `type`, `only_tag`, `upd_time`) VALUES
('0', '优惠券二期开关', '控制优惠券领取/使用功能是否开放（二期扩展）', '请选择是否开启', 'admin', 'feature_coupon_v2_enabled', UNIX_TIMESTAMP()),
('0', '积分体系二期开关', '控制积分兑换/积分商城功能是否开放（二期扩展）', '请选择是否开启', 'admin', 'feature_points_v2_enabled', UNIX_TIMESTAMP()),
('0', '会员等级二期开关', '控制会员等级/付费VIP功能是否开放（二期扩展）', '请选择是否开启', 'admin', 'feature_membership_v2_enabled', UNIX_TIMESTAMP()),
('0', '钱包余额二期开关', '控制钱包/余额/充值/提现功能是否开放（二期扩展）', '请选择是否开启', 'admin', 'feature_wallet_v2_enabled', UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `describe`=VALUES(`describe`), `upd_time`=UNIX_TIMESTAMP();

-- 回滚
-- DELETE FROM sxo_config WHERE only_tag = 'feature_feedback_enabled';
-- DELETE FROM sxo_config WHERE only_tag IN ('feature_coupon_v2_enabled', 'feature_points_v2_enabled', 'feature_membership_v2_enabled', 'feature_wallet_v2_enabled');
