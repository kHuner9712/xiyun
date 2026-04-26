<?php
namespace app\admin\controller;

use app\admin\controller\Base;
use app\service\ApiService;
use app\service\MuyingDataAnonymizeService;

class Muyingprivacy extends Base
{
    public function Index()
    {
        MyViewAssign([]);
        return MyView();
    }

    public function Search()
    {
        $params = $this->data_request;
        return ApiService::ApiDataReturn(MuyingDataAnonymizeService::SearchUser($params));
    }

    public function Anonymize()
    {
        if (!$this->data_request || empty($this->data_request['user_id'])) {
            return ApiService::ApiDataReturn(DataReturn('用户ID不能为空', -1));
        }

        $confirm = isset($this->data_request['confirm']) ? intval($this->data_request['confirm']) : 0;
        if ($confirm !== 1) {
            return ApiService::ApiDataReturn(DataReturn('请确认执行匿名化操作', -1));
        }

        return ApiService::ApiDataReturn(MuyingDataAnonymizeService::AnonymizeUser($this->data_request));
    }
}
