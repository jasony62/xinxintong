# 信信通

信信通（xinxintong）是一个采用 MIT 协议（MIT-licensed）的开源项目。它是一个开源社群运营平台。

版本：1.0

# 部署

## 在容器中运行

> docker-compose up -d db php-fpm nginx adminer mongodb backapi swagger-ui

启动 kafka 消息队列

> docker-compose up -d zookeeper kafka

启动 canal-server 数据同步

> docker-compose up -d canal-server

需要在`php-fpm`容器中，`/usr/share/nginx/html`目录下，执行`composer install`命令，安装依赖的第三方包。

如果要支持微信录音，必须在`php-fpm`中安装`ffmpeg`，将`arm`格式文件转为`mp3`。

## 本地化配置

新建`docker-compose.override.yml`文件。

新建`cus`目录，新建`config.php`文件和`db.php`文件，设置系统参数。

在`kcfinder`目录下，新建`upload`目录，保存上传文件。需要执行`chmod -R 777 upload`命令解决权限问题。

## 环境变量

需要在`docker-compose.override.yml`中指定。

| 环境变量            | 说明         |
| ------------------- | ------------ |
| NGINX_HTTP_PROTOCOL | 应用地址协议 |
| NGINX_HTTP_HOST     | 应用主机名   |
| NGINX_HTTP_PORT     | 端口         |

在重定向时需要使用，例如：邀请连接。

查看`docker/nginx/nginx.conf.template`了解上述环境变量的用户。

# License

[MIT](http://opensource.org/licenses/MIT)
Copyright (c) 2014-present, Yue Yang (jasony62)

# 目录说明

| 目录 | 说明            |
| ---- | --------------- |
| api  | nodejs 提供 api |
