define(['frame'], function(ngApp) {
    'use strict';
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlDetail', ['$scope', 'http2', 'mediagallery', 'srvWallApp', '$uibModal', 'srvTag', function($scope, http2, mediagallery, srvWallApp, $uibModal, srvTag) {
        (function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        })();
        $scope.$parent.subView = 'detail';
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
        $scope.$watch('wall', function(oWall) {
            if (oWall) {
                $scope.entry = {
                    url: oWall.user_url,
                    qrcode: '/rest/site/fe/matter/wall/qrcode?site=' + oWall.siteid + '&url=' + encodeURIComponent(oWall.user_url),
                };
            }
        });
    }]);
});
