# 孕禧小程序正式服务器部署执行手册

## 1. 部署前准备

### 1.1 服务器环境要求

| 项目 | 最低要求 | 推荐配置 |
|------|----------|----------|
| PHP | ≥8.0.2 | 8.1+ |
| MySQL | ≥5.6（utf8mb4） | 5.7+ / 8.0 |
| Redis | 可选 | 推荐 6.0+ |
| Nginx | 1.18+ | 1.22+ |
| PHP 扩展 | pdo_mysql, mbstring, curl, gd, openssl, json, xml | 同左 + redis |
| 磁盘空间 | ≥2GB | ≥10GB（含上传文件） |

### 1.2 域名与证书

| 域名类型 | 用途 | 是否必须 |
|----------|------|----------|
| API 域名 | 后端接口，如 `api.yunxi.com` | 必须，需 HTTPS |
| 静态资源域名 | CSS/JS/图片 CDN | 可选，可与 API 同域 |
| 上传文件域名 | 图片上传，与 API 同域 | 必须，需 HTTPS |

### 1.3 微信小程序后台准备

| 配置项 | 位置 | 说明 |
|--------|------|------|
| AppID | 微信后台 → 开发管理 → 开发设置 | 当前 `wxda7779770f53e901` |
| AppSecret | 同上 | 需在微信后台获取 |
| request 合法域名 | 同上 → 服务器域名 | 添加 `https://api.yunxi.com` |
| uploadFile 合法域名 | 同上 | 添加 `https://api.yunxi.com` |
| downloadFile 合法域名 | 同上 | 添加 `https://api.yunxi.com` |

### 1.4 备份

```bash
mysqldump -u root -p shopxo > backup_$(date +%Y%m%d%H%M%S).sql
tar czf backend_backup_$(date +%Y%m%d%H%M%S).tar.gz /path/to/shopxo-backend/
```

---

## 2. 数据库执行步骤

### 2.1 执行顺序

| 步骤 | 操作 | SQL 来源 | 说明 |
|------|------|----------|------|
| D0 | 备份数据库 | — | 必须先做 |
| D1 | ShopXO 原生建表 | 安装向导自动执行 | 全新环境走安装向导；已有环境跳过 |
| D2 | 母婴基础建表 | `muying-final-migration.sql` A 段 | 4 张新表 |
| D3 | 母婴增量补丁 | `muying-final-migration.sql` B 段 | **必须执行**，含 P0 缺失字段 |
| D4 | 补邀请码 | `muying-final-migration.sql` C1 段 | 为已有用户生成邀请码 |
| D5 | 邀请码唯一索引 | `muying-final-migration.sql` C2 段 | D4 确认无空值后执行 |
| D6 | 邀请奖励去重+唯一约束 | `muying-final-migration.sql` C3 段 | 先去重再加索引 |
| D7 | 枚举值修复 | `muying-final-migration.sql` C4 段 | 可选，仅当有旧脏数据 |
| D8 | 邀请奖励配置项 | `muying-final-migration.sql` C5 段 | **必须执行**，否则奖励为 0 |
| D9 | 菜单权限 | `muying-final-migration.sql` C6 段 | 可选，仅当后台无运营菜单 |
| D10 | 隐藏一期菜单 | `muying-final-migration.sql` C7 段 | 可选 |

### 2.2 不要执行的 SQL

| 文件 | 原因 |
|------|------|
| `config/shopxo.sql`（手动执行） | 含 DROP TABLE，会清空所有数据 |
| `docs/muying-demo-data.sql` | 演示数据，正式环境不需要 |
| 旧 migration 文件（已废弃） | 已合并到 `muying-final-migration.sql` |

### 2.3 需要人工确认后执行的 SQL

| SQL | 确认项 |
|-----|--------|
| C2 邀请码唯一索引 | `SELECT COUNT(*) FROM sxo_user WHERE invite_code='' OR invite_code IS NULL;` → 必须 = 0 |
| C3 邀请奖励去重 | `SELECT inviter_id, invitee_id, trigger_event, COUNT(*) AS cnt FROM sxo_invite_reward GROUP BY inviter_id, invitee_id, trigger_event HAVING cnt > 1;` |
| C4 枚举值修复 | `SELECT DISTINCT stage FROM sxo_activity WHERE stage NOT IN ('prepare','pregnancy','postpartum','all','');` |

### 2.4 数据库执行后验证

```sql
SHOW TABLES LIKE 'sxo_activity%';
SHOW TABLES LIKE 'sxo_invite%';
SHOW TABLES LIKE 'sxo_muying%';
DESCRIBE sxo_user;
SHOW COLUMNS FROM sxo_activity_signup LIKE 'privacy_agreed_time';
SHOW COLUMNS FROM sxo_goods_favor LIKE 'type';
SHOW INDEX FROM sxo_user WHERE Key_name = 'uk_invite_code';
SHOW INDEX FROM sxo_invite_reward WHERE Key_name = 'uk_inviter_invitee_event';
SELECT COUNT(*) FROM sxo_user WHERE invite_code = '' OR invite_code IS NULL;
SELECT * FROM sxo_config WHERE only_tag IN ('muying_invite_register_reward', 'muying_invite_first_order_reward');
```

---

## 3. 后端部署步骤

### 3.1 代码部署

```bash
cd /var/www/
git clone https://github.com/kHuner9712/xiyun.git yunxi
cd yunxi/shopxo-backend
composer install --no-dev --optimize-autoloader
```

### 3.2 目录权限

```bash
chown -R www-data:www-data /var/www/yunxi/shopxo-backend
chmod -R 755 runtime/
chmod -R 755 public/static/upload/
chmod -R 755 public/download/
chmod -R 755 public/storage/
chmod -R 755 rsakeys/
chmod -R 755 resources/
```

| 目录 | 权限 | 用途 |
|------|------|------|
| `runtime/` | 755 | 缓存/日志/session |
| `public/static/upload/` | 755 | 用户上传文件 |
| `public/download/` | 755 | 下载文件 |
| `rsakeys/` | 755 | RSA 密钥 |
| `resources/` | 755 | 资源文件 |

### 3.3 Nginx 配置

```nginx
server {
    listen 443 ssl http2;
    server_name api.yunxi.com;

    ssl_certificate     /etc/nginx/ssl/api.yunxi.com.pem;
    ssl_certificate_key /etc/nginx/ssl/api.yunxi.com.key;

    root /var/www/yunxi/shopxo-backend/public;
    index index.php index.html;

    location / {
        if (!-e $request_filename) {
            rewrite ^(.*)$ /index.php?s=/$1 last;
        }
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ ^/(runtime|config|app|extend|vendor)/ {
        deny all;
    }

    client_max_body_size 20m;
}
```

### 3.4 配置确认清单

| 序号 | 检查项 | 检查方式 | 期望值 |
|------|--------|----------|--------|
| 1 | PHP 版本 | `php -v` | ≥8.0.2 |
| 2 | PHP 扩展 | `php -m` | 包含 pdo_mysql, mbstring, curl, gd, openssl |
| 3 | `config/database.php` 存在 | `ls config/database.php` | 安装后生成 |
| 4 | 数据库表前缀 | 查看该文件 `prefix` 字段 | `sxo_` |
| 5 | 时区配置 | `config/app.php` → `default_timezone` | `Asia/Shanghai` |
| 6 | 调试模式关闭 | `config/app.php` → `show_error_msg` | `false` |
| 7 | `.env` 不存在或 `APP_DEBUG = false` | `cat .env` | 生产环境必须关闭 |
| 8 | 管理后台入口文件 | `ls public/admin*.php` | 随机文件名 |
| 9 | `install.php` 已删除 | `ls public/install.php` | 应不存在 |

### 3.5 安装向导（仅全新环境）

1. 浏览器访问 `https://api.yunxi.com/install.php`
2. 填写数据库信息，**表前缀必须为 `sxo_`**
3. 安装完成后删除 `public/install.php`，记录管理后台入口地址
4. 安装后不要再执行 `config/shopxo.sql`

---

## 4. 前端部署步骤

### 4.1 构建前配置

| 序号 | 配置项 | 文件 | 操作 |
|------|--------|------|------|
| 1 | 微信小程序 AppID | `project.config.json` → `appid` | 确认为正式 AppID |
| 2 | API 接口域名 | 环境变量 `UNI_APP_REQUEST_URL` | `https://api.yunxi.com/` |
| 3 | 静态资源域名 | 环境变量 `UNI_APP_STATIC_URL` | `https://cdn.yunxi.com/` 或留空 |
| 4 | manifest.local.json | 从 `manifest.local.json.example` 复制 | 填入微信支付/OAuth 等密钥 |

### 4.2 构建步骤（HBuilderX）

1. 打开 HBuilderX，导入 `shopxo-uniapp` 项目
2. 菜单：发行 → 小程序-微信
3. 设置环境变量：`UNI_APP_REQUEST_URL` = `https://api.yunxi.com/`
4. 点击发行，构建产物在 `unpackage/dist/build/mp-weixin/`

### 4.3 微信开发者工具上传

1. 打开微信开发者工具，导入 `unpackage/dist/build/mp-weixin/`
2. 确认 AppID 与正式 AppID 一致
3. 本地调试确认接口可通
4. 点击"上传"，版本号 `1.0.0`
5. 登录微信后台 → 版本管理 → 提交审核

### 4.4 正式/测试环境配置区分

| 配置项 | 测试环境 | 正式环境 |
|--------|----------|----------|
| `UNI_APP_REQUEST_URL` | `https://test-api.yunxi.com/` | `https://api.yunxi.com/` |
| 微信 AppID | 测试号 AppID | 正式 AppID |
| `APP_DEBUG` | `true` | `false` |
| 后端 `show_error_msg` | `true` | `false` |

---

## 5. 后台配置步骤

登录管理后台：`https://api.yunxi.com/admin{随机字符}.php`

### 5.1 站点基础配置

| 序号 | 配置项 | 后台路径 | 操作 |
|------|--------|----------|------|
| 1 | 站点名称 | 系统设置 → 基础 → `home_site_name` | 改为"孕禧" |
| 2 | 站点 Logo | 系统设置 → 基础 → `home_site_logo` | 上传孕禧 Logo |
| 3 | 移动端 Logo | 系统设置 → 基础 → `home_site_logo_wap` | 上传孕禧 Logo |
| 4 | 热门搜索 | 系统设置 → 搜索 → `home_search_keywords` | 改为"叶酸,孕妇装,奶瓶,纸尿裤" |

### 5.2 协议配置

| 序号 | 配置项 | 后台路径 | 操作 |
|------|--------|----------|------|
| 1 | 注册协议 | 协议管理 → 注册协议 | 填写孕禧注册协议正文 |
| 2 | 隐私政策 | 协议管理 → 隐私政策 | 填写孕禧隐私政策正文 |
| 3 | 注销协议 | 协议管理 → 注销协议 | 填写孕禧注销协议正文 |

### 5.3 邀请奖励配置

| 配置项 | 推荐值 | 说明 |
|--------|--------|------|
| `muying_invite_register_reward` | 100 | 邀请注册奖励积分 |
| `muying_invite_first_order_reward` | 200 | 邀请首单奖励积分 |

> 如后台搜索不到，直接通过数据库修改：
> `UPDATE sxo_config SET value='100' WHERE only_tag='muying_invite_register_reward';`

### 5.4 商品分类配置

后台路径：商品管理 → 商品分类

**分类名称必须包含后端硬编码关键词才能被阶段筛选命中**：

| 阶段 | 命中关键词 | 建议分类名 |
|------|-----------|-----------|
| 备孕 (prepare) | 备孕、孕前 | 备孕好物、孕前营养 |
| 孕期 (pregnancy) | 孕期、孕妇、孕中、孕妈、怀孕 | 孕期必备、孕妇装 |
| 产后 (postpartum) | 产后、月子、哺乳、新生儿、婴儿、宝宝 | 产后恢复、新生儿护理 |

### 5.5 首页导航配置

后台路径：手机管理 → 首页导航

建议导航项：孕妈课堂、线下沙龙、试用官、签到打卡

### 5.6 轮播图配置

后台路径：手机管理 → 首页轮播

至少配置 3 张轮播图。

---

## 6. 上线后 Smoke Test

### A. 首页

| 序号 | 测试动作 | 预期 | 失败时看哪里 |
|------|----------|------|-------------|
| A1 | 打开小程序进入首页 | 页面正常加载 | 控制台 |
| A2 | 查看轮播图 | 显示后台配置的轮播 | 后台"首页轮播" |
| A3 | 切换阶段标签 | 商品列表按阶段筛选 | 控制台搜 `[index]` |

### B. 活动

| 序号 | 测试动作 | 预期 | 失败时看哪里 |
|------|----------|------|-------------|
| B1 | 进入活动列表 | 活动列表正常 | `sxo_activity` 是否有数据 |
| B2 | 点击活动卡片 | 跳转活动详情 | 后端日志搜 `活动` |
| B3 | 点击收藏按钮 | 收藏成功 | `sxo_goods_favor.type` 字段 |

### C. 报名

| 序号 | 测试动作 | 预期 | 失败时看哪里 |
|------|----------|------|-------------|
| C1 | 点"立即报名" | 跳转报名表单 | — |
| C2 | 填写表单提交 | 提示"报名成功" | `privacy_agreed_time` 字段是否存在 |

### D. 用户阶段

| 序号 | 测试动作 | 预期 | 失败时看哪里 |
|------|----------|------|-------------|
| D1 | 新用户进入用户中心 | 弹出阶段引导 | 控制台搜 `[user]` |
| D2 | 选择"孕期中"确认 | 标签显示"孕期中" | 控制台搜 `阶段保存失败` |

### E. 邀请注册

| 序号 | 测试动作 | 预期 | 失败时看哪里 |
|------|----------|------|-------------|
| E1 | 进入邀请页 | 显示邀请码 | `sxo_user.invite_code` |
| E2 | 新用户通过邀请码注册 | 注册成功 | 后端日志搜 `邀请码无效` |

### F. 邀请首单奖励

| 序号 | 测试动作 | 预期 | 失败时看哪里 |
|------|----------|------|-------------|
| F1 | 被邀请用户下单完成 | 邀请人获得积分 | 后端日志搜 `首单邀请奖励` |

---

## 7. 高风险项与排查路径

| 序号 | 风险项 | 可能后果 | 排查路径 |
|------|--------|----------|----------|
| 1 | `sxo_activity_signup.privacy_agreed_time` 缺失 | 报名功能不可用 | `DESCRIBE sxo_activity_signup;` |
| 2 | `sxo_goods_favor.type` 缺失 | 活动收藏不可用 | `DESCRIBE sxo_goods_favor;` |
| 3 | 邀请奖励配置项未插入 | 奖励为 0 | `SELECT * FROM sxo_config WHERE only_tag LIKE 'muying_%';` |
| 4 | 数据库表前缀不是 `sxo_` | 所有扩展表查询失败 | `SHOW TABLES;` |
| 5 | `UNI_APP_REQUEST_URL` 未配置 | 小程序所有接口 404 | 微信开发者工具 Network |
| 6 | 微信服务器域名未配置 | 真机请求被拦截 | 微信后台 → 服务器域名 |
| 7 | 商品分类名不含关键词 | 阶段推荐返回空 | `SELECT name FROM sxo_goods_category WHERE name LIKE '%备孕%';` |
| 8 | `install.php` 未删除 | 安全风险 | `ls public/install.php` |
| 9 | `APP_DEBUG = true` 未关闭 | 暴露错误信息 | 检查 `.env` |
| 10 | runtime 目录权限不足 | 页面 500 | `ls -la runtime/` |
