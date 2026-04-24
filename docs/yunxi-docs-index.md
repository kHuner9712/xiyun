# 孕禧小程序 — 文档索引

> 最后更新：2026-04-24
> 本索引只保留当前发布链路中真正有用的文档入口

---

## 一、发布链路文档（执行文档，按流程顺序）

| 序号 | 文档 | 路径 | 用途 | 类型 |
|:---:|------|------|------|:---:|
| 1 | 体验版部署 Runbook | [docs/release/experience-deploy-runbook.md](release/experience-deploy-runbook.md) | 7 步一键部署体验版 | 执行 |
| 2 | 体验版上线执行清单 | [docs/release/experience-version-launch-checklist.md](release/experience-version-launch-checklist.md) | 5 阶段详细版清单，首次部署逐项确认 | 执行 |
| 3 | 后台首次登录配置清单 | [docs/release/admin-first-login-checklist.md](release/admin-first-login-checklist.md) | 后端部署后 11 步配置 | 执行 |
| 4 | 运营首批数据模板 | [docs/release/seed-content-template.md](release/seed-content-template.md) | 活动/商品/文章/妈妈说录入示例 | 执行 |
| 5 | 体验版 Smoke Test | [docs/release/experience-smoke-test.md](release/experience-smoke-test.md) | 18 步核心链路验收 | 执行 |
| 6 | 提审切换 Runbook | [docs/release/submit-switch-runbook.md](release/submit-switch-runbook.md) | 从体验版切到提审版 8 步 | 执行 |
| 7 | 提审材料准备清单 | [docs/release/submission-materials-checklist.md](release/submission-materials-checklist.md) | 服务类目/隐私/截图/测试账号/客服 | 执行 |
| 8 | 宝塔部署与回滚手册 | [docs/release/bt-deploy-rollback-guide.md](release/bt-deploy-rollback-guide.md) | 宝塔+Nginx+HTTPS+安全加固+回滚 | 参考 |
| 9 | 数据库迁移执行顺序 | [docs/release/db-migration-order.md](release/db-migration-order.md) | SQL 执行顺序+一键脚本+验证 | 执行 |
| 10 | 正式上线前人工配置清单 | [docs/release/pre-launch-config-checklist.md](release/pre-launch-config-checklist.md) | 三阶段配置项 | 执行 |
| 11 | UAT 最终验收清单 | [docs/release/uat-final-checklist.md](release/uat-final-checklist.md) | 真机验收 15 大类 22 阻断项 | 执行 |

## 二、报告文档（只读参考）

| 文档 | 路径 | 用途 | 类型 |
|------|------|------|:---:|
| RC 封板前总检查报告 | [docs/release/rc-gate-report.md](release/rc-gate-report.md) | 代码完成度+死角排查+真实阻塞项 | 报告 |

## 三、数据库迁移 SQL（执行顺序见 db-migration-order.md）

### 主链路 SQL（必须按顺序执行）

| 序号 | 文件 | 路径 | 用途 | 可重复 |
|:---:|------|------|------|:---:|
| 1 | shopxo.sql | `shopxo-backend/config/shopxo.sql` | ShopXO 主库初始化 | ❌ |
| 2 | muying-final-migration.sql | [docs/muying-final-migration.sql](muying-final-migration.sql) | 孕禧核心表+补丁+索引（唯一真相源） | ❌ |
| 3 | muying-feedback-review-migration.sql | [docs/muying-feedback-review-migration.sql](muying-feedback-review-migration.sql) | 反馈审核字段 | ✅ |
| 4 | muying-invite-reward-unify-migration.sql | [docs/muying-invite-reward-unify-migration.sql](muying-invite-reward-unify-migration.sql) | 邀请奖励统一 | ✅ |
| 5 | muying-feature-flag-upgrade-migration.sql | [docs/muying-feature-flag-upgrade-migration.sql](muying-feature-flag-upgrade-migration.sql) | 功能开关配置 | ✅ |
| 6 | muying-admin-power-migration.sql | [docs/muying-admin-power-migration.sql](muying-admin-power-migration.sql) | 后台菜单权限 | ✅ |

### 演示数据 SQL（可选）

| 文件 | 路径 | 用途 |
|------|------|------|
| yunxi-init-config.sql | [docs/sql/yunxi-init-config.sql](sql/yunxi-init-config.sql) | 配置项初始化 |
| yunxi-init-activity-demo.sql | [docs/sql/yunxi-init-activity-demo.sql](sql/yunxi-init-activity-demo.sql) | 活动演示数据 |
| yunxi-init-feedback-demo.sql | [docs/sql/yunxi-init-feedback-demo.sql](sql/yunxi-init-feedback-demo.sql) | 妈妈说演示数据 |

## 四、自动化脚本

| 脚本 | 路径 | 用途 |
|------|------|------|
| bootstrap-backend.sh | `scripts/deploy/bootstrap-backend.sh` | 一键部署后端 |
| run-migrations.sh | `scripts/deploy/run-migrations.sh` | 一键执行数据库迁移 |
| fix-permissions.sh | `scripts/deploy/fix-permissions.sh` | 修复目录权限 |
| post-deploy-check.sh | `scripts/deploy/post-deploy-check.sh` | 部署后自动验收 |
| rollback-guide.sh | `scripts/deploy/rollback-guide.sh` | 回滚 |
| run-rc-gate.sh | `scripts/preflight/run-rc-gate.sh` | RC 门禁一键检查 |
| check-release-placeholders.sh | `scripts/preflight/check-release-placeholders.sh` | 发布占位符与配置值检查 |
| check-wechat-submit-readiness.sh | `scripts/preflight/check-wechat-submit-readiness.sh` | 提审就绪检查 |
| check-server.sh | `scripts/preflight/check-server.sh` | 服务器环境预检 |
| check-runtime-config.sh | `scripts/preflight/check-runtime-config.sh` | 运行时配置检查 |
| check-admin-bootstrap.sh | `scripts/preflight/check-admin-bootstrap.sh` | 后台初始化检查 |

## 五、已归档文档

以下文档已移至 `docs/archive/`，不在当前发布链路中，仅供参考：

| 归档目录 | 内容 | 说明 |
|----------|------|------|
| `docs/archive/deployment/` | 旧部署文档 | 已被 bt-deploy-rollback-guide.md 替代 |
| `docs/archive/design/` | 设计文档 | 阶段性设计说明，开发参考 |
| `docs/archive/guides/` | 旧指南+项目总览 | 历史开发文档，含项目结构/本地启动/已知问题等 |
| `docs/archive/release/` | 旧发布文档 | 已被 release/ 下新文档替代 |
| `docs/archive/retro/` | 整改说明 | 历史整改记录 |
| `docs/archive/sql/` | 旧迁移 SQL | 已合并到 muying-final-migration.sql |

## 六、MySQL 版本要求

- 最低：MySQL 5.6+（utf8mb4）
- 推荐：MySQL 5.7+ / 8.0
- 所有 SQL 已兼容 5.6+，不依赖 `ADD COLUMN IF NOT EXISTS` 等仅 8.0+ 支持的语法

## 七、项目结构

```
├── shopxo-backend/              # 后端（ThinkPHP）
├── shopxo-uniapp/               # 前端（uni-app 微信小程序）
├── scripts/
│   ├── deploy/                  # 部署脚本（6 个）
│   └── preflight/               # 预检脚本（7 个 + 测试）
├── docs/
│   ├── muying-final-migration.sql         # 核心迁移 SQL（唯一真相源）
│   ├── muying-*-migration.sql             # 增量迁移 SQL（4 个）
│   ├── yunxi-docs-index.md                # 本索引
│   ├── release/                           # 发布链路文档（12 个）
│   ├── sql/                               # 演示数据 SQL（3 个）
│   └── archive/                           # 已归档文档
│       ├── deployment/                    # 旧部署文档
│       ├── design/                        # 设计文档
│       ├── guides/                        # 旧指南
│       ├── release/                       # 旧发布文档
│       ├── retro/                         # 整改说明
│       └── sql/                           # 旧迁移 SQL
├── deploy/                      # Nginx 生产配置示例
└── docker/                      # Docker 开发环境配置
```
