app.controller('setCtrl', ['$scope', 'http2', 'noticebox', function($scope, http2, noticebox) {
    $scope.repeatPwd = (function() {
        return {
            test: function(value) {
                if (value == $scope.npwd) return true;
                else return false;
            }
        };
    })();
    $scope.changePwd = function() {
        http2.post('/rest/pl/fe/user/changePwd', {
            opwd: $scope.opwd,
            npwd: $scope.npwd
        }, function(rsp) {
            noticebox.success('修改成功');
        });
    };
}]);