-- ============================================================
-- 邀请奖励幂等整改 - 数据库唯一约束 v2
-- 目的：为 sxo_invite_reward 表增加业务唯一约束，防止重复发奖
-- 执行顺序：查重复 → 清重复 → 加约束 → 校验
-- 回滚方案见文末
-- ============================================================

-- ============================================================
-- 0. 执行前检查
-- ============================================================
-- 检查当前重复记录数
-- SELECT inviter_id, invitee_id, trigger_event, COUNT(*) AS cnt
--   FROM `sxo_invite_reward`
--   GROUP BY inviter_id, invitee_id, trigger_event
--   HAVING cnt > 1;
-- 预期: 无结果或少量结果

-- 检查总记录数
-- SELECT COUNT(*) AS total FROM `sxo_invite_reward`;

-- ============================================================
-- 1. 清理已有的重复记录（保留最早的一条，删除后续重复）
-- ============================================================
DELETE r1 FROM `sxo_invite_reward` r1
INNER JOIN `sxo_invite_reward` r2
ON r1.inviter_id = r2.inviter_id
   AND r1.invitee_id = r2.invitee_id
   AND r1.trigger_event = r2.trigger_event
   AND r1.id > r2.id;

-- ============================================================
-- 2. 清理后校验：确认无重复
-- ============================================================
-- SELECT inviter_id, invitee_id, trigger_event, COUNT(*) AS cnt
--   FROM `sxo_invite_reward`
--   GROUP BY inviter_id, invitee_id, trigger_event
--   HAVING cnt > 1;
-- 预期: 无结果

-- ============================================================
-- 3. 添加业务唯一约束
-- ============================================================
ALTER TABLE `sxo_invite_reward`
ADD UNIQUE INDEX `uk_inviter_invitee_event` (`inviter_id`, `invitee_id`, `trigger_event`);

-- ============================================================
-- 4. 最终校验
-- ============================================================
-- SHOW INDEX FROM `sxo_invite_reward` WHERE Key_name = 'uk_inviter_invitee_event';
-- SELECT COUNT(*) AS total_after FROM `sxo_invite_reward`;

-- ============================================================
-- 回滚方案
-- ============================================================
-- ALTER TABLE `sxo_invite_reward` DROP INDEX `uk_inviter_invitee_event`;
