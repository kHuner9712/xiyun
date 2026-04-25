# 孕禧 V1.0 合规整改日志

本文件记录每次合规整改的详细信息，由 Trae AI 自动追加。

---

## 2026-04-25 — 第一轮合规整改（P0 + P1）

### 整改范围

将项目从「孕禧母婴商城（ShopXO 二开）」整改为「孕禧 V1.0：自营母婴服务小程序」，确保一期功能完全合规。

### 修改清单

#### P0-1: 前端 pages.json 补全一期允许插件页面

- **commit**: `e734a59` (前序) → 整批提交
- **涉及文件**: `shopxo-uniapp/pages.json`
- **修改内容**: 添加 coupon/signin/delivery/points 插件页面注册到 pages.json subPackages
- **自测结果**: 这些页面在 PHASE_ONE_ALLOWED_ROUTES 中但之前未注册，用户无法访问。注册后可通过路由守卫正常访问。

#### P0-2: 后端非插件API控制器合规拦截

- **commit**: `88959b7`
- **涉及文件**:
  - `shopxo-backend/app/api/controller/Common.php` — 添加 `$CONTROLLER_FEATURE_MAP` 集中式安全网
  - `shopxo-backend/app/api/controller/UserIntegral.php` — 添加 `feature_points_enabled` 检查
- **修改内容**:
  - Common.php 新增 `$CONTROLLER_FEATURE_MAP`，映射控制器名到功能开关 key
  - CommonInit() 中自动检查当前控制器是否需要功能开关
  - UserIntegral 添加 `CheckFeatureEnabled('feature_points_enabled')`
- **自测结果**: 集中式安全网覆盖 activity/article/feedback/invite/muyinguser/userintegral 六个控制器

#### P0-3: 前端DIY组件过滤高风险插件模块

- **commit**: `e734a59`
- **涉及文件**: `shopxo-uniapp/pages/diy/components/diy/diy.vue`
- **修改内容**:
  - 添加 `BLOCKED_DIY_MODULE_KEYS` 列表
  - init() 中过滤 diy_data 和 tabs_data，移除被屏蔽模块
- **自测结果**: 即使后端返回 ask/blog/shop/realstore/seckill 等 DIY 数据，前端不会渲染

#### P0-4: 后台菜单隐藏高风险模块 + 插件管理合规拦截

- **commit**: `a49fd15`
- **涉及文件**:
  - `docs/muying-final-migration.sql` — 修复 C7/C8 段
  - `shopxo-backend/app/admin/controller/Plugins.php` — 添加合规拦截
  - `shopxo-backend/app/admin/controller/Pluginsadmin.php` — 添加合规拦截
- **修改内容**:
  - C7: 从隐藏菜单列表中移除 coupon/signin（一期允许插件）
  - C8: 从强制关闭列表中移除 feature_coupon_enabled/feature_signin_enabled/feature_points_enabled
  - admin/Plugins.php: 添加 MuyingComplianceService::IsPluginBlocked 检查
  - admin/Pluginsadmin.php: Save/StatusUpdate/Upload 方法添加合规检查
- **自测结果**: 管理员无法通过后台访问/安装/启用被屏蔽的插件

#### P0-5: extraction-address 页面移除

- **commit**: `98e2c5c`
- **涉及文件**:
  - `shopxo-uniapp/common/js/config/compliance-scope.js` — 从 PHASE_ONE_ALLOWED_ROUTES 移除
  - `shopxo-uniapp/pages.json` — 移除 extraction-address 页面注册
- **修改内容**: extraction-address 是 realstore（多门店自提点）功能，一期应屏蔽
- **自测结果**: 路由守卫拦截 + 页面未注册，双重保障

#### P0-6: 数据库配置与功能开关一致性验证

- **验证结果**: muying-feature-switch-migration.sql / muying-final-migration.sql C8 / muying-constants.js / compliance-scope.js / MuyingComplianceService.php 五方一致

#### P1-1: 生产部署适配

- **commit**: `333ef97`
- **涉及文件**: `deploy/nginx.production.example.conf`
- **修改内容**:
  - fastcgi_pass 改为 `/tmp/php-cgi-81.sock`（宝塔默认）
  - 日志路径改为 `/www/wwwlogs/`（宝塔默认）
  - 添加宝塔 PHP-FPM socket 路径确认说明

#### P1-2: 提审配置安全检查

- **commit**: `efce963`
- **涉及文件**:
  - `shopxo-uniapp/manifest.json` — 设置 AppID + urlCheck=true
  - `shopxo-uniapp/project.config.json` — 设置 AppID
- **修改内容**:
  - mp-weixin.appid 设为 wxda7779770f53e901（测试号）
  - project.config.json appid 与 manifest.json 保持一致
  - urlCheck 从 false 改为 true

#### P1-3: preflight 检查脚本补充

- **commit**: `2a6c92f`
- **涉及文件**: `scripts/preflight/preflight-production-check.php`
- **修改内容**:
  - 添加检查 #10: MUYING_PRIVACY_KEY 必须配置且 >= 16 字符
  - 添加检查 #11: pages.json 不能包含被屏蔽插件页面

### 遗留风险

| # | 风险 | 严重性 | 说明 |
|---|------|--------|------|
| 1 | 正式 AppID 未申请 | 中 | 当前使用测试号 wxda7779770f53e901，提审前需替换为正式 AppID |
| 2 | 域名备案未完成 | 中 | ICP 备案进入管局审核阶段，体验版可用 IP，提审需备案域名 |
| 3 | MUYING_PRIVACY_KEY 未生成 | 高 | 生产部署时必须生成并配置，否则用户敏感数据无法加密 |
| 4 | 默认管理员密码 | 高 | 首次部署后必须修改默认管理员密码 |
| 5 | index 模块控制器 | 低 | Web 端 index 模块控制器未添加合规检查，但小程序不使用该模块 |
| 6 | ShopXO 升级兼容 | 中 | 二开代码使用 [MUYING-二开] 标注，但 ShopXO 大版本升级时需人工合并 |

---

## 2026-04-25 — 第二轮合规硬门禁整改（P0 合规硬门禁）

### 整改目标

在没有 ICP经营许可证、EDI、医疗、直播、支付储值等资质的阶段，确保高风险功能"前端路由不能进、前端请求不能发、后端接口不能调用、后台不能误开启、菜单不能显示"。

### 核心变更

1. **coupon/points/signin 从"一期允许"降级为"一期受控"** — 默认关闭，后台可按需开启，约束：非现金、不可提现、不可转让、仅自营商品
2. **统一合规拦截返回码为 -403** — 前后端完全对齐
3. **http.js 增加 plugins 请求拦截** — 即使前端绕过路由守卫，请求层也会拦截
4. **API 层合规日志增强** — 记录 controller/action/userID/IP
5. **后台功能开关门禁增强** — coupon/signin/points 不再无条件放行

### 修改清单

#### 前端（3个文件）

| 文件 | 修改内容 |
|------|---------|
| `shopxo-uniapp/common/js/config/compliance-scope.js` | coupon/signin/points 移入 PHASE_ONE_BLOCKED_PLUGINS；添加 FEATURE_FLAG_PLUGIN_MAP 映射；is_route_allowed 支持 is_plugin_allowed 放行 |
| `shopxo-uniapp/common/js/http.js` | 引入 is_plugin_allowed；增加 plugins 请求拦截 |
| `shopxo-uniapp/common/js/config/phase-one-scope.js` | 无需修改（逻辑自动跟随 compliance-scope.js） |

#### 后端（6个文件）

| 文件 | 修改内容 |
|------|---------|
| `shopxo-backend/app/service/MuyingComplianceService.php` | coupon/signin/points 移入 PHASE_ONE_BLOCKED_PLUGINS；添加 FEATURE_FLAG_PLUGIN_MAP；IsPhaseOneFeatureKey 移除 coupon/signin/points；TryToggleFeature 增加 LogComplianceToggle；LogComplianceBlock 增加 controller/action 字段；返回码 -10001 → -403 |
| `shopxo-backend/app/api/controller/Common.php` | ExitFeatureDisabled 增加 DB 日志记录（controller/action/userID/IP） |
| `shopxo-backend/app/api/controller/Plugins.php` | 返回码 -10000 → -403 |
| `shopxo-backend/app/admin/controller/Plugins.php` | 返回码 -10000 → -403 |
| `shopxo-backend/app/admin/controller/Pluginsadmin.php` | 返回码 -10000 → -403 |
| `shopxo-backend/app/admin/controller/Featureswitch.php` | coupon/signin/points 移到"营销功能（一期不开放）"组；desc 增加约束说明；返回码 -10001 → -403 |

#### SQL（2个文件）

| 文件 | 修改内容 |
|------|---------|
| `docs/sql/muying-feature-switch-migration.sql` | coupon/signin/points 从"一期基础能力"移到"高风险功能"（默认关闭=0） |
| `docs/sql/muying-compliance-center-migration.sql` | 合规日志表增加 controller/api_action/user_id 字段 |

#### 迁移（1个文件）

| 文件 | 修改内容 |
|------|---------|
| `docs/muying-final-migration.sql` | C7 隐藏优惠券/签到菜单；C8 强制关闭 coupon/signin/points 开关 |

#### 脚本（1个文件）

| 文件 | 修改内容 |
|------|---------|
| `scripts/preflight/preflight-production-check.php` | 增加 #12 高风险功能开关检查、#13 HTTPS 检查、#14 APP_DEBUG 检查 |

#### 文档（2个文件）

| 文件 | 修改内容 |
|------|---------|
| `docs/phase-one-compliance.md` | 新建：一期合规策略完整文档 |
| `docs/trae-remediation-log.md` | 追加本次整改记录 |

### 自测结果

| 测试项 | 结果 |
|--------|------|
| 前端 PHASE_ONE_BLOCKED_PLUGINS 包含 22 项 | 通过 |
| 后端 $PHASE_ONE_BLOCKED_PLUGINS 与前端一致 | 通过 |
| http.js plugins 请求拦截 | 通过 |
| Common.php $CONTROLLER_FEATURE_MAP 包含 userintegral | 通过 |
| Featureswitch coupon/signin/points 在"营销功能（一期不开放）"组 | 通过 |
| 所有合规拦截返回码统一为 -403 | 通过 |
| coupon/signin/points 在 PHASE_ONE_BLOCKED_PLUGINS 中 | 通过 |

### 遗留风险

| # | 风险 | 严重性 | 说明 |
|---|------|--------|------|
| 1 | 正式 AppID 未申请 | 中 | 当前使用测试号，提审前需替换 |
| 2 | 域名备案未完成 | 中 | 体验版可用 IP，提审需备案域名 |
| 3 | MUYING_PRIVACY_KEY 未生成 | 高 | 部署时必须配置 |
| 4 | 默认管理员密码 | 高 | 首次登录后必须修改 |
| 5 | 合规日志表 DDL 变更 | 中 | 需在生产环境执行 ALTER TABLE 添加 controller/api_action/user_id 列 |
| 6 | ShopXO 升级兼容 | 中 | [MUYING-二开] 标注代码需人工合并 |

---

## 2026-04-25 — 第三轮生产配置与微信小程序提审配置整改

### 整改目标

确保生产环境配置安全：HTTPS、正式 AppID、禁止 localhost/内网IP、禁止测试号、构建时硬校验。

### 核心变更

1. **manifest.json 不再硬编码 AppID** — appid 留空，通过 .env 环境变量注入
2. **requiredPrivateInfos/permission 清空** — 不默认申请定位权限，按需动态申请
3. **prod.js 增加构建时 5 项硬校验** — HTTPS/AppID/localhost/内网IP/测试号
4. **.env.production.example 增强** — 明确 HTTPS/AppID 约束，增加 UNI_APP_ENABLE_LOCATION
5. **.env.development.example 增强** — 明确测试号仅用于开发
6. **后端 .env.production.example 增强** — 增加宝塔部署注意事项 10 项
7. **新建宝塔部署文档** — docs/deploy-baota-production.md
8. **新建提审检查清单** — docs/wechat-miniapp-submit-checklist.md
9. **preflight 增加 3 项检查** — #15 密钥泄露、#16 测试号 AppID、#17 static_url HTTPS

### 修改清单

#### 前端（5个文件）

| 文件 | 修改内容 |
|------|---------|
| `shopxo-uniapp/manifest.json` | appid 清空、requiredPrivateInfos=[]、permission={}、urlCheck=false |
| `shopxo-uniapp/project.config.json` | appid 清空 |
| `shopxo-uniapp/common/js/config/prod.js` | 5项构建时硬校验（HTTPS/AppID/localhost/内网IP/测试号） |
| `shopxo-uniapp/.env.production.example` | 增加 HTTPS/AppID 约束、UNI_APP_ENABLE_LOCATION |
| `shopxo-uniapp/.env.development.example` | 增加测试号 AppID、UNI_APP_ENABLE_LOCATION |

#### 后端（1个文件）

| 文件 | 修改内容 |
|------|---------|
| `shopxo-backend/.env.production.example` | 增加宝塔部署注意事项 10 项、APP_DEBUG=false 强调 |

#### 脚本（1个文件）

| 文件 | 修改内容 |
|------|---------|
| `scripts/preflight/preflight-production-check.php` | 增加 #15 密钥泄露、#16 测试号 AppID、#17 static_url HTTPS |

#### 文档（2个新文件）

| 文件 | 修改内容 |
|------|---------|
| `docs/deploy-baota-production.md` | 新建：宝塔面板生产部署指南（7章） |
| `docs/wechat-miniapp-submit-checklist.md` | 新建：微信小程序提审检查清单（5章） |

### 自测结果

| 测试项 | 结果 |
|--------|------|
| prod.js 5项硬校验完整 | 通过 |
| manifest.json appid 为空 | 通过 |
| manifest.json requiredPrivateInfos 为空 | 通过 |
| project.config.json appid 为空 | 通过 |
| .env.development.example 含测试号 | 通过 |
| .env.production.example 含 HTTPS 约束 | 通过 |
| 后端 .env.production.example 含宝塔注意事项 | 通过 |
| 宝塔部署文档存在 | 通过 |
| 提审检查清单存在 | 通过 |
| preflight #15/#16/#17 存在 | 通过 |

### 遗留风险

| # | 风险 | 严重性 | 说明 |
|---|------|--------|------|
| 1 | 正式 AppID 未申请 | 中 | 当前 appid 为空，开发用 .env.development 注入测试号 |
| 2 | 域名备案未完成 | 中 | 体验版可用 IP，提审需备案域名 |
| 3 | 定位权限运行时注入 | 中 | manifest.json 不含定位权限，需在代码中按需动态申请 |
| 4 | urlCheck 提审时需开启 | 低 | 当前 urlCheck=false（开发用），提审时微信后台会自动校验合法域名 |

---

## 2026-04-25 — 第四轮母婴敏感数据隐私与后台权限整改

### 整改目标

确保后台敏感数据默认脱敏、查看明文需权限、导出需权限、操作有审计。

### 核心变更

1. **CanViewSensitive 从空实现改为权限校验** — 基于 `muyingsensitive/view` 权限 key
2. **新增 CanExportSensitive** — 基于 `muyingsensitive/export` 权限 key
3. **报名导出从始终明文改为权限控制** — 无权限导出脱敏数据
4. **反馈详情页修复** — 增加 MaskFeedbackRow + 关联用户手机号脱敏 + 审计日志
5. **数据库迁移补齐** — phone_hash/is_waitlist/signup_code 字段
6. **敏感数据权限 SQL** — 新建 muying-sensitive-permission-migration.sql

### 修改清单

#### 后端（4个文件）

| 文件 | 修改内容 |
|------|---------|
| `shopxo-backend/app/service/MuyingPrivacyService.php` | CanViewSensitive 改为权限校验（AdminIsPower + 数据库查询）；新增 CanExportSensitive |
| `shopxo-backend/app/service/ActivityService.php` | SignupExport 增加权限控制，无权限导出脱敏数据；审计日志区分明文/脱敏导出 |
| `shopxo-backend/app/admin/controller/Feedback.php` | Detail 增加 MaskFeedbackRow + 关联用户手机号脱敏 + 审计日志 + can_view_sensitive 传递 |
| `shopxo-backend/app/admin/controller/Activitysignup.php` | Export 增加 CanExportSensitive 权限检查 |

#### SQL（2个文件）

| 文件 | 修改内容 |
|------|---------|
| `docs/sql/muying-sensitive-permission-migration.sql` | 新建：注册 muyingsensitive 权限（View/Export）到 sxo_power 表 |
| `docs/muying-final-migration.sql` | 补齐 B5b: phone_hash 字段、B5c: is_waitlist/signup_code 字段 |

### 自测结果

| 测试项 | 结果 |
|--------|------|
| CanViewSensitive 不再始终返回 true | 通过（改为权限校验） |
| CanExportSensitive 独立于 CanViewSensitive | 通过 |
| 报名导出无权限时输出脱敏数据 | 通过 |
| 反馈详情页联系方式已脱敏 | 通过 |
| 反馈详情页关联用户手机号已脱敏 | 通过 |
| 反馈详情页查看明文记录审计日志 | 通过 |
| phone_hash 字段已补齐迁移 SQL | 通过 |
| 小程序报名页隐私协议勾选 | 通过（已有） |
| 小程序个人资料页选填标注 | 通过（已有） |

### 遗留风险

| # | 风险 | 严重性 | 说明 |
|---|------|--------|------|
| 1 | 超级管理员默认有敏感权限 | 低 | id=780-782 已授权给 role_id=1，其他角色需手动授权 |
| 2 | 反馈模块无导出功能 | 低 | FeedbackExport 方法不存在，如需导出需后续开发 |
| 3 | 报名列表搜索手机号 | 中 | 数据已加密，like 查询无法命中，需用 phone_hash 精确匹配（已实现） |

---

## 2026-04-26 — 第五轮母婴敏感数据隐私与后台权限整改（续）

### 整改目标

补齐第四轮遗留的后台模板脱敏、控制器脱敏、审计日志表迁移、权限按钮等关键缺口。

### 核心变更

1. **Activity 控制器 Detail() 报名列表脱敏** — 之前直接传原始数据给模板，现通过 MaskSignupRow 脱敏
2. **Activitysignup 控制器 Detail() 改造** — 从空实现改为调用 AdminSignupDetail 服务方法，确保脱敏和审计
3. **后台模板统一添加脱敏标识** — 姓名/手机/联系方式旁标注"明文"或"已脱敏"徽章
4. **活动详情页报名列表添加权限提示** — 有权限显示警告提示，无权限显示脱敏提示
5. **导出按钮权限控制** — 有导出权限显示"明文导出"，无权限显示"脱敏导出"+ 提示
6. **MuyingAuditLogService 增强** — 增加 admin_username、target_id 字段写入，与迁移 SQL 对齐
7. **muying-final-migration.sql 补齐 D 段** — 审计日志表建表 + 敏感数据权限注册 + 角色授权 + 密钥配置项
8. **lib/module/user.html 公共模块脱敏标识** — 手机号旁添加脱敏锁图标

### 修改清单

#### 后端（5个文件）

| 文件 | 修改内容 |
|------|---------|
| `shopxo-backend/app/admin/controller/Activity.php` | 引入 MuyingPrivacyService；Detail() 报名列表通过 MaskSignupRow 脱敏；传递 can_view_sensitive/can_export_sensitive |
| `shopxo-backend/app/admin/controller/Activitysignup.php` | Detail() 从空实现改为调用 AdminSignupDetail；传递 can_view_sensitive/can_export_sensitive |
| `shopxo-backend/app/service/MuyingAuditLogService.php` | Log() 增加 admin_username/target_id 字段；LogExport/LogSensitiveView 增加 admin_username 传递；自动回填 admin_username |
| `shopxo-backend/app/service/MuyingPrivacyService.php` | CanViewSensitive 改为权限校验；新增 CanExportSensitive（第四轮已完成） |
| `shopxo-backend/app/service/ActivityService.php` | SignupExport 增加权限控制（第四轮已完成） |

#### 模板（4个文件）

| 文件 | 修改内容 |
|------|---------|
| `shopxo-backend/app/admin/view/default/activitysignup/detail.html` | 姓名/手机旁添加"明文"/"已脱敏"徽章 |
| `shopxo-backend/app/admin/view/default/activity/detail.html` | 报名列表添加权限提示框 + 导出按钮权限控制（明文/脱敏） |
| `shopxo-backend/app/admin/view/default/feedback/detail.html` | 联系方式/关联用户手机号旁添加"明文"/"已脱敏"徽章 |
| `shopxo-backend/app/admin/view/default/lib/module/user.html` | 手机号旁添加脱敏锁图标（mobile_masked 标记） |

#### SQL（1个文件）

| 文件 | 修改内容 |
|------|---------|
| `docs/muying-final-migration.sql` | 新增 D 段：D1 审计日志表、D2 敏感数据权限注册、D3 超管授权、D4 密钥配置项 |

### 自测结果

| 测试项 | 结果 |
|--------|------|
| Activity Detail() 报名列表姓名/手机已脱敏 | 通过（MaskSignupRow） |
| Activitysignup Detail() 调用 AdminSignupDetail | 通过（含脱敏+审计） |
| 报名详情页姓名/手机有脱敏标识 | 通过 |
| 活动详情页报名列表有权限提示 | 通过 |
| 导出按钮根据权限显示 | 通过 |
| 反馈详情页联系方式/用户手机有脱敏标识 | 通过 |
| MuyingAuditLogService 写入 admin_username/target_id | 通过 |
| muying-final-migration.sql D 段完整 | 通过 |
| 审计日志不记录明文手机号 | 通过（conditions 仅含 target_id） |
| 手机号重复校验仍用 phone_hash | 通过 |

### 遗留风险

| # | 风险 | 严重性 | 说明 |
|---|------|--------|------|
| 1 | user/saveinfo.html 编辑表单手机号 | 中 | 管理员编辑用户时需看到手机号，属于必要操作；建议后续增加编辑审计日志 |
| 2 | lib/module/user.html 脱敏依赖上游传 mobile_masked | 中 | 需上游控制器在 module_data 中设置 mobile_masked 标记，当前仅添加了模板条件渲染 |
| 3 | 反馈模块无导出功能 | 低 | FeedbackExport 方法不存在，如需导出需后续开发 |
| 4 | 报名列表搜索手机号 | 中 | 数据已加密，需用 phone_hash 精确匹配（已实现） |
| 5 | 旧数据未加密 | 高 | 需运行 scripts/migrate-encrypt-sensitive.php 将明文数据加密 |
