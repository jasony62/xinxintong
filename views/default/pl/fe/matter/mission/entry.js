define(['frame'], function (ngApp) {
  'use strict'
  ngApp.provider.controller('ctrlEntry', [
    '$scope',
    '$uibModal',
    'srvSite',
    'srvTimerNotice',
    function ($scope, $uibModal, srvSite, srvTimerNotice) {
      /* 定时任务服务 */
      $scope.srvTimer = srvTimerNotice
      /* 定时任务截止时间 */
      $scope.$on('xxt.tms-datepicker.change', function (event, data) {
        var oTimer
        if ((oTimer = $scope.srvTimer.timerById(data.state))) {
          oTimer.task.task_expire_at = data.value
        }
      })
      $scope.openPageSetting = function () {
        $uibModal.open({
          templateUrl: 'pageSetting.html',
          controller: [
            '$scope',
            '$uibModalInstance',
            function ($scope2, $mi) {
              $scope2.pageConfig = $scope.mission.pageConfig
              $scope2.dismiss = function () {
                $mi.dismiss()
              }
              $scope2.save = function () {
                $scope.update('pageConfig').then(function () {
                  $mi.close()
                })
              }
            },
          ],
        })
      }
      $scope.$watch('mission', function (oMission) {
        if (!oMission) return
        /* 项目通讯录 */
        srvSite
          .memberSchemaList(oMission, true)
          .then(function (aMemberSchemas) {
            $scope.missionMschemas = aMemberSchemas
          })
      })
    },
  ])
  ngApp.provider.controller('ctrlAccess', [
    '$scope',
    'srvSite',
    'tkEntryRule',
    function ($scope, srvSite, tkEntryRule) {
      $scope.$watch('mission', function (oMission) {
        if (!oMission) return
        srvSite.snsList().then(function (oSns) {
          $scope.tkEntryRule = new tkEntryRule(oMission, oSns, false, [
            'group',
            'enroll',
          ])
        })
        srvSite.memberSchemaList(oMission).then(function (aMemberSchemas) {
          $scope.memberSchemas = aMemberSchemas
          $scope.mschemasById = {}
          $scope.memberSchemas.forEach(function (mschema) {
            $scope.mschemasById[mschema.id] = mschema
          })
        })
      })
    },
  ])
  ngApp.provider.controller('ctrlRemind', [
    '$scope',
    function ($scope) {
      $scope.$watch('mission', function (oMission) {
        if (!oMission) return
        $scope.srvTimer.list(oMission, 'remind').then(function (timers) {
          $scope.timers = timers
        })
      })
    },
  ])
})
