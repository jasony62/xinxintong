define(['frame'], function(ngApp) {
    'use strict';
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlDetail', ['$scope', 'http2', 'mediagallery', 'srvWallApp', '$uibModal', 'srvTag', 'srvSite', 'cstApp', function($scope, http2, mediagallery, srvWallApp, $uibModal, srvTag, srvSite, cstApp) {
        (function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        })();
        $scope.$parent.subView = 'detail';
        $scope.matterTypes = cstApp.matterTypes;
        $scope.tagMatter = function(subType){
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.wall, oTags, subType);
        };
        $scope.start = function() {
            $scope.wall.active = 'Y';
            $scope.update('active');
        };
        $scope.end = function() {
            $scope.wall.active = 'N';
            $scope.update('active');
        };
        $scope.del = function() {
            var vcode;
            vcode = prompt('如果此信息墙中没有用户，删除后不可恢复！若要继续请输入信息墙名称');
            if (vcode === $scope.wall.title) {
                http2.get('/rest/pl/fe/matter/wall/remove?site=' + $scope.wall.siteid + '&app=' + $scope.wall.id, function(rsp) {
                   location.href = '/rest/pl/fe';
                });
            }
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.wall.pic = url + '?_=' + (new Date() * 1);
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.siteId, options);
        };
        $scope.removePic = function() {
            $scope.wall.pic = '';
            $scope.update('pic');
        };
        $scope.setQrcode = function() {
            if($scope.wall.matters_img && $scope.wall.matters_img.length >= 3) {
                alert("最多允许上传3张二维码");
                return;
            }
            var options = {
                callback: function(url) {
                    var img = {};
                    img.qrcodesrc = url;
                    !$scope.wall.matters_img && ($scope.wall.matters_img = []);
                    $scope.wall.matters_img.push(img);
                    $scope.update('matters_img');
                }
            };
            mediagallery.open($scope.siteId, options);
        }
        $scope.setImage = function() {
            if($scope.wall.result_img && $scope.wall.result_img.length >= 4) {
                alert("最多允许上传4张图片");
                return;
            }
            var options = {
                callback: function(url) {
                    var img = {};
                    img.imgsrc = url;
                    !$scope.wall.result_img && ($scope.wall.result_img = []);
                    $scope.wall.result_img.push(img);
                    $scope.update('result_img');
                }
            };
            mediagallery.open($scope.siteId, options);
        }
        $scope.removeQrcode = function(qrcodeimgs, index) {
            qrcodeimgs.splice(index,1);
            $scope.update('matters_img');
        }
        $scope.removeImage = function(qrcodeimgs, index) {
            qrcodeimgs.splice(index,1);
            $scope.update('result_img');
        }
        $scope.assignMission = function() {
            srvWallApp.assignMission();
        };
        $scope.quitMission = function() {
            srvWallApp.quitMission();
        };
        $scope.choosePhase = function() {
            srvWallApp.choosePhase();
        };
        $scope.downloadQrcode = function(url) {
            $('<a href="' + url + '" download="' + $scope.wall.title + '.png"></a>')[0].click();
        };
        $scope.addInteractMatter = function() {
            srvSite.openGallery({
                matterTypes: $scope.matterTypes
            }).then(function(result) {
                var relations;
                if (result.matters && result.matters.length) {
                    result.matters.forEach(function(matter) {
                        matter.type = result.type;
                    });
                    relations = { matters: result.matters };
                    http2.post('/rest/pl/fe/matter/wall/addInteractMatter?site=' + $scope.wall.siteid + '&app=' + $scope.wall.id, relations, function(rsp) {
                        $scope.wall.interact_matter = rsp.data.interact_matter;
                    });
                }
            });
        };
        $scope.gotoMatter = function(matter) {
            location.href = '/rest/pl/fe/matter/' + matter.type + '?site=' + $scope.wall.siteid + '&id=' + matter.id;
        };
        $scope.removeInteractMatter = function(matter) {
            var removed = {
                id: matter.id,
                type: matter.type.toLowerCase(),
                title: matter.title
            };
            http2.post('/rest/pl/fe/matter/wall/removeInteractMatter?site=' + $scope.wall.siteid + '&app=' + $scope.wall.id, removed, function(rsp) {
                $scope.wall.interact_matter = rsp.data.interact_matter;
            });
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope.wall[data.state] = data.value;
            $scope.update(data.state);
        });
        $scope.$watch('wall', function(oWall) {
            if (oWall) {
                $scope.interactAction = oWall.scenario_config.interact_action;
                $scope.entry = {
                    url: oWall.user_url,
                    qrcode: '/rest/site/fe/matter/wall/qrcode?site=' + oWall.siteid + '&url=' + encodeURIComponent(oWall.user_url),
                };
            }
        });
    }]);
});
