define(['frame'], function (ngApp) {
  'use strict'
  ngApp.provider.controller('ctrlEntry', [
    '$scope',
    'http2',
    'tmsfinder',
    'srvSigninApp',
    function ($scope, http2, tmsfinder, srvSigninApp) {
      $scope.$on('xxt.tms-datepicker.change', function (event, data) {
        if (/app\./.test(data.state)) {
          $scope.app[data.state.split('.')[1]] = data.value
          srvSigninApp.update(data.state)
        }
      })
      $scope.setPic = function () {
        tmsfinder.open($scope.app.siteid).then(function (result) {
          if (result.url) {
            $scope.app.pic = result.url + '?_=' + new Date() * 1
            $scope.update('pic')
          }
        })
      }
      $scope.removePic = function () {
        $scope.app.pic = ''
        $scope.update('pic')
      }
      $scope.setPic2 = function () {
        tmsfinder.open($scope.app.siteid).then(function (result) {
          if (result.url) {
            $scope.app.pic2 = result.url + '?_=' + new Date() * 1
            $scope.update('pic2')
          }
        })
      }
      $scope.removePic2 = function () {
        $scope.app.pic2 = ''
        $scope.update('pic2')
      }
      srvSigninApp.get().then(function (oApp) {
        var url = '/rest/pl/fe/matter/signin/opData'
        url += '?site=' + oApp.siteid
        url += '&app=' + oApp.id
        http2.get(url).then(function (rsp) {
          $scope.summary = rsp.data
        })
      })
    },
  ])
  /**
   * 签到轮次
   */
  ngApp.provider.controller('ctrlRound', [
    '$scope',
    'srvSigninApp',
    'srvSigninRound',
    function ($scope, srvSigninApp, srvSigninRound) {
      $scope.batch = function () {
        srvSigninRound.batch($scope.app).then(function (rounds) {
          $scope.rounds = rounds
        })
      }
      $scope.$on('xxt.tms-datepicker.change', function (event, data) {
        var prop
        if (/round\./.test(data.state)) {
          prop = data.state.split('.')[1]
          data.obj[prop] = data.value
          $scope.update(data.obj, prop)
        }
      })
      $scope.add = function () {
        srvSigninRound.add($scope.rounds)
      }
      $scope.update = function (round, prop) {
        srvSigninRound.update(round, prop)
      }
      $scope.remove = function (round) {
        srvSigninRound.remove(round, $scope.rounds)
      }
      $scope.qrcode = function (round) {
        srvSigninRound.qrcode(
          $scope.app,
          $scope.sns,
          round,
          $scope.app.entryUrl
        )
      }
      srvSigninApp.get().then(function (app) {
        $scope.rounds = app.rounds
      })
    },
  ])
})
