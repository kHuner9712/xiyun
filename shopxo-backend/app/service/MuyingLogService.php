<?php
namespace app\service;

use think\facade\Db;
use think\facade\Log;

class MuyingLogService
{
    const TYPE_ACTIVITY_SIGNUP = 'activity_signup';
    const TYPE_ACTIVITY_CHECKIN = 'activity_checkin';
    const TYPE_ACTIVITY_CONFIRM = 'activity_confirm';
    const TYPE_ACTIVITY_CANCEL = 'activity_cancel';
    const TYPE_INVITE_REWARD = 'invite_reward';
    const TYPE_USER_STAGE = 'user_stage';
    const TYPE_USER_PROFILE = 'user_profile';

    public static function Log($type, $action, $user_id, $target_id = 0, $detail = '', $status = 1)
    {
        try {
            Db::name('MuyingAuditLog')->insert([
                'type'       => $type,
                'action'     => $action,
                'user_id'    => intval($user_id),
                'target_id'  => intval($target_id),
                'detail'     => is_string($detail) ? $detail : json_encode($detail, JSON_UNESCAPED_UNICODE),
                'status'     => intval($status),
                'ip'         => request()->ip(),
                'add_time'   => time(),
            ]);
        } catch (\Exception $e) {
            Log::error('审计日志写入失败 type=' . $type . ' action=' . $action . ' error=' . $e->getMessage());
        }
    }

    public static function LogSuccess($type, $action, $user_id, $target_id = 0, $detail = '')
    {
        self::Log($type, $action, $user_id, $target_id, $detail, 1);
    }

    public static function LogFail($type, $action, $user_id, $target_id = 0, $detail = '')
    {
        self::Log($type, $action, $user_id, $target_id, $detail, 0);
    }
}
