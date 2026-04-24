# RC 封板前总检查报告

> 项目：孕禧一期  
> 日期：2026-04-24  
> 结论：**代码层面已封板，体验版可立即上线；提审需等待正式 AppID + 备案域名**

---

> **自动化检查脚本对应关系**  
> 本报告所有检查项均可通过脚本自动验证：  
> - 代码/配置占位符 → `check-release-placeholders.sh --mode=experience`  
> - 运行时配置完整性 → `check-runtime-config.sh --env /path/to/.env`  
> - 后台管理完整性 → `check-admin-bootstrap.sh .`  
> - 微信提审就绪 → `check-wechat-submit-readiness.sh .`  
> - 一键全量检查 → `run-rc-gate.sh --mode=experience --env /path/to/.env .`

---

## 一、代码完成度

| 模块 | 代码 | 后台页面 | 后台菜单 | 前台页面 | 状态 |
|------|------|----------|----------|----------|------|
| 用户注册登录 | ✅ | ✅ | ✅(系统自带) | ✅ | 完成 |
| 阶段设置与资料 | ✅ | ✅ | ✅(系统自带) | ✅ | 完成 |
| 首页阶段推荐 | ✅ | ✅ | ✅(系统自带) | ✅ | 完成 |
| 活动列表/详情/报名/取消 | ✅ | ✅ | ✅(已注册) | ✅ | 完成 |
| 活动签到核销 | ✅ | ✅ | ✅(已注册) | ✅(后台操作) | 完成 |
| 商品浏览/下单/支付 | ✅ | ✅ | ✅(系统自带) | ✅ | 完成 |
| 邀请关系绑定 | ✅ | - | - | ✅ | 完成 |
| 首单奖励发放 | ✅ | ✅ | ✅(已注册) | ✅ | 完成 |
| 邀请奖励配置 | ✅ | ✅ | ✅(已注册) | ✅ | 完成 |
| 邀请奖励补发/撤销 | ✅ | ✅ | ✅(已注册) | ✅(后台操作) | 完成 |
| 妈妈说提交 | ✅ | - | - | ✅ | 完成 |
| 妈妈说审核 | ✅ | ✅ | ✅(已注册) | ✅(后台操作) | 完成 |
| 功能开关配置 | ✅ | ✅ | ✅(已注册) | ✅ | 完成 |
| 隐私政策 | ✅ | ✅ | ✅(系统自带) | ✅ | 完成 |
| 用户协议 | ✅ | ✅ | ✅(系统自带) | ✅ | 完成 |
| 客服入口 | ✅ | ✅ | ✅(系统自带) | ✅ | 完成 |
| 反馈入口 | ✅ | ✅ | ✅(已注册) | ✅ | 完成 |
| 支付未配置兜底 | ✅ | - | - | ✅ | 完成（本轮补齐） |

---

## 二、死角排查结果

### 已修复

| 问题 | 修复动作 |
|------|----------|
| `feature_feedback_enabled` 前端未使用，反馈入口无法关闭 | user.vue 反馈入口加 `v-if="is_feature_enabled(FeatureFlagKey.FEEDBACK)"` |
| 首页妈妈说区块用 CONTENT 开关而非 FEEDBACK 开关 | index.vue 妈妈说区块改用 `FeatureFlagKey.FEEDBACK` |
| 支付未配置时前端无友好提示 | buy.vue 补充"当前为体验版/支付未开通"提示 |
| 支付未配置时后端无专用错误码 | BuyService 补充 `payment_not_configured` 错误码 |

### 已知限制（不影响提审）

| 问题 | 等级 | 说明 |
|------|------|------|
| 120+ 个插件页面未在 pages.json 注册 | P2 | 一期路由守卫会拦截，不会导航到这些页面 |
| 15+ 个用户页面缺少显式登录检查 | P2 | API 层 is_login_check 兜底，用户会先看到空白再跳转 |
| chooseAvatar 按钮 @tap 和 open-type 可能双重触发 | P2 | 实际测试中未发现重复调用，但代码层面存在风险 |

---

## 三、权限最小化确认

| 权限项 | 状态 |
|--------|------|
| requiredPrivateInfos 仅 chooseLocation + getLocation | ✅ |
| Android 高敏感权限已移除 | ✅ |
| iOS 后台定位声明已移除 | ✅ |
| app-plus 模块已精简 | ✅ |
| 位置权限 desc 含具体场景 | ✅ |
| 位置监听 API 已条件编译排除 | ✅ |
| 隐私政策含位置/相册/摄像头说明 | ✅ |

---

## 四、功能开关前后端一致性

| 开关 | 后端(API守卫) | 前端(UI控制) | 一致性 |
|------|-------------|------------|--------|
| feature_activity_enabled | ✅ API层 CheckFeatureEnabled | ✅ 首页+用户中心 v-if | ✅ 一致 |
| feature_invite_enabled | ✅ API层 CheckFeatureEnabled | ✅ 首页+用户中心 v-if | ✅ 一致 |
| feature_content_enabled | ✅ API层 CheckFeatureEnabled | ✅ 首页孕育知识 v-if | ✅ 一致 |
| feature_feedback_enabled | ✅ API层 CheckFeatureEnabled | ✅ 首页妈妈说+用户中心反馈 v-if | ✅ 一致（本轮修复） |

---

## 五、真实阻塞项

### 体验版上线：无外部阻塞

> **限定条件**：在测试号 AppID + 服务器 IP/测试域名 + 未启用正式支付的前提下，体验版无外部阻塞

### 提审上线：4 项外部阻塞

| 序号 | 阻塞项 | 预计耗时 | 说明 |
|------|--------|----------|------|
| 1 | 正式微信小程序 AppID | 1-3 天 | 注册微信公众平台获取 |
| 2 | ICP 域名备案 | 7-20 天 | 域名未备案则微信审核不通过 |
| 3 | 微信公众平台隐私保护指引 | 30 分钟 | 需在微信后台填写 |
| 4 | 微信公众平台合法域名 | 30 分钟 | 需备案域名后配置 |

### 正式发布：1 项额外阻塞

| 序号 | 阻塞项 | 预计耗时 | 说明 |
|------|--------|----------|------|
| 5 | 微信支付商户号 | 3-7 天 | 支付功能上线前必须完成 |

> **注意**：服务器+宝塔+Nginx+PHP+MySQL 已具备，不列为阻塞项。

---

## 六、自动化检查脚本清单

| 脚本 | 检查内容 | 输出等级 |
|------|----------|----------|
| `check-release-placeholders.sh` | manifest/project.config/.env 占位符/空值/IP/测试值 | PASS/WARN/BLOCKER |
| `check-runtime-config.sh` | 数据库表/功能开关/邀请配置/客服电话/隐私协议/支付方式 | PASS/WARN/BLOCKER |
| `check-admin-bootstrap.sh` | 后台入口/控制器/视图/菜单权限 | PASS/WARN/BLOCKER |
| `check-wechat-submit-readiness.sh` | AppID/隐私合规/域名/安全配置/测试内容 | PASS/WARN/BLOCKER |
| `run-rc-gate.sh` | 一键执行上述全部检查 | PASS/WARN/BLOCKER |
