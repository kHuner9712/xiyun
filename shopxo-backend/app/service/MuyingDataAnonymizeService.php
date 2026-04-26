<?php
namespace app\service;

use think\facade\Db;
use think\facade\Log;

class MuyingDataAnonymizeService
{
    const SCENE_DATA_ANONYMIZE = 'data_anonymize';

    public static function SearchUser($params = [])
    {
        $keyword = trim($params['keyword'] ?? '');
        $search_type = trim($params['search_type'] ?? 'id');

        if (empty($keyword)) {
            return DataReturn('请输入搜索关键词', -1);
        }

        $where = [];
        switch ($search_type) {
            case 'id':
                $where[] = ['id', '=', intval($keyword)];
                break;
            case 'mobile':
                $where[] = ['mobile', 'like', '%' . $keyword . '%'];
                break;
            case 'openid':
                $where[] = ['wxalipayuser_openid', 'like', '%' . $keyword . '%'];
                break;
            default:
                $where[] = ['id', '=', intval($keyword)];
        }

        $user = Db::name('User')->where($where)->where('is_delete_time', 0)->find();
        if (empty($user)) {
            return DataReturn('未找到用户', -1);
        }

        $user_id = intval($user['id']);

        $signups = Db::name('ActivitySignup')
            ->alias('s')
            ->leftJoin('activity a', 's.activity_id = a.id')
            ->where('s.user_id', $user_id)
            ->where('s.status', 'in', [0, 1])
            ->field('s.id,s.activity_id,s.name,s.phone,s.phone_hash,s.stage,s.status,s.add_time,a.title as activity_title')
            ->order('s.add_time desc')
            ->select()
            ->toArray();

        $feedbacks = Db::name('MuyingFeedback')
            ->where('user_id', $user_id)
            ->where('is_delete_time', 0)
            ->field('id,content,contact,contact_hash,stage,review_status,add_time')
            ->order('add_time desc')
            ->select()
            ->toArray();

        $invite_as_inviter = Db::name('InviteReward')
            ->where('inviter_id', $user_id)
            ->count();
        $invite_as_invitee = Db::name('InviteReward')
            ->where('invitee_id', $user_id)
            ->count();

        $orders = Db::name('Order')
            ->where('user_id', $user_id)
            ->field('id,order_no,status,total_price,pay_price,add_time')
            ->order('add_time desc')
            ->limit(20)
            ->select()
            ->toArray();

        $masked_user = [
            'id'              => $user['id'],
            'nickname'        => $user['nickname'] ?? '',
            'mobile'          => MuyingPrivacyService::MaskPhone($user['mobile'] ?? ''),
            'current_stage'   => $user['current_stage'] ?? '',
            'due_date'        => $user['due_date'] ?? 0,
            'baby_birthday'   => $user['baby_birthday'] ?? 0,
            'invite_code'     => $user['invite_code'] ?? '',
            'add_time'        => $user['add_time'] ?? 0,
        ];

        foreach ($signups as &$s) {
            $s['name'] = MuyingPrivacyService::MaskName(MuyingPrivacyService::DecryptIfEncrypted($s['name']));
            $s['phone'] = MuyingPrivacyService::MaskPhone(MuyingPrivacyService::DecryptIfEncrypted($s['phone']));
        }
        unset($s);

        foreach ($feedbacks as &$f) {
            $f['contact'] = MuyingPrivacyService::MaskPhone(MuyingPrivacyService::DecryptIfEncrypted($f['contact']));
        }
        unset($f);

        return DataReturn(MyLang('handle_success'), 0, [
            'user'               => $masked_user,
            'signups'            => $signups,
            'feedbacks'          => $feedbacks,
            'invite_as_inviter'  => $invite_as_inviter,
            'invite_as_invitee'  => $invite_as_invitee,
            'orders'             => $orders,
        ]);
    }

    public static function AnonymizeUser($params = [])
    {
        $user_id = intval($params['user_id'] ?? 0);
        if ($user_id <= 0) {
            return DataReturn('用户ID无效', -1);
        }

        $user = Db::name('User')->where('id', $user_id)->where('is_delete_time', 0)->find();
        if (empty($user)) {
            return DataReturn('用户不存在', -1);
        }

        $admin = AdminService::LoginInfo();
        if (empty($admin)) {
            return DataReturn('管理员信息获取失败', -1);
        }

        if (!self::CanAnonymize($admin)) {
            return DataReturn('当前权限不允许执行数据匿名化操作', -403);
        }

        Db::startTrans();
        try {
            $anonymized_name = '已注销用户';
            $anonymized_phone_hash = MuyingPrivacyService::HashPhone('ANONYMIZED_' . $user_id);

            Db::name('User')->where('id', $user_id)->update([
                'nickname'       => $anonymized_name,
                'current_stage'  => '',
                'due_date'       => 0,
                'baby_birthday'  => 0,
                'upd_time'       => time(),
            ]);

            $signups = Db::name('ActivitySignup')
                ->where('user_id', $user_id)
                ->where('status', 'in', [0, 1])
                ->select()
                ->toArray();

            foreach ($signups as $signup) {
                $update = [
                    'name'       => MuyingPrivacyService::EncryptSensitive($anonymized_name),
                    'phone'      => MuyingPrivacyService::EncryptSensitive('ANONYMIZED'),
                    'phone_hash' => $anonymized_phone_hash,
                    'upd_time'   => time(),
                ];
                Db::name('ActivitySignup')->where('id', $signup['id'])->update($update);
            }

            Db::name('MuyingFeedback')
                ->where('user_id', $user_id)
                ->where('is_delete_time', 0)
                ->update([
                    'contact'      => '',
                    'contact_hash' => '',
                    'upd_time'     => time(),
                ]);

            Db::name('InviteReward')
                ->where('invitee_id', $user_id)
                ->where('trigger_event', 'register')
                ->update([
                    'status'   => 2,
                    'upd_time' => time(),
                ]);

            Db::commit();

            MuyingAuditLogService::Log([
                'admin_id'   => $admin['id'],
                'scene'      => self::SCENE_DATA_ANONYMIZE,
                'target_id'  => $user_id,
                'remark'     => '用户数据匿名化处理 UID=' . $user_id,
                'ip'         => request()->ip(),
            ]);

            Log::info('[MuyingDataAnonymize] 用户数据匿名化完成 user_id=' . $user_id . ' admin_id=' . $admin['id']);

            return DataReturn('匿名化处理完成', 0);
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('[MuyingDataAnonymize] 匿名化失败 user_id=' . $user_id . ' error=' . $e->getMessage());
            return DataReturn('匿名化处理失败: ' . $e->getMessage(), -1);
        }
    }

    public static function CanAnonymize($admin)
    {
        if (AdminIsPower('muyingprivacy', 'delete')) {
            return true;
        }
        if (!empty($admin) && isset($admin['id']) && $admin['id'] == 1) {
            return true;
        }
        return false;
    }
}
