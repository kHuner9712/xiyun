#!/usr/bin/env php
<?php
/**
 * 合规拦截静态检查脚本
 * 验证：
 * 1. 不存在 self::$user 错误访问（static方法访问实例属性）
 * 2. CheckFeatureEnabled/ExitFeatureDisabled 不使用 static 调用实例属性
 * 3. 受控控制器列表与 CONTROLLER_FEATURE_MAP 一致
 * 4. 高风险插件接口路径不在 pages.json 中
 */

$backend_dir = dirname(__DIR__, 2) . '/shopxo-backend';
$frontend_dir = dirname(__DIR__, 2) . '/shopxo-uniapp';
$errors = [];
$warnings = [];

echo "=== 合规拦截静态检查 ===\n\n";

// 检查1: self::$user 在 static 方法中的错误访问
echo "[1] 检查 self::\$user 错误访问...\n";
$php_files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($backend_dir . '/app', RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $php_files[] = $file->getPathname();
    }
}

foreach ($php_files as $filepath) {
    $content = file_get_contents($filepath);
    $rel = str_replace($backend_dir . '/', '', $filepath);

    if (preg_match('/self::\$user\b/', $content)) {
        if (preg_match('/\bprivate\s+static\s+function\b.*\{.*?self::\$user/s', $content) ||
            preg_match('/\bprotected\s+static\s+function\b.*\{.*?self::\$user/s', $content) ||
            preg_match('/\bpublic\s+static\s+function\b.*\{.*?self::\$user/s', $content)) {
            $errors[] = "$rel: static 方法中使用了 self::\$user（实例属性），会导致 PHP fatal error";
        }
    }
}

if (empty($errors)) {
    echo "  ✅ 未发现 self::\$user 错误访问\n";
} else {
    foreach ($errors as $e) {
        echo "  ❌ $e\n";
    }
}

// 检查2: CheckFeatureEnabled 调用方式
echo "\n[2] 检查 CheckFeatureEnabled 调用方式...\n";
$controller_dir = $backend_dir . '/app/api/controller';
$ctrl_files = glob($controller_dir . '/*.php');
$static_calls = 0;
$instance_calls = 0;

foreach ($ctrl_files as $filepath) {
    $content = file_get_contents($filepath);
    $rel = basename($filepath);
    if (preg_match('/self::CheckFeatureEnabled/', $content)) {
        $warnings[] = "$rel: 使用 self::CheckFeatureEnabled()，应改为 \$this->CheckFeatureEnabled()";
        $static_calls++;
    }
    if (preg_match('/\$this->CheckFeatureEnabled/', $content)) {
        $instance_calls++;
    }
}

if ($static_calls > 0) {
    foreach ($warnings as $w) {
        echo "  ⚠️ $w\n";
    }
} else {
    echo "  ✅ 所有 CheckFeatureEnabled 调用均为实例方法调用\n";
}
echo "  实例调用: $instance_calls, 静态调用: $static_calls\n";

// 检查3: CONTROLLER_FEATURE_MAP 覆盖的控制器文件是否存在
echo "\n[3] 检查 CONTROLLER_FEATURE_MAP 控制器文件...\n";
$common_content = file_get_contents($controller_dir . '/Common.php');
preg_match("/\\\$CONTROLLER_FEATURE_MAP\s*=\s*\[(.*?)\]/s", $common_content, $matches);
if (!empty($matches[1])) {
    preg_match_all("/'(\w+)'\s*=>\s*'([^']+)'/", $matches[1], $map_matches);
    $map = array_combine($map_matches[1], $map_matches[2]);
    foreach ($map as $ctrl => $flag) {
        $ctrl_file = $controller_dir . '/' . ucfirst($ctrl) . '.php';
        if (!file_exists($ctrl_file)) {
            $errors[] = "CONTROLLER_FEATURE_MAP 中 '$ctrl' 对应的控制器文件不存在: " . ucfirst($ctrl) . ".php";
        }
    }
    echo "  ✅ CONTROLLER_FEATURE_MAP 中 " . count($map) . " 个控制器文件均存在\n";
}

// 检查4: pages.json 中不包含高风险插件路径
echo "\n[4] 检查 pages.json 高风险插件路径...\n";
$pages_file = $frontend_dir . '/pages.json';
if (file_exists($pages_file)) {
    $pages_content = file_get_contents($pages_file);
    $high_risk_plugins = [
        'wallet', 'coin', 'distribution', 'shop', 'realstore',
        'ask', 'blog', 'hospital', 'weixinliveplayer', 'scanpay',
        'extraction-address'
    ];
    $found_risky = [];
    foreach ($high_risk_plugins as $plugin) {
        $pattern = '/pages/plugins/' . $plugin . '/';
        if (strpos($pages_content, $plugin) !== false) {
            if (preg_match('#/pages/plugins/' . preg_quote($plugin, '#') . '/#', $pages_content)) {
                $found_risky[] = $plugin;
            }
        }
        if ($plugin === 'extraction-address') {
            if (strpos($pages_content, 'extraction-address') !== false) {
                $found_risky[] = 'extraction-address';
            }
        }
    }
    if (empty($found_risky)) {
        echo "  ✅ pages.json 中未发现高风险插件路径\n";
    } else {
        foreach ($found_risky as $r) {
            $errors[] = "pages.json 包含高风险路径: $r";
            echo "  ❌ pages.json 包含高风险路径: $r\n";
        }
    }
} else {
    $warnings[] = "pages.json 文件不存在";
    echo "  ⚠️ pages.json 文件不存在\n";
}

// 检查5: manifest.json 定位权限
echo "\n[5] 检查 manifest.json 定位权限...\n";
$manifest_file = $frontend_dir . '/manifest.json';
if (file_exists($manifest_file)) {
    $manifest_content = file_get_contents($manifest_file);
    $manifest = json_decode($manifest_content, true);
    $mp_weixin = $manifest['mp-weixin'] ?? [];
    $required_private = $mp_weixin['requiredPrivateInfos'] ?? [];
    $permission = $mp_weixin['permission'] ?? [];

    if (!empty($required_private)) {
        $warnings[] = "manifest.json requiredPrivateInfos 非空: " . json_encode($required_private);
        echo "  ⚠️ requiredPrivateInfos 非空: " . json_encode($required_private) . "\n";
    } else {
        echo "  ✅ requiredPrivateInfos 为空\n";
    }

    if (!empty($permission['scope.userLocation'])) {
        $warnings[] = "manifest.json permission.scope.userLocation 仍存在";
        echo "  ⚠️ permission.scope.userLocation 仍存在\n";
    } else {
        echo "  ✅ permission.scope.userLocation 已清空\n";
    }

    $url_check = $mp_weixin['setting']['urlCheck'] ?? null;
    if ($url_check === false) {
        $warnings[] = "manifest.json setting.urlCheck=false，提审时应为 true";
        echo "  ⚠️ setting.urlCheck=false（提审时应为 true）\n";
    } else {
        echo "  ✅ setting.urlCheck 配置正确\n";
    }
}

// 汇总
echo "\n=== 检查结果 ===\n";
echo "错误: " . count($errors) . "\n";
echo "警告: " . count($warnings) . "\n";

if (count($errors) > 0) {
    echo "\n❌ 存在阻塞错误，必须修复后才能上线：\n";
    foreach ($errors as $e) {
        echo "  - $e\n";
    }
    exit(1);
} else {
    echo "\n✅ 无阻塞错误\n";
    if (count($warnings) > 0) {
        echo "⚠️ 存在警告，建议修复：\n";
        foreach ($warnings as $w) {
            echo "  - $w\n";
        }
    }
    exit(0);
}
