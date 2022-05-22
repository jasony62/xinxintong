module.exports = {
  port: 3000,
  name: 'tms-koa-0',
  router: {
    controllers: {
      prefix: '/api', // 接口调用url的前缀，例如：/api
    },
    swagger: {
      prefix: '/oas',
    },
    metrics: {
      prefix: '/metrics', // 提供给prometheus的地址
    },
  },
  cors: {
    origin: '*',
    credentials: true,
  },
}
