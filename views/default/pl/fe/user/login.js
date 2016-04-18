app.controller('ctrlLogin', ['$scope', '$http', '$rootScope', function($scope, $http, $rootScope) {
    $scope.data = {};
    $rootScope.keypress = function(event) {
        var code = event.keyCode || event.which;
        if (code === 13 && $scope.data.email && $scope.data.password) {
            event.preventDefault();
            $scope.login();
        }
    };
    $scope.login = function() {
        $http.post('/rest/pl/fe/user/login/do', $scope.data).success(function(rsp) {
            if (rsp.err_code != 0) {
                $rootScope.errmsg = rsp.err_msg;
                return;
            }
            location.replace('/rest/pl/fe/user/auth/passed?uid=' + rsp.data);
        });
    };
}]);