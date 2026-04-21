var PHASE_ONE_DISABLED_PLUGIN_NAMES = [
    'distribution',
    'wallet',
    'coin',
    'shop',
    'realstore',
    'ask',
    'blog',
    'membershiplevelvip',
    'seckill',
    'coupon',
    'signin',
    'points',
    'video',
    'hospital',
    'giftcard',
    'givegift',
    'complaint',
    'invoice',
    'certificate',
    'scanpay',
    'weixinliveplayer',
    'intellectstools',
    'excellentbuyreturntocash',
    'exchangerate',
    'goodscompare',
    'orderfeed',
    'ordergoodsform',
    'orderresources',
    'antifakecode',
    'form',
    'binding',
    'label',
];

var FeatureFlagKey = {
    SHOP: 'feature_shop_enabled',
    REALSTORE: 'feature_realstore_enabled',
    DISTRIBUTION: 'feature_distribution_enabled',
    WALLET: 'feature_wallet_enabled',
    COIN: 'feature_coin_enabled',
    UGC: 'feature_ugc_enabled',
    MEMBERSHIP: 'feature_membership_enabled',
    SECKILL: 'feature_seckill_enabled',
    COUPON: 'feature_coupon_enabled',
    SIGNIN: 'feature_signin_enabled',
    POINTS: 'feature_points_enabled',
    VIDEO: 'feature_video_enabled',
    HOSPITAL: 'feature_hospital_enabled',
    GIFTCARD: 'feature_giftcard_enabled',
    GIVEGIFT: 'feature_givegift_enabled',
    COMPLAINT: 'feature_complaint_enabled',
    INVOICE: 'feature_invoice_enabled',
    CERTIFICATE: 'feature_certificate_enabled',
    SCANPAY: 'feature_scanpay_enabled',
    LIVE: 'feature_live_enabled',
    INTELLECTSTOOLS: 'feature_intellectstools_enabled',
};

var FEATURE_FLAG_PLUGIN_MAP = {};
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.SHOP] = 'shop';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.REALSTORE] = 'realstore';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.DISTRIBUTION] = 'distribution';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.WALLET] = 'wallet';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.COIN] = 'coin';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.UGC] = ['ask', 'blog'];
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.MEMBERSHIP] = 'membershiplevelvip';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.SECKILL] = 'seckill';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.COUPON] = 'coupon';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.SIGNIN] = 'signin';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.POINTS] = 'points';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.VIDEO] = 'video';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.HOSPITAL] = 'hospital';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.GIFTCARD] = 'giftcard';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.GIVEGIFT] = 'givegift';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.COMPLAINT] = 'complaint';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.INVOICE] = 'invoice';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.CERTIFICATE] = 'certificate';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.SCANPAY] = 'scanpay';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.LIVE] = 'weixinliveplayer';
FEATURE_FLAG_PLUGIN_MAP[FeatureFlagKey.INTELLECTSTOOLS] = 'intellectstools';

var _feature_flags = null;

function init_feature_flags(flags) {
    _feature_flags = flags || {};
    var dynamic_disabled = [];
    for (var flag_key in FEATURE_FLAG_PLUGIN_MAP) {
        if (!_feature_flags[flag_key]) {
            var plugin_names = FEATURE_FLAG_PLUGIN_MAP[flag_key];
            if (Array.isArray(plugin_names)) {
                for (var i = 0; i < plugin_names.length; i++) {
                    if (dynamic_disabled.indexOf(plugin_names[i]) === -1) {
                        dynamic_disabled.push(plugin_names[i]);
                    }
                }
            } else {
                if (dynamic_disabled.indexOf(plugin_names) === -1) {
                    dynamic_disabled.push(plugin_names);
                }
            }
        }
    }
    PHASE_ONE_DISABLED_PLUGIN_NAMES = dynamic_disabled;
    _rebuild_route_prefixes();
}

function _rebuild_route_prefixes() {
    PHASE_ONE_DISABLED_ROUTE_PREFIXES = PHASE_ONE_DISABLED_PLUGIN_NAMES.map(function(name) {
        return '/pages/plugins/' + name + '/';
    });
}

var PHASE_ONE_DISABLED_ROUTE_PREFIXES = PHASE_ONE_DISABLED_PLUGIN_NAMES.map(function(name) {
    return '/pages/plugins/' + name + '/';
});

function normalize_page_path(url) {
    if ((url || null) == null) {
        return '';
    }
    var value = String(url).trim();
    if (value === '') {
        return '';
    }
    var query_index = value.indexOf('?');
    if (query_index !== -1) {
        value = value.substring(0, query_index);
    }
    var hash_index = value.indexOf('#');
    if (hash_index !== -1) {
        value = value.substring(0, hash_index);
    }
    if (value[0] !== '/') {
        value = '/' + value;
    }
    return value;
}

function is_phase_one_disabled_route(url) {
    var value = normalize_page_path(url);
    if (value === '') {
        return false;
    }
    for (var i = 0; i < PHASE_ONE_DISABLED_ROUTE_PREFIXES.length; i++) {
        var prefix = PHASE_ONE_DISABLED_ROUTE_PREFIXES[i];
        if (prefix[prefix.length - 1] === '/') {
            if (value.indexOf(prefix) === 0) {
                return true;
            }
        } else if (value === prefix) {
            return true;
        }
    }
    return false;
}

function is_phase_one_disabled_plugin(plugins) {
    if ((plugins || null) == null) {
        return false;
    }
    return PHASE_ONE_DISABLED_PLUGIN_NAMES.indexOf(String(plugins).toLowerCase()) !== -1;
}

function is_feature_enabled(flag_key) {
    if (_feature_flags && typeof _feature_flags[flag_key] !== 'undefined') {
        return !!_feature_flags[flag_key];
    }
    return false;
}

function route_value_from_navigation_item(item) {
    if ((item || null) == null) {
        return null;
    }
    return item.event_value || item.url || null;
}

function filter_phase_one_navigation(list) {
    if (!Array.isArray(list) || list.length <= 0) {
        return [];
    }
    var result = [];
    for (var i = 0; i < list.length; i++) {
        var item = list[i] || null;
        if (item == null) {
            continue;
        }
        var route_value = route_value_from_navigation_item(item);
        if (is_phase_one_disabled_route(route_value)) {
            continue;
        }
        var next = Object.assign({}, item);
        if (Array.isArray(item.extension_data)) {
            next.extension_data = filter_phase_one_navigation(item.extension_data);
        }
        result.push(next);
    }
    return result;
}

function filter_phase_one_plugin_sort_list(list) {
    if (!Array.isArray(list) || list.length <= 0) {
        return [];
    }
    var result = [];
    for (var i = 0; i < list.length; i++) {
        var item = list[i] || null;
        if (item == null || is_phase_one_disabled_plugin(item.plugins)) {
            continue;
        }
        result.push(item);
    }
    return result;
}

export {
    PHASE_ONE_DISABLED_PLUGIN_NAMES,
    PHASE_ONE_DISABLED_ROUTE_PREFIXES,
    FEATURE_FLAG_PLUGIN_MAP,
    init_feature_flags,
    normalize_page_path,
    is_phase_one_disabled_route,
    is_phase_one_disabled_plugin,
    is_feature_enabled,
    filter_phase_one_navigation,
    filter_phase_one_plugin_sort_list,
};
