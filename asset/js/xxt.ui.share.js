'use strict'
var ngMod = angular.module('snsshare.ui.xxt', [])
ngMod.service('tmsSnsShare', [
  '$http',
  function ($http) {
    function setWxShare(title, link, desc, img, options) {
      var _this = this
      window.wx.onMenuShareTimeline({
        title: options.descAsTitle ? desc : title,
        link: link,
        imgUrl: img,
        success: function () {
          try {
            options.logger && options.logger('T')
          } catch (ex) {
            alert('share failed:' + ex.message)
          }
        },
        cancel: function () {},
        fail: function () {
          alert('shareT: fail')
        },
      })
      window.wx.onMenuShareAppMessage({
        title: title,
        desc: desc,
        link: link,
        imgUrl: img,
        success: function () {
          try {
            options.logger && options.logger('F')
          } catch (ex) {
            alert('share failed:' + ex.message)
          }
        },
        cancel: function () {},
        fail: function () {
          alert('shareF: fail')
        },
      })
    }

    var _isReady = false
    this.config = function (options) {
      this.options = options
    }
    this.set = function (title, link, desc, img, fnOther) {
      var _this = this
      // 将图片的相对地址改为绝对地址
      img &&
        img.indexOf(location.protocol) === -1 &&
        (img = location.protocol + '//' + location.host + img)
      if (_isReady) {
        if (/MicroMessenger/i.test(navigator.userAgent)) {
          setWxShare(title, link, desc, img, _this.options)
        } else if (fnOther && typeof fnOther === 'function') {
          fnOther(title, link, desc, img)
        }
      } else {
        if (/MicroMessenger/i.test(navigator.userAgent)) {
          var script
          script = document.createElement('script')
          script.src =
            location.protocol + '//res.wx.qq.com/open/js/jweixin-1.0.0.js'
          script.onload = function () {
            var xhr, url
            xhr = new XMLHttpRequest()
            url =
              '/rest/site/fe/wxjssdksignpackage?site=' +
              _this.options.siteId +
              '&url=' +
              encodeURIComponent(location.href.split('#')[0])
            xhr.open('GET', url, true)
            xhr.onreadystatechange = function () {
              if (xhr.readyState == 4) {
                if (xhr.status >= 200 && xhr.status < 400) {
                  var signPackage
                  try {
                    eval('(' + xhr.responseText + ')')
                    if (signPackage) {
                      signPackage.debug = false
                      signPackage.jsApiList = _this.options.jsApiList
                      wx.config(signPackage)
                      wx.ready(function () {
                        setWxShare(title, link, desc, img, _this.options)
                        _isReady = true
                      })
                      wx.error(function (res) {
                        alert(JSON.stringify(res))
                      })
                    }
                  } catch (e) {
                    alert('local error:' + e.toString())
                  }
                } else {
                  alert('http error:' + xhr.statusText)
                }
              }
            }
            xhr.send()
          }
          document.body.appendChild(script)
        } else if (fnOther && typeof fnOther === 'function') {
          fnOther(title, link, desc, img)
          _isReady = true
        }
      }
    }
  },
])
