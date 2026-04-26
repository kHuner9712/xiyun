-- [MUYING-二开] 反馈表增加类型字段
-- 用途：支持"数据删除/隐私请求"等反馈类型
-- 兼容：MySQL 5.7+

ALTER TABLE `sxo_muying_feedback`
ADD COLUMN `type` char(30) NOT NULL DEFAULT 'feedback' COMMENT '反馈类型(feedback反馈/suggestion建议/complaint投诉/privacy_request隐私请求)' AFTER `user_id`;

ALTER TABLE `sxo_muying_feedback`
ADD INDEX `idx_type` (`type`);
