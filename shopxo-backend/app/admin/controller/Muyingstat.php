<?php
namespace app\admin\controller;

use app\admin\controller\Base;
use app\service\MuyingStatService;

class Muyingstat extends Base
{
    private static $VALID_RANGES = ['today', 'yesterday', 'last7', 'last30', 'custom'];

    public function Index()
    {
        $params = $this->data_request;

        $time_range = isset($params['time_range']) ? trim($params['time_range']) : 'today';
        if (!in_array($time_range, self::$VALID_RANGES)) {
            $time_range = 'today';
        }

        $start_date = '';
        $end_date = '';
        if ($time_range === 'custom') {
            $start_date = isset($params['start_date']) ? trim($params['start_date']) : '';
            $end_date = isset($params['end_date']) ? trim($params['end_date']) : '';
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
                $start_date = date('Y-m-d', strtotime('-7 days'));
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                $end_date = date('Y-m-d');
            }
        }

        $stat_params = [
            'time_range' => $time_range,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ];

        $dashboard = MuyingStatService::DashboardData($stat_params);

        $assign = [
            'dashboard'   => $dashboard,
            'time_range'  => $time_range,
            'start_date'  => $start_date,
            'end_date'    => $end_date,
            'registration_conversion_rate'   => MuyingStatService::RegistrationConversionRate(),
            'stage_profile_completion_rate'  => MuyingStatService::StageProfileCompletionRate(),
            'activity_signup_conversion_rate' => MuyingStatService::ActivitySignupConversionRate(),
            'product_payment_conversion_rate' => MuyingStatService::ProductPaymentConversionRate(),
            'repurchase_rate'                => MuyingStatService::RepurchaseRate(),
            'invite_referral_rate'           => MuyingStatService::InviteReferralRate(),
        ];

        MyViewAssign($assign);
        return MyView();
    }
}
