(function() {
    xxtApp.register.controller('settingCtrl', ['$scope', 'http2', function($scope, http2) {
        $scope.$parent.subView = 'setting';
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.wall.pic = url + '?_=' + (new Date()) * 1;
                    $scope.update('pic');
                }
            };
            $scope.$broadcast('mediagallery.open', options);
        };
        $scope.removePic = function() {
            $scope.wall.pic = '';
            $scope.update('pic');
        };
        $scope.start = function() {
            $scope.wall.active = 'Y';
            $scope.update('active');
        };
        $scope.end = function() {
            $scope.wall.active = 'N';
            $scope.update('active');
        };
    }]);
})();