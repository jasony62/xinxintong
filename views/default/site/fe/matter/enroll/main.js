'use strict'
require('../../../../../../asset/js/xxt.ui.share.js')
if (
  /MicroMessenger/i.test(navigator.userAgent) &&
  window.signPackage &&
  window.wx
) {
  window.wx.ready(function () {
    window.wx.showOptionMenu()
  })
}

require('./directive.css')
require('./main.css')
require('../../../../../../asset/js/xxt.ui.trace.js')
require('../../../../../../asset/js/xxt.ui.notice.js')
require('../../../../../../asset/js/xxt.ui.http.js')
require('../../../../../../asset/js/xxt.ui.page.js')
require('../../../../../../asset/js/xxt.ui.siteuser.js')
require('../../../../../../asset/js/xxt.ui.coinpay.js')
require('../../../../../../asset/js/xxt.ui.picviewer.js')
require('../../../../../../asset/js/xxt.ui.nav.js')
require('../../../../../../asset/js/xxt.ui.act.js')

require('./directive.js')
require('./service.js')

/* 公共加载的模块 */
var angularModules = [
  'ngSanitize',
  'ui.bootstrap',
  'notice.ui.xxt',
  'http.ui.xxt',
  'trace.ui.xxt',
  'page.ui.xxt',
  'snsshare.ui.xxt',
  'siteuser.ui.xxt',
  'directive.enroll',
  'picviewer.ui.xxt',
  'nav.ui.xxt',
  'act.ui.xxt',
  'service.enroll',
]
/* 加载指定的模块 */
if (window.moduleAngularModules) {
  window.moduleAngularModules.forEach(function (m) {
    angularModules.push(m)
  })
}

var ngApp = angular.module('app', angularModules)
ngApp.config([
  '$controllerProvider',
  '$uibTooltipProvider',
  '$locationProvider',
  'tmsLocationProvider',
  function ($cp, $uibTooltipProvider, $locationProvider, tmsLocationProvider) {
    ngApp.provider = {
      controller: $cp.register,
    }
    $uibTooltipProvider.setTriggers({
      show: 'hide',
    })
    $locationProvider.html5Mode(true)
    ;(function () {
      var baseUrl
      baseUrl = '/rest/site/fe/matter/enroll'
      //
      tmsLocationProvider.config(baseUrl)
    })()
  },
])
ngApp.controller('ctrlMain', [
  '$scope',
  '$q',
  '$parse',
  '$uibModal',
  'http2',
  '$timeout',
  'tmsLocation',
  'tmsDynaPage',
  'tmsSnsShare',
  '$location',
  function (
    $scope,
    $q,
    $parse,
    $uibModal,
    http2,
    $timeout,
    LS,
    tmsDynaPage,
    tmsSnsShare,
    $location
  ) {
    function refreshEntryRuleResult() {
      var url, defer
      defer = $q.defer()
      url = LS.j('entryRule', 'site', 'app')
      return http2.get(url).then(function (rsp) {
        $scope.params.entryRuleResult = rsp.data
        defer.resolve(rsp.data)
      })
    }

    function openPlugin(url, fnCallback) {
      var body, elWrap, elIframe
      body = document.body
      elWrap = document.createElement('div')
      elWrap.setAttribute('id', 'frmPlugin')
      elWrap.height = body.clientHeight
      elIframe = document.createElement('iframe')
      elWrap.appendChild(elIframe)
      body.scrollTop = 0
      body.appendChild(elWrap)
      window.onClosePlugin = function () {
        if (fnCallback) {
          fnCallback().then(function (data) {
            elWrap.parentNode.removeChild(elWrap)
          })
        } else {
          elWrap.parentNode.removeChild(elWrap)
        }
      }
      elWrap.onclick = function () {
        onClosePlugin()
      }
      if (url) {
        elIframe.setAttribute('src', url)
      }
      elWrap.style.display = 'block'
    }

    function execTask(task) {
      var obj, fn, args, valid
      valid = true
      obj = $scope
      args = task
        .match(/\((.*?)\)/)[1]
        .replace(/'|"/g, '')
        .split(',')
      angular.forEach(task.replace(/\(.*?\)/, '').split('.'), function (attr) {
        if (fn) obj = fn
        if (!obj[attr]) {
          valid = false
          return
        }
        fn = obj[attr]
      })
      if (valid) {
        fn.apply(obj, args)
      }
    }
    var tasksOfOnReady = []
    $scope.showAppInfo = function () {
      $uibModal.open({
        templateUrl: 'info.html',
        controller: [
          '$scope',
          '$uibModalInstance',
          function ($scope2, $mi) {
            const { title, summary } = $scope.app
            $scope2.app = {
              title,
              summary,
            }
            $scope2.cancel = function () {
              $mi.dismiss()
            }
          },
        ],
        backdrop: 'static',
      })
    }
    $scope.closeWindow = function () {
      if (/MicroMessenger/i.test(navigator.userAgent)) {
        window.wx.closeWindow()
      }
    }
    $scope.askFollowSns = function () {
      var url
      if ($scope.app.entryRule && $scope.app.entryRule.scope.sns === 'Y') {
        url = LS.j('askFollow', 'site')
        url += '&sns=' + Object.keys($scope.app.entryRule.sns).join(',')
        openPlugin(url, refreshEntryRuleResult)
      }
    }
    $scope.askBecomeMember = function () {
      var url, mschemaIds
      if ($scope.app.entryRule && $scope.app.entryRule.scope.member === 'Y') {
        mschemaIds = Object.keys($scope.app.entryRule.member)
        if (mschemaIds.length === 1) {
          url = '/rest/site/fe/user/member?site=' + $scope.app.siteid
          url += '&schema=' + mschemaIds[0]
        } else if (mschemaIds.length > 1) {
          url = '/rest/site/fe/user/memberschema?site=' + $scope.app.siteid
          url += '&schema=' + mschemaIds.join(',')
        }
        openPlugin(url, refreshEntryRuleResult)
      }
    }
    $scope.addRecord = function (event, page) {
      if (page) {
        $scope.gotoPage(event, page, null, null, 'Y')
      } else {
        for (var i in $scope.app.pages) {
          var oPage = $scope.app.pages[i]
          if (oPage.type === 'I') {
            $scope.gotoPage(event, oPage.name, null, null, 'Y')
            break
          }
        }
      }
    }
    $scope.siteUser = function () {
      var url = location.protocol + '//' + location.host
      url += '/rest/site/fe/user'
      url += '?site=' + LS.s().site
      location.href = url
    }
    $scope.gotoApp = function (event) {
      location.replace($scope.app.entryUrl)
    }
    $scope.gotoPage = function (event, page, ek, rid, newRecord) {
      if (event) {
        event.preventDefault()
        event.stopPropagation()
      }
      if (typeof page === 'object' && page.name) {
        page = page.name
      }
      var url = LS.j('', 'site', 'app')
      if (ek) {
        url += '&ek=' + ek
      } else if (page === 'cowork') {
        url += '&ek=' + LS.s().ek
      }
      rid && (url += '&rid=' + rid)
      page && (url += '&page=' + page)
      newRecord && newRecord === 'Y' && (url += '&newRecord=Y')
      location = url
    }
    $scope.openMatter = function (id, type, replace, newWindow) {
      var url =
        '/rest/site/fe/matter?site=' +
        LS.s().site +
        '&id=' +
        id +
        '&type=' +
        type
      if (replace) {
        location.replace(url)
      } else {
        if (newWindow === false) {
          location.href = url
        } else {
          window.open(url)
        }
      }
    }
    $scope.onReady = function (task) {
      if ($scope.params) {
        execTask(task)
      } else {
        tasksOfOnReady.push(task)
      }
    }
    /* 设置限制通讯录访问时的状态*/
    $scope.setOperateLimit = function (operate) {
      if (
        !$scope.app.entryRule.exclude_action ||
        $scope.app.entryRule.exclude_action[operate] !== 'Y'
      ) {
        if ($scope.entryRuleResult.passed == 'N') {
          tmsDynaPage
            .openPlugin($scope.entryRuleResult.passUrl)
            .then(function (data) {
              location.reload()
              return true
            })
          return false
        } else {
          return true
        }
      } else {
        return true
      }
    }
    /* 设置公众号分享信息 */
    $scope.setSnsShare = function (oRecord, oParams, oData, fnBackSharelink) {
      function fnReadySnsShare() {
        if (window.__wxjs_environment === 'miniprogram') {
          return
        }
        var oApp, oPage, oUser, sharelink, shareid, shareby, summary
        oApp = $scope.app
        oPage = $scope.page
        oUser = $scope.user
        /* 设置活动的当前链接 */
        sharelink =
          location.protocol +
          '//' +
          location.host +
          LS.j('', 'site', 'app', 'rid')
        if (oPage && oPage.share_page && oPage.share_page === 'Y') {
          sharelink += '&page=' + oPage.name
        } else if (LS.s().page) {
          sharelink += '&page=' + LS.s().page
        }
        oRecord &&
          oRecord.enroll_key &&
          (sharelink += '&ek=' + oRecord.enroll_key)
        if (oParams) {
          angular.forEach(oParams, function (v, k) {
            if (v !== undefined) {
              sharelink += '&' + k + '=' + v
            }
          })
        }
        shareid = oUser.uid + '_' + new Date() * 1
        shareby = location.search.match(/shareby=([^&]*)/)
          ? location.search.match(/shareby=([^&]*)/)[1]
          : ''
        sharelink += '&shareby=' + shareid
        /* 设置分享 */
        summary = oApp.summary
        if (
          oPage &&
          oPage.share_summary &&
          oPage.share_summary.length &&
          oRecord &&
          oRecord.data &&
          oRecord.data[oPage.share_summary]
        ) {
          summary = oRecord.data[oPage.share_summary]
        }

        /* 分享次数计数器 */
        if (/MicroMessenger/i.test(navigator.userAgent)) {
          window.shareCounter = 0
          tmsSnsShare.config({
            siteId: oApp.siteid,
            logger: function (shareto) {
              var url
              url = '/rest/site/fe/matter/logShare'
              url += '?shareid=' + shareid
              url += '&site=' + oApp.siteid
              url += '&id=' + oApp.id
              url += '&type=enroll'
              if (oData && oData.title) {
                url += '&title=' + oData.title
              } else {
                url += '&title=' + oApp.title
              }
              if (oData) {
                url += '&target_type=' + oData.target_type
                url += '&target_id=' + oData.target_id
              }
              url += '&shareby=' + shareby
              url += '&shareto=' + shareto
              http2.get(url)
              window.shareCounter++
              window.onshare && window.onshare(window.shareCounter)
            },
            jsApiList: [
              'hideOptionMenu',
              'onMenuShareTimeline',
              'onMenuShareAppMessage',
              'chooseImage',
              'uploadImage',
              'getLocation',
              'startRecord',
              'stopRecord',
              'onVoiceRecordEnd',
              'playVoice',
              'pauseVoice',
              'stopVoice',
              'onVoicePlayEnd',
              'uploadVoice',
              'downloadVoice',
              'translateVoice',
            ],
          })
          tmsSnsShare.set(oApp.title, sharelink, summary, oApp.pic2 || oApp.pic)
          // 返回生成的页面共享链接
        }
        if (fnBackSharelink) fnBackSharelink(sharelink)
      }
      if (/MicroMessenger/i.test(navigator.userAgent)) {
        if (!window.WeixinJSBridge || !WeixinJSBridge.invoke) {
          document.addEventListener(
            'WeixinJSBridgeReady',
            fnReadySnsShare,
            false
          )
        } else {
          fnReadySnsShare()
        }
      } else if (fnBackSharelink) {
        fnReadySnsShare()
      }
    }
    /* 设置页面操作 */
    $scope.setPopAct = function (aNames, fromPage, oParamsByAct) {
      if (!fromPage || !aNames || aNames.length === 0) return
      if ($scope.user) {
        var oEnlUser, oCustom
        if ((oEnlUser = $scope.user.enrollUser)) {
          oCustom = $parse(fromPage + '.act')(oEnlUser.custom)
        }
        if (!oCustom) {
          oCustom = {
            stopTip: false,
          }
        }
        $scope.popAct = {
          acts: [],
          custom: oCustom,
        }
        $scope.$watch(
          'popAct.custom',
          function (nv, ov) {
            var oCustom
            if (oEnlUser) {
              oCustom = oEnlUser.custom
              if (nv !== ov) {
                if (!oCustom[fromPage]) {
                  oCustom[fromPage] = {}
                }
                oCustom[fromPage].act = $scope.popAct.custom
                http2
                  .post(LS.j('user/updateCustom', 'site', 'app'), oCustom)
                  .then(function (rsp) {})
              }
            }
          },
          true
        )
        aNames.forEach(function (name) {
          var oAct
          switch (name) {
            case 'save':
              oAct = {
                title: '保存',
              }
              break
            case 'addRecord':
              if ($scope.app && oEnlUser) {
                if (
                  parseInt($scope.app.count_limit) === 0 ||
                  $scope.app.count_limit > oEnlUser.enroll_num
                ) {
                  /* 允许添加记录 */
                  oAct = {
                    title: '添加记录',
                    func: $scope.addRecord,
                  }
                }
              }
              break
            case 'newRecord':
              oAct = {
                title: '添加记录',
              }
              break
            case 'voteRecData':
              oAct = {
                title: '题目投票',
              }
              break
            case 'scoreSchema':
              oAct = {
                title: '题目打分',
              }
              break
          }
          if (oAct) {
            if (oParamsByAct) {
              if (oParamsByAct.func)
                if (oParamsByAct.func[name]) oAct.func = oParamsByAct.func[name]
              if (!oAct.func && $scope[name]) oAct.func = $scope[name]
              if (oParamsByAct.toggle)
                if (oParamsByAct.toggle[name])
                  oAct.toggle = oParamsByAct.toggle[name]
            }
            $scope.popAct.acts.push(oAct)
          }
        })
      }
    }
    /* 设置弹出导航页 */
    $scope.setPopNav = function (aNames, fromPage, oUser) {
      if (!fromPage || !aNames || aNames.length === 0) return
      if ($scope.user) {
        var oApp, oEnlUser, oCustom
        oApp = $scope.app
        oEnlUser = $scope.user.enrollUser
        if (oEnlUser) {
          oCustom = $parse(fromPage + '.nav')(oEnlUser.custom)
        }
        if (!oCustom) {
          oCustom = {
            stopTip: false,
          }
        }
        /*设置页面导航*/
        $scope.popNav = {
          navs: [],
          custom: oCustom,
        }
        $scope.$watch(
          'popNav.custom',
          function (nv, ov) {
            var oCustom
            if (oEnlUser) {
              oCustom = oEnlUser.custom
              if (nv !== ov) {
                if (!oCustom[fromPage]) {
                  oCustom[fromPage] = {}
                }
                oCustom[fromPage].nav = $scope.popNav.custom
                http2
                  .post(LS.j('user/updateCustom', 'site', 'app'), oCustom)
                  .then(function (rsp) {})
              }
            }
          },
          true
        )
        if (oApp.scenario === 'voting' && aNames.indexOf('votes') !== -1) {
          $scope.popNav.navs.push({
            name: 'votes',
            title: '投票榜',
            url: LS.j('', 'site', 'app') + '&page=votes',
          })
        }
        if (oApp.scenarioConfig) {
          if (
            oApp.scenarioConfig.can_repos === 'Y' &&
            aNames.indexOf('repos') !== -1
          ) {
            $scope.popNav.navs.push({
              name: 'repos',
              title: '共享页',
              url: LS.j('', 'site', 'app') + '&page=repos',
            })
          }
          if (
            oApp.scenarioConfig.can_rank === 'Y' &&
            aNames.indexOf('rank') !== -1
          ) {
            $scope.popNav.navs.push({
              name: 'rank',
              title: '排行页',
              url: LS.j('', 'site', 'app') + '&page=rank',
            })
          }
          if (oApp.scenarioConfig.can_stat === 'Y' && fromPage !== 'stat') {
            $scope.popNav.navs.push({
              name: 'stat',
              title: '统计页',
              url: LS.j('', 'site', 'app') + '&page=stat',
            })
          }
          if (
            oApp.scenarioConfig.can_kanban === 'Y' &&
            aNames.indexOf('kanban') !== -1
          ) {
            $scope.popNav.navs.push({
              name: 'kanban',
              title: '看板页',
              url: LS.j('', 'site', 'app') + '&page=kanban',
            })
          }
          if (
            oApp.scenarioConfig.can_action === 'Y' &&
            aNames.indexOf('event') !== -1
          ) {
            $scope.popNav.navs.push({
              name: 'event',
              title: '动态页',
              url: LS.j('', 'site', 'app') + '&page=event',
            })
          }
        }
        if (aNames.indexOf('favor') !== -1) {
          $scope.popNav.navs.push({
            name: 'favor',
            title: '收藏页',
            url: LS.j('', 'site', 'app') + '&page=favor',
          })
        }
        if (
          aNames.indexOf('task') !== -1 &&
          (oApp.questionConfig.length ||
            oApp.answerConfig.length ||
            oApp.voteConfig.length ||
            oApp.scoreConfig.length)
        ) {
          $scope.popNav.navs.push({
            name: 'task',
            title: '任务页',
            url: LS.j('', 'site', 'app') + '&page=task',
          })
        }
        if ($scope.mission) {
          $scope.popNav.navs.push({
            name: 'mission',
            title: '项目主页',
            url:
              '/rest/site/fe/matter/mission?site=' +
              oApp.siteid +
              '&mission=' +
              $scope.mission.id,
          })
        }
      }
      // if (oApp.scenarioConfig.can_action === 'Y') {
      //        /* 设置活动事件提醒 */
      //        http2.get(LS.j('notice/count', 'site', 'app')).then(function(rsp) {
      //            $scope.noticeCount = rsp.data;
      //        });
      //        oAppNavs.event = {};
      //        oApp.length++;
      //    }
    }
    /* 设置记录阅读日志信息 */
    $scope.logAccess = function (oParams) {
      var oApp, oUser, activeRid, oData, shareby
      oApp = $scope.app
      oUser = $scope.user
      activeRid = oApp.appRound.rid
      shareby = location.search.match(/shareby=([^&]*)/)
        ? location.search.match(/shareby=([^&]*)/)[1]
        : ''
      oData = {
        search: location.search.replace('?', ''),
        referer: document.referrer,
        rid: activeRid,
        assignedNickname: oUser.nickname,
        id: oApp.id,
        type: 'enroll',
        title: oApp.title,
        shareby: shareby,
      }

      if (oParams) {
        if (oParams.title) {
          oData.title = oParams.title
        }
        oData.target_type = oParams.target_type
        oData.target_id = oParams.target_id
      }
      http2.post('/rest/site/fe/matter/logAccess?site=' + oApp.siteid, oData)
    }
    $scope.openLogAnalysis = false // 是否收集页面分析数据
    $scope.isSmallLayout = false
    if (window.screen && window.screen.width < 992) {
      $scope.isSmallLayout = true
    }
    var url
    if ($location.$$path.indexOf('/summary/') > 0) {
      url = LS.j('get', 'site', 'app', 'rid', 'ek', 'newRecord')
      url += '&page=IGNORE'
    } else {
      url = LS.j('get', 'site', 'app', 'page', 'rid', 'ek', 'newRecord')
    }
    http2.get(url).then(function success(rsp) {
      var params = rsp.data,
        oSite = params.site,
        oApp = params.app,
        oEntryRuleResult = params.entryRuleResult,
        oMission = params.mission,
        schemasById = {}

      oApp.dynaDataSchemas.forEach(function (schema) {
        schemasById[schema.id] = schema
      })
      oApp._schemasById = schemasById
      $scope.params = params
      $scope.site = oSite
      $scope.mission = oMission
      $scope.app = oApp
      $scope.entryRuleResult = oEntryRuleResult
      if (oApp.use_site_header === 'Y' && oSite && oSite.header_page) {
        tmsDynaPage.loadCode(ngApp, oSite.header_page)
      }
      if (oApp.use_mission_header === 'Y' && oMission && oMission.header_page) {
        tmsDynaPage.loadCode(ngApp, oMission.header_page)
      }
      if (oApp.use_mission_footer === 'Y' && oMission && oMission.footer_page) {
        tmsDynaPage.loadCode(ngApp, oMission.footer_page)
      }
      if (oApp.use_site_footer === 'Y' && oSite && oSite.footer_page) {
        tmsDynaPage.loadCode(ngApp, oSite.footer_page)
      }
      if (params.page) {
        tmsDynaPage.loadCode(ngApp, params.page).then(function () {
          $scope.page = params.page
        })
      }
      if (tasksOfOnReady.length) {
        angular.forEach(tasksOfOnReady, execTask)
      }
      /* 是否打开收集分析数据 */
      if (
        !oApp.scenarioConfig.close_log_analysis ||
        oApp.scenarioConfig.close_log_analysis !== 'Y'
      )
        $scope.openLogAnalysis = true
      /* 用户信息 */
      $scope.user = params['user']
      $timeout(() => {
        $scope.$broadcast('xxt.app.enroll.ready', params)
      })
      var eleLoading
      if ((eleLoading = document.querySelector('.loading'))) {
        eleLoading.parentNode.removeChild(eleLoading)
      }
    })
  },
])
module.exports = ngApp
