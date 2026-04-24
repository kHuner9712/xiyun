#!/usr/bin/env bash
# ============================================================
# 孕禧小程序 — 数据库迁移执行脚本
# ============================================================
#
# 【用途】按正确顺序执行所有 muying 迁移 SQL
# 【用法】bash run-migrations.sh [选项]
# 【选项】
#   --site-dir PATH   站点目录（必填）
#   --db-host HOST    数据库主机（默认 127.0.0.1）
#   --db-port PORT    数据库端口（默认 3306）
#   --db-name NAME    数据库名（必填）
#   --db-user USER    数据库用户（必填）
#   --db-pass PASS    数据库密码（必填）
#   --skip-main       跳过 shopxo.sql 主库导入
#   --help            显示帮助
#
# 【迁移顺序】（不可调换）
#   1. shopxo.sql                              — 主库（不可重复执行）
#   2. muying-final-migration.sql              — 孕禧核心表（不可重复）
#   3. muying-feedback-review-migration.sql    — 反馈审核字段（幂等）
#   4. muying-invite-reward-unify-migration.sql — 邀请奖励统一（幂等）
#   5. muying-feature-flag-upgrade-migration.sql — 功能开关升级（幂等）
#   6. muying-admin-power-migration.sql        — 后台菜单权限（幂等）
#
# 【退出码】0=成功，1=失败
# ============================================================

set -uo pipefail

SITE_DIR=""
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME=""
DB_USER=""
DB_PASS=""
SKIP_MAIN=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        --site-dir=*) SITE_DIR="${1#*=}"; shift ;;
        --site-dir)   SITE_DIR="${2:-}"; shift 2 ;;
        --db-host=*)  DB_HOST="${1#*=}"; shift ;;
        --db-port=*)  DB_PORT="${1#*=}"; shift ;;
        --db-name=*)  DB_NAME="${1#*=}"; shift ;;
        --db-user=*)  DB_USER="${1#*=}"; shift ;;
        --db-pass=*)  DB_PASS="${1#*=}"; shift ;;
        --skip-main)  SKIP_MAIN=1; shift ;;
        --help|-h)    head -30 "$0" | grep '^#' | sed 's/^# \?//'; exit 0 ;;
        *)            shift ;;
    esac
done

RED="\033[31m"; GREEN="\033[32m"; YELLOW="\033[33m"; CYAN="\033[36m"; RESET="\033[0m"
step() { echo -e "\n${CYAN}[STEP]${RESET} $1"; }
ok()   { echo -e "${GREEN}[OK]${RESET} $1"; }
warn() { echo -e "${YELLOW}[WARN]${RESET} $1"; }
fail() { echo -e "${RED}[FAIL]${RESET} $1"; exit 1; }

if [[ -z "$SITE_DIR" ]]; then fail "--site-dir 必填"; fi
if [[ -z "$DB_NAME" ]]; then fail "--db-name 必填"; fi
if [[ -z "$DB_USER" ]]; then fail "--db-user 必填"; fi

MYSQL_CMD="mysql -h $DB_HOST -P $DB_PORT -u $DB_USER"
if [[ -n "$DB_PASS" ]]; then
    MYSQL_CMD="mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS"
fi

if ! command -v mysql &>/dev/null; then
    fail "mysql 客户端未安装"
fi

# 检查数据库连接
$MYSQL_CMD -e "SELECT 1;" "$DB_NAME" &>/dev/null || fail "数据库连接失败"

# 迁移文件搜索路径：先在 site-dir/docs 找，再在仓库根 docs 找
find_sql() {
    local name="$1"
    for dir in "$SITE_DIR" "$SITE_DIR/.." "$(cd "$SITE_DIR" && git rev-parse --show-toplevel 2>/dev/null)"; do
        if [[ -f "${dir}/docs/${name}" ]]; then
            echo "${dir}/docs/${name}"
            return
        fi
    done
    echo ""
}

# 检查表是否已存在（用于幂等判断）
table_exists() {
    local table="$1"
    local count=$($MYSQL_CMD -N -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}' AND TABLE_NAME='${table}';" "$DB_NAME" 2>/dev/null || echo "0")
    [[ "${count:-0}" -gt 0 ]]
}

# 检查列是否已存在（用于幂等判断）
column_exists() {
    local table="$1"
    local column="$2"
    local count=$($MYSQL_CMD -N -e "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='${DB_NAME}' AND TABLE_NAME='${table}' AND COLUMN_NAME='${column}';" "$DB_NAME" 2>/dev/null || echo "0")
    [[ "${count:-0}" -gt 0 ]]
}

# 检查配置项是否已存在
config_exists() {
    local tag="$1"
    local count=$($MYSQL_CMD -N -e "SELECT COUNT(*) FROM sxo_config WHERE only_tag='${tag}';" "$DB_NAME" 2>/dev/null || echo "0")
    [[ "${count:-0}" -gt 0 ]]
}

echo ""
echo "=========================================="
echo " 数据库迁移执行 (按顺序)"
echo "=========================================="

# --- 1. shopxo.sql 主库 ---
if [[ $SKIP_MAIN -eq 0 ]]; then
    step "1/6 shopxo.sql — 主库初始化（不可重复）"
    MAIN_SQL="${SITE_DIR}/config/shopxo.sql"
    if [[ -f "$MAIN_SQL" ]]; then
        if table_exists "sxo_user"; then
            warn "sxo_user 表已存在，跳过主库导入（不可重复执行）"
        else
            $MYSQL_CMD "$DB_NAME" < "$MAIN_SQL" || fail "shopxo.sql 导入失败"
            ok "shopxo.sql 导入成功"
        fi
    else
        fail "找不到 config/shopxo.sql"
    fi
else
    step "1/6 shopxo.sql — 已跳过（--skip-main）"
fi

# --- 2. muying-final-migration.sql ---
step "2/6 muying-final-migration.sql — 孕禧核心表"
SQL_FILE=$(find_sql "muying-final-migration.sql")
if [[ -n "$SQL_FILE" ]]; then
    if table_exists "sxo_activity"; then
        warn "sxo_activity 表已存在，跳过"
    else
        $MYSQL_CMD "$DB_NAME" < "$SQL_FILE" || fail "muying-final-migration.sql 执行失败"
        ok "孕禧核心表创建成功"
    fi
else
    warn "未找到 muying-final-migration.sql，跳过"
fi

# --- 3. muying-feedback-review-migration.sql ---
step "3/6 muying-feedback-review-migration.sql — 反馈审核字段（幂等）"
SQL_FILE=$(find_sql "muying-feedback-review-migration.sql")
if [[ -n "$SQL_FILE" ]]; then
    if column_exists "sxo_muying_feedback" "review_status"; then
        warn "review_status 字段已存在，跳过"
    else
        $MYSQL_CMD "$DB_NAME" < "$SQL_FILE" || fail "muying-feedback-review-migration.sql 执行失败"
        ok "反馈审核字段添加成功"
    fi
else
    warn "未找到 muying-feedback-review-migration.sql，跳过"
fi

# --- 4. muying-invite-reward-unify-migration.sql ---
step "4/6 muying-invite-reward-unify-migration.sql — 邀请奖励统一（幂等）"
SQL_FILE=$(find_sql "muying-invite-reward-unify-migration.sql")
if [[ -n "$SQL_FILE" ]]; then
    if column_exists "sxo_invite_reward" "trigger_event"; then
        warn "trigger_event 字段已存在，跳过"
    else
        $MYSQL_CMD "$DB_NAME" < "$SQL_FILE" || fail "muying-invite-reward-unify-migration.sql 执行失败"
        ok "邀请奖励字段统一成功"
    fi
else
    warn "未找到 muying-invite-reward-unify-migration.sql，跳过"
fi

# --- 5. muying-feature-flag-upgrade-migration.sql ---
step "5/6 muying-feature-flag-upgrade-migration.sql — 功能开关升级（幂等）"
SQL_FILE=$(find_sql "muying-feature-flag-upgrade-migration.sql")
if [[ -n "$SQL_FILE" ]]; then
    if config_exists "feature_feedback_enabled"; then
        warn "feature_feedback_enabled 已存在，跳过"
    else
        $MYSQL_CMD "$DB_NAME" < "$SQL_FILE" || fail "muying-feature-flag-upgrade-migration.sql 执行失败"
        ok "功能开关配置插入成功"
    fi
else
    warn "未找到 muying-feature-flag-upgrade-migration.sql，跳过"
fi

# --- 6. muying-admin-power-migration.sql ---
step "6/6 muying-admin-power-migration.sql — 后台菜单权限（幂等）"
SQL_FILE=$(find_sql "muying-admin-power-migration.sql")
if [[ -n "$SQL_FILE" ]]; then
    POWER_COUNT=$($MYSQL_CMD -N -e "SELECT COUNT(*) FROM sxo_power WHERE name='孕禧运营';" "$DB_NAME" 2>/dev/null || echo "0")
    if [[ "${POWER_COUNT:-0}" -gt 0 ]]; then
        warn "孕禧运营菜单已存在，跳过"
    else
        $MYSQL_CMD "$DB_NAME" < "$SQL_FILE" || fail "muying-admin-power-migration.sql 执行失败"
        ok "后台菜单权限插入成功"
    fi
else
    warn "未找到 muying-admin-power-migration.sql，跳过"
fi

echo ""
echo "=========================================="
echo " 数据库迁移执行完成"
echo "=========================================="
echo ""
echo "  已执行的迁移:"
echo "    1. shopxo.sql — 主库"
echo "    2. muying-final-migration.sql — 核心表"
echo "    3. muying-feedback-review-migration.sql — 反馈审核"
echo "    4. muying-invite-reward-unify-migration.sql — 邀请统一"
echo "    5. muying-feature-flag-upgrade-migration.sql — 功能开关"
echo "    6. muying-admin-power-migration.sql — 菜单权限"
echo ""
ok "全部迁移完成"
exit 0
