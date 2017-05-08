define(['frame'], function(ngApp) {
    'use strict';
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlDetail', ['$scope', 'mediagallery', 'srvWallApp', function($scope, mediagallery, srvWallApp) {
        (function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        })();
        $scope.$parent.subView = 'detail';
        $scope.start = function() {
            $scope.wall.active = 'Y';
            $scope.update('active');
        };
        $scope.end = function() {
            $scope.wall.active = 'N';
            $scope.update('active');
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
