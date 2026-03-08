# Epay 聚合支付项目

## 项目概述

这是一个基于 PHP 的聚合支付系统，主要提供以下能力：

- 页面支付与 API 支付
- 支付通道路由与插件化扩展
- 支付结果同步回跳与异步通知
- 商户中心 / 平台后台
- 余额充值、购买会员、注册付费
- 订单退款、代付、分账
- 风控、域名白名单、消息提醒

### 技术栈

- PHP 7.4+
- MySQL / MariaDB
- OpenResty / Nginx + PHP-FPM
- jQuery + Bootstrap 3

### 主要目录

- `index.php`：首页入口
- `submit.php`：页面支付入口
- `mapi.php`：API 下单入口
- `pay.php`：支付路由分发入口
- `api.php`：开放 API 入口
- `cashier.php`：收银台页
- `submit2.php`：收银台二次提交页
- `cron.php`：计划任务入口
- `getshop.php`：支付结果轮询接口
- `includes/`：公共函数、核心类、配置加载
- `plugins/`：支付插件
- `jik/`：平台后台
- `user/`：商户中心
- `template/`：首页与文档模板
- `paypage/`：支付展示页资源
- `assets/`：公共静态资源
- `install/`：安装与升级脚本

## 部署方法

### 一、环境要求

推荐环境：

- Ubuntu
- OpenResty / Nginx
- PHP-FPM
- MySQL / MariaDB

### 二、代码部署

1. 将项目代码放置到站点目录。
2. 确保 Web 根目录指向项目根目录。
3. 确保 PHP-FPM 可正常解析 PHP 文件。
4. 确保 Nginx / OpenResty 已正确配置 URL 重写，支持 `/pay/...` 路由访问。

### 三、伪静态 / URL 重写

本项目依赖伪静态规则来支持以下访问形式：

- `/xxx.html` -> 首页模板页
- `/doc/xxx.html` -> 文档页
- `/pay/...` -> 支付分发入口
- `/api/...` -> 新版 API 路由入口

#### Nginx / OpenResty

当前站点实际使用的重写规则可参考：

- `/opt/1panel/www/sites/api.233233.cc/rewrite/api.233233.cc.conf`

推荐配置如下：

```nginx
location / {
    try_files $uri $uri/ @rewrite;
}

location @rewrite {
    rewrite ^/(.[a-zA-Z0-9\-\_]+).html$ /index.php?mod=$1 last;
    rewrite ^/pay/(.*)$ /pay.php?s=$1 last;
    rewrite ^/api/(.*)$ /api.php?s=$1 last;
    rewrite ^/doc/(.[a-zA-Z0-9\-\_]+).html$ /index.php?doc=$1 last;
    rewrite ^ /index.php last;
}
```

#### Apache

Apache 需确保已启用：

- `mod_rewrite`

可使用等价规则：

```apache
<IfModule mod_rewrite.c>
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule ^([A-Za-z0-9\-_]+)\.html$ index.php?mod=$1 [L,QSA]
RewriteRule ^pay/(.*)$ pay.php?s=$1 [L,QSA]
RewriteRule ^api/(.*)$ api.php?s=$1 [L,QSA]
RewriteRule ^doc/([A-Za-z0-9\-_]+)\.html$ index.php?doc=$1 [L,QSA]
RewriteRule ^ index.php [L,QSA]
</IfModule>
```

#### 注意事项

- 如果伪静态未生效，最直接的表现通常是：
  - `/pay/...` 无法访问
  - `/api/...` 新接口无法访问
  - `*.html` 模板页 / 文档页无法按预期打开
- 在 1Panel 环境下，优先核对站点的“网站设置 -> 伪静态”是否已加载上述规则。

### 三点五、数据库配置

1. 复制或创建根目录 `config.php`。
2. 按实际环境填写数据库连接信息：
   - 数据库主机
   - 端口
   - 用户名
   - 密码
   - 数据库名
   - 表前缀
3. **不要将真实数据库配置提交到 Git 仓库。**

### 四、安装与初始化

1. 首次部署时访问：`/install/`
2. 按页面提示完成安装。
3. 安装完成后确认以下文件存在：
   - `install/install.lock`
4. 如果是升级已有站点，请使用：
   - `install/update.php`

### 五、支付与回调配置

上线前请至少确认以下内容：

- 站点公网访问地址正常
- 回调地址可被外部访问
- 支付通道已在后台正确配置
- 插件依赖的证书文件已放到对应插件目录
- 商户号、密钥、证书与插件配置一致

### 六、计划任务

建议检查并配置：

- 订单统计任务
- 自动结算任务
- 异步通知重试任务
- 分账任务（如有启用）
- 风控检查任务

相关入口：

- `cron.php`

### 七、敏感文件与安全注意事项

以下文件或目录不应提交到公开仓库：

- `config.php`
- `.user.ini`
- `log/`
- `install/install.lock`
- 证书、私钥、公钥、`cert/` 目录内容
- 本地缓存、锁文件、运行时生成文件

### 八、上线前建议

上线前建议至少完成以下核对：

- 首页可访问
- 后台可登录
- 商户中心可登录
- 支付页可正常拉起
- 支付成功后异步通知正常
- 支付成功后页面可正常跳转
- 计划任务可正常执行

