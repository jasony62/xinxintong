xxtApp.controller('userCtrl',['$scope','$http','$rootScope',function($scope,$http,$rootScope){
    $scope.data = {group:1};
    if (window.uid && window.uid.length > 0) {
        $scope.data.username = window.uid;
        $scope.data.dessaposs = 'Y';
        $http.post('/rest/cus/crccre/auth/auth/login', $scope.data).
        success(function(rsp){
            if (angular.isString(rsp)) {
                $rootScope.errmsg = rsp;
                return;
            }
            if (rsp.err_code != 0) {
                $rootScope.errmsg = rsp.err_msg;
                return;
            }
            window.location.href = '/page/main';
        });
    }
    $rootScope.keypress = function(event) {
        var code = event.keyCode || event.which;
        if (code === 13 && $scope.data.username && $scope.data.password) {
            event.preventDefault();
            $scope.login();
        }
    };
    $scope.login = function() {
        $http.post('/rest/cus/crccre/auth/auth/login', $scope.data).
        success(function(rsp){
            if (angular.isString(rsp)) {
                $rootScope.errmsg = rsp;
                return;
            }
            if (rsp.err_code != 0) {
                $rootScope.errmsg = rsp.err_msg;
                return;
            }
            window.location.href = '/page/main';
        });
    };
    angular.element('[ng-model="data.username"]').focus();
}]);
