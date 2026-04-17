<?php
namespace app\service;

use think\facade\Db;
use app\service\ResourcesService;
use app\extend\muying\MuyingStage;

class FeedbackService
{
    public static function FeedbackWhere($params = [])
    {
        $where = [
            ['is_enable', '=', 1],
            ['is_delete_time', '=', 0],
        ];

        if (!empty($params['stage'])) {
            $where[] = ['stage', '=', MuyingStage::Normalize($params['stage'])];
        }

        return $where;
    }

    public static function FeedbackTotal($where)
    {
        return (int) Db::name('MuyingFeedback')->where($where)->count();
    }

    public static function FeedbackList($params)
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'sort_level desc, id desc' : trim($params['order_by']);
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        $data = Db::name('MuyingFeedback')->field($field)->where($where)->order($order_by)->limit($m, $n)->select()->toArray();

        if (!empty($data)) {
            foreach ($data as $k => &$v) {
                $v['data_index'] = $k + 1;
                if (!empty($v['avatar'])) {
                    $v['avatar'] = ResourcesService::AttachmentPathViewHandle($v['avatar']);
                }
                $v['stage_text'] = MuyingStage::getName(MuyingStage::Normalize($v['stage'] ?? ''));
                $v['add_time_text'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
            }
        }

        return DataReturn(MyLang('handle_success'), 0, $data);
    }
}
