<?php
namespace app\service;

use think\facade\Db;

class MuyingStatService
{
    private static function ResolveTimeRange($params = [])
    {
        $range = isset($params['time_range']) ? trim($params['time_range']) : 'today';
        $start = strtotime('today');
        $end = time();

        switch ($range) {
            case 'yesterday':
                $start = strtotime('yesterday');
                $end = strtotime('today') - 1;
                break;
            case 'last7':
                $start = strtotime('-7 days');
                break;
            case 'last30':
                $start = strtotime('-30 days');
                break;
            case 'custom':
                $s = isset($params['start_date']) ? trim($params['start_date']) : '';
                $e = isset($params['end_date']) ? trim($params['end_date']) : '';
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $e)) {
                    $start = strtotime($s);
                    $end = strtotime($e . ' 23:59:59');
                    if ($start === false || $end === false || $start > $end) {
                        $start = strtotime('today');
                        $end = time();
                    }
                }
                break;
            case 'today':
            default:
                break;
        }

        return ['start' => $start, 'end' => $end, 'range' => $range];
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

    public static function TransactionMetrics($params = [])
    {
        $tr = self::ResolveTimeRange($params);

        $gmv = self::SafeSum(
            Db::name('Order')
                ->where('pay_status', 1)
                ->where('pay_time', '>=', $tr['start'])
                ->where('pay_time', '<=', $tr['end']),
            'pay_price'
        );

        $paid_orders = self::SafeCount(
            Db::name('Order')
                ->where('pay_status', 1)
                ->where('pay_time', '>=', $tr['start'])
                ->where('pay_time', '<=', $tr['end'])
        );

        $pending_shipment = self::SafeCount(
            Db::name('Order')
                ->where('status', 2)
                ->where('add_time', '>=', $tr['start'])
                ->where('add_time', '<=', $tr['end'])
        );

        $aftersale_orders = self::SafeCount(
            Db::name('OrderAftersale')
                ->where('apply_time', '>=', $tr['start'])
                ->where('apply_time', '<=', $tr['end'])
        );

        $total_orders = self::SafeCount(
            Db::name('Order')
                ->where('add_time', '>=', $tr['start'])
                ->where('add_time', '<=', $tr['end'])
        );

        $payment_rate = ($total_orders > 0) ? round(($paid_orders / $total_orders) * 100, 2) : 0;

        $repurchase_rate = self::CalcRepurchaseRate($tr);

        return [
            'gmv' => [
                'value' => round($gmv, 2),
                'unit' => '元',
                'desc' => '已支付订单的 pay_price 之和（按支付时间筛选）',
            ],
            'paid_orders' => [
                'value' => $paid_orders,
                'unit' => '单',
                'desc' => 'pay_status=1 的订单数（按支付时间筛选）',
            ],
            'pending_shipment' => [
                'value' => $pending_shipment,
                'unit' => '单',
                'desc' => 'status=2（已支付待发货）的订单数',
            ],
            'aftersale_orders' => [
                'value' => $aftersale_orders,
                'unit' => '单',
                'desc' => '售后申请数（按申请时间筛选）',
            ],
            'payment_rate' => [
                'value' => $payment_rate,
                'unit' => '%',
                'desc' => '已支付订单数 / 总订单数 × 100（按下单时间筛选）',
            ],
            'repurchase_rate' => [
                'value' => $repurchase_rate,
                'unit' => '%',
                'desc' => '在筛选期内下单≥2次的用户数 / 有订单的用户数 × 100',
            ],
        ];
    }

    public static function UserMetrics($params = [])
    {
        $tr = self::ResolveTimeRange($params);

        $new_users = self::SafeCount(
            Db::name('User')
                ->where('add_time', '>=', $tr['start'])
                ->where('add_time', '<=', $tr['end'])
        );

        $total_users = self::SafeCount(Db::name('User'));

        $users_with_stage = self::SafeCount(
            Db::name('User')->where('current_stage', '<>', '')
        );

        $profile_rate = ($total_users > 0) ? round(($users_with_stage / $total_users) * 100, 2) : 0;

        $stage_prepare = self::SafeCount(Db::name('User')->where('current_stage', 'prepare'));
        $stage_pregnancy = self::SafeCount(Db::name('User')->where('current_stage', 'pregnancy'));
        $stage_postpartum = self::SafeCount(Db::name('User')->where('current_stage', 'postpartum'));

        return [
            'new_users' => [
                'value' => $new_users,
                'unit' => '人',
                'desc' => '注册时间在筛选范围内的用户数',
            ],
            'total_users' => [
                'value' => $total_users,
                'unit' => '人',
                'desc' => '全量用户数（不受时间筛选影响）',
            ],
            'users_with_stage' => [
                'value' => $users_with_stage,
                'unit' => '人',
                'desc' => 'current_stage 不为空的用户数',
            ],
            'profile_rate' => [
                'value' => $profile_rate,
                'unit' => '%',
                'desc' => '已填写阶段用户数 / 总用户数 × 100',
            ],
            'stage_prepare' => [
                'value' => $stage_prepare,
                'unit' => '人',
                'desc' => 'current_stage=prepare（备孕）的用户数',
            ],
            'stage_pregnancy' => [
                'value' => $stage_pregnancy,
                'unit' => '人',
                'desc' => 'current_stage=pregnancy（孕期）的用户数',
            ],
            'stage_postpartum' => [
                'value' => $stage_postpartum,
                'unit' => '人',
                'desc' => 'current_stage=postpartum（产后）的用户数',
            ],
        ];
    }

    public static function ActivityMetrics($params = [])
    {
        $tr = self::ResolveTimeRange($params);

        $activity_views = self::SafeSum(
            Db::name('Activity')
                ->where('is_delete_time', 0)
                ->where('is_enable', 1),
            'access_count'
        );

        $signups = self::SafeCount(
            Db::name('ActivitySignup')
                ->where('is_delete_time', 0)
                ->where('add_time', '>=', $tr['start'])
                ->where('add_time', '<=', $tr['end'])
        );

        $waitlist = self::SafeCount(
            Db::name('ActivitySignup')
                ->where('is_delete_time', 0)
                ->where('status', 0)
                ->where('add_time', '>=', $tr['start'])
                ->where('add_time', '<=', $tr['end'])
        );

        $checkins = self::SafeCount(
            Db::name('ActivitySignup')
                ->where('is_delete_time', 0)
                ->where('checkin_status', 1)
                ->where('add_time', '>=', $tr['start'])
                ->where('add_time', '<=', $tr['end'])
        );

        $attendance_rate = ($signups > 0) ? round(($checkins / $signups) * 100, 2) : 0;
        $signup_rate = ($activity_views > 0) ? round(($signups / $activity_views) * 100, 2) : 0;

        return [
            'activity_views' => [
                'value' => $activity_views,
                'unit' => '次',
                'desc' => '所有启用活动的 access_count 之和（累计值，不受时间筛选影响）',
            ],
            'signups' => [
                'value' => $signups,
                'unit' => '人',
                'desc' => '报名记录数（按报名时间筛选）',
            ],
            'waitlist' => [
                'value' => $waitlist,
                'unit' => '人',
                'desc' => 'status=0（待确认）的报名数',
            ],
            'checkins' => [
                'value' => $checkins,
                'unit' => '人',
                'desc' => 'checkin_status=1（已签到/已核销）的报名数',
            ],
            'attendance_rate' => [
                'value' => $attendance_rate,
                'unit' => '%',
                'desc' => '已签到数 / 报名数 × 100',
            ],
            'signup_rate' => [
                'value' => $signup_rate,
                'unit' => '%',
                'desc' => '报名数 / 活动总访问量 × 100',
            ],
        ];
    }

    public static function InviteMetrics($params = [])
    {
        $tr = self::ResolveTimeRange($params);

        $invite_registrations = self::SafeCount(
            Db::name('InviteReward')
                ->where('trigger_event', 'register')
                ->where('add_time', '>=', $tr['start'])
                ->where('add_time', '<=', $tr['end'])
        );

        $first_order_success = self::SafeCount(
            Db::name('InviteReward')
                ->where('trigger_event', 'first_order')
                ->where('add_time', '>=', $tr['start'])
                ->where('add_time', '<=', $tr['end'])
        );

        $total_users = self::SafeCount(Db::name('User'));
        $total_invited = self::SafeCount(
            Db::name('InviteReward')
                ->where('trigger_event', 'register')
                ->group('invitee_id')
        );
        $invite_rate = ($total_users > 0) ? round(($total_invited / $total_users) * 100, 2) : 0;

        return [
            'invite_registrations' => [
                'value' => $invite_registrations,
                'unit' => '人',
                'desc' => 'trigger_event=register 的邀请奖励记录数',
            ],
            'first_order_success' => [
                'value' => $first_order_success,
                'unit' => '人',
                'desc' => 'trigger_event=first_order 的邀请奖励记录数',
            ],
            'invite_rate' => [
                'value' => $invite_rate,
                'unit' => '%',
                'desc' => '被邀请注册用户数 / 总用户数 × 100（累计值）',
            ],
        ];
    }

    public static function FeedbackMetrics($params = [])
    {
        $tr = self::ResolveTimeRange($params);

        $pending_review = self::SafeCount(
            Db::name('MuyingFeedback')
                ->where('review_status', 'pending')
                ->where('is_delete_time', 0)
        );

        $approved = self::SafeCount(
            Db::name('MuyingFeedback')
                ->where('review_status', 'approved')
                ->where('is_delete_time', 0)
                ->where('add_time', '>=', $tr['start'])
                ->where('add_time', '<=', $tr['end'])
        );

        $sensitive_intercepts = 0;
        try {
            $sensitive_intercepts = Db::name('MuyingSensitiveLog')
                ->where('add_time', '>=', $tr['start'])
                ->where('add_time', '<=', $tr['end'])
                ->count();
        } catch (\Exception $e) {
        }

        return [
            'pending_review' => [
                'value' => $pending_review,
                'unit' => '条',
                'desc' => 'review_status=pending 且未删除的反馈数（不受时间筛选影响）',
            ],
            'approved' => [
                'value' => $approved,
                'unit' => '条',
                'desc' => 'review_status=approved 的反馈数（按创建时间筛选）',
            ],
            'sensitive_intercepts' => [
                'value' => $sensitive_intercepts,
                'unit' => '条',
                'desc' => '敏感词拦截记录数（如日志表不存在则为 0）',
            ],
        ];
    }

    public static function ProductMetrics($params = [])
    {
        $listed_products = self::SafeCount(
            Db::name('Goods')
                ->where('is_shelves', 1)
                ->where('is_delete_time', 0)
        );

        $low_inventory = self::SafeCount(
            Db::name('Goods')
                ->where('is_shelves', 1)
                ->where('is_delete_time', 0)
                ->where('inventory', '<=', 10)
        );

        $high_risk_pending = 0;
        try {
            $high_risk_pending = Db::name('Goods')
                ->where('is_shelves', 1)
                ->where('is_delete_time', 0)
                ->where('stage', '<>', '')
                ->where(function ($query) {
                    $query->whereNull('approval_number')->whereOr('approval_number', '');
                })
                ->count();
        } catch (\Exception $e) {
        }

        $maternal_products = 0;
        try {
            $maternal_products = Db::name('Goods')
                ->where('is_shelves', 1)
                ->where('is_delete_time', 0)
                ->where('stage', '<>', '')
                ->count();
        } catch (\Exception $e) {
        }

        return [
            'listed_products' => [
                'value' => $listed_products,
                'unit' => '件',
                'desc' => 'is_shelves=1 且未删除的商品数',
            ],
            'low_inventory' => [
                'value' => $low_inventory,
                'unit' => '件',
                'desc' => '库存 ≤ 10 的上架商品数',
            ],
            'high_risk_pending' => [
                'value' => $high_risk_pending,
                'desc' => '有阶段标记但缺少批准文号的上架商品数',
            ],
            'maternal_products' => [
                'value' => $maternal_products,
                'unit' => '件',
                'desc' => 'stage 字段不为空的上架商品数（母婴推荐商品）',
            ],
        ];
    }

    private static function CalcRepurchaseRate($tr)
    {
        try {
            $order_users = Db::name('Order')
                ->where('add_time', '>=', $tr['start'])
                ->where('add_time', '<=', $tr['end'])
                ->group('user_id')
                ->column('COUNT(*) as cnt', 'user_id');

            $users_with_orders = count($order_users);
            $users_with_2plus = count(array_filter($order_users, function ($cnt) {
                return $cnt >= 2;
            }));

            return ($users_with_orders > 0) ? round(($users_with_2plus / $users_with_orders) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public static function DashboardData($params = [])
    {
        return [
            'time_range' => self::ResolveTimeRange($params),
            'transaction' => self::TransactionMetrics($params),
            'user' => self::UserMetrics($params),
            'activity' => self::ActivityMetrics($params),
            'invite' => self::InviteMetrics($params),
            'feedback' => self::FeedbackMetrics($params),
            'product' => self::ProductMetrics($params),
        ];
    }

    public static function RegistrationConversionRate()
    {
        $total_users = self::SafeCount(Db::name('User'));
        $users_with_stage = self::SafeCount(Db::name('User')->where('current_stage', '<>', ''));
        $value = ($total_users > 0) ? round(($users_with_stage / $total_users) * 100, 2) : 0;
        return [
            'value' => $value,
            'desc' => '已填写阶段的用户数 / 总用户数 × 100',
        ];
    }

    public static function StageProfileCompletionRate()
    {
        return self::RegistrationConversionRate();
    }

    public static function ActivitySignupConversionRate()
    {
        $total_activity_views = self::SafeSum(Db::name('Activity'), 'access_count');
        $total_signups = self::SafeCount(Db::name('ActivitySignup')->where('is_delete_time', 0));
        if ($total_activity_views > 0) {
            $value = round(($total_signups / $total_activity_views) * 100, 2);
        } else {
            $activity_count = self::SafeCount(Db::name('Activity')->where('is_delete_time', 0));
            $value = ($activity_count > 0) ? round(($total_signups / $activity_count) * 100, 2) : 0;
        }
        return [
            'value' => $value,
            'desc' => '总报名数 / 活动总访问量 × 100（无访问量时按活动数计算）',
        ];
    }

    public static function ProductPaymentConversionRate()
    {
        $total_orders = self::SafeCount(Db::name('Order'));
        $paid_orders = self::SafeCount(Db::name('Order')->where('status', '>=', 2)->where('status', '<=', 5));
        $value = ($total_orders > 0) ? round(($paid_orders / $total_orders) * 100, 2) : 0;
        return [
            'value' => $value,
            'desc' => '已支付订单数 / 总订单数 × 100',
        ];
    }

    public static function RepurchaseRate()
    {
        try {
            $order_users = Db::name('Order')->group('user_id')->column('COUNT(*) as cnt', 'user_id');
            $users_with_orders = count($order_users);
            $users_with_2plus = count(array_filter($order_users, function ($cnt) {
                return $cnt >= 2;
            }));
            $value = ($users_with_orders > 0) ? round(($users_with_2plus / $users_with_orders) * 100, 2) : 0;
        } catch (\Exception $e) {
            $value = 0;
        }
        return [
            'value' => $value,
            'desc' => '下单≥2次的用户数 / 有订单的用户数 × 100',
        ];
    }

    public static function InviteReferralRate()
    {
        $total_users = self::SafeCount(Db::name('User'));
        $users_with_inviter = self::SafeCount(Db::name('InviteReward')->group('invitee_id'));
        $value = ($total_users > 0) ? round(($users_with_inviter / $total_users) * 100, 2) : 0;
        return [
            'value' => $value,
            'desc' => '被邀请注册的用户数 / 总用户数 × 100',
        ];
    }
}
