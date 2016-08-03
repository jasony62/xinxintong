
define(['frame'], function (ngApp) {
    /**
     * app setting controller
     */

    ngApp.provider.controller('ctrlDetail', ['$scope', '$q', 'http2','mediagallery', function ($scope, $q, http2, mediagallery) {
        $scope.$parent.subView = 'detail';
        //上传图片-start
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.editing.pic = url + '?_=' + (new Date()) * 1;
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.siteId, options);
        };

        //上传图片-end
       }]);
});