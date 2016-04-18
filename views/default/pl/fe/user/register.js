app.controller('ctrlReg', ['$scope', '$http', '$rootScope', function($scope, $http, $rootScope) {
    $scope.repeatPwd = (function() {
        return {
            test: function(value) {
                if (value == $scope.password) return true;
                else return false;
            }
        };
    })();
    $scope.register = function() {
        $http.post('/rest/pl/fe/user/register/do', {
            email: $scope.email,
            password: $scope.password
        }).
        success(function(rsp) {
            if (rsp.err_code != 0) {
                $rootScope.errmsg = rsp.err_msg;
                return;
            }
            location.replace('/rest/pl/fe/user/login');
        });
    };
}]);