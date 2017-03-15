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
        var url = '/rest/home/listSite';
        $http.get(url).success(function(rsp) {
            $scope.sites = rsp.data.sites;
        });
    };
    $scope.subscribeSite = function(site) {
        if (!$scope.siteUser || !$scope.siteUser.loginExpire) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'subscribeSite',
                    args: [site]
                });
                window.sessionStorage.setItem('xxt.home.auth.pending', method);
            }
            location.href = '/rest/site/fe/user/login?site=platform';
        } else {
            var url = '/rest/site/fe/user/site/subscribe?site=platform&target=' + site.siteid;
            $http.get(url).success(function(rsp) {
                site._subscribed = 'Y';
            });
        }
    };
    $scope.unsubscribeSite = function(site) {
        var url = '/rest/site/fe/user/site/unsubscribe?site=platform&target=' + site.siteid;
        $http.get(url).success(function(rsp) {
            site._subscribed = 'N';
        });
    };
    $scope.$watch('platform', function(platform) {
        if (!platform) return;
        srvUser.getSiteUser().then(function(user) {
            $scope.siteUser = user;
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
        srvUser.getSiteAdminUser().then(function(user) {
            $scope.siteAdminUser = user;
        });
    });
}]);
