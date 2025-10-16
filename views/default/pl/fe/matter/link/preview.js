define(['frame'], function (ngApp) {
  ngApp.provider.controller('ctrlPreview', [
    '$scope',
    'http2',
    'noticebox',
    'srvTimerNotice',
    function ($scope, http2, noticebox, srvTimerNotice) {
      $scope.applyToHome = function () {
        var url =
          '/rest/pl/fe/matter/home/apply?site=' +
          $scope.editing.siteid +
          '&type=link&id=' +
          $scope.editing.id
        http2.get(url).then(function (rsp) {
          noticebox.success('完成申请！')
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
      $scope.$watch('editing', function (oLink) {
        if (!oLink) return
        $scope.srvTimer.list(oLink, 'remind').then(function (timers) {
          $scope.timers = timers
        })
      })
    },
  ])
})
