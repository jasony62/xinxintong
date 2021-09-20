module.exports = {
  disabled: false, // 可省略
  master: {
    user: process.env.XXT_MONGO_USERNAME,
    password: process.env.XXT_MONGO_PASSWORD,
    host: 'mongodb',
    port: 27017,
  },
}
