ngApp.provider.controller('ctrlSite', ['$scope', '$http', '$uibModal', function ($scope, $http, $uibModal) {
    $scope.criteria = {
        scope: 'A'
    };
    $scope.page = {
        size: 21,
        at: 1,
        total: 0
    };
    $scope.changeScope = function (scope) {
        $scope.criteria.scope = scope;
    };
    $scope.searchSite = function () {
        var url = '/rest/home/listSite';
        $http.get(url).success(function (rsp) {
            $scope.sites = rsp.data.sites;
        });
    };
    $scope.subscribeSite = function (site) {
        if ($scope.siteAdminUser === false) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'subscribeSite',
                    args: [site]
                });
                window.sessionStorage.setItem('xxt.home.auth.pending', method);
            }
            location.href = '/rest/pl/fe/user/auth';
        } else {
            var url = '/rest/pl/fe/site/subscribe/sitesByUser?site=' + site.siteid + '&_=' + (new Date() * 1);
            $http.get(url).success(function (rsp) {
                var sites = rsp.data;
                if (sites.length === 1) {

                } else if (sites.length === 0) {

                } else {
                    $uibModal.open({
                        templateUrl: 'subscribeSite.html',
                        dropback: 'static',
                        controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                            $scope2.mySites = sites;
                            $scope2.ok = function () {
                                var selected = [];
                                sites.forEach(function (site) {
                                    site._selected === 'Y' && selected.push(site);
                                });
                                if (selected.length) {
                                    $mi.close(selected);
                                } else {
                                    $mi.dismiss();
                                }
                            };
                            $scope2.cancel = function () {
                                $mi.dismiss();
                            };
                        }]
                    }).result.then(function (selected) {
                        var url = '/rest/pl/fe/site/subscribe?site=' + site.siteid;
                        sites = [];

                        selected.forEach(function (mySite) {
                            sites.push(mySite.id);
                        });
                        url += '&subscriber=' + sites.join(',');
                        $http.get(url).success(function (rsp) {
                            site._subscribed = 'Y';
                        });
                    });
                }
            });
        }
    };
    $scope.$watch('platform', function (platform) {
        if (!platform) return;
        $scope.searchSite();
    });
}]);