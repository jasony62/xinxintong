module.exports = [
  '$q',
  'tmsLocation',
  'tmsDynaPage',
  function ($q, LS, tmsDynaPage) {
    function onSubmit($scope) {
      var defer
      defer = $q.defer()
      if (!oResumable.files || oResumable.files.length === 0) {
        defer.resolve('empty')
      }
      oResumable.on('progress', function () {
        var phase, p
        p = oResumable.progress()
        var phase = $scope.$root.$$phase
        if (phase === '$digest' || phase === '$apply') {
          $scope.progressOfUploadFile = Math.ceil(p * 100)
        } else {
          $scope.$apply(function () {
            $scope.progressOfUploadFile = Math.ceil(p * 100)
          })
        }
      })
      oResumable.on('complete', function () {
        var phase = $scope.$root.$$phase
        if (phase === '$digest' || phase === '$apply') {
          $scope.progressOfUploadFile = '完成'
        } else {
          $scope.$apply(function () {
            $scope.progressOfUploadFile = '完成'
          })
        }
        oResumable.cancel()
        defer.resolve('ok')
      })
      oResumable.upload()
      return defer.promise
    }
    var oResumable
    tmsDynaPage.loadScript(['/static/js/resumable.js']).then(function () {
      oResumable = new Resumable({
        target: LS.j('record/uploadFile', 'site', 'app'),
        testChunks: false,
        chunkSize: 512 * 1024,
      })
    })
    return {
      restrict: 'A',
      controller: [
        '$scope',
        'noticebox',
        'http2',
        function ($scope, noticebox, http2) {
          $scope.progressOfUploadFile = 0
          $scope.beforeSubmit(function () {
            return onSubmit($scope)
          })
          $scope.clickFile = function (schemaId, index) {
            if ($scope.data[schemaId] && $scope.data[schemaId][index]) {
              noticebox
                .confirm(
                  '删除文件【' +
                    $scope.data[schemaId][index].name +
                    '】，确定？'
                )
                .then(function () {
                  $scope.data[schemaId].splice(index, 1)
                })
            }
          }
          $scope.chooseFile = function (schemaId, count, accept) {
            var ele = document.createElement('input')
            ele.setAttribute('type', 'file')
            accept !== undefined && ele.setAttribute('accept', accept)
            ele.addEventListener(
              'change',
              function (evt) {
                var i, cnt, f
                cnt = evt.target.files.length
                for (i = 0; i < cnt; i++) {
                  f = evt.target.files[i]
                  if ($scope.fileConfig.allowtype) {
                    var allowtype = $scope.fileConfig.allowtype
                    if (!f.name.lastIndexOf('.')) {
                      noticebox.error(
                        '文件数据格式错误:只能上传' + allowtype + '格式的文件'
                      )
                      return false
                    }
                    var seat = f.name.lastIndexOf('.') + 1,
                      extendsion = f.name.substring(seat).toLowerCase()
                    if (allowtype.indexOf(extendsion) === -1) {
                      noticebox.error(
                        '文件数据格式错误:只能上传' + allowtype + '格式的文件'
                      )
                      return false
                    }
                  }
                  if (
                    $scope.fileConfig.maxsize !== 0 &&
                    $scope.fileConfig.maxsize * 1024 * 1024 <= f.size
                  ) {
                    noticebox.error(
                      '文件上传失败，大小不能超过' +
                        $scope.fileConfig.maxsize +
                        'M'
                    )
                    return false
                  }

                  oResumable.addFile(f)
                  $scope.$apply(function () {
                    $scope.data[schemaId] === undefined &&
                      ($scope.data[schemaId] = [])
                    $scope.data[schemaId].push({
                      uniqueIdentifier:
                        oResumable.files[oResumable.files.length - 1]
                          .uniqueIdentifier,
                      name: f.name,
                      size: f.size,
                      type: f.type,
                      url: '',
                    })
                    $scope.$broadcast('xxt.enroll.file.choose.done', schemaId)
                  })
                }
                ele = null
              },
              true
            )
            ele.click()
          }
        },
      ],
    }
  },
]
