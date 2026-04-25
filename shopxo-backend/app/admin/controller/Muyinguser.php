<?php
namespace app\admin\controller;

use app\admin\controller\Base;
use app\service\ApiService;
use app\service\MuyingAuditLogService;
use app\service\MuyingPrivacyService;
use think\facade\Db;

class Muyinguser extends Base
{
    public function Index()
    {
        $params = $this->data_request;
        $where = [['id', '>', 0]];

        if (!empty($params['current_stage'])) {
            $where[] = ['current_stage', '=', trim($params['current_stage'])];
        }
        if (!empty($params['due_date_start']) && !empty($params['due_date_end'])) {
            $where[] = ['due_date', '>=', strtotime($params['due_date_start'])];
            $where[] = ['due_date', '<=', strtotime($params['due_date_end'])];
        }
        if (!empty($params['baby_month_age'])) {
            $age = intval($params['baby_month_age']);
            $now = time();
            $where[] = ['baby_birthday', '>', 0];
            $where[] = ['baby_birthday', '<=', $now - $age * 30 * 86400];
            $where[] = ['baby_birthday', '>', $now - ($age + 3) * 30 * 86400];
        }
        if (!empty($params['keywords'])) {
            $kw = trim($params['keywords']);
            $where[] = ['nickname|username|invite_code', 'like', '%' . $kw . '%'];
        }

        $page = max(1, intval($params['page'] ?? 1));
        $page_size = 20;

        $total = Db::name('User')->where($where)->count();
        $list = Db::name('User')->where($where)
            ->field('id,nickname,username,avatar,mobile,current_stage,due_date,baby_birthday,invite_code,add_time,upd_time,integral')
            ->order('id desc')
            ->page($page, $page_size)
            ->select()
            ->toArray();

        $can_view_sensitive = !empty($this->admin) && MuyingPrivacyService::CanViewSensitive($this->admin);

        $stage_list = \app\extend\muying\MuyingStage::getList();
        foreach ($list as &$v) {
            $v['current_stage_text'] = '';
            foreach ($stage_list as $s) {
                if ($s['value'] === $v['current_stage']) {
                    $v['current_stage_text'] = $s['name'];
                    break;
                }
            }
            $v['due_date_text'] = $v['due_date'] > 0 ? date('Y-m-d', $v['due_date']) : '';
            $v['baby_birthday_text'] = $v['baby_birthday'] > 0 ? date('Y-m-d', $v['baby_birthday']) : '';
            if ($v['baby_birthday'] > 0) {
                $months = intval((time() - $v['baby_birthday']) / (86400 * 30));
                $v['baby_month_age_text'] = $months . '个月';
            } else {
                $v['baby_month_age_text'] = '';
            }
            if (!$can_view_sensitive && !empty($v['mobile'])) {
                $v['mobile'] = MuyingPrivacyService::MaskPhone($v['mobile']);
            }
        }

        $page_total = ceil($total / $page_size);
        $page_url = MyUrl('admin/muyinguser/index');

        MyViewAssign([
            'data_list'          => $list,
            'total'              => $total,
            'page'               => $page,
            'page_size'          => $page_size,
            'page_total'         => $page_total,
            'page_url'           => $page_url,
            'can_view_sensitive' => $can_view_sensitive,
            'params'             => $params,
            'stage_list'         => $stage_list,
        ]);

        return MyView();
    }

    public function Detail()
    {
        $params = $this->data_request;
        $id = isset($params['id']) ? intval($params['id']) : 0;
        if ($id <= 0) {
            return MyView();
        }

        $user = Db::name('User')->where(['id' => $id])->find();
        if (empty($user)) {
            return MyView();
        }

        $can_view_sensitive = !empty($this->admin) && MuyingPrivacyService::CanViewSensitive($this->admin);
        if (!$can_view_sensitive && !empty($user['mobile'])) {
            $user['mobile'] = MuyingPrivacyService::MaskPhone($user['mobile']);
        }

        $stage_list = \app\extend\muying\MuyingStage::getList();
        $user['current_stage_text'] = '';
        foreach ($stage_list as $s) {
            if ($s['value'] === $user['current_stage']) {
                $user['current_stage_text'] = $s['name'];
                break;
            }
        }
        $user['due_date_text'] = $user['due_date'] > 0 ? date('Y-m-d', $user['due_date']) : '';
        $user['baby_birthday_text'] = $user['baby_birthday'] > 0 ? date('Y-m-d', $user['baby_birthday']) : '';

        $signups = Db::name('ActivitySignup')->where(['user_id' => $id, 'is_delete_time' => 0])
            ->field('id,activity_id,status,is_waitlist,add_time')->order('id desc')->limit(10)->select()->toArray();

        $feedbacks = Db::name('MuyingFeedback')->where(['user_id' => $id, 'is_delete_time' => 0])
            ->field('id,content,review_status,add_time')->order('id desc')->limit(10)->select()->toArray();

        $invites = Db::name('InviteReward')->where(['inviter_id' => $id])
            ->field('id,invitee_id,trigger_event,reward_value,status,add_time')->order('id desc')->limit(10)->select()->toArray();

        $orders = Db::name('Order')->where(['user_id' => $id])
            ->field('id,order_no,status,total_price,add_time')->order('id desc')->limit(10)->select()->toArray();

        if ($can_view_sensitive && !empty($this->admin)) {
            MuyingAuditLogService::LogSensitiveView($this->admin, MuyingAuditLogService::SCENE_SENSITIVE_VIEW, $id, '查看用户详情含敏感信息');
        }

        MyViewAssign([
            'data'               => $user,
            'signups'            => $signups,
            'feedbacks'          => $feedbacks,
            'invites'            => $invites,
            'orders'             => $orders,
            'can_view_sensitive' => $can_view_sensitive,
        ]);

        return MyView();
    }
}
