export default {
  disabled: false, // boolean 是否启用验证码
  storageType: 'lowdb', // 验证码存储方式  lowdb | redis
  masterCaptcha: 'aabb', // string 万能验证码
  codeSize: 4, //验证码长度  默认4
  alphabetType: 'number,upperCase,lowerCase', // 字母表生产类型 默认 数字+大写字母+小写字母
  alphabet: '1234567890', // 与alphabetType不可公用，优先级大于alphabetType
  expire: 300, // 过期时间 s 默认300
}
