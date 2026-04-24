import { FeatureFlagKey, QualificationKey } from './muying-constants.js';
import { PERMANENTLY_BLOCKED_PLUGINS, PHASE_ONE_BLOCKED_PLUGINS, PHASE_ONE_ALLOWED_PLUGINS, PHASE_ONE_ALLOWED_ROUTES, FEATURE_FLAG_PLUGIN_MAP as _COMPLIANCE_FEATURE_FLAG_MAP, QUALIFICATION_REQUIRED_MAP, init_compliance as _init_compliance, is_feature_enabled as _is_feature_enabled, is_qualification_met as _is_qualification_met, is_plugin_allowed as _is_plugin_allowed, is_plugin_blocked as _is_plugin_blocked, get_block_reason as _get_block_reason, normalize_page_path as _normalize_page_path, is_route_allowed as _is_route_allowed, is_route_blocked as _is_route_blocked, get_blocked_route_reason as _get_blocked_route_reason, filter_navigation as _filter_navigation, filter_plugin_sort_list as _filter_plugin_sort_list, get_effective_blocked_plugins as _get_effective_blocked_plugins, get_effective_blocked_route_prefixes as _get_effective_blocked_route_prefixes } from './compliance-scope.js';

var BASE_DISABLED_PLUGIN_NAMES = PERMANENTLY_BLOCKED_PLUGINS.slice();
var DYNAMIC_DISABLED_PLUGIN_NAMES = PHASE_ONE_BLOCKED_PLUGINS.slice();
var PHASE_ONE_DISABLED_PLUGIN_NAMES = BASE_DISABLED_PLUGIN_NAMES.concat(DYNAMIC_DISABLED_PLUGIN_NAMES);

var FEATURE_FLAG_PLUGIN_MAP = _COMPLIANCE_FEATURE_FLAG_MAP;

var _feature_flags = null;

function _rebuild_disabled_list() {
    var dynamic = DYNAMIC_DISABLED_PLUGIN_NAMES.slice();
    for (var i = dynamic.length - 1; i >= 0; i--) {
        if (_is_plugin_allowed(dynamic[i])) {
            dynamic.splice(i, 1);
        }
    }
    PHASE_ONE_DISABLED_PLUGIN_NAMES = BASE_DISABLED_PLUGIN_NAMES.concat(dynamic);
}

function _rebuild_route_prefixes() {
    PHASE_ONE_DISABLED_ROUTE_PREFIXES = PHASE_ONE_DISABLED_PLUGIN_NAMES.map(function (name) {
        return '/pages/plugins/' + name + '/';
    });
}

function init_feature_flags(flags) {
    _feature_flags = flags || {};
    _init_compliance(flags, _feature_flags._qualifications || {});
    _rebuild_disabled_list();
    _rebuild_route_prefixes();
}

function normalize_page_path(url) {
    return _normalize_page_path(url);
}

function is_phase_one_disabled_route(url) {
    return _is_route_blocked(url);
}

function is_phase_one_disabled_plugin(plugins) {
    return _is_plugin_blocked(plugins);
}

function is_feature_enabled(flag_key) {
    return _is_feature_enabled(flag_key);
}

function route_value_from_navigation_item(item) {
    if ((item || null) == null) return null;
    return item.event_value || item.url || null;
}

function filter_phase_one_navigation(list) {
    return _filter_navigation(list);
}

function filter_phase_one_plugin_sort_list(list) {
    return _filter_plugin_sort_list(list);
}

var PHASE_ONE_DISABLED_ROUTE_PREFIXES = PHASE_ONE_DISABLED_PLUGIN_NAMES.map(function (name) {
    return '/pages/plugins/' + name + '/';
});

export { BASE_DISABLED_PLUGIN_NAMES, DYNAMIC_DISABLED_PLUGIN_NAMES, PHASE_ONE_DISABLED_PLUGIN_NAMES, PHASE_ONE_DISABLED_ROUTE_PREFIXES, FEATURE_FLAG_PLUGIN_MAP, init_feature_flags, normalize_page_path, is_phase_one_disabled_route, is_phase_one_disabled_plugin, is_feature_enabled, filter_phase_one_navigation, filter_phase_one_plugin_sort_list };
