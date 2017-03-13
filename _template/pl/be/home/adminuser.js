ngApp.provider.controller('ctrlHome', ['$scope', '$http', '$uibModal', 'srvUser', function($scope, $http, $uibModal, srvUser) {
    function listSites() {
        $http.get('/rest/home/listSite?userType=admin').success(function(rsp) {
            $scope.sites = rsp.data.sites;
        });
    };

    function listTemplates() {
        $http.get('/rest/home/listTemplate').success(function(rsp) {
            $scope.templates = rsp.data;
        });
    };

    function createSite() {
        var defer = $q.defer(),
            url = '/rest/pl/fe/site/create?_=' + (new Date() * 1);

        $http.get(url).success(function(rsp) {
            defer.resolve(rsp.data);
        });
        return defer.promise;
    }

    function useTemplate(site, template) {
        var url = '/rest/pl/fe/template/purchase?template=' + template.id;
        url += '&site=' + site.id;

        $http.get(url).success(function(rsp) {
            $http.get('/rest/pl/fe/matter/enroll/createByOther?site=' + site.id + '&template=' + template.id).success(function(rsp) {
                location.href = '/rest/pl/fe/matter/enroll?id=' + rsp.data.id + '&site=' + site.id;
            });
        });
    }
    $scope.openMatter = function(matter) {
        location.href = matter.url;
    };
    $scope.favorTemplate = function(template) {
        if ($scope.siteAdminUser === false) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'favorTemplate',
                    args: [template]
                });
                window.sessionStorage.setItem('xxt.home.auth.pending', method);
            }
            location.href = '/rest/pl/fe/user/auth';
        } else {
            var url = '/rest/pl/fe/template/siteCanFavor?template=' + template.id + '&_=' + (new Date() * 1);
            $http.get(url).success(function(rsp) {
                var sites = rsp.data;
                $uibModal.open({
                    templateUrl: 'favorTemplateSite.html',
                    dropback: 'static',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                        $scope2.mySites = sites;
                        $scope2.ok = function() {
                            var selected = [];
                            sites.forEach(function(site) {
                                site._selected === 'Y' && selected.push(site);
                            });
                            if (selected.length) {
                                $mi.close(selected);
                            } else {
                                $mi.dismiss();
                            }
                        };
                        $scope2.cancel = function() {
                            $mi.dismiss();
                        };
                    }]
                }).result.then(function(selected) {
                    var url = '/rest/pl/fe/template/favor?template=' + template.id,
                        sites = [];

                    selected.forEach(function(site) {
                        sites.push(site.id);
                    });
                    url += '&site=' + sites.join(',');
                    $http.get(url).success(function(rsp) {});
                });
            });
        }
    };
    $scope.useTemplate = function(template) {
        if ($scope.siteAdminUser === false) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'useTemplate',
                    args: [template]
                });
                window.sessionStorage.setItem('xxt.home.auth.pending', method);
            }
            location.href = '/rest/pl/fe/user/auth';
        } else {
            var url = '/rest/pl/fe/site/list?_=' + (new Date() * 1);
            $http.get(url).success(function(rsp) {
                var sites = rsp.data;
                if (sites.length === 1) {
                    useTemplate(sites[0], template);
                } else if (sites.length === 0) {
                    createSite().then(function(site) {
                        useTemplate(site, template);
                    });
                } else {
                    $uibModal.open({
                        templateUrl: 'useTemplateSite.html',
                        dropback: 'static',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            var data;
                            $scope2.mySites = sites;
                            $scope2.data = data = {};
                            $scope2.ok = function() {
                                if (data.index !== undefined) {
                                    $mi.close(sites[data.index]);
                                } else {
                                    $mi.dismiss();
                                }
                            };
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                        }]
                    }).result.then(function(site) {
                        useTemplate(site, template);
                    });
                }
            });
        }
    };
    $scope.listApps();
    $scope.listArticles();
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
            listSites();
            listTemplates();
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
