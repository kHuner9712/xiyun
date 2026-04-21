var _is_prod = process.env.NODE_ENV === 'production';

var LEVEL_DEBUG = 0;
var LEVEL_INFO = 1;
var LEVEL_WARN = 2;
var LEVEL_ERROR = 3;

var _min_level = _is_prod ? LEVEL_WARN : LEVEL_DEBUG;

function _format(level_name, tag, args) {
    var ts = new Date().toISOString();
    var parts = ['[' + ts + ']', '[' + level_name + ']'];
    if (tag) parts.push('[' + tag + ']');
    for (var i = 0; i < args.length; i++) {
        parts.push(args[i]);
    }
    return parts;
}

function debug() {
    if (_min_level > LEVEL_DEBUG) return;
    var args = _format('DEBUG', null, arguments);
    // #ifdef APP-PLUS || MP-WEIXIN
    console.log.apply(console, args);
    // #endif
}

function info(tag) {
    if (_min_level > LEVEL_INFO) return;
    var rest = Array.prototype.slice.call(arguments, 1);
    var args = _format('INFO', tag, rest);
    console.info.apply(console, args);
}

function warn(tag) {
    if (_min_level > LEVEL_WARN) return;
    var rest = Array.prototype.slice.call(arguments, 1);
    var args = _format('WARN', tag, rest);
    console.warn.apply(console, args);
}

function error(tag) {
    var rest = Array.prototype.slice.call(arguments, 1);
    var args = _format('ERROR', tag, rest);
    console.error.apply(console, args);
}

function setLevel(level) {
    _min_level = level;
}

export var logger = {
    debug: debug,
    info: info,
    warn: warn,
    error: error,
    setLevel: setLevel,
    LEVEL_DEBUG: LEVEL_DEBUG,
    LEVEL_INFO: LEVEL_INFO,
    LEVEL_WARN: LEVEL_WARN,
    LEVEL_ERROR: LEVEL_ERROR,
};

export default logger;
