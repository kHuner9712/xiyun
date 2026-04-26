<?php
namespace app\admin\form;

use app\extend\muying\MuyingStage;

class Feedback
{
    public $condition_base = [
        ['is_delete_time', '=', 0],
    ];

    public function Run($params = [])
    {
        return [
            'base' => [
                'key_field'     => 'id',
                'status_field'  => 'is_enable',
                'is_search'     => 1,
                'is_delete'     => AdminIsPower('feedback', 'delete') ? 1 : 0,
                'is_middle'     => 0,
            ],
            'form' => [
                [
                    'view_type'         => 'checkbox',
                    'is_checked'        => 0,
                    'checked_text'      => MyLang('reverse_select_title'),
                    'not_checked_text'  => MyLang('select_all_title'),
                    'align'             => 'center',
                    'width'             => 80,
                ],
                [
                    'label'         => 'ID',
                    'view_type'     => 'field',
                    'view_key'      => 'id',
                    'width'         => 80,
                    'is_copy'       => 1,
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => '=',
                    ],
                ],
                [
                    'label'         => '用户昵称',
                    'view_type'     => 'field',
                    'view_key'      => 'nickname',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'             => '反馈类型',
                    'view_type'         => 'field',
                    'view_key'          => 'type_text',
                    'is_sort'           => 1,
                    'width'             => 120,
                    'search_config'     => [
                        'form_type'         => 'select',
                        'form_name'         => 'type',
                        'where_type'        => 'in',
                        'data'              => $this->TypeList(),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'             => '阶段',
                    'view_type'         => 'field',
                    'view_key'          => 'stage_text',
                    'is_sort'           => 1,
                    'width'             => 100,
                    'search_config'     => [
                        'form_type'         => 'select',
                        'form_name'         => 'stage',
                        'where_type'        => 'in',
                        'data'              => $this->StageList(),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '反馈内容',
                    'view_type'     => 'field',
                    'view_key'      => 'content',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'awd',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'              => '审核状态',
                    'view_type'          => 'field',
                    'view_key'           => 'review_status_text',
                    'align'              => 'center',
                    'is_sort'            => 1,
                    'width'              => 100,
                    'search_config'      => [
                        'form_type'         => 'select',
                        'form_name'         => 'review_status',
                        'where_type'        => 'in',
                        'data'              => $this->ReviewStatusList(),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'              => '展示状态',
                    'view_type'          => 'status',
                    'view_key'           => 'is_enable',
                    'post_url'           => MyUrl('admin/feedback/statusupdate'),
                    'is_form_su'         => 1,
                    'align'              => 'center',
                    'is_sort'            => 1,
                    'width'              => 130,
                    'params_where_name'  => 'is_enable',
                    'search_config'      => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_is_text_list'),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '审核时间',
                    'view_type'     => 'field',
                    'view_key'      => 'review_time_text',
                    'is_sort'       => 1,
                    'width'         => 170,
                ],
                [
                    'label'         => '提交时间',
                    'view_type'     => 'field',
                    'view_key'      => 'add_time_text',
                    'is_sort'       => 1,
                    'width'         => 170,
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => MyLang('operate_title'),
                    'view_type'     => 'operate',
                    'view_key'      => 'feedback/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                ],
            ],
            'data'  => [
                'table_name'    => 'MuyingFeedback',
                'data_handle'   => 'app\service\FeedbackService::FeedbackAdminListHandle',
            ],
        ];
    }

    public function StageList()
    {
        $list = MuyingStage::getList();
        $result = [];
        foreach ($list as $value => $name) {
            $result[] = ['value' => $value, 'name' => $name];
        }
        return $result;
    }

    public function ReviewStatusList()
    {
        return [
            ['value' => 'pending', 'name' => '待审核'],
            ['value' => 'approved', 'name' => '已通过'],
            ['value' => 'rejected', 'name' => '已驳回'],
        ];
    }

    public function TypeList()
    {
        return [
            ['value' => 'feedback', 'name' => '问题反馈'],
            ['value' => 'suggestion', 'name' => '功能建议'],
            ['value' => 'complaint', 'name' => '投诉'],
            ['value' => 'privacy_request', 'name' => '数据删除/隐私请求'],
        ];
    }
}
