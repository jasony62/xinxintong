define(['frame'], function (ngApp) {
  ngApp.provider.controller('ctrlDispatchExec', [
    '$scope',
    'http2',
    'noticebox',
    function ($scope, http2, noticebox) {
      let task
      $scope.task = task = {}
      $scope.execute = function () {
        /*检查url是否合规*/
        let { id, title } = $scope.editing
        http2
          .post(
            task.url,
            { id, title },
            {
              headers: {
                'X-Dispatch-Executor': `{"id":${id},"type":"channel"}`,
              }, // 添加自定义头指定发起方
              parseResponse: false,
            }
          )
          .then((rsp) => {
            if (rsp.code === 0 && rsp.result) {
              noticebox.success(
                typeof rsp.result === 'string' ? rsp.result : '操作成功'
              )
            } else if (rsp.code !== 0) {
              noticebox.error(rsp.msg)
            }
          })
      }
    },
  ])
})
