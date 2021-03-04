import axios from 'axios'
import wx from 'weixin-js-sdk'

const DefaultJsApiList = [
  'hideOptionMenu',
  'onMenuShareTimeline',
  'onMenuShareAppMessage',
]
/**
 * 微信jssdk
 */
export default {
  /**
   * 配置
   *
   * @param {String} siteId
   * @param {Array} jsApiList
   */
  config: function(siteId, jsApiList) {
    return new Promise((resolve, reject) => {
      if (/MicroMessenger/i.test(navigator.userAgent)) {
        const url = '/rest/site/fe/wxjssdksignpackage2'
        const params = {
          site: siteId,
          url: encodeURIComponent(location.href.split('#')[0]),
        }
        axios.get(url, { params }).then((res) => {
          const result = res.data
          if (result.err_code !== 0) return reject(result.err_msg)
          const { appId, timestamp, nonceStr, signature } = result.data
          wx.config({
            appId,
            timestamp,
            nonceStr,
            signature,
            jsApiList: jsApiList || DefaultJsApiList,
          })
          wx.ready(() => {
            resolve(true)
          })
        })
      } else resolve(false)
    })
  },
  /**
   * 分享
   *
   * @param {String} title
   * @param {String} desc
   * @param {String} pic
   * @param {String} link
   */
  setShare(title, desc, pic, link) {
    const imgUrl =
      pic && pic.indexOf(location.protocol) === -1
        ? location.protocol + '//' + location.host + pic
        : pic

    const shareOptions = {
      title,
      desc,
      link,
      imgUrl,
      fail: () => alert('分享失败'),
    }
    wx.onMenuShareTimeline(shareOptions)
    wx.onMenuShareAppMessage(shareOptions)
  },
}
