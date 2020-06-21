用`tms-finder`提供文件管理。

需要将 cookie 传递给`tms-koa`框架，进行用户身份认证。

添加全局变量`TMS_FINDER_ADDRESS`，指定打开`tms-finder`的地址。

`tms-finder`通过`window.postMessage`返回结果。

# 部署 tms-finder

```
git clone https://github.com/jasony62/tms-finder.git
```

```
cd tms-finder
```

新建`docker-compose.override.yml`文件。

指定可以的服务端口。

将`kcfinder/upload`目录挂载到`tms-finder-back`容器。

在`back/config`目录下，新建`fs.local.js`文件。

```
const schemas = {
  $schema: 'http://json-schema.org/draft-07/schema#',
  type: 'object',
  title: 'Json-Doc-File',
  description: 'tms-vue-finder file',
  properties: {
    comment: {
      type: 'string',
      minLength: 0,
      maxLength: 80,
      title: '说明',
      attrs: {
        placeholder: '请输入说明',
        title: '说明',
      },
    },
  },
}

module.exports = {
  local: {
    rootDir: '/Users/yangyue/project/xxt/kcfinder/upload',
    domains: {
      图片: {
        schemas,
      },
    },
    defaultDomain: '图片',
  },
}
```

在`back/config`目录下，新建`app.local.js`文件，设置`tms-finder`用户认证规则。

```
const appConfig = {
  auth: {
    jwt: {
      disabled: true,
    },
    bucket: {
      validator: '/Users/yangyue/project/xxt/tms-koa/auth/bucket.js',
    },
  },
  cors: {
    credentials: true,
  },
}

module.exports = appConfig
```

设置`ue`服务参数

```
ue:
    build:
      args:
        vue_app_auth_server: http://yourdomain:3000
        vue_app_api_server: http://yourdomain:3000
        vue_app_fs_server: http://yourdomain:3000/fs
```

如果需要编译

修改`.env.local`文件

# xxt

修改`cus/config.php`，添加全局变量`TMS_FINDER_ADDRESS`。

```
/**
 * 文件服务地址
 */
define('TMS_FINDER_ADDRESS', 'http://yourdomain:8080/finder_ue/web');
```

docker-compose up -d back ue

```
docker run --rm -it -v $PWD/tms-koa:/usr/src/app node:alpine sh
```
