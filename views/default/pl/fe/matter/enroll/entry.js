define(['frame', 'groupService'], function (ngApp) {
  'use strict'
  ngApp.provider.controller('ctrlEntry', [
    '$scope',
    'mediagallery',
    '$uibModal',
    'srvEnrollApp',
    'srvTimerNotice',
    function ($scope, mediagallery, $uibModal, srvEnlApp, srvTimerNotice) {
      $scope.setPic = function () {
        var options = {
          callback: function (url) {
            $scope.app.pic = url + '?_=' + new Date() * 1
            srvEnlApp.update('pic')
          },
        }
        mediagallery.open($scope.app.siteid, options)
      }
      $scope.removePic = function () {
        $scope.app.pic = ''
        srvEnlApp.update('pic')
      }
      $scope.setPic2 = function () {
        var options = {
          callback: function (url) {
            $scope.app.pic2 = url + '?_=' + new Date() * 1
            srvEnlApp.update('pic2')
          },
        }
        mediagallery.open($scope.app.siteid, options)
      }
      $scope.removePic2 = function () {
        $scope.app.pic2 = ''
        srvEnlApp.update('pic2')
      }
      $scope.downloadQrcode = function (url) {
        $(
          '<a href="' +
            url +
            '" download="' +
            $scope.app.title +
            '-二维码.png"></a>'
        )[0].click()
      }
      $scope.openRankSetting = function () {
        $uibModal.open({
          templateUrl: 'rankSetting.html',
          controller: [
            '$scope',
            '$uibModalInstance',
            function ($scope2, $mi) {
              var oApp
              $scope2.app = oApp = $scope.app
              $scope2.rankConfig = oApp.rankConfig
              $scope2.singleSchemas = oApp.dataSchemas.filter(function (
                oSchema
              ) {
                return oSchema.type === 'single'
              })
              $scope2.numberSchemas = oApp.dataSchemas.filter(function (
                oSchema
              ) {
                return (
                  oSchema.type === 'shorttext' &&
                  /number|calculate/.test(oSchema.format)
                )
              })
              $scope2.dismiss = function () {
                $mi.dismiss()
              }
              $scope2.save = function () {
                $scope.update('rankConfig').then(function () {
                  $mi.close()
                })
              }
            },
          ],
        })
      }
      /* 定时任务服务 */
      $scope.srvTimer = srvTimerNotice
      /* 定时任务截止时间 */
      $scope.$on('xxt.tms-datepicker.change', function (event, data) {
        var oTimer
        if ((oTimer = $scope.srvTimer.timerById(data.state))) {
          oTimer.task.task_expire_at = data.value
        }
      })
      srvEnlApp.get().then(function (app) {
        var oEntry
        oEntry = {
          url: app.entryUrl,
          qrcode:
            '/rest/site/fe/matter/enroll/qrcode?site=' +
            app.siteid +
            '&url=' +
            encodeURIComponent(app.entryUrl),
        }
        $scope.entry = oEntry
      })
    },
  ])
  /**
   * 微信二维码
   */
  ngApp.provider.controller('ctrlWxQrcode', [
    '$scope',
    'http2',
    function ($scope, http2) {
      $scope.create = function () {
        var url
        url = '/rest/pl/fe/site/sns/wx/qrcode/create?site=' + $scope.app.siteid
        url += '&matter_type=enroll&matter_id=' + $scope.app.id
        //url += '&expire=864000';
        http2.get(url).then(function (rsp) {
          $scope.qrcode = rsp.data
        })
      }
      $scope.download = function () {
        $(
          '<a href="' +
            $scope.qrcode.pic +
            '" download="微信登记二维码.jpeg"></a>'
        )[0].click()
      }
      http2
        .get(
          '/rest/pl/fe/matter/enroll/wxQrcode?site=' +
            $scope.app.siteid +
            '&app=' +
            $scope.app.id
        )
        .then(function (rsp) {
          var qrcodes = rsp.data
          $scope.qrcode = qrcodes.length ? qrcodes[0] : false
        })
    },
  ])
  /**
   * 任务提醒
   */
  ngApp.provider.controller('ctrlTaskRemind', [
    '$scope',
    '$parse',
    '$q',
    'srvEnrollApp',
    'tkGroupApp',
    function ($scope, $parse, $q, srvEnlApp, tkGrpApp) {
      function fnGetEntryRuleMschema(oApp) {
        if (oApp === undefined) {
          oApp = $scope.app
        }
        if (!oApp) {
          return false
        }
        var oAppEntryRule = $scope.app.entryRule
        if (oAppEntryRule.scope && oAppEntryRule.scope.member === 'Y') {
          if ($scope.mschemasById) {
            if (
              oAppEntryRule.member &&
              Object.keys(oAppEntryRule.member).length
            ) {
              if ($scope.mschemasById[Object.keys(oAppEntryRule.member)[0]]) {
                return $scope.mschemasById[Object.keys(oAppEntryRule.member)[0]]
              }
            }
          }
        }
        return false
      }
      $scope.srvTimer.onBeforeSave(function (oTimer) {
        var defer = $q.defer(),
          oTask = oTimer.task
        if (oTask && oTask.task_model === 'remind') {
          if (oTask.task_arguments) {
            var oArgs = oTask.task_arguments
            if (oArgs.receiver) {
              if (oArgs.receiver.scope) {
                if (oArgs.receiver.scope === 'enroll') {
                  delete oArgs.receiver.app
                } else if (oArgs.receiver.scope === 'mschema') {
                  delete oArgs.receiver.app
                } else if (oArgs.receiver.scope === 'group') {
                  if (
                    oTimer._temp &&
                    oTimer._temp.group &&
                    !oTimer._temp.group.auto
                  ) {
                    oArgs.receiver.app = oTimer._temp.group
                  }
                }
              }
            }
          }
          defer.resolve()
        } else {
          defer.resolve()
        }
        return defer.promise
      })
      $scope.assignGroup = function (oTimer) {
        tkGrpApp.choose($scope.app, true).then(function (oResult) {
          var oGrpApp
          if (oResult.app) {
            oGrpApp = {
              id: oResult.app.id,
              title: oResult.app.title,
            }
            if (oResult.teams && oResult.teams.length) {
              oGrpApp.teams = {
                id: [],
                title: [],
              }
              oResult.teams.forEach(function (oTeam) {
                oGrpApp.teams.id.push(oTeam.team_id)
                oGrpApp.teams.title.push(oTeam.title)
              })
            }
            $parse('_temp.group').assign(oTimer, oGrpApp)
            oTimer.modified = true
          }
        })
      }
      $scope.defaultReceiver = function (oTimer, oApp) {
        var oRule = $parse('task.task_arguments.receiver')(oTimer)
        if (oRule && oRule.scope) {
          switch (oRule.scope) {
            case 'mschema':
              var oMschema
              if ((oMschema = fnGetEntryRuleMschema(oApp))) {
                $parse('_temp.mschema').assign(oTimer, {
                  title: oMschema.title,
                  auto: true,
                })
              }
              break
            case 'group':
              if (!oRule.app && $scope.app.entryRule.group) {
                $parse('_temp.group').assign(oTimer, {
                  id: $scope.app.entryRule.group.id,
                  title: $scope.app.entryRule.group.title,
                  auto: true,
                })
              }
              break
          }
        }
      }
      srvEnlApp.get().then(function (oApp) {
        $scope.srvTimer.list(oApp, 'remind').then(function (timers) {
          if (timers && timers.length) {
            timers.forEach(function (oTimer) {
              $scope.defaultReceiver(oTimer, oApp)
            })
          }
          $scope.timers = timers
        })
      })
    },
  ])
  /**
   * 任务提醒
   */
  ngApp.provider.controller('ctrlUndoneRemind', [
    '$scope',
    '$parse',
    'srvEnrollApp',
    'tkGroupApp',
    function ($scope, $parse, srvEnlApp, tkGrpApp) {
      $scope.assignGroup = function (oTimer) {
        tkGrpApp.choose($scope.app, true).then(function (oResult) {
          var oGrpApp
          if (oResult.app) {
            oGrpApp = {
              id: oResult.app.id,
              title: oResult.app.title,
            }
            if (oResult.teams && oResult.teams.length) {
              oGrpApp.teams = {
                id: [],
                title: [],
              }
              oResult.teams.forEach(function (oTeam) {
                oGrpApp.teams.id.push(oTeam.team_id)
                oGrpApp.teams.title.push(oTeam.title)
              })
            }
            $parse('task.task_arguments.receiver.group').assign(oTimer, oGrpApp)
            oTimer.modified = true
          }
        })
      }
      srvEnlApp.get().then(function (oApp) {
        $scope.srvTimer.list(oApp, 'undone').then(function (timers) {
          $scope.timers = timers
        })
      })
    },
  ])
  /**
   * 事件提醒
   */
  ngApp.provider.controller('ctrlEventRemind', [
    '$scope',
    '$parse',
    'http2',
    '$timeout',
    'srvEnrollApp',
    'tkGroupApp',
    'tkEnrollApp',
    function (
      $scope,
      $parse,
      http2,
      $timeout,
      srvEnlApp,
      tkGrpApp,
      tkEnrollApp
    ) {
      var _oConfig
      $scope.modified = false
      $scope.config = null
      $scope.initConfig = function (eventName) {
        _oConfig[eventName] = {
          valid: false,
          page: 'cowork',
          receiver: {
            scope: [],
          },
        }
        switch (eventName) {
          case 'submit':
            break
          case 'cowork':
          case 'remark':
            _oConfig[eventName].receiver.scope.push('related')
            break
        }
      }
      $scope.assignGroup = function (oRule) {
        tkGrpApp.choose($scope.app).then(function (oResult) {
          var oGrpApp
          if (oResult.app) {
            oGrpApp = {
              id: oResult.app.id,
              title: oResult.app.title,
            }
            if (oResult.team) {
              oGrpApp.team = {
                id: oResult.team.team_id,
                title: oResult.team.title,
              }
            }
            $parse('group').assign(oRule, oGrpApp)
          }
        })
      }
      $scope.save = function () {
        tkEnrollApp
          .update($scope.app, {
            notifyConfig: _oConfig,
          })
          .then(function (oNewApp) {
            http2.merge($scope.app.notifyConfig, oNewApp.notifyConfig)
            http2.merge(_oConfig, oNewApp.notifyConfig)
            /* watch后再执行 */
            $timeout(function () {
              $scope.modified = false
            })
          })
      }
      $scope.remove = function (eventName) {
        delete _oConfig[eventName]
        $scope.save()
      }
      srvEnlApp.get().then(function (oApp) {
        $scope.config = _oConfig = angular.copy(oApp.notifyConfig)
        $scope.$watch(
          'config',
          function (oNewConfig, oOldConfig) {
            if (oNewConfig && oNewConfig !== oOldConfig) {
              $scope.modified = true
            }
          },
          true
        )
      })
    },
  ])
})
