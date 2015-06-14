angular.module('xxtApp', []).
controller('loginCtrl',['$scope','$http', function($scope,$http){
    $scope.errmsg = '';
    $scope.submitting = false;
    $scope.user = {};
    $scope.$watchCollection('user', function(){
        $scope.errmsg = '';
    });
    $scope.submit = function() {
        $scope.submitting = true;
        $http.post('/rest/cus/crccre/member/auth/login?token='+$scope.token+'&authid='+$scope.authid, $scope.user).
        success(function(rsp){
            $scope.submitting = false;
            if (angular.isString(rsp)) {
                $scope.errmsg = rsp;
                return;
            }
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            location.href = rsp.data;
        });
    };
    $scope.$watch('jsonMember', function(nv){
        nv && nv.length && ($scope.authedMember = JSON.parse(decodeURIComponent(nv)));
    });
}]);
