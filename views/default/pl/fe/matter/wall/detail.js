
define(['frame'], function (ngApp) {
    /**
     * app setting controller
     */

    ngApp.provider.controller('ctrlDetail', ['$scope', '$q', 'http2','mediagallery', function ($scope, $q, http2, mediagallery) {
        $scope.$parent.subView = 'detail';
        $scope.start = function() {
            $scope.wall.active = 'Y';
            $scope.update('active');
        };
        $scope.end = function() {
            $scope.wall.active = 'N';
            $scope.update('active');
        };
        //上传图片-start
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.wall.pic = url + '?_=' + (new Date()) * 1;
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.siteId, options);
        };
        //上传图片-end
        //删除图片-start
        $scope.removePic = function() {
            $scope.wall.pic = '';
            $scope.update('pic');
        };
       }]);
});