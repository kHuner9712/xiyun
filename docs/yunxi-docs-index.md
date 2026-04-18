# 孕禧小程序 — 上线相关文档入口

## 核心文档

| 文档 | 路径 | 用途 |
|------|------|------|
| 最终上线 SQL | [docs/muying-final-migration.sql](docs/muying-final-migration.sql) | 数据库建表+补丁+索引，唯一真相源 |
| 正式部署手册 | [docs/yunxi-production-deployment.md](docs/yunxi-production-deployment.md) | 服务器部署全流程，照着执行 |
| 首批内容清单 | [docs/yunxi-launch-content-checklist.md](docs/yunxi-launch-content-checklist.md) | 后台初始化内容，照着配置 |

## 已废弃的旧文件

以下文件已合并到 `muying-final-migration.sql`，**不要直接执行**：

- `docs/muying-migration.sql`
- `docs/muying-mvp-migration.sql`
- `docs/muying-invite-code-migration.sql`
- `docs/muying-invite-idempotent-migration.sql`
- `docs/muying-enum-normalize-migration.sql`
- `shopxo-backend/sql/muying_feedback.sql`

## 项目结构

```
├── shopxo-backend/          # 后端（ThinkPHP）
├── shopxo-uniapp/           # 前端（uni-app 微信小程序）
└── docs/                    # 文档与 SQL
    ├── muying-final-migration.sql       # 最终上线 SQL
    ├── yunxi-production-deployment.md   # 部署手册
    └── yunxi-launch-content-checklist.md # 内容清单
```
