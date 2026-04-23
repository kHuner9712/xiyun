#!/bin/bash
# API 健康检查脚本
# 用法: bash scripts/preflight/check-api-health.sh [BASE_URL]
# 示例: bash scripts/preflight/check-api-health.sh https://your-domain.com/api.php

set -euo pipefail
BASE_URL="${1:-http://localhost:8080/api.php}"
PASS=0
FAIL=0

check_pass() { echo "  ✅ PASS: $1"; ((PASS++)); }
check_fail() { echo "  ❌ FAIL: $1 - HTTP $2"; ((FAIL++)); }

echo "========================================="
echo "  API 健康检查"
echo "  BASE_URL: $BASE_URL"
echo "========================================="

# 1. 首页配置接口
echo ""
echo "[1] 首页配置接口"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL?s=common.index.index" 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    check_pass "首页配置接口返回 200"
else
    check_fail "首页配置接口" "$HTTP_CODE"
fi

# 2. 活动列表接口
echo ""
echo "[2] 活动列表接口"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL?s=activity.index.index&n=4" 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    check_pass "活动列表接口返回 200"
else
    check_fail "活动列表接口" "$HTTP_CODE"
fi

# 3. 商品搜索接口
echo ""
echo "[3] 商品搜索接口"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL?s=search.datalist&n=5" 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    check_pass "商品搜索接口返回 200"
else
    check_fail "商品搜索接口" "$HTTP_CODE"
fi

# 4. 文章列表接口
echo ""
echo "[4] 文章列表接口"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL?s=article.datalist&n=3" 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    check_pass "文章列表接口返回 200"
else
    check_fail "文章列表接口" "$HTTP_CODE"
fi

# 5. 反馈列表接口
echo ""
echo "[5] 反馈列表接口"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL?s=feedback.index&n=3" 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    check_pass "反馈列表接口返回 200"
else
    check_fail "反馈列表接口" "$HTTP_CODE"
fi

# 6. 邀请配置接口
echo ""
echo "[6] 邀请配置接口"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL?s=invite.rewardconfigpublic" 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    check_pass "邀请配置接口返回 200"
else
    check_fail "邀请配置接口" "$HTTP_CODE"
fi

# 7. 协议接口
echo ""
echo "[7] 协议接口"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL?s=agreement.index&document=userprivacy" 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    check_pass "隐私协议接口返回 200"
else
    check_fail "隐私协议接口" "$HTTP_CODE"
fi

# 8. 功能开关接口
echo ""
echo "[8] 功能开关接口"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL?s=common.index.index" 2>/dev/null || echo "000")
RESPONSE=$(curl -s "$BASE_URL?s=common.index.index" 2>/dev/null || echo "")
if echo "$RESPONSE" | grep -q "feature_activity_enabled\|feature_invite_enabled\|feature_feedback_enabled"; then
    check_pass "功能开关配置在首页接口中返回"
else
    check_fail "功能开关配置未在首页接口中返回" "$HTTP_CODE"
fi

# 9. 地区接口
echo ""
echo "[9] 地区接口"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL?s=region.index&pid=0" 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    check_pass "地区接口返回 200"
else
    check_fail "地区接口" "$HTTP_CODE"
fi

# 汇总
echo ""
echo "========================================="
echo "  API 健康检查结果"
echo "  ✅ PASS: $PASS"
echo "  ❌ FAIL: $FAIL"
echo "========================================="
if [ "$FAIL" -gt 0 ]; then
    echo "  结论: ❌ 有接口不可用，请排查"
    exit 1
else
    echo "  结论: ✅ 全部接口正常"
    exit 0
fi
