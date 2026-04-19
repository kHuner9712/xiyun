# 孕禧小程序 — 上线前预检脚本

## 文件说明

| 文件 | 用途 | 执行方式 |
|------|------|---------|
| `check-server.sh` | 服务器环境+数据库全量预检 | `bash check-server.sh /path/to/shopxo-backend` |
| `check-db.sql` | 纯数据库结构预检 | `mysql -u root -p shopxo < check-db.sql` |

## 快速开始

### 方式一：一键全量预检（推荐）

```bash
# 在服务器上执行
cd /path/to/yunxi/scripts/preflight

# 基本用法（默认数据库 root@127.0.0.1:3306/shopxo）
bash check-server.sh /var/www/yunxi/shopxo-backend

# 指定数据库连接
DB_HOST=127.0.0.1 DB_PORT=3306 DB_USER=shopxo DB_PASS=yourpass DB_NAME=shopxo DB_PREFIX=sxo_ \
  bash check-server.sh /var/www/yunxi/shopxo-backend
```

### 方式二：仅检查数据库

```bash
mysql -u shopxo -p shopxo < check-db.sql
```

## 检查项清单

### check-server.sh 覆盖项

| 类别 | 检查项 | 阻断级别 |
|------|--------|---------|
| 基础环境 | PHP ≥8.0.2 | FAIL |
| 基础环境 | MySQL ≥5.6 | FAIL |
| 基础环境 | Composer 可用 | FAIL |
| PHP 扩展 | pdo_mysql, mbstring, curl, gd, openssl, json, xml | FAIL |
| PHP 扩展 | redis, bcmath（可选） | WARN |
| 目录权限 | runtime, upload, download, storage, rsakeys, resources | FAIL |
| 安全配置 | install.php 已删除 | FAIL |
| 安全配置 | APP_DEBUG = false | FAIL |
| 安全配置 | show_error_msg = false | FAIL |
| 安全配置 | 管理后台入口存在 | WARN |
| 数据库 | 连接成功 | FAIL |
| 数据库 | 必需表存在（7张） | FAIL |
| 数据库 | 关键字段存在（9个） | FAIL |
| 数据库 | 关键索引存在（2个） | FAIL |
| 配置项 | 6个关键配置项已插入 | FAIL |
| 数据完整性 | 邀请码空值 | WARN |
| 数据完整性 | 活动数据 | WARN |
| 数据完整性 | 妈妈说数据 | WARN |

### check-db.sql 覆盖项

| 类别 | 检查项 |
|------|--------|
| 必需表 | sxo_activity, sxo_activity_signup, sxo_invite_reward, sxo_muying_feedback, sxo_user, sxo_goods_favor, sxo_config |
| 关键字段 | privacy_agreed_time, type, current_stage, due_date, baby_birthday, invite_code, suitable_crowd, stage, category |
| 关键索引 | uk_invite_code, uk_inviter_invitee_event |
| 配置项 | muying_invite_register_reward, muying_invite_first_order_reward, home_site_name, common_app_is_weixin_force_user_base, common_user_is_mandatory_bind_mobile, home_search_keywords |
| 数据完整性 | 邀请码空值、邀请奖励重复、活动数据、妈妈说数据 |
| 阶段筛选 | 备孕/孕期/产后分类关键词命中 |

## 结果判定

| 状态 | 含义 | 是否阻断上线 |
|------|------|:---:|
| PASS | 检查通过 | 否 |
| WARN | 建议修复 | 否（但强烈建议处理） |
| FAIL | 必须修复 | **是** |

- 存在任何 FAIL 项 → 脚本退出码 1，不建议上线
- 仅有 WARN 项 → 脚本退出码 0，建议处理后再上线
- 全部 PASS → 脚本退出码 0，可以上线

## 输出示例

```
==========================================
 1. 基础环境
==========================================
[PASS] PHP 版本: 8.1.2 (≥8.0.2)
[PASS] MySQL 版本: 8.0 (≥5.6)
[PASS] Composer: Composer version 2.5.1

==========================================
 2. PHP 扩展
==========================================
[PASS] PHP 扩展: pdo_mysql
[PASS] PHP 扩展: mbstring
[FAIL] PHP 扩展: redis 缺失 | 修复: apt install php8.1-redis

==========================================
 3. 目录权限
==========================================
[PASS] 目录可写: runtime
[FAIL] 目录不可写: public/static/upload | 修复: chmod -R 755 public/static/upload

==========================================
 4. 安全配置
==========================================
[PASS] install.php 已删除
[FAIL] APP_DEBUG = true | 修复: 修改 .env 中 APP_DEBUG=false

==========================================
 检查汇总
==========================================
  PASS: 18  WARN: 2  FAIL: 3  总计: 23

存在 3 个 FAIL 项，不建议上线！
请按上述 FAIL 项的修复建议逐项处理。
```

## 哪些问题会阻断上线

以下 FAIL 项会阻断上线（必须修复）：

| # | FAIL 项 | 后果 | 修复方式 |
|---|---------|------|---------|
| 1 | PHP 版本不足 | 后端无法运行 | 升级 PHP |
| 2 | MySQL 版本不足 | SQL 语法报错 | 升级 MySQL |
| 3 | 必需 PHP 扩展缺失 | 功能异常 | apt install php8.1-{ext} |
| 4 | 目录不可写 | 上传/缓存失败 | chmod/chown |
| 5 | install.php 存在 | 安全风险 | rm public/install.php |
| 6 | APP_DEBUG = true | 暴露错误信息 | 修改 .env |
| 7 | 必需表缺失 | 功能不可用 | 执行 migration SQL |
| 8 | 关键字段缺失 | 功能报错 | 执行 migration SQL B 段 |
| 9 | 关键索引缺失 | 性能/数据一致性 | 执行 migration SQL C 段 |
| 10 | 邀请奖励配置缺失 | 奖励=0，伤害信任 | 执行 yunxi-init-config.sql |
| 11 | 商品分类无阶段关键词 | 阶段推荐返回空 | 创建含关键词的分类 |

以下 WARN 项不阻断但强烈建议修复：

| # | WARN 项 | 后果 |
|---|---------|------|
| 1 | 可选 PHP 扩展缺失 | 部分功能降级 |
| 2 | 邀请码空值 | 邀请链路不完整 |
| 3 | 活动数据为空 | 首页/活动页空白 |
| 4 | 妈妈说数据为空 | 首页缺少社区氛围 |
