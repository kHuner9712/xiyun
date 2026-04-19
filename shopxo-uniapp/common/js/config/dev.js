import { build_runtime_config, get_default_dev_request_url } from './runtime-config.js';

export default build_runtime_config({
    default_request_url: get_default_dev_request_url(),
});
