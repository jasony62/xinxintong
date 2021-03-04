module.exports = [
  "$q",
  function ($q) {
    var aModifiedImgFields;
    aModifiedImgFields = [];
    return {
      restrict: "A",
      controller: [
        "$scope",
        "$timeout",
        "noticebox",
        function ($scope, $timeout, noticebox) {
          function imgCount(schemaId, count) {
            if (schemaId !== null) {
              aModifiedImgFields.indexOf(schemaId) === -1 &&
                aModifiedImgFields.push(schemaId);
              $scope.data[schemaId] === undefined &&
                ($scope.data[schemaId] = []);
              if (count) {
                count = parseInt(count);
                if (count > 0 && $scope.data[schemaId].length >= count) {
                  noticebox.warn("最多允许上传（" + count + "）张图片");
                  return false;
                }
              }
            }
            return true;
          }

          function appendImg(schemaId, newImgs) {
            let imgData = $scope.data[schemaId];
            newImgs.forEach((img) => imgData.push(img));
            if (window.wx) {
              $timeout(function () {
                let startOffset = imgData.length - newImgs.length;
                newImgs.forEach((img, index) => {
                  let pos = startOffset + index + 1;
                  document
                    .querySelector(
                      'ul[name="' +
                        schemaId +
                        '"] li[wrap=img]:nth-child(' +
                        pos +
                        ") img"
                    )
                    .setAttribute("src", img.imgSrc);
                });
              });
            }
          }

          function imgBind(schemaId, imgs) {
            var phase;
            phase = $scope.$root.$$phase;
            if (phase === "$digest" || phase === "$apply") {
              appendImg(schemaId, imgs);
            } else {
              $scope.$apply(() => {
                appendImg(schemaId, imgs);
              });
            }
          }

          function onWxSubmit(defer, imgs, index) {
            if (index >= imgs.length) {
              defer.resolve("ok");
            } else {
              window.xxt.image.wxUpload($q.defer(), imgs[index]).then(() => {
                onWxSubmit(defer, imgs, ++index);
              });
            }
          }

          $scope.beforeSubmit(function () {
            let defer = $q.defer();
            if (window.wx) {
              let imgs = [];
              let imgSchemas = $scope.app.dynaDataSchemas.filter(
                (s) => s.type === "image"
              );
              imgSchemas.forEach((s) => {
                if ($scope.data[s.id] && $scope.data[s.id].length) {
                  $scope.data[s.id].forEach((img) => imgs.push(img));
                }
              });
              onWxSubmit(defer, imgs, 0);
            } else {
              defer.resolve("ok");
            }
            return defer.promise;
          });
          $scope.chooseImage = function (schemaId, count, from) {
            if (imgCount(schemaId, count, from)) {
              window.xxt.image.choose($q.defer(), from).then(function (result) {
                if (result instanceof Object) {
                  imgBind(schemaId, result);
                } else {
                  noticebox.error(result);
                }
              });
            }
          };
          $scope.removeImage = function (imgField, index) {
            imgField.splice(index, 1);
          };
          $scope.pasteImage = function (schemaId, event, count, from) {
            if (imgCount(schemaId, count, from)) {
              var targetDiv;
              targetDiv =
                event.currentTarget.children[
                  event.currentTarget.children.length - 1
                ];
              window.xxt.image
                .paste(angular.element(targetDiv)[0], $q.defer(), from)
                .then(function (imgs) {
                  imgBind(schemaId, imgs);
                });
            }
          };
        },
      ],
    };
  },
];
