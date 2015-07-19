xxtApp.controller('regCtrl',['$scope','$http','$rootScope',function($scope,$http,$rootScope){
    $scope.repeatPwd = (function() {
        return {
            test:function(value) {
                if (value == $scope.password) return true;
                else return false;
            }
        };
    })();
    $scope.register = function() {
        $http.post('/rest/user/register', {
            email: $scope.email,
            password: $scope.password
        }).
        success(function(rsp) {
            if (rsp.err_code != 0) {
                $rootScope.errmsg = rsp.err_msg;
                return;
            }
            location.href = '/page/user/login';
        });
    };
}]);
