// 环境配置构建器 — 唯一真相源
// 四环境：local / test / staging / production
// 所有地址和 AppID 均通过环境变量注入，不在代码中硬编码

var ENV_LOCAL = 'local';
var ENV_TEST = 'test';
var ENV_STAGING = 'staging';
var ENV_PRODUCTION = 'production';

var DEFAULT_DEV_REQUEST_URL_H5 = 'http://localhost:8080/';
var DEFAULT_DEV_REQUEST_URL_NON_H5 = 'http://127.0.0.1:8080/';

var get_default_dev_request_url = function() {
    // #ifdef H5
    return DEFAULT_DEV_REQUEST_URL_H5;
    // #endif
    // #ifndef H5
    return DEFAULT_DEV_REQUEST_URL_NON_H5;
    // #endif
};

var normalize_base_url = function(value) {
    var raw = (value || '').trim();
    if (!raw) {
        return '';
    }
    return raw.endsWith('/') ? raw : raw + '/';
};

var read_env = function(name) {
    var direct = (process.env[name] || '').trim();
    if (direct) {
        return direct;
    }
    return (process.env['VUE_APP_' + name] || '').trim();
};

var detect_env = function() {
    var env_name = read_env('UNI_APP_ENV');
    if (env_name) {
        return env_name;
    }
    if (process.env.NODE_ENV === 'production') {
        return ENV_PRODUCTION;
    }
    return ENV_LOCAL;
};

var build_runtime_config = function(opts) {
    opts = opts || {};
    var default_request_url = opts.default_request_url || '';

    var env = detect_env();
    var env_url = read_env('UNI_APP_REQUEST_URL');
    var request_url = normalize_base_url(env_url || default_request_url);
    var static_url = normalize_base_url(read_env('UNI_APP_STATIC_URL') || env_url || default_request_url);
    var upload_url = normalize_base_url(read_env('UNI_APP_UPLOAD_URL') || env_url || default_request_url);
    var appid = read_env('UNI_APP_WX_APPID') || '';

    if (!env_url && !default_request_url) {
        console.warn(
            '[CONFIG] UNI_APP_REQUEST_URL 未配置，接口请求将失败。\n' +
            '请在 .env.development / .env.staging / .env.production 中设置，\n' +
            '例如：UNI_APP_REQUEST_URL=http://192.168.1.100:8080/'
        );
    }

    // [MUYING-二开] 生产环境强制门禁
    var TEST_APPIDS = ['wxda7779770f53e901'];
    var is_localhost_or_ip = function(url) {
        if (!url) return false;
        return /(?:localhost|127\.0\.0\.1|0\.0\.0\.0|10\.\d+\.\d+\.\d+|172\.(?:1[6-9]|2\d|3[01])\.\d+\.\d+|192\.168\.\d+\.\d+)/.test(url);
    };

    if (env === ENV_PRODUCTION) {
        if (!request_url) {
            throw new Error(
                '[CONFIG][PROD] UNI_APP_REQUEST_URL 未配置，生产构建终止。\n' +
                '配置方式：\n' +
                '1) HBuilderX 发行对话框 → 环境变量：UNI_APP_REQUEST_URL=https://api.example.com/\n' +
                '2) CLI 构建 → 设置 .env.production 或 export 环境变量\n' +
                '3) 确保该域名已加入微信小程序 request 合法域名'
            );
        }
        if (request_url.indexOf('https://') !== 0) {
            throw new Error('[CONFIG][PROD] 生产环境 request_url 必须以 https:// 开头，当前值: ' + request_url);
        }
        if (is_localhost_or_ip(request_url)) {
            throw new Error('[CONFIG][PROD] 生产环境 request_url 不能使用 localhost/127.0.0.1/内网IP，当前值: ' + request_url);
        }
        if (static_url && static_url.indexOf('https://') !== 0) {
            throw new Error('[CONFIG][PROD] 生产环境 static_url 必须以 https:// 开头，当前值: ' + static_url);
        }
        if (!appid) {
            throw new Error('[CONFIG][PROD] UNI_APP_WX_APPID 未配置，生产构建终止。必须在 .env.production 中设置正式 AppID');
        }
        if (TEST_APPIDS.indexOf(appid) !== -1) {
            throw new Error('[CONFIG][PROD] UNI_APP_WX_APPID 为测试号 (' + appid + ')，生产环境必须使用正式 AppID');
        }
    }

    return {
        env: env,
        request_url: request_url,
        static_url: static_url,
        upload_url: upload_url,
        wx_appid: appid,
    };
};

export {
    ENV_LOCAL,
    ENV_TEST,
    ENV_STAGING,
    ENV_PRODUCTION,
    DEFAULT_DEV_REQUEST_URL_H5,
    DEFAULT_DEV_REQUEST_URL_NON_H5,
    get_default_dev_request_url,
    normalize_base_url,
    read_env,
    detect_env,
    build_runtime_config,
};
