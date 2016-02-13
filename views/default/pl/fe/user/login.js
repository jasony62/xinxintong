xxtApp.controller('userCtrl', ['$scope', '$http', '$rootScope', function($scope, $http, $rootScope) {
    var search, params, callback;
    search = decodeURIComponent(location.search);
    params = search.split('&');
    for (var i in params) {
        if (callback = params[i].match(/callback=(.+)/)) {
            callback = callback[1];
            break;
        }
    }
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
            if (callback && callback.length > 0) {
                callback += '?uid=' + rsp.data;
                location.replace(callback);
            } else {
                location.replace('/rest/pl/fe/main');
            }
        });
    };
}]);