<?php
namespace app\api\controller;

use app\service\ApiService;
use app\service\UserTagService;

class Muyinguser extends Common
{
    private static $FEATURE_FLAG_KEY = 'feature_membership_enabled';

    public function __construct()
    {
        parent::__construct();
        $this->CheckFeatureEnabled(self::$FEATURE_FLAG_KEY);
    }

    public function TagList()
    {
        $this->IsLogin();
        $data = UserTagService::TagList(['is_enable' => 1, 'n' => 100]);
        return ApiService::ApiDataReturn($data);
    }

    public function UserTags()
    {
        $this->IsLogin();
        $tags = UserTagService::UserTags($this->user['id']);
        return ApiService::ApiDataReturn(DataReturn(MyLang('handle_success'), 0, $tags));
    }

    public function UserTagSet()
    {
        $this->IsLogin();
        $params = $this->data_request;
        $params['user_id'] = $this->user['id'];
        return ApiService::ApiDataReturn(UserTagService::UserTagSet($params));
    }
}
