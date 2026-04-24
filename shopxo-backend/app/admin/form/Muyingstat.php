<?php
namespace app\admin\form;

class Muyingstat
{
    public $condition_base = [];

    public function Run($params = [])
    {
        return [
            'base' => [
                'key_field'     => 'id',
                'is_search'     => 0,
                'is_middle'     => 0,
            ],
            'form' => [
                [
                    'label'     => '指标名称',
                    'view_type' => 'field',
                    'view_key'  => 'metric_name',
                    'is_sort'   => 0,
                ],
                [
                    'label'     => '指标值',
                    'view_type' => 'field',
                    'view_key'  => 'metric_value',
                    'is_sort'   => 0,
                ],
            ],
            'data' => [
                'table_name' => 'User',
                'select_field' => 'id',
                'detail_where' => [['id', '=', 0]],
            ],
        ];
    }
}
