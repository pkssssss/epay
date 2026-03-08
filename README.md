# Epay 聚合支付项目

## 简要概述

这是一个基于 PHP 的聚合支付系统，主要提供：

- 页面支付与 API 支付
- 支付通道与插件扩展
- 同步回跳与异步通知
- 商户中心与平台后台
- 退款、充值、购买会员、代付、分账等能力

主要目录：

- `includes/`：公共函数与核心业务类
- `plugins/`：支付插件
- `jik/`：平台后台
- `user/`：商户中心
- `template/`：模板与文档页
- `install/`：安装与升级脚本

## 部署方法

### 环境要求

- PHP 7.4+
- MySQL / MariaDB
- OpenResty / Nginx 或 Apache

### 基本部署步骤

1. 将代码部署到站点目录。
2. 将 Web 根目录指向项目根目录。
3. 配置数据库连接信息到根目录 `config.php`。
4. 首次安装访问：`/install/`
5. 已有站点升级访问：`/install/update.php`
6. 安装完成后确认存在：`install/install.lock`
7. 核对支付通道、回调地址、计划任务是否配置正确。

### 注意事项

- `config.php`、`.user.ini`、日志、证书、私钥、`cert/` 目录内容不要提交到 Git 仓库。
- 上线前至少确认：首页、后台、商户中心、支付回调、计划任务都能正常工作。

## 伪静态

本项目依赖伪静态支持以下路由：

- `/xxx.html`
- `/doc/xxx.html`
- `/pay/...`
- `/api/...`

### Nginx / OpenResty

可参考当前站点规则：

- `/opt/1panel/www/sites/api.233233.cc/rewrite/api.233233.cc.conf`

推荐配置：

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

### Apache

需启用 `mod_rewrite`，可使用等价规则：

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
