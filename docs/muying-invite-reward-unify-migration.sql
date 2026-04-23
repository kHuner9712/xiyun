-- ============================================================
-- 邀请奖励逻辑统一迁移 — 移除注册奖励，首单为唯一触发
-- 作用：关闭注册奖励链路，统一奖励类型为 integral，处理旧数据
-- 执行时机：在 muying-final-migration.sql 之后执行
-- 幂等性：是（配置项使用 UPDATE，数据操作有条件判断）
-- 兼容：MySQL 5.7.44+
-- ============================================================

-- 1. 关闭注册奖励配置
UPDATE `sxo_config` SET `value` = '0' WHERE `only_tag` = 'muying_invite_register_reward';
UPDATE `sxo_config` SET `value` = '0' WHERE `only_tag` = 'muying_invite_register_auto_grant';

-- 2. 开启首单自动发放（默认为1，确保）
UPDATE `sxo_config` SET `value` = '1' WHERE `only_tag` = 'muying_invite_first_order_auto_grant';

-- 3. 处理旧的注册奖励记录：将已发放的注册奖励标记为已撤销（status=3），并扣回积分
-- 注意：此步骤需要根据实际业务决定是否执行，以下为安全策略
-- 3a. 将待发放的注册奖励标记为已取消
UPDATE `sxo_invite_reward` SET `status` = 2, `upd_time` = UNIX_TIMESTAMP()
WHERE `trigger_event` = 'register' AND `status` = 0 AND `reward_value` > 0;

-- 3b. 将已发放的注册奖励标记为已撤销（积分扣回需后台手动操作或执行下方补充SQL）
UPDATE `sxo_invite_reward` SET `status` = 3, `upd_time` = UNIX_TIMESTAMP()
WHERE `trigger_event` = 'register' AND `status` = 1 AND `reward_value` > 0;

-- 3c. 将 reward_value=0 的注册记录保留为 GRANTED（仅绑定关系，无实际奖励）
-- 无需操作，这些记录本来就是纯绑定记录

-- 4. 清理旧的 coupon 类型记录：将 coupon 类型标记为已取消
UPDATE `sxo_invite_reward` SET `status` = 2, `upd_time` = UNIX_TIMESTAMP()
WHERE `reward_type` = 'coupon' AND `status` = 0;

-- 5. 更新邀请标语
UPDATE `sxo_config` SET `value` = '邀请好友 赢积分' WHERE `only_tag` = 'muying_invite_slogan' AND (`value` IS NULL OR `value` = '');

-- ============================================================
-- 补充SQL：扣回已发放的注册奖励积分（谨慎执行，需确认业务影响）
-- 执行前请先 SELECT 统计受影响记录数：
--   SELECT inviter_id, SUM(reward_value) as total FROM sxo_invite_reward
--   WHERE trigger_event='register' AND status=3 AND reward_value>0 GROUP BY inviter_id;
-- ============================================================
-- SET @deduct_ts = UNIX_TIMESTAMP();
-- INSERT INTO sxo_user_integral_log (user_id, original_integral, new_integral, type, msg, add_time)
-- SELECT r.inviter_id, u.integral, u.integral - r.reward_value, 0,
--   CONCAT('撤销注册邀请奖励(用户ID:', r.invitee_id, ')'), @deduct_ts
-- FROM sxo_invite_reward r
-- JOIN sxo_user u ON u.id = r.inviter_id
-- WHERE r.trigger_event = 'register' AND r.status = 3 AND r.reward_value > 0;
--
-- UPDATE sxo_user u
-- JOIN (SELECT inviter_id, SUM(reward_value) as total_deduct FROM sxo_invite_reward
--   WHERE trigger_event='register' AND status=3 AND reward_value>0 GROUP BY inviter_id) r
-- ON u.id = r.inviter_id
-- SET u.integral = GREATEST(0, u.integral - r.total_deduct);

-- 回滚
-- UPDATE sxo_config SET value = '100' WHERE only_tag = 'muying_invite_register_reward';
-- UPDATE sxo_config SET value = '1' WHERE only_tag = 'muying_invite_register_auto_grant';
-- UPDATE sxo_invite_reward SET status = 1 WHERE trigger_event = 'register' AND status = 3 AND reward_value > 0;
