app.controller('setCtrl', ['$scope', '$http', '$rootScope', function($scope, $http, $rootScope) {
    $scope.repeatPwd = (function() {
        return {
            test: function(value) {
                if (value == $scope.npwd) return true;
                else return false;
            }
        };
    })();
    $scope.changePwd = function() {
        $http.post('/rest/user/changePwd', {
            opwd: $scope.opwd,
            npwd: $scope.npwd
        }).
        success(function(rsp) {
            if (rsp.err_code !== 0) {
                $rootScope.errmsg = rsp.err_msg;
                return;
            }
            $rootScope.infomsg = '修改成功';
        });
    };
}]);