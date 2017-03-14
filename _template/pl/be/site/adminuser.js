ngApp.provider.controller('ctrlSite', ['$scope', '$http', 'srvUser', function($scope, $http, srvUser) {
    $scope.criteria = {
        scope: 'A'
    };
    $scope.page = {
        size: 21,
        at: 1,
        total: 0
    };
    $scope.changeScope = function(scope) {
        $scope.criteria.scope = scope;
        $scope.searchTemplate();
    };
    $scope.searchSite = function() {
        var url = '/rest/home/listSite?userType=admin';
        $http.get(url).success(function(rsp) {
            $scope.sites = rsp.data.sites;
        });
    };
    $scope.$watch('platform', function(platform) {
        if (!platform) return;
        srvUser.getSiteAdminUser().then(function(user) {
            $scope.siteAdminUser = user;
            if (window.sessionStorage) {
                var pendingMethod;
                if (pendingMethod = window.sessionStorage.getItem('xxt.home.auth.pending')) {
                    window.sessionStorage.removeItem('xxt.home.auth.pending');
                    pendingMethod = JSON.parse(pendingMethod);
                    $scope[pendingMethod.name].apply($scope, pendingMethod.args);
                }
            }
            $scope.searchSite();
        });
    });
}]);
