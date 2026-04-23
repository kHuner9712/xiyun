#!/bin/bash
# 小程序提审前自动化检查
# 用法: bash scripts/preflight/check-wechat-review.sh [项目根目录]

set -euo pipefail
ROOT_DIR="${1:-$(cd "$(dirname "$0")/../.." && pwd)}"
UNIAPP_DIR="$ROOT_DIR/shopxo-uniapp"
MANIFEST="$UNIAPP_DIR/manifest.json"
PASS=0
FAIL=0
WARN=0

check_pass() { echo "  ✅ PASS: $1"; ((PASS++)); }
check_fail() { echo "  ❌ FAIL: $1"; ((FAIL++)); }
check_warn() { echo "  ⚠️  WARN: $1"; ((WARN++)); }

echo "========================================="
echo "  小程序提审前自动化检查"
echo "  项目: $ROOT_DIR"
echo "========================================="

# 1. requiredPrivateInfos 检查
echo ""
echo "[1] requiredPrivateInfos 检查"
if command -v python3 &>/dev/null; then
    RPI=$(python3 -c "
import json,sys
with open('$MANIFEST','r',encoding='utf-8') as f:
    d=json.load(f)
rpi=d.get('mp-weixin',{}).get('requiredPrivateInfos',[])
print(','.join(rpi))
" 2>/dev/null)
    if [ "$RPI" = "chooseLocation,getLocation" ]; then
        check_pass "requiredPrivateInfos 仅含 chooseLocation,getLocation"
    else
        check_fail "requiredPrivateInfos=$RPI, 预期仅 chooseLocation,getLocation"
    fi
else
    check_warn "python3 不可用，跳过 JSON 解析"
fi

# 2. 位置权限 desc 检查
echo ""
echo "[2] 位置权限 desc 文案检查"
if command -v python3 &>/dev/null; then
    DESC=$(python3 -c "
import json
with open('$MANIFEST','r',encoding='utf-8') as f:
    d=json.load(f)
desc=d.get('mp-weixin',{}).get('permission',{}).get('scope.userLocation',{}).get('desc','')
print(desc)
" 2>/dev/null)
    if echo "$DESC" | grep -qiE "活动|签到|地址"; then
        check_pass "位置权限 desc 包含具体场景说明"
    else
        check_fail "位置权限 desc 缺少具体场景说明: $DESC"
    fi
else
    check_warn "python3 不可用，跳过"
fi

# 3. __usePrivacyCheck__ 检查
echo ""
echo "[3] 隐私检查开关"
if command -v python3 &>/dev/null; then
    PRIVACY=$(python3 -c "
import json
with open('$MANIFEST','r',encoding='utf-8') as f:
    d=json.load(f)
print(d.get('mp-weixin',{}).get('__usePrivacyCheck__',False))
" 2>/dev/null)
    if [ "$PRIVACY" = "True" ]; then
        check_pass "__usePrivacyCheck__ 已开启"
    else
        check_fail "__usePrivacyCheck__ 未开启"
    fi
else
    check_warn "python3 不可用，跳过"
fi

# 4. iOS 后台定位声明检查
echo ""
echo "[4] iOS 后台定位声明检查"
if command -v python3 &>/dev/null; then
    ALWAYS_DESC=$(python3 -c "
import json
with open('$MANIFEST','r',encoding='utf-8') as f:
    d=json.load(f)
pd=d.get('app-plus',{}).get('distribute',{}).get('ios',{}).get('privacyDescription',{})
print(pd.get('NSLocationAlwaysUsageDescription','NOT_FOUND'))
" 2>/dev/null)
    if [ "$ALWAYS_DESC" = "NOT_FOUND" ]; then
        check_pass "iOS 后台定位声明已移除"
    else
        check_fail "iOS 后台定位声明仍存在，提审可能被拒"
    fi
else
    check_warn "python3 不可用，跳过"
fi

# 5. Android 高敏感权限检查
echo ""
echo "[5] Android 高敏感权限检查"
DANGEROUS_PERMS="READ_CONTACTS|WRITE_CONTACTS|READ_PHONE_STATE|CALL_PHONE|RECORD_AUDIO|READ_LOGS"
if grep -qE "$DANGEROUS_PERMS" "$MANIFEST" 2>/dev/null; then
    check_fail "manifest.json 仍包含高敏感 Android 权限"
else
    check_pass "无高敏感 Android 权限"
fi

# 6. 代码中 startLocationUpdate/onLocationChange 检查
echo ""
echo "[6] 微信端位置监听 API 残留检查"
if grep -rn "uni\.startLocationUpdate\|uni\.onLocationChange\|uni\.startLocationUpdateBackground" \
    "$UNIAPP_DIR/App.vue" 2>/dev/null | grep -v "#ifndef MP-WEIXIN" | grep -v "// #"; then
    check_warn "App.vue 中存在未加 MP-WEIXIN 条件编译守卫的位置监听 API（需确认编译后是否排除）"
else
    check_pass "App.vue 中位置监听 API 已加条件编译守卫"
fi

# 7. 隐私政策内容检查
echo ""
echo "[7] 隐私政策内容完整性检查"
AGREEMENT="$UNIAPP_DIR/pages/agreement/agreement.vue"
if [ -f "$AGREEMENT" ]; then
    for keyword in "位置信息" "相册" "摄像头" "拒绝授权"; do
        if grep -q "$keyword" "$AGREEMENT"; then
            check_pass "隐私政策包含'$keyword'说明"
        else
            check_fail "隐私政策缺少'$keyword'说明"
        fi
    done
else
    check_fail "agreement.vue 文件不存在"
fi

# 8. 测试/调试内容扫描
echo ""
echo "[8] 测试/调试内容扫描"
TEST_KEYWORDS="TODO:|FIXME:|HACK:|占位|测试数据|test123"
FOUND=0
for ext in vue js; do
    while IFS= read -r line; do
        echo "  ⚠️  $line"
        ((FOUND++))
    done < <(grep -rn -iE "$TEST_KEYWORDS" "$UNIAPP_DIR/pages/" --include="*.$ext" 2>/dev/null | head -20)
done
if [ "$FOUND" -eq 0 ]; then
    check_pass "页面代码中未发现测试/调试内容"
else
    check_warn "发现 $FOUND 处疑似测试/调试内容，请人工确认"
fi

# 9. 版本号检查
echo ""
echo "[9] 版本号检查"
if command -v python3 &>/dev/null; then
    VER=$(python3 -c "
import json
with open('$MANIFEST','r',encoding='utf-8') as f:
    d=json.load(f)
print(d.get('versionName',''))
" 2>/dev/null)
    if [ -n "$VER" ] && [ "$VER" != "0.0.1" ] && [ "$VER" != "0.0.0" ]; then
        check_pass "版本号: $VER"
    else
        check_fail "版本号异常: $VER"
    fi
else
    check_warn "python3 不可用，跳过"
fi

# 10. AppID 非空检查
echo ""
echo "[10] AppID 检查"
if command -v python3 &>/dev/null; then
    APPID=$(python3 -c "
import json
with open('$MANIFEST','r',encoding='utf-8') as f:
    d=json.load(f)
print(d.get('mp-weixin',{}).get('appid',''))
" 2>/dev/null)
    if [ -n "$APPID" ] && [ "$APPID" != "" ]; then
        check_pass "微信 AppID 已配置"
    else
        check_fail "微信 AppID 未配置"
    fi
else
    check_warn "python3 不可用，跳过"
fi

# 汇总
echo ""
echo "========================================="
echo "  检查结果汇总"
echo "  ✅ PASS: $PASS"
echo "  ❌ FAIL: $FAIL"
echo "  ⚠️  WARN: $WARN"
echo "========================================="
if [ "$FAIL" -gt 0 ]; then
    echo "  结论: ❌ 不通过，请修复 FAIL 项后重新检查"
    exit 1
elif [ "$WARN" -gt 0 ]; then
    echo "  结论: ⚠️  有警告项，请人工确认后决定是否继续"
    exit 0
else
    echo "  结论: ✅ 全部通过"
    exit 0
fi
