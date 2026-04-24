# 数据库初始化与迁移执行顺序清单

> 适用阶段：后端部署  
> 执行人：开发/运维  
> 输入物：MySQL 连接信息  
> 输出物：完整可用的数据库  
> 最后更新：2026-04-24

---

## 执行方式

### 方式一：一键脚本（推荐）

```bash
bash scripts/deploy/run-migrations.sh \
  --site-dir /www/wwwroot/yunxi-api \
  --db-host 127.0.0.1 --db-port 3306 \
  --db-name yunxi --db-user yunxi --db-pass YOUR_PASSWORD
```

脚本会自动按顺序执行、检查幂等性、跳过已执行的迁移。

### 方式二：手动逐个执行

按下方顺序逐个导入 SQL 文件。

---

## 迁移执行顺序（不可调换）

| 序号 | SQL 文件 | 位置 | 用途 | 可否重复执行 | 幂等检查方式 |
|------|----------|------|------|-------------|-------------|
| 1 | `shopxo.sql` | `shopxo-backend/config/shopxo.sql` | ShopXO 主库初始化 | ❌ 不可重复 | 检查 `sxo_user` 表是否存在 |
| 2 | `muying-final-migration.sql` | `docs/muying-final-migration.sql` | 孕禧核心表（activity/activity_signup/invite_reward/muying_feedback） | ❌ 不可重复 | 检查 `sxo_activity` 表是否存在 |
| 3 | `muying-feedback-review-migration.sql` | `docs/muying-feedback-review-migration.sql` | 反馈审核字段（review_status/is_enable） | ✅ 幂等 | 检查 `review_status` 列是否存在 |
| 4 | `muying-invite-reward-unify-migration.sql` | `docs/muying-invite-reward-unify-migration.sql` | 邀请奖励统一（trigger_event/reward_value/status） | ✅ 幂等 | 检查 `trigger_event` 列是否存在 |
| 5 | `muying-feature-flag-upgrade-migration.sql` | `docs/muying-feature-flag-upgrade-migration.sql` | 功能开关配置（feature_xxx_enabled） | ✅ 幂等 | 检查 `feature_feedback_enabled` 配置是否存在 |
| 6 | `muying-admin-power-migration.sql` | `docs/muying-admin-power-migration.sql` | 后台菜单权限（孕禧运营分组） | ✅ 幂等 | 检查 `孕禧运营` 菜单是否存在 |

---

## 手动执行命令

```bash
# 设置变量
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="yunxi"
DB_USER="yunxi"
DB_PASS="YOUR_PASSWORD"
SITE_DIR="/www/wwwroot/yunxi-api"
MYSQL="mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS $DB_NAME"

# 1. 主库（不可重复）
$MYSQL < $SITE_DIR/config/shopxo.sql

# 2. 孕禧核心表（不可重复）
$MYSQL < $SITE_DIR/../docs/muying-final-migration.sql

# 3. 反馈审核字段（幂等）
$MYSQL < $SITE_DIR/../docs/muying-feedback-review-migration.sql

# 4. 邀请奖励统一（幂等）
$MYSQL < $SITE_DIR/../docs/muying-invite-reward-unify-migration.sql

# 5. 功能开关升级（幂等）
$MYSQL < $SITE_DIR/../docs/muying-feature-flag-upgrade-migration.sql

# 6. 后台菜单权限（幂等）
$MYSQL < $SITE_DIR/../docs/muying-admin-power-migration.sql
```

---

## 验证

```bash
# 检查所有必需表
$MYSQL -e "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}' AND TABLE_NAME LIKE 'sxo_%' ORDER BY TABLE_NAME;" | grep -E '(activity|activity_signup|invite_reward|muying_feedback|config|payment|user)'

# 检查功能开关
$MYSQL -e "SELECT only_tag, value FROM sxo_config WHERE only_tag LIKE 'feature_%enabled';"

# 检查后台菜单
$MYSQL -e "SELECT id, name FROM sxo_power WHERE name='孕禧运营';"
```

---

## 常见问题

| 问题 | 原因 | 解决 |
|------|------|------|
| 主库导入报"Table already exists" | 主库已导入过 | 跳过步骤 1，从步骤 2 继续 |
| 核心表报"Table already exists" | 迁移已执行 | 跳过步骤 2，从步骤 3 继续 |
| 幂等迁移报"Duplicate column name" | 迁移已执行 | 跳过该步骤 |
| 幂等迁移报"Duplicate entry" | 配置已存在 | 跳过该步骤 |
| 后台菜单不显示 | 步骤 6 未执行 | 执行 muying-admin-power-migration.sql |
