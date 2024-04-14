module.exports = [
  '$q',
  function ($q) {
    var aModifiedImgFields
    aModifiedImgFields = []
    return {
      restrict: 'A',
      controller: [
        '$scope',
        '$timeout',
        'noticebox',
        function ($scope, $timeout, noticebox) {
          function imgCount(schemaId, count) {
            if (schemaId !== null) {
              aModifiedImgFields.indexOf(schemaId) === -1 &&
                aModifiedImgFields.push(schemaId)
              $scope.data[schemaId] === undefined &&
                ($scope.data[schemaId] = [])
              if (count) {
                count = parseInt(count)
                if (count > 0 && $scope.data[schemaId].length >= count) {
                  noticebox.warn('最多允许上传（' + count + '）张图片')
                  return false
                }
              }
            }
            return true
          }

          function appendImg(schemaId, newImgs) {
            let imgData = $scope.data[schemaId]
            newImgs.forEach((img) => imgData.push(img))
            if (window.wx) {
              $timeout(function () {
                let startOffset = imgData.length - newImgs.length
                newImgs.forEach((img, index) => {
                  let pos = startOffset + index + 1
                  document
                    .querySelector(
                      'ul[name="' +
                        schemaId +
                        '"] li[wrap=img]:nth-child(' +
                        pos +
                        ') img'
                    )
                    .setAttribute('src', img.imgSrc)
                })
              })
            }
          }

          function imgBind(schemaId, imgs) {
            var phase
            phase = $scope.$root.$$phase
            if (phase === '$digest' || phase === '$apply') {
              appendImg(schemaId, imgs)
            } else {
              $scope.$apply(() => {
                appendImg(schemaId, imgs)
              })
            }
          }

          function onWxSubmit(defer, imgs, index, isShowProgressTips = 1) {
            if (index >= imgs.length) {
              defer.resolve('ok')
            } else {
              noticebox.progress(
                `正在上传图片（${index + 1}/${imgs.length}），请稍等`
              )
              window.xxt.image
                .wxUpload($q.defer(), imgs[index], isShowProgressTips)
                .then(() => {
                  noticebox.close()
                  onWxSubmit(defer, imgs, ++index, isShowProgressTips)
                })
                .catch((errmsg) => {
                  noticebox.error('上传图片失败，原因：' + errmsg)
                })
            }
          }
          /**
           * 注册回调任务
           * 如果一个页面中有多个图片题，会导致注册多次，但是方法会上传所有题目的图片，因此要判断是否已经执行过，避免重复执行
           */
          $scope.beforeSubmit(function () {
            let defer = $q.defer()
            if (window.wx) {
              let imgs = []
              // 所有的图片题目
              let imgSchemas = $scope.app.dynaDataSchemas.filter(
                (s) => s.type === 'image'
              )
              imgSchemas.forEach((s) => {
                if ($scope.data[s.id] && $scope.data[s.id].length) {
                  $scope.data[s.id].forEach((img) => {
                    // 如果有serverId说明已经执行过上传微信，不用再执行
                    if (!img.serverId) imgs.push(img)
                  })
                }
              })
              onWxSubmit(defer, imgs, 0, 0)
            } else {
              defer.resolve('ok')
            }
            return defer.promise
          })
          $scope.chooseImage = function (schemaId, count, from) {
            if (imgCount(schemaId, count, from)) {
              window.xxt.image.choose($q.defer(), from).then(function (result) {
                if (result instanceof Object) {
                  imgBind(schemaId, result)
                } else {
                  noticebox.error(result)
                }
              })
            }
          }
          $scope.removeImage = function (imgField, index) {
            imgField.splice(index, 1)
          }
          $scope.pasteImage = function (schemaId, event, count, from) {
            if (imgCount(schemaId, count, from)) {
              var targetDiv
              targetDiv =
                event.currentTarget.children[
                  event.currentTarget.children.length - 1
                ]
              window.xxt.image
                .paste(angular.element(targetDiv)[0], $q.defer(), from)
                .then(function (imgs) {
                  imgBind(schemaId, imgs)
                })
            }
          }
        },
      ],
    }
  },
]
