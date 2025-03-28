'use strict'

require('../../../../../../asset/js/xxt.ui.http.js')
require('../../../../../../asset/js/xxt.ui.page.js')
require('../../../../../../asset/js/xxt.ui.siteuser.js')
require('../../../../../../asset/js/xxt.ui.subscribe.js')
require('../../../../../../asset/js/xxt.ui.favor.js')
require('../../../../../../asset/js/xxt.ui.forward.js')
require('../../../../../../asset/js/xxt.ui.coinpay.js')
require('../../../../../../asset/js/xxt.ui.share.js')
require('../../../../../../asset/js/xxt.ui.picviewer.js')

window.onerror = (message, source, lineno, colno) => {
  if (
    /_test=ep/i.test(location.search) ||
    /^script error/.test(message) === false
  )
    alert('错误：' + JSON.stringify({ message, source, lineno, colno }))
}

var ngApp = angular.module('app', [
  'ui.bootstrap',
  'http.ui.xxt',
  'page.ui.xxt',
  'snsshare.ui.xxt',
  'siteuser.ui.xxt',
  'subscribe.ui.xxt',
  'favor.ui.xxt',
  'forward.ui.xxt',
  'coinpay.ui.xxt',
  'picviewer.ui.xxt',
  'ngSanitize',
])
ngApp.config([
  '$controllerProvider',
  ($cp) => {
    ngApp.provider = {
      controller: $cp.register,
    }
  },
])
ngApp.directive('tmsScroll', [
  function () {
    function _endScroll(event, $scope) {
      var target = event.target,
        scrollTop = target.scrollTop

      if (scrollTop === 0) {
        if ($scope.$parent.uppermost) {
          $scope.$parent.uppermost(target)
        }
      } else if (scrollTop === target.scrollHeight - target.clientHeight) {
        if ($scope.$parent.downmost) {
          $scope.$parent.downmost(target)
        }
      } else {
        if (
          target.__lastScrollTop === undefined ||
          scrollTop > target.__lastScrollTop
        ) {
          if ($scope.$parent.upward) {
            $scope.$parent.upward(target)
          }
        } else {
          if ($scope.$parent.downward) {
            $scope.$parent.downward(target)
          }
        }
      }
      target.__lastScrollTop = scrollTop
    }

    function _domReady($scope, elems) {
      for (var i = elems.length - 1; i >= 0; i--) {
        if (elems[i].scrollHeight === elems[i].clientHeight) {
          if (
            $scope.downmost &&
            angular.isString($scope.downmost) &&
            $scope.$parent.downmost
          ) {
            $scope.$parent.downmost(elems[i])
          }
        }
      }
    }

    return {
      restrict: 'EA',
      scope: {
        upward: '@',
        downward: '@',
        uppermost: '@',
        downmost: '@',
        ready: '=',
      },
      link: function ($scope, elems, attrs) {
        if (attrs.ready) {
          $scope.$watch('ready', function (ready) {
            if (ready === 'Y') {
              _domReady($scope, elems)
            }
          })
        } else {
          /* link发生在load之前 */
          window.addEventListener('load', function () {
            _domReady($scope, elems)
          })
        }
        for (var i = elems.length - 1; i >= 0; i--) {
          elems[i].onscroll = function (event) {
            var target = event.target
            if (target.__timer) {
              clearTimeout(target.__timer)
            }
            target.__timer = setTimeout(function () {
              _endScroll(event, $scope)
            }, 35)
          }
        }
      },
    }
  },
])
ngApp.filter('filesize', function () {
  return function (length) {
    var unit
    if (length / 1024 < 1) {
      unit = 'B'
    } else {
      length = length / 1024
      if (length / 1024 < 1) {
        unit = 'K'
      } else {
        length = length / 1024
        unit = 'M'
      }
    }
    length = new Number(length).toFixed(2)

    return length + unit
  }
})
ngApp.controller('ctrlMain', [
  '$scope',
  'http2',
  '$timeout',
  '$q',
  'tmsDynaPage',
  'tmsSubscribe',
  'tmsSnsShare',
  'picviewer',
  function (
    $scope,
    http2,
    $timeout,
    $q,
    tmsDynaPage,
    tmsSubscribe,
    tmsSnsShare,
    picviewer
  ) {
    var width = document.body.clientWidth
    $scope.width = width

    function finish() {
      let eleLoading
      if ((eleLoading = document.querySelector('.loading'))) {
        eleLoading.parentNode.removeChild(eleLoading)
      }
    }

    function articleLoaded() {
      finish()
      $timeout(function () {
        var audios, elems, tableEles

        audios = document.querySelectorAll('audio')
        audios.length > 0 && audios[0].play()
        if ($scope.article.can_picviewer === 'Y') {
          elems = document.querySelectorAll('.wrap img')
          picviewer.init(elems)
        }

        tableEles = document.querySelectorAll('table')

        if (tableEles) {
          angular.forEach(tableEles, function (tableEle) {
            var tableWrap = tableEle.parentNode
            if (
              !tableWrap.getAttribute('wrap') ||
              tableWrap.getAttribute('wrap') !== 'table'
            ) {
              tableWrap = document.createElement('div')
              tableWrap.setAttribute('wrap', 'table')
              tableEle.parentNode.insertBefore(tableWrap, tableEle)
              tableWrap.appendChild(tableEle)
            }
            /*if (!tableWrap ) {
                        
                    } else if (!(tableWrap.getAttribute('wrap') && tableWrap.getAttribute('wrap') !== 'table')) {
                        tableWrap.setAttribute('wrap', 'table');
                    }*/
          })
        }
      })
      $scope.code =
        '/rest/site/fe/matter/article/qrcode?site=' +
        siteId +
        '&url=' +
        encodeURIComponent(location.href)
      if (window.sessionStorage) {
        var pendingMethod
        if (
          (pendingMethod = window.sessionStorage.getItem(
            'xxt.site.fe.matter.article.auth.pending'
          ))
        ) {
          window.sessionStorage.removeItem(
            'xxt.site.fe.matter.article.auth.pending'
          )
          if ($scope.user.loginExpire) {
            pendingMethod = JSON.parse(pendingMethod)
            $scope[pendingMethod.name].apply($scope, pendingMethod.args || [])
          }
        }
      }
    }
    /**设置微信分享*/
    function setWxShare(oArticle) {
      /* 设置分享 */
      if (
        /MicroMessenger/i.test(navigator.userAgent) &&
        oArticle.can_share === 'Y'
      ) {
        var shareid, sharelink
        shareid = `${$scope.user.uid}_${Date.now()}`
        sharelink = `${location.protocol}//${location.hostname}/rest/site/fe/matter?site=${siteId}&type=article&id=${id}&shareby=${shareid}`
        tmsSnsShare.config({
          siteId,
          logger: function (shareto) {
            let url = `/rest/site/fe/matter/logShare?shareid=${shareid}&site=${siteId}&id=${id}&type=article&title=${oArticle.title}&shareto=${shareto}&shareby=${shareby}`
            http2.get(url)
          },
          jsApiList: [
            'hideOptionMenu',
            'onMenuShareTimeline',
            'onMenuShareAppMessage',
          ],
        })
        tmsSnsShare.set(
          oArticle.title,
          sharelink,
          oArticle.summary,
          oArticle.pic2 || oArticle.pic
        )
      }
    }
    function loadArticle() {
      var deferred = $q.defer()
      http2
        .get(`/rest/site/fe/matter/article/get?site=${siteId}&id=${id}`)
        .then(
          (rsp) => {
            let { site, mission, article: oArticle } = rsp.data
            let { channels } = oArticle
            if (oArticle.use_site_header === 'Y' && site && site.header_page) {
              tmsDynaPage.loadCode(ngApp, site.header_page)
            }
            if (
              oArticle.use_mission_header === 'Y' &&
              mission &&
              mission.header_page
            ) {
              tmsDynaPage.loadCode(ngApp, mission.header_page)
            }
            if (
              oArticle.use_mission_footer === 'Y' &&
              mission &&
              mission.footer_page
            ) {
              tmsDynaPage.loadCode(ngApp, mission.footer_page)
            }
            if (oArticle.use_site_footer === 'Y' && site && site.footer_page) {
              tmsDynaPage.loadCode(ngApp, site.footer_page)
            }
            if (channels && channels.length) {
              for (var i = 0, l = channels.length, channel; i < l; i++) {
                channel = channels[i]
                if (channel.style_page) {
                  tmsDynaPage.loadCode(ngApp, channel.style_page)
                }
              }
            }
            // 设置是否显示主页链接
            if (
              $scope.showHome &&
              (oArticle?.config?.hide?.home === 'Y' ||
                site?.config?.matter?.hide?.home === 'Y')
            ) {
              $scope.showHome = false
            }
            // 设置是否显示主页链接
            if (
              oArticle.config &&
              oArticle.config.hide &&
              oArticle.config.hide.siteCard === 'Y'
            ) {
              $scope.showSiteCard = false
            }
            $scope.site = site
            $scope.mission = mission
            $scope.article = oArticle
            $scope.user = rsp.data.user
            /* 设置分享 */
            setWxShare(oArticle)

            if (oArticle.can_siteuser === 'Y') {
              $scope.siteUser = function (siteId) {
                var url = location.protocol + '//' + location.host
                url += '/rest/site/fe/user'
                url += '?site=' + siteId
                location.href = url
              }
            }
            if (!_bPreview) {
              http2.post(`/rest/site/fe/matter/logAccess?site=${siteId}`, {
                id: id,
                type: 'article',
                title: oArticle.title,
                shareby: shareby,
                search: location.search.replace('?', ''),
                referer: document.referrer,
              })
              /*通过新api记录访问日志*/
              let { uid, nickname } = $scope.user
              http2.post(
                `/api/site/fe/matter/article/event/logAccess?site=${siteId}`,
                {
                  user: { id: uid, name: nickname },
                  article: { id: oArticle.id, title: oArticle.title },
                  shareby: shareby,
                  search: location.search.replace('?', ''),
                  referer: document.referrer,
                }
              )
            }
            // 设置页面背景图
            if (mission && mission.pageConfig && mission.pageConfig.watermark) {
              let eleArticle = document.querySelector('.article')
              if (eleArticle)
                eleArticle.style.backgroundImage = `url(${mission.pageConfig.watermark})`
            }
            // 完成设置
            $scope.dataReady = 'Y'
            http2
              .get(
                `/rest/site/fe/matter/article/assocRecords?id=${id}&site=${siteId}`
              )
              .then((rsp) => {
                $scope.enrollAssocs = rsp.data
                angular.forEach(rsp.data, function (data) {
                  if (data.entity_a_str) {
                    data.entity_a_str = data.entity_a_str.replace(
                      /<[^>]+>/g,
                      ''
                    )
                  }
                })
              })
            deferred.resolve()
          },
          function (content, httpCode) {
            finish()
            if (httpCode === 401) {
              tmsDynaPage.openPlugin(content).then(function () {
                loadArticle().then(articleLoaded)
              })
            } else {
              alert(content)
            }
          }
        )
      return deferred.promise
    }

    var ls, siteId, id, shareby, _bPreview

    ls = location.search
    siteId = ls.match(/[\?&]site=([^&]*)/)[1]
    id = ls.match(/[\?|&]id=([^&]*)/)[1]
    shareby = ls.match(/shareby=([^&]*)/) ? ls.match(/shareby=([^&]*)/)[1] : ''
    _bPreview = ls.match(/[\?|&]preview=Y/)

    $scope.back = function () {
      history.back()
    }
    $scope.showReturn = /site\/(fe|home)/.test(document.referrer) // 是否显示返回链接
    $scope.showHome = !$scope.showReturn // 是否显示返回主页链接
    $scope.showSiteCard = true // 是否显示团队卡片
    $scope.elSiteCard = angular.element(document.querySelector('#site-card'))
    $scope.siteCardToggled = function (open) {
      var elDropdownMenu
      if (open) {
        if (
          (elDropdownMenu = document.querySelector('#site-card>.dropdown-menu'))
        ) {
          elDropdownMenu.style.left = 'auto'
          elDropdownMenu.style.right = 0
        }
      }
    }
    $scope.openChannel = function (ch) {
      location.href = `/rest/site/fe/matter?site=${siteId}&type=channel&id=${ch.id}`
    }
    $scope.openEnrollAssoc = function (oEnrollAssoc) {
      if (oEnrollAssoc.app && oEnrollAssoc.entityA)
        location.href = `/rest/site/fe/matter/enroll?site=${oEnrollAssoc.app.siteid}&app=${oEnrollAssoc.app.id}&ek=${oEnrollAssoc.entityA.enroll_key}&page=cowork`
    }
    $scope.searchByTag = function (tag) {
      location.href = `/rest/site/fe/matter/article?site=${siteId}&tagid=${tag.id}`
    }
    $scope.openMatter = function (evt, id, type) {
      evt.preventDefault()
      evt.stopPropagation()
      if (/article|custom|channel|link/.test(type)) {
        location.href = `/rest/site/fe/matter?site=${siteId}&id=${id}&type=${type}`
      } else {
        location.href = `/rest/site/fe/matter/${type}?site=${siteId}&app=${id}`
      }
    }
    $scope.gotoNavApp = function (oNavApp, event) {
      event.preventDefault()
      event.stopPropagation()
      switch (oNavApp.type) {
        case 'enroll':
          location.href = `/rest/site/fe/matter/enroll?site=${oNavApp.siteid}&app=${oNavApp.id}`
          break
        case 'article':
        case 'channel':
          location.href = `/rest/site/fe/matter?site=${oNavApp.siteid}&id=${oNavApp.id}&type=${oNavApp.type}`
          break
        case 'link':
          location.href = `/rest/site/fe/matter/link?site=${oNavApp.siteid}&id=${oNavApp.id}&type=${oNavApp.type}`
          break
        case 'topic':
          location.href = `/rest/site/fe/matter/enroll?site=${oNavApp.siteid}&app=${oNavApp.aid}&topic=${oNavApp.id}&page=topic`
          break
        default:
          break
      }
    }
    $scope.subscribeSite = function () {
      if (!$scope.user.loginExpire) {
        if (window.sessionStorage) {
          var method = JSON.stringify({
            name: 'subscribeSite',
          })
          window.sessionStorage.setItem(
            'xxt.site.fe.matter.article.auth.pending',
            method
          )
        }
        location.href = '/rest/site/fe/user/access?site=platform#login'
      } else {
        tmsSubscribe.open($scope.user, $scope.site)
      }
    }
    loadArticle().then(articleLoaded)
  },
])
