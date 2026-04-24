<?php
namespace app\service;

use app\extend\muying\MuyingStage;

class MuyingComplianceService
{
    const QUALIFICATION_ICP_COMMERCIAL = 'qualification_icp_commercial';
    const QUALIFICATION_EDI = 'qualification_edi';
    const QUALIFICATION_MEDICAL = 'qualification_medical';
    const QUALIFICATION_LIVE = 'qualification_live';
    const QUALIFICATION_PAYMENT = 'qualification_payment';

    private static $QUALIFICATION_DEFAULTS = [
        self::QUALIFICATION_ICP_COMMERCIAL => 0,
        self::QUALIFICATION_EDI => 0,
        self::QUALIFICATION_MEDICAL => 0,
        self::QUALIFICATION_LIVE => 0,
        self::QUALIFICATION_PAYMENT => 0,
    ];

    private static $PHASE_ONE_ALLOWED_PLUGINS = [
        'brand', 'coupon', 'delivery', 'express', 'points', 'signin',
    ];

    private static $PHASE_ONE_BLOCKED_PLUGINS = [
        'distribution', 'wallet', 'coin', 'shop', 'realstore',
        'ask', 'blog', 'membershiplevelvip', 'seckill', 'video',
        'hospital', 'giftcard', 'givegift', 'complaint', 'invoice',
        'certificate', 'scanpay', 'weixinliveplayer', 'intellectstools',
    ];

    private static $PERMANENTLY_BLOCKED_PLUGINS = [
        'excellentbuyreturntocash', 'exchangerate', 'goodscompare',
        'orderfeed', 'ordergoodsform', 'orderresources',
        'antifakecode', 'form', 'binding', 'label',
    ];

    private static $FEATURE_FLAG_PLUGIN_MAP = [
        'feature_shop_enabled'              => 'shop',
        'feature_realstore_enabled'         => 'realstore',
        'feature_distribution_enabled'      => 'distribution',
        'feature_wallet_enabled'            => 'wallet',
        'feature_coin_enabled'              => 'coin',
        'feature_ugc_enabled'               => ['ask', 'blog'],
        'feature_membership_enabled'        => 'membershiplevelvip',
        'feature_seckill_enabled'           => 'seckill',
        'feature_giftcard_enabled'          => 'giftcard',
        'feature_givegift_enabled'          => 'givegift',
        'feature_video_enabled'             => 'video',
        'feature_hospital_enabled'          => 'hospital',
        'feature_complaint_enabled'         => 'complaint',
        'feature_invoice_enabled'           => 'invoice',
        'feature_certificate_enabled'       => 'certificate',
        'feature_scanpay_enabled'           => 'scanpay',
        'feature_live_enabled'              => 'weixinliveplayer',
        'feature_intellectstools_enabled'   => 'intellectstools',
    ];

    private static $QUALIFICATION_REQUIRED_MAP = [
        'shop'              => [self::QUALIFICATION_ICP_COMMERCIAL, self::QUALIFICATION_EDI],
        'realstore'         => [self::QUALIFICATION_ICP_COMMERCIAL, self::QUALIFICATION_EDI],
        'distribution'      => [self::QUALIFICATION_ICP_COMMERCIAL],
        'wallet'            => [self::QUALIFICATION_PAYMENT],
        'coin'              => [self::QUALIFICATION_PAYMENT],
        'ask'               => [self::QUALIFICATION_ICP_COMMERCIAL],
        'blog'              => [self::QUALIFICATION_ICP_COMMERCIAL],
        'membershiplevelvip'=> [self::QUALIFICATION_ICP_COMMERCIAL],
        'seckill'           => [self::QUALIFICATION_ICP_COMMERCIAL],
        'giftcard'          => [self::QUALIFICATION_PAYMENT],
        'givegift'          => [self::QUALIFICATION_PAYMENT],
        'video'             => [self::QUALIFICATION_LIVE],
        'hospital'          => [self::QUALIFICATION_MEDICAL],
        'complaint'         => [self::QUALIFICATION_ICP_COMMERCIAL],
        'invoice'           => [self::QUALIFICATION_ICP_COMMERCIAL],
        'certificate'       => [self::QUALIFICATION_ICP_COMMERCIAL],
        'scanpay'           => [self::QUALIFICATION_PAYMENT],
        'weixinliveplayer'  => [self::QUALIFICATION_LIVE],
        'intellectstools'   => [self::QUALIFICATION_ICP_COMMERCIAL],
    ];

    public static function GetQualificationValue($key)
    {
        $default = isset(self::$QUALIFICATION_DEFAULTS[$key]) ? self::$QUALIFICATION_DEFAULTS[$key] : 0;
        return intval(MyC($key, $default));
    }

    public static function GetAllQualifications()
    {
        $result = [];
        foreach (self::$QUALIFICATION_DEFAULTS as $key => $default) {
            $result[$key] = self::GetQualificationValue($key);
        }
        return $result;
    }

    public static function IsPluginAllowed($pluginsname)
    {
        $name = strtolower(trim($pluginsname));
        if (empty($name)) {
            return false;
        }

        if (in_array($name, self::$PERMANENTLY_BLOCKED_PLUGINS)) {
            return false;
        }

        if (in_array($name, self::$PHASE_ONE_BLOCKED_PLUGINS)) {
            $feature_enabled = self::IsFeatureEnabledForPlugin($name);
            if (!$feature_enabled) {
                return false;
            }
            $qualification_met = self::IsQualificationMetForPlugin($name);
            if (!$qualification_met) {
                return false;
            }
            return true;
        }

        return true;
    }

    public static function IsPluginBlocked($pluginsname)
    {
        return !self::IsPluginAllowed($pluginsname);
    }

    public static function GetBlockReason($pluginsname)
    {
        $name = strtolower(trim($pluginsname));
        if (empty($name)) {
            return '无效的插件标识';
        }

        if (in_array($name, self::$PERMANENTLY_BLOCKED_PLUGINS)) {
            return '该功能暂未开放';
        }

        if (in_array($name, self::$PHASE_ONE_BLOCKED_PLUGINS)) {
            $feature_enabled = self::IsFeatureEnabledForPlugin($name);
            if (!$feature_enabled) {
                return '该功能暂未开放';
            }
            $qualification_met = self::IsQualificationMetForPlugin($name);
            if (!$qualification_met) {
                return '当前资质暂不支持该功能';
            }
        }

        return '';
    }

    public static function GetEffectiveBlockedPlugins()
    {
        $blocked = array_merge(self::$PHASE_ONE_BLOCKED_PLUGINS, self::$PERMANENTLY_BLOCKED_PLUGINS);
        $result = [];
        foreach ($blocked as $plugin) {
            if (self::IsPluginBlocked($plugin)) {
                $result[] = $plugin;
            }
        }
        return $result;
    }

    public static function GetPhaseOneAllowedPlugins()
    {
        return self::$PHASE_ONE_ALLOWED_PLUGINS;
    }

    public static function GetPhaseOneBlockedPlugins()
    {
        return self::$PHASE_ONE_BLOCKED_PLUGINS;
    }

    public static function GetPermanentlyBlockedPlugins()
    {
        return self::$PERMANENTLY_BLOCKED_PLUGINS;
    }

    public static function GetFeatureFlagPluginMap()
    {
        return self::$FEATURE_FLAG_PLUGIN_MAP;
    }

    public static function GetQualificationRequiredMap()
    {
        return self::$QUALIFICATION_REQUIRED_MAP;
    }

    public static function IsFeatureEnabledForPlugin($pluginsname)
    {
        $name = strtolower(trim($pluginsname));
        foreach (self::$FEATURE_FLAG_PLUGIN_MAP as $flag_key => $plugin_names) {
            $plugin_names = is_array($plugin_names) ? $plugin_names : [$plugin_names];
            if (in_array($name, $plugin_names)) {
                return intval(MyC($flag_key, 0)) === 1;
            }
        }
        return true;
    }

    public static function IsQualificationMetForPlugin($pluginsname)
    {
        $name = strtolower(trim($pluginsname));
        if (!isset(self::$QUALIFICATION_REQUIRED_MAP[$name])) {
            return true;
        }
        $required = self::$QUALIFICATION_REQUIRED_MAP[$name];
        foreach ($required as $qual_key) {
            if (self::GetQualificationValue($qual_key) !== 1) {
                return false;
            }
        }
        return true;
    }

    public static function GetAllFeatureFlags()
    {
        $result = [];
        $all_keys = array_keys(self::$FEATURE_FLAG_PLUGIN_MAP);
        $phase_one_keys = [
            'feature_activity_enabled',
            'feature_invite_enabled',
            'feature_content_enabled',
            'feature_feedback_enabled',
            'feature_coupon_enabled',
            'feature_signin_enabled',
            'feature_points_enabled',
        ];
        $v2_keys = [
            'feature_coupon_v2_enabled',
            'feature_points_v2_enabled',
            'feature_membership_v2_enabled',
            'feature_wallet_v2_enabled',
        ];
        $all_keys = array_merge($all_keys, $phase_one_keys, $v2_keys);
        foreach ($all_keys as $key) {
            $result[$key] = intval(MyC($key, 0));
        }
        return $result;
    }

    public static function GetComplianceStatus()
    {
        return [
            'qualifications' => self::GetAllQualifications(),
            'feature_flags' => self::GetAllFeatureFlags(),
            'blocked_plugins' => self::GetEffectiveBlockedPlugins(),
            'allowed_plugins' => self::$PHASE_ONE_ALLOWED_PLUGINS,
        ];
    }
}
