module.exports = [
  '$q',
  'noticebox',
  function ($q, noticebox) {
    function doUpload2Wx(oPendingData) {
      var defer
      defer = $q.defer()
      wx.translateVoice({
        localId: oPendingData.localId, // 需要识别的音频的本地Id，由录音相关接口获得
        isShowProgressTips: 1, // 默认为1，显示进度提示
        success: function (res) {
          oPendingData.text = res.translateResult
          wx.uploadVoice({
            localId: oPendingData.localId,
            isShowProgressTips: 1,
            success: function (res) {
              oPendingData.serverId = res.serverId
              delete oPendingData.localId
              defer.resolve()
            },
            fail: function (res) {
              noticebox.error('录音文件上传失败：' + res.errMsg)
              defer.reject()
            },
          })
        },
      })

      return defer.promise
    }

    return {
      restrict: 'A',
      controller: [
        '$scope',
        '$uibModal',
        'noticebox',
        function ($scope, $uibModal, noticebox) {
          $scope.clickFile = function (schemaId, index) {
            var buttons, oSchemaData
            if ($scope.data[schemaId] && $scope.data[schemaId][index]) {
              buttons = [
                {
                  label: '删除',
                  value: 'delete',
                },
                {
                  label: '取消',
                  value: 'cancel',
                },
              ]
              oSchemaData = $scope.data[schemaId]
              noticebox
                .confirm(
                  '操作录音文件【' + oSchemaData[index].name + '】',
                  buttons
                )
                .then(function (value) {
                  switch (value) {
                    case 'delete':
                      oSchemaData.splice(index, 1)
                      break
                  }
                })
            }
          }
          $scope.startVoice = function (schemaId) {
            var oSchema, oSchemaData
            if (!window.wx || !wx.startRecord) {
              noticebox.warn('请在微信中进行录音')
              return
            }
            if ($scope.schemasById && $scope.schemasById[schemaId]) {
              oSchema = $scope.schemasById[schemaId]
            } else if (
              $scope.app &&
              $scope.app.dynaDataSchemas &&
              $scope.app.dynaDataSchemas.length
            ) {
              for (var i = $scope.app.dynaDataSchemas.length - 1; i >= 0; i--) {
                if (($scope.app.dynaDataSchemas[i].id = schemaId)) {
                  oSchema = $scope.app.dynaDataSchemas[i]
                  break
                }
              }
            }
            if (!oSchema) {
              noticebox.warn('数据错误，未找到题目定义')
              return
            }
            /* 检查限制条件 */
            $scope.data[oSchema.id] === undefined &&
              ($scope.data[oSchema.id] = [])
            oSchemaData = $scope.data[oSchema.id]
            if (oSchema.count && oSchemaData.length >= oSchema.count) {
              noticebox.warn('最多允许上传（' + oSchema.count + '）段录音')
              return
            }
            $uibModal
              .open({
                templateUrl: 'recordVoice.html',
                controller: [
                  '$scope',
                  '$interval',
                  '$uibModalInstance',
                  function ($scope2, $interval, $mi) {
                    var _oData, _timer
                    $scope2.data = _oData = {
                      name: '录音' + (oSchemaData.length + 1),
                      time: 0,
                      reset: function () {
                        this.time = 0
                        delete this.localId
                      },
                    }
                    $scope2.startRecord = function () {
                      wx.startRecord()
                      _oData.reset()
                      _timer = $interval(function () {
                        _oData.time++
                      }, 1000)
                      wx.onVoiceRecordEnd({
                        // 录音时间超过一分钟没有停止的时候会执行 complete 回调
                        complete: function (res) {
                          $scope.$apply(function () {
                            _oData.localId = res.localId
                          })
                          $interval.cancel(_timer)
                        },
                      })
                    }
                    $scope2.stopRecord = function () {
                      wx.stopRecord({
                        success: function (res) {
                          $scope.$apply(function () {
                            _oData.localId = res.localId
                          })
                        },
                      })
                      $interval.cancel(_timer)
                    }
                    $scope2.play = function () {
                      wx.playVoice({
                        localId: _oData.localId,
                      })
                      wx.onVoicePlayEnd({
                        success: function (res) {
                          var localId = res.localId
                        },
                      })
                    }
                    $scope2.pause = function () {
                      wx.pauseVoice({
                        localId: _oData.localId,
                      })
                    }
                    $scope2.stop = function () {
                      wx.stopVoice({
                        localId: _oData.localId,
                      })
                    }
                    $scope2.cancel = function () {
                      $mi.dismiss()
                    }
                    $scope2.ok = function () {
                      $mi.close($scope2.data)
                    }
                  },
                ],
                backdrop: 'static',
              })
              .result.then(function (oResult) {
                var oNewVoice
                oNewVoice = {
                  localId: oResult.localId,
                  name: oResult.name,
                  time: oResult.time,
                }
                if (oResult.localId) {
                  $scope.data[oSchema.id].push(oNewVoice)
                }
                /* 记录整体提交时处理文件上传 */
                $scope.beforeSubmit(function () {
                  return doUpload2Wx(oNewVoice)
                })
              })
          }
          $scope.playVoice = function () {}
        },
      ],
    }
  },
]
