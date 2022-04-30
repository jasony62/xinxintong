const svgCaptcha = require('svg-captcha')

module.exports = (ctx) => {
  const {
    mathMin = 1,
    mathMax = 30,
    mathOperator = '+',
    width = 120,
    height = 45,
    fontSize = 50,
    noise = 1,
    color = true,
    background = '#eee',
    mode = 'text',
  } = ctx.request.query
  let cap
  if (mode === 'math') {
    cap = svgCaptcha.createMathExpr({
      mathMin: mathMin,
      mathMax: mathMax,
      mathOperator: mathOperator,
      // size: 4, // 验证码长度
      width: width,
      height: height,
      fontSize: fontSize,
      ignoreChars: '0oO1ilI', // 验证码字符中排除 0o1i
      noise: noise, // 干扰线条的数量
      // color: color, // 验证码的字符是否有颜色，默认没有，如果设定了背景，则默认有
      // background: background, // 验证码图片背景颜色
    })
  } else {
    cap = svgCaptcha.create({
      size: 4, // 验证码长度
      width: width,
      height: height,
      fontSize: fontSize,
      ignoreChars: '0oO1ilI', // 验证码字符中排除 0o1i
      noise: noise, // 干扰线条的数量
      // color: color, // 验证码的字符是否有颜色，默认没有，如果设定了背景，则默认有
      // background: background, // 验证码图片背景颜色
    })
  }

  return [true, cap.data, cap.text]
}
