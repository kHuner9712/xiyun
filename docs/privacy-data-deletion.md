# 孕禧 V1.0 用户数据删除/匿名化处理流程

---

## 一、概述

本文档描述孕禧平台处理用户数据删除/匿名化请求的完整流程，确保符合《个人信息保护法》要求。

## 二、用户申请入口

| 入口 | 位置 | 说明 |
|------|------|------|
| 反馈提交 | 小程序 → 反馈 → 类型选择"数据删除/隐私请求" | 用户提交申请，后台审核 |
| 联系客服 | 小程序 → 设置 → 客服电话 | 用户口头申请 |
| 隐私政策 | 小程序 → 关于我们 → 隐私政策 → 第五章"您的权利" | 说明申请方式 |

## 三、后台处理流程

### 3.1 查找用户

1. 登录后台 → 孕禧运营 → 隐私数据管理
2. 通过用户 ID / 手机号 / openid 搜索用户
3. 系统展示用户关联数据（默认脱敏）：
   - 用户基础资料（昵称、手机号、孕育阶段、预产期、宝宝生日）
   - 活动报名记录
   - 反馈记录
   - 邀请关系
   - 订单记录

### 3.2 执行匿名化

**前置条件：**
- 操作人必须是超级管理员或拥有 `muyingprivacy/delete` 权限
- 必须二次确认操作

**匿名化处理范围：**

| 数据项 | 处理方式 |
|--------|----------|
| 用户昵称 | 改为"已注销用户" |
| 手机号 | 保留（避免破坏登录和订单关联），但脱敏展示 |
| current_stage | 清空 |
| due_date | 清零 |
| baby_birthday | 清零 |
| 活动报名姓名 | 替换为加密的"已注销用户" |
| 活动报名手机号 | 替换为加密的"ANONYMIZED" + 重新 hash |
| 反馈联系方式 | 清空 |
| 反馈联系方式 hash | 清空 |
| 邀请注册奖励 | 状态改为已失效（status=2） |

**不处理的数据：**

| 数据项 | 原因 |
|--------|------|
| 订单记录 | 财务/售后链路需要，不可删除 |
| 订单中的用户信息 | 保留必要售后信息，但个人敏感字段已匿名化 |
| 用户 ID | 保留用于订单关联 |

### 3.3 操作权限

| 权限 | 说明 |
|------|------|
| 超级管理员（id=1） | 可执行匿名化 |
| muyingprivacy/delete 权限 | 可执行匿名化 |
| 其他角色 | 不可见匿名化按钮 |

### 3.4 审计日志

每次匿名化操作自动记录审计日志：
- 操作管理员 ID 和用户名
- 操作场景：data_anonymize
- 目标用户 ID
- 操作时间
- 操作 IP
- **不记录明文手机号**

## 四、处理时效

- 收到申请后 15 个工作日内完成处理
- 处理完成后通过反馈系统回复用户

## 五、数据库迁移

执行以下 SQL 文件以启用此功能：

1. `docs/sql/muying-feedback-type-migration.sql` — 反馈表增加 type 字段
2. `docs/sql/muying-privacy-power-migration.sql` — 后台菜单权限注册

## 六、相关文件

| 文件 | 说明 |
|------|------|
| `shopxo-backend/app/service/MuyingDataAnonymizeService.php` | 数据匿名化核心服务 |
| `shopxo-backend/app/admin/controller/Muyingprivacy.php` | 后台隐私数据管理控制器 |
| `shopxo-backend/app/admin/view/default/muyingprivacy/index.html` | 后台隐私数据管理页面 |
| `shopxo-uniapp/pages/feedback-submit/feedback-submit.vue` | 小程序反馈提交页（含隐私请求类型） |
| `shopxo-uniapp/pages/agreement/agreement.vue` | 隐私政策（含数据删除说明） |
| `shopxo-uniapp/pages/about/about.vue` | 关于我们（含数据删除申请入口） |
