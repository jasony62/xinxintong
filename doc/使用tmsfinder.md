用`tms-finder`提供文件管理。

需要将 cookie 传递给`tms-koa`框架，进行用户身份认证。

添加全局变量`TMS_FINDER_ADDRESS`，指定打开`tms-finder`的地址。

`tms-finder`通过`window.postMessage`返回结果。

# 部署 tms-finder

```
docker-compose up -d tms-finder
```

```
cd tms-finder
```

在`docker/tms-finder`目录下，新建`fs.local.js`文件。

```js
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
    rootDir: '',
    domains: {
      图片: {
        schemas,
      },
    },
    defaultDomain: '图片',
  },
}
```

在`docker/tms-finder`目录下，新建`app.local.js`文件，设置`tms-finder`用户认证规则。

```js
const appConfig = {
  auth: {
    jwt: {
      disabled: true,
    },
    bucket: {
      validator: '/usr/app/xxt/tms-koa/auth/bucket.js',
    },
  },
  cors: {
    credentials: true,
  },
}

module.exports = appConfig
```

# xxt

修改`cus/config.php`，添加全局变量`TMS_FINDER_ADDRESS`。

```php
/**
 * 文件服务地址
 */
define('TMS_FINDER_ADDRESS', 'http://yourdomain:8080/tmsfinder/web');
```
