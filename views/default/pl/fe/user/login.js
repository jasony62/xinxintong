app.controller('ctrlLogin', ['$scope', 'http2', function($scope, http2) {
    $scope.data = {};
    $scope.$root.keypress = function(event) {
        var code = event.keyCode || event.which;
        if (code === 13 && $scope.data.email && $scope.data.password) {
            event.preventDefault();
            $scope.login();
        }
    };
    $scope.login = function() {
        http2.post('/rest/pl/fe/user/login/do', $scope.data, function(rsp) {
            location.replace('/rest/pl/fe/user/auth/passed?uid=' + rsp.data);
        });
    };
}]);