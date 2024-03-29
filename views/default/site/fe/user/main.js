'use strict'
var ngApp = angular.module('app', ['http.ui.xxt'])
ngApp.config([
  '$locationProvider',
  function ($locationProvider) {
    $locationProvider.html5Mode(true)
  },
])
ngApp.service('userService', [
  'http2',
  'tmsLocation',
  '$q',
  function (http2, LS, $q) {
    var _baseUrl = '/rest/site/fe/user',
      _user
    return {
      get: function () {
        var deferred = $q.defer()
        http2.get(_baseUrl + '/get?site=' + LS.s().site).then(function (rsp) {
          _user = rsp.data
          if (!_user.headimgurl) {
            _user.headimgurl = '/static/img/avatar.png'
          }
          if (!_user.uname) {
            _user.uname = '未知昵称'
          }
          deferred.resolve(_user)
        })
        return deferred.promise
      },
      changePwd: function (data) {
        var deferred = $q.defer()
        http2
          .post(_baseUrl + '/changePwd?site=' + LS.s().site, data)
          .then(function (rsp) {
            _user = rsp.data
            deferred.resolve(_user)
          })
        return deferred.promise
      },
      changeNickname: function (data) {
        var deferred = $q.defer()
        http2
          .post(_baseUrl + '/changeNickname?site=' + LS.s().site, data)
          .then(function (rsp) {
            _user = rsp.data
            deferred.resolve(_user)
          })
        return deferred.promise
      },
    }
  },
])
ngApp.controller('ctrlMain', [
  '$scope',
  '$timeout',
  'http2',
  'tmsLocation',
  'userService',
  function ($scope, $timeout, http2, LS, userService) {
    function newSubscriptions(afterAt) {
      var url
      url =
        '/rest/site/fe/user/subscribe/count?site=' +
        LS.s().site +
        '&after=' +
        afterAt
      http2.get(url).then(function (rsp) {
        $scope.count.newSubscriptions = rsp.data
      })
    }

    var cachedStatus, lastCachedStatus
    $scope.count = {}
    $scope.userSetting = false
    $scope.openThirdAppUrl = function (thirdApp) {
      location.href =
        '/rest/site/fe/user/login/byRegAndThird?thirdId=' + thirdApp.id
    }
    $scope.toggleUserSetting = function (event) {
      event.preventDefault()
      event.stopPropagation()
      if (event.target.classList.contains('list-group-item')) {
        $scope.userSetting = !$scope.userSetting
      }
    }
    $scope.toggleVisible = function (event) {
      var target = event.target
      if (
        target.tagName === 'SPAN' ||
        ((target = target.parentNode) && target.tagName === 'SPAN')
      ) {
        var childEle = target.querySelector('i')
        if (
          childEle.getAttribute('class') === 'glyphicon glyphicon-eye-close'
        ) {
          childEle.setAttribute('class', 'glyphicon glyphicon-eye-open')
          target.previousElementSibling.setAttribute('type', 'text')
        } else {
          childEle.setAttribute('class', 'glyphicon glyphicon-eye-close')
          target.previousElementSibling.setAttribute('type', 'password')
        }
      }
    }
    $scope.changeNickname = function () {
      var data = {}
      data.nickname = $scope.user.nickname
      userService.changeNickname(data).then(function () {
        alert('修改成功')
      })
    }
    $scope.changePwd = function () {
      var data = {}
      if ($scope.user.newPassword !== $scope.user.password2) {
        alert('两次输入密码不一致')
        return false
      }
      data.oldPassword = $scope.user.oldPassword
      data.newPassword = $scope.user.newPassword
      userService.changePwd(data).then(function () {
        alert('修改成功')
      })
    }
    $scope.logout = function () {
      http2
        .get('/rest/site/fe/user/logout/do?site=' + LS.s().site)
        .then(function (rsp) {
          location.replace('/rest/site/fe/user?site=' + LS.s().site)
        })
    }
    $scope.gotoRegister = function () {
      location.href =
        '/rest/site/fe/user/access?site=' + LS.s().site + '#register'
    }
    $scope.gotoLogin = function () {
      location.href = '/rest/site/fe/user/access?site=' + LS.s().site + '#login'
    }
    $scope.gotoMember = function (memberSchema) {
      location.href =
        '/rest/site/fe/user/member?site=' +
        LS.s().site +
        '&schema=' +
        memberSchema.id
    }
    $scope.gotoConsole = function () {
      location.href = '/rest/pl/fe'
    }
    $scope.shiftRegUser = function (oOtherRegUser) {
      http2
        .post('/rest/site/fe/user/shiftRegUser?site=' + LS.s().site, {
          uname: oOtherRegUser.uname,
        })
        .then(function (rsp) {
          location.reload()
        })
    }
    $scope.loginByReg = function (oRegUser) {
      http2
        .post(
          '/rest/site/fe/user/login/byRegAndWxopenid?site=' + LS.s().site,
          oRegUser
        )
        .then(function (rsp) {
          location.reload(true)
        })
    }
    http2.get('/rest/site/fe/get?site=' + LS.s().site).then(function (rsp) {
      $scope.site = rsp.data
      http2.get('/rest/site/fe/user/login/thirdList').then(function (rsp) {
        $scope.thirdApps = rsp.data
      })
      userService.get().then(function (oUser) {
        $scope.user = oUser
        if (oUser.unionid) {
          http2
            .get('/rest/site/fe/user/memberschema/atHome?site=' + LS.s().site)
            .then(function (rsp) {
              $scope.memberSchemas = rsp.data
            })
          http2
            .get('/rest/site/fe/user/subscribe/count?site=' + LS.s().site)
            .then(function (rsp) {
              $scope.count.subscription = rsp.data
            })
          http2
            .get('/rest/site/fe/user/favor/count?site=' + LS.s().site)
            .then(function (rsp) {
              $scope.count.favor = rsp.data
            })
          http2
            .get('/rest/site/fe/user/notice/count?site=' + LS.s().site)
            .then(function (rsp) {
              $scope.count.notice = rsp.data
            })
          http2
            .get('/rest/site/fe/user/history/appCount?site=' + LS.s().site)
            .then(function (rsp) {
              $scope.count.app = rsp.data
            })
          http2
            .get('/rest/site/fe/user/invite/count?site=' + LS.s().site)
            .then(function (rsp) {
              $scope.count.invite = rsp.data
            })
          /*上一次访问状态*/
          if (window.localStorage) {
            if (
              (cachedStatus = window.localStorage.getItem('site.fe.user.main'))
            ) {
              cachedStatus = JSON.parse(cachedStatus)
              lastCachedStatus = angular.copy(cachedStatus)
            } else {
              cachedStatus = {}
            }
            $timeout(function () {
              cachedStatus.lastAt = parseInt((new Date() * 1) / 1000)
              window.localStorage.setItem(
                'site.fe.user.main',
                JSON.stringify(cachedStatus)
              )
            }, 6000)
            if (lastCachedStatus && lastCachedStatus.lastAt) {
              newSubscriptions(lastCachedStatus.lastAt)
            }
          }
        }
        http2
          .get('/rest/site/fe/user/history/missionCount2?site=' + LS.s().site)
          .then(function (rsp) {
            $scope.count.mission = rsp.data
          })

        var eleLoading
        eleLoading = document.querySelector('.loading')
        eleLoading.parentNode.removeChild(eleLoading)
      })
    })
  },
])
angular.bootstrap(document, ['app'])
