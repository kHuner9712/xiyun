<?php
namespace app\service;

use think\facade\Db;
use think\facade\Log;

class DashboardService
{
    public static function Overview()
    {
        $today_start = strtotime(date('Y-m-d'));
        $yesterday_start = $today_start - 86400;

        $new_users_today = self::SafeCount(
            Db::name('User')->where('add_time', '>=', $today_start)->where('add_time', '<', $today_start + 86400)
        );
        $new_users_yesterday = self::SafeCount(
            Db::name('User')->where('add_time', '>=', $yesterday_start)->where('add_time', '<', $today_start)
        );

        $activity_signup_today = self::SafeCount(
            Db::name('ActivitySignup')
                ->where('add_time', '>=', $today_start)
                ->where('add_time', '<', $today_start + 86400)
                ->where('status', 'in', [0, 1])
        );

        $invite_first_order_today = self::SafeCount(
            Db::name('InviteReward')
                ->where('add_time', '>=', $today_start)
                ->where('add_time', '<', $today_start + 86400)
                ->where('trigger_event', 'first_order')
                ->where('status', 1)
                ->group('invitee_id')
        );

        $feedback_today = self::SafeCount(
            Db::name('MuyingFeedback')
                ->where('add_time', '>=', $today_start)
                ->where('add_time', '<', $today_start + 86400)
                ->where('is_delete_time', 0)
        );

        $feedback_pending = self::SafeCount(
            Db::name('MuyingFeedback')
                ->where('is_delete_time', 0)
                ->where('review_status', 'pending')
        );

        $total_users = self::SafeCount(Db::name('User'));
        $total_activities = self::SafeCount(
            Db::name('Activity')->where('is_enable', 1)->where('is_delete_time', 0)
        );
        $total_signups = self::SafeCount(
            Db::name('ActivitySignup')->where('status', 'in', [0, 1])
        );
        $total_invites = self::SafeCount(
            Db::name('InviteReward')->where('status', 1)->where('trigger_event', 'first_order')->group('invitee_id')
        );

        $today_orders = self::SafeCount(
            Db::name('Order')->where('add_time', '>=', $today_start)->where('add_time', '<', $today_start + 86400)
        );
        $today_sales = self::SafeSum(
            Db::name('Order')
                ->where('add_time', '>=', $today_start)
                ->where('add_time', '<', $today_start + 86400)
                ->where('status', 4),
            'total_price'
        );
        $total_orders = self::SafeCount(Db::name('Order')->where('status', 4));
        $total_sales = self::SafeSum(Db::name('Order')->where('status', 4), 'total_price');

        $stage_distribution = [];
        $stage_list = \app\extend\muying\MuyingStage::getList();
        foreach ($stage_list as $item) {
            if ($item['value'] === 'all') continue;
            $count = self::SafeCount(Db::name('User')->where('current_stage', $item['value']));
            $stage_distribution[] = ['stage' => $item['value'], 'name' => $item['name'], 'count' => $count];
        }

        $now = time();
        $thirty_days_later = $now + 86400 * 30;
        $due_soon_count = self::SafeCount(
            Db::name('User')
                ->where('current_stage', 'pregnancy')
                ->where('due_date', '>', 0)
                ->where('due_date', '<=', $thirty_days_later)
        );

        $baby_age_buckets = self::GetBabyAgeBuckets();

        $activity_signup_density = self::CalcActivitySignupDensity($today_start);
        $invite_register_ratio = self::CalcInviteRegisterRatio($today_start);
        $repurchase_rate = self::CalcRepurchaseRate();

        return DataReturn(MyLang('handle_success'), 0, [
            'today' => [
                'new_users'        => $new_users_today,
                'activity_signups' => $activity_signup_today,
                'invite_first_order' => $invite_first_order_today,
                'feedback_count'   => $feedback_today,
                'orders'           => $today_orders,
                'sales'            => round(floatval($today_sales), 2),
            ],
            'yesterday' => [
                'new_users' => $new_users_yesterday,
            ],
            'total' => [
                'users'      => $total_users,
                'activities' => $total_activities,
                'signups'    => $total_signups,
                'invites'    => $total_invites,
                'orders'     => $total_orders,
                'sales'      => round(floatval($total_sales), 2),
            ],
            'stage_distribution' => $stage_distribution,
            'due_soon_count'     => $due_soon_count,
            'baby_age_buckets'   => $baby_age_buckets,
            'feedback_pending'   => $feedback_pending,
            'conversion'         => [
                'activity_signup_density' => $activity_signup_density,
                'invite_register_ratio'   => $invite_register_ratio,
                'repurchase_rate'         => $repurchase_rate,
            ],
        ]);
    }

    public static function Trend($params = [])
    {
        $days = isset($params['days']) ? intval($params['days']) : 7;
        if ($days <= 0 || $days > 30) {
            $days = 7;
        }
        $metric_key = isset($params['metric_key']) ? trim($params['metric_key']) : '';

        $start_date = date('Y-m-d', strtotime("-{$days} days"));

        if (!empty($metric_key)) {
            return DataReturn(MyLang('handle_success'), 0, self::GetTrendByMetric($metric_key, $start_date));
        }

        $metrics = ['new_users', 'activity_signups', 'orders', 'sales', 'feedback_count',
                    'activity_signup_density', 'invite_register_ratio', 'repurchase_rate', 'stage_completion'];
        $result = [];
        foreach ($metrics as $key) {
            $result[$key] = self::GetTrendByMetric($key, $start_date);
        }

        return DataReturn(MyLang('handle_success'), 0, $result);
    }

    private static function GetTrendByMetric($metric_key, $start_date)
    {
        $data = Db::name('MuyingStatSnapshot')->where([
            ['metric_key', '=', $metric_key],
            ['stat_date', '>=', $start_date],
        ])->order('stat_date asc')->field('stat_date,metric_value')->select()->toArray();

        $legacy_map = [
            'activity_signup_density' => 'signup_conversion',
            'invite_register_ratio'  => 'invite_conversion',
        ];
        if (isset($legacy_map[$metric_key]) && empty($data)) {
            $data = Db::name('MuyingStatSnapshot')->where([
                ['metric_key', '=', $legacy_map[$metric_key]],
                ['stat_date', '>=', $start_date],
            ])->order('stat_date asc')->field('stat_date,metric_value')->select()->toArray();
        }

        $items = [];
        foreach ($data as $row) {
            $items[] = [
                'date'  => $row['stat_date'],
                'value' => floatval($row['metric_value']),
            ];
        }

        return $items;
    }

    public static function GenerateDailySnapshot()
    {
        $today = date('Y-m-d');
        $yesterday_start = strtotime("-1 day");
        $yesterday_end = strtotime("today");

        $metrics = [];

        $total_new = self::SafeCount(
            Db::name('User')->where('add_time', '>=', $yesterday_start)->where('add_time', '<', $yesterday_end)
        );
        $metrics['new_users'] = $total_new;

        $metrics['activity_signups'] = self::SafeCount(
            Db::name('ActivitySignup')->where('add_time', '>=', $yesterday_start)->where('add_time', '<', $yesterday_end)
        );

        $metrics['orders'] = self::SafeCount(
            Db::name('Order')->where('add_time', '>=', $yesterday_start)->where('add_time', '<', $yesterday_end)
        );

        $metrics['sales'] = self::SafeSum(
            Db::name('Order')
                ->where('add_time', '>=', $yesterday_start)
                ->where('add_time', '<', $yesterday_end)
                ->where('status', 4),
            'total_price'
        );

        $metrics['feedback_count'] = self::SafeCount(
            Db::name('MuyingFeedback')
                ->where('add_time', '>=', $yesterday_start)
                ->where('add_time', '<', $yesterday_end)
                ->where('is_delete_time', 0)
        );

        $with_stage = self::SafeCount(
            Db::name('User')
                ->where('add_time', '>=', $yesterday_start)
                ->where('add_time', '<', $yesterday_end)
                ->where('current_stage', '<>', '')
        );
        $metrics['stage_completion'] = $total_new > 0 ? round($with_stage / $total_new * 100, 2) : 0;

        $metrics['activity_signup_density'] = self::CalcActivitySignupDensity($yesterday_start);
        $metrics['invite_register_ratio'] = self::CalcInviteRegisterRatio($yesterday_start);
        $metrics['repurchase_rate'] = self::CalcRepurchaseRate();

        foreach ($metrics as $key => $value) {
            $exists = Db::name('MuyingStatSnapshot')
                ->where('stat_date', $today)
                ->where('metric_key', $key)
                ->count();
            if ($exists > 0) {
                Db::name('MuyingStatSnapshot')
                    ->where('stat_date', $today)
                    ->where('metric_key', $key)
                    ->update(['metric_value' => $value, 'add_time' => time()]);
            } else {
                Db::name('MuyingStatSnapshot')->insert([
                    'stat_date'    => $today,
                    'metric_key'   => $key,
                    'metric_value' => $value,
                    'add_time'     => time(),
                ]);
            }
        }

        Log::info('仪表盘每日快照生成完成 date=' . $today . ' metrics=' . count($metrics));
        return DataReturn('快照生成完成', 0, ['date' => $today, 'metrics' => count($metrics)]);
    }

    private static function GetBabyAgeBuckets()
    {
        $now = time();
        $three_months = $now - 86400 * 90;
        $six_months = $now - 86400 * 180;
        $twelve_months = $now - 86400 * 365;

        $bucket_0_3 = self::SafeCount(
            Db::name('User')->where('current_stage', 'postpartum')->where('baby_birthday', '>', 0)->where('baby_birthday', '>=', $three_months)
        );
        $bucket_3_6 = self::SafeCount(
            Db::name('User')->where('current_stage', 'postpartum')->where('baby_birthday', '>', 0)->where('baby_birthday', '<', $three_months)->where('baby_birthday', '>=', $six_months)
        );
        $bucket_6_12 = self::SafeCount(
            Db::name('User')->where('current_stage', 'postpartum')->where('baby_birthday', '>', 0)->where('baby_birthday', '<', $six_months)->where('baby_birthday', '>=', $twelve_months)
        );

        return [
            ['key' => '0_3', 'name' => '0-3月', 'count' => $bucket_0_3],
            ['key' => '3_6', 'name' => '3-6月', 'count' => $bucket_3_6],
            ['key' => '6_12', 'name' => '6-12月', 'count' => $bucket_6_12],
        ];
    }

    private static function CalcActivitySignupDensity($day_start)
    {
        $day_end = $day_start + 86400;
        $signup_count = self::SafeCount(
            Db::name('ActivitySignup')->where('add_time', '>=', $day_start)->where('add_time', '<', $day_end)
        );
        $active_activities = self::SafeCount(
            Db::name('Activity')->where('is_enable', 1)->where('is_delete_time', 0)
        );
        if ($active_activities <= 0) {
            return 0;
        }
        return round($signup_count / $active_activities, 2);
    }

    private static function CalcInviteRegisterRatio($day_start)
    {
        $day_end = $day_start + 86400;
        $invited_user_count = self::SafeCount(
            Db::name('InviteReward')
                ->where('add_time', '>=', $day_start)
                ->where('add_time', '<', $day_end)
                ->where('trigger_event', 'register')
                ->where('status', 1)
                ->group('invitee_id')
        );
        $new_users = self::SafeCount(
            Db::name('User')->where('add_time', '>=', $day_start)->where('add_time', '<', $day_end)
        );
        if ($new_users <= 0) {
            return 0;
        }
        return round($invited_user_count / $new_users * 100, 2);
    }

    private static function CalcRepurchaseRate()
    {
        try {
            $repeat_buyers = Db::query(
                "SELECT COUNT(*) as cnt FROM (
                    SELECT user_id FROM `sxo_order`
                    WHERE `status` = 4 AND `user_id` > 0
                    GROUP BY `user_id`
                    HAVING COUNT(*) > 1
                ) t"
            );
            $repeat_count = !empty($repeat_buyers) ? intval($repeat_buyers[0]['cnt']) : 0;

            $total_buyers = Db::query(
                "SELECT COUNT(*) as cnt FROM (
                    SELECT user_id FROM `sxo_order`
                    WHERE `status` = 4 AND `user_id` > 0
                    GROUP BY `user_id`
                ) t"
            );
            $total_count = !empty($total_buyers) ? intval($total_buyers[0]['cnt']) : 0;

            if ($total_count <= 0) {
                return 0;
            }
            return round($repeat_count / $total_count * 100, 2);
        } catch (\Exception $e) {
            Log::error('[Dashboard] 复购率计算失败: ' . $e->getMessage());
            return 0;
        }
    }

    private static function SafeCount($query)
    {
        try {
            return $query->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private static function SafeSum($query, $field)
    {
        try {
            $val = $query->sum($field);
            return $val ?: 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
