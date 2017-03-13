ngApp.provider.controller('ctrlSiteUser', ['$scope', '$http', function($scope, $http) {
    function listSites() {
        $http.get('/rest/home/listSite').success(function(rsp) {
            $scope.sites = rsp.data.sites;
        });
    };

    function listTrends() {
        var url = '/rest/site/fe/user/site/trends?site=platform&_=' + (new Date() * 1);
        $http.get(url).success(function(rsp) {
            $scope.trends = rsp.data.trends;
        });
    };

    $scope.subscribeSite = function(site) {
        if ($scope.siteUser === false) {
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
    $scope.openTrend = function(trend) {
        if (/article|custom|news|channel|link/.test(trend.matter_type)) {
            location.href = '/rest/site/fe/matter?site=' + trend.siteid + '&id=' + id + '&type=' + trend.matter_type;
        } else {
            location.href = '/rest/site/fe/matter/' + trend.matter_type + '?site=' + trend.siteid + '&app=' + id;
        }
    };
    $scope.openMatter = function(matter) {
        location.href = matter.url;
    };
    listSites();
    $scope.listApps();
    $scope.listArticles();
    $scope.$watch('platform', function(platform) {
        if (!platform) return;
        $http.get('/rest/site/fe/user/get?site=platform').success(function(rsp) {
            $scope.siteUser = rsp.data;
            if (window.sessionStorage) {
                var pendingMethod;
                if (pendingMethod = window.sessionStorage.getItem('xxt.home.auth.pending')) {
                    window.sessionStorage.removeItem('xxt.home.auth.pending');
                    pendingMethod = JSON.parse(pendingMethod);
                    $scope[pendingMethod.name].apply($scope, pendingMethod.args);
                }
            }
            listTrends();
        });
    });
}]);
ngApp.provider.controller('ctrlCarousel', function($scope) {
    $scope.myInterval = 5000;
    $scope.noWrapSlides = false;
    $scope.active = 0;

    $scope.$watch('platform', function(platform) {
        if (platform === undefined) return;
        if (platform.home_carousel.length) {
            $scope.slides = platform.home_carousel;
        }
    });
});
