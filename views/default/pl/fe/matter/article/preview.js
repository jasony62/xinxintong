define(['frame'], function (ngApp) {
  ngApp.provider.controller('ctrlPreview', [
    '$sce',
    '$scope',
    'http2',
    'noticebox',
    function ($sce, $scope, http2, noticebox) {
      $scope.downloadQrcode = function (url) {
        $(
          '<a href="' +
            url +
            '" download="' +
            $scope.editing.title +
            '二维码.png"></a>'
        )[0].click()
      }
      var modifiedData = {}

      $scope.modified = false
      $scope.submit = function () {
        http2
          .post(
            '/rest/pl/fe/matter/article/update?site=' +
              $scope.editing.siteid +
              '&id=' +
              $scope.editing.id,
            modifiedData
          )
          .then(function () {
            modifiedData = {}
            $scope.modified = false
            noticebox.success('完成保存')
          })
      }
      $scope.applyToHome = function () {
        var url =
          '/rest/pl/fe/matter/home/apply?site=' +
          $scope.editing.siteid +
          '&type=article&id=' +
          $scope.editing.id
        http2.get(url).then(function (rsp) {
          noticebox.success('完成申请！')
        })
      }
      $scope.$watch('editing', function (oArticle) {
        if (oArticle) {
          $scope.previewURL =
            '/rest/site/fe/matter?site=' +
            oArticle.siteid +
            '&id=' +
            oArticle.id +
            '&type=article&preview=Y'
        }
      })
    },
  ])
})
