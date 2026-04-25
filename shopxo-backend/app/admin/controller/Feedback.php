<?php
namespace app\admin\controller;

use app\admin\controller\Base;
use app\service\ApiService;
use app\service\SystemBaseService;
use app\service\FeedbackService;
use app\service\MuyingPrivacyService;
use app\service\MuyingAuditLogService;
use app\extend\muying\MuyingStage;

class Feedback extends Base
{
    public function Index()
    {
        $stage_list = [];
        foreach (MuyingStage::getList() as $value => $name) {
            $stage_list[] = ['value' => $value, 'name' => $name];
        }
        $review_status_list = [
            ['value' => 'pending', 'name' => '待审核'],
            ['value' => 'approved', 'name' => '已通过'],
            ['value' => 'rejected', 'name' => '已驳回'],
        ];
        MyViewAssign([
            'common_service_muying_stage_list' => $stage_list,
            'common_service_review_status_list' => $review_status_list,
        ]);
        return MyView();
    }

    public function DataIndex()
    {
        $params = $this->data_request;
        $where = FeedbackService::FeedbackAdminWhere($params);
        $total = FeedbackService::FeedbackAdminTotal($where);
        $page_total = ceil($total / $this->page_size);
        $start = intval(($this->page - 1) * $this->page_size);

        $data_params = array_merge($params, [
            'm'     => $start,
            'n'     => $this->page_size,
            'where' => $where,
        ]);
        $data = FeedbackService::FeedbackAdminList($data_params);

        $result = [
            'total'      => $total,
            'page_total' => $page_total,
            'data'       => $data['data'],
        ];
        return ApiService::ApiDataReturn(SystemBaseService::DataReturn($result));
    }

    public function Detail()
    {
        $params = $this->data_request;
        $id = isset($params['id']) ? intval($params['id']) : 0;
        if ($id <= 0) {
            return MyView();
        }

        $data = \think\facade\Db::name('MuyingFeedback')->where(['id' => $id])->find();
        if (!empty($data)) {
            $data['stage_text'] = \app\extend\muying\MuyingStage::getName(\app\extend\muying\MuyingStage::Normalize($data['stage'] ?? ''));
            $data['add_time_text'] = empty($data['add_time']) ? '' : date('Y-m-d H:i:s', $data['add_time']);
            $data['upd_time_text'] = empty($data['upd_time']) ? '' : date('Y-m-d H:i:s', $data['upd_time']);
            $data['review_status_text'] = FeedbackService::GetReviewStatusText($data['review_status'] ?? 'pending');
            $data['review_time_text'] = empty($data['review_time']) ? '' : date('Y-m-d H:i:s', $data['review_time']);

            if (!empty($data['review_admin_id'])) {
                $admin = \think\facade\Db::name('Admin')->where(['id' => $data['review_admin_id']])->field('id,username')->find();
                $data['review_admin_name'] = !empty($admin) ? $admin['username'] : '';
            } else {
                $data['review_admin_name'] = '';
            }

            $show_full = !empty($this->admin) && MuyingPrivacyService::CanViewSensitive($this->admin);
            $data = MuyingPrivacyService::MaskFeedbackRow($data, $show_full);

            $user = \think\facade\Db::name('User')->where(['id' => $data['user_id']])->field('id,nickname,mobile')->find();
            if (!empty($user)) {
                if (!$show_full && !empty($user['mobile'])) {
                    $user['mobile'] = MuyingPrivacyService::MaskPhone($user['mobile']);
                }
            }
            $data['user_info'] = $user;

            if ($show_full && !empty($this->admin)) {
                MuyingAuditLogService::LogSensitiveView($this->admin, MuyingAuditLogService::SCENE_SENSITIVE_VIEW, $id, '查看反馈详情含敏感信息');
            }
        }

        $assign = ['data' => $data, 'can_view_sensitive' => !empty($this->admin) && MuyingPrivacyService::CanViewSensitive($this->admin)];
        MyViewAssign($assign);
        return MyView();
    }

    public function Review()
    {
        $params = $this->data_request;
        $params['admin'] = $this->admin;
        return ApiService::ApiDataReturn(FeedbackService::FeedbackReview($params));
    }

    public function StatusUpdate()
    {
        $params = $this->data_request;
        $params['admin'] = $this->admin;
        return ApiService::ApiDataReturn(FeedbackService::FeedbackStatusUpdate($params));
    }

    public function Delete()
    {
        $params = $this->data_request;
        $params['admin'] = $this->admin;
        return ApiService::ApiDataReturn(FeedbackService::FeedbackDelete($params));
    }
}
