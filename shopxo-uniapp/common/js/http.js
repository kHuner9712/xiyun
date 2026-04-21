import { is_feature_enabled } from '../config/phase-one-scope.js';
import { FeatureFlagKey, TipMessage, RoutePath } from '../config/muying-constants.js';
import { userStore } from './user-store.js';
import { logger } from './logger.js';

var FEATURE_FLAG_ACTION_MAP = {};
FEATURE_FLAG_ACTION_MAP['activity'] = FeatureFlagKey.ACTIVITY;
FEATURE_FLAG_ACTION_MAP['invite'] = FeatureFlagKey.INVITE;
FEATURE_FLAG_ACTION_MAP['feedback'] = FeatureFlagKey.FEEDBACK;

var LOGIN_EXPIRED_CODES = [-100, -9999];
var DEFAULT_LOADING_TITLE = '加载中...';
var _loading_count = 0;

function _show_loading(title) {
    _loading_count++;
    if (_loading_count === 1) {
        uni.showLoading({ title: title || DEFAULT_LOADING_TITLE, mask: true });
    }
}

function _hide_loading() {
    _loading_count = Math.max(0, _loading_count - 1);
    if (_loading_count === 0) {
        uni.hideLoading();
    }
}

function _handle_login_expired(msg) {
    logger.warn('HTTP', '登录失效 ' + msg);
    userStore.clear();
    var pages = getCurrentPages();
    var current = pages[pages.length - 1];
    var current_route = current ? '/' + current.route : '';
    if (current_route.indexOf(RoutePath.LOGIN) === -1) {
        uni.showToast({ title: TipMessage.LOGIN_EXPIRED, icon: 'none', duration: 2000 });
        setTimeout(function() {
            uni.navigateTo({ url: RoutePath.LOGIN });
        }, 1500);
    }
}

function request(options) {
    var app = getApp();
    if (!app || !app.globalData) {
        logger.error('HTTP', 'getApp() 失败，无法发起请求');
        if (options.fail) options.fail({ errMsg: '应用未初始化' });
        return;
    }

    var action = options.action || 'index';
    var controller = options.controller || 'index';
    var plugins = options.plugins || null;
    var params = options.params || '';
    var group = options.group || 'api';

    var feature_flag_key = FEATURE_FLAG_ACTION_MAP[controller];
    if (feature_flag_key && !is_feature_enabled(feature_flag_key)) {
        logger.warn('HTTP', '功能已关闭，拦截请求 ' + controller + '/' + action);
        if (options.fail) {
            options.fail({ errMsg: TipMessage.FEATURE_DISABLED, code: -1, feature_disabled: true });
        }
        if (options.complete) options.complete();
        return;
    }

    var url = app.globalData.get_request_url(action, controller, plugins, params, group);

    var show_loading = options.loading !== false;
    if (show_loading) {
        _show_loading(options.loading_title);
    }

    var request_data = options.data || {};

    uni.request({
        url: url,
        method: options.method || 'POST',
        data: request_data,
        dataType: options.dataType || 'json',
        success: function(res) {
            if (show_loading) _hide_loading();

            if (!res.data || typeof res.data !== 'object') {
                logger.error('HTTP', '响应格式异常 ' + url);
                if (options.fail) options.fail({ errMsg: '服务器响应格式异常', statusCode: res.statusCode });
                if (options.complete) options.complete(res);
                return;
            }

            var code = res.data.code;
            var msg = res.data.msg || '';
            var data = res.data.data;

            if (LOGIN_EXPIRED_CODES.indexOf(code) !== -1) {
                _handle_login_expired(msg);
                if (options.fail) options.fail({ errMsg: msg || TipMessage.LOGIN_EXPIRED, code: code, login_expired: true });
                if (options.complete) options.complete(res);
                return;
            }

            if (code == 0) {
                if (options.success) options.success(data, res);
            } else {
                if (!options.silent) {
                    app.globalData.showToast(msg || '操作失败');
                }
                if (options.fail) options.fail({ errMsg: msg, code: code, data: data });
            }

            if (options.complete) options.complete(res);
        },
        fail: function(err) {
            if (show_loading) _hide_loading();

            logger.error('HTTP', '网络请求失败 ' + url);

            if (!options.silent) {
                app.globalData.showToast(TipMessage.NETWORK_ERROR);
            }

            if (options.fail) options.fail({ errMsg: (err && err.errMsg) || TipMessage.NETWORK_ERROR, network_error: true });
            if (options.complete) options.complete(err);
        },
    });
}

function get(options) {
    options.method = 'GET';
    return request(options);
}

function post(options) {
    options.method = 'POST';
    return request(options);
}

export { request, get, post, _show_loading, _hide_loading };
export default request;
