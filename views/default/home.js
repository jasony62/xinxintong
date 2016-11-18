define(["angular", "xxt-page"], function(angular, codeAssembler) {
    'use strict';
    var ngApp = angular.module('home', ['ui.bootstrap', 'ui.tms']);
    ngApp.config(['$locationProvider', '$controllerProvider', function($lp, $cp) {
        $lp.html5Mode(true);
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$q', '$uibModal', 'http2', function($scope, $q, $uibModal, http2) {
        var platform, pages = {};
        $scope.subView = '';
        $scope.shiftPage = function(subView) {
            if ($scope.subView === subView) return;
            if (pages[subView] === undefined) {
                codeAssembler.loadCode(ngApp, platform[subView + '_page']).then(function() {
                    pages[subView] = platform[subView + '_page'];
                    $scope.page = pages[subView];
                    $scope.subView = subView;
                });
            } else {
                $scope.page = pages[subView];
                $scope.subView = subView;
            }
        };
        $scope.favorTemplate = function(template) {
            if ($scope.isLogin === 'N') {
                location.href = '/rest/pl/fe/user/login';
            } else {
                var url = '/rest/pl/fe/template/siteCanFavor?template=' + template.id + '&_=' + (new Date() * 1);
                http2.get(url, function(rsp) {
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
                        http2.get(url, function(rsp) {});
                    });
                });
            }
        };
        $scope.useTemplate = function(template) {
            if ($scope.isLogin === 'N') {
                location.href = '/rest/pl/fe/user/login';
            } else {
                var url = '/rest/pl/fe/site/list?_=' + (new Date() * 1);
                http2.get(url, function(rsp) {
                    var sites = rsp.data;
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
                        var url = '/rest/pl/fe/template/purchase?template=' + template.id;
                        url += '&site=' + site.id;
                        http2.get(url, function(rsp) {
                            http2.get('/rest/pl/fe/matter/enroll/createByOther?site=' + site.id + '&template=' + template.id, function(rsp) {
                                location.href = '/rest/pl/fe/matter/enroll?id=' + rsp.data.id + '&site=' + site.id;
                            });
                        });
                    });
                });
            }
        };
        $scope.openSite = function(site) {
            location.href = '/rest/site/home?site=' + site.siteid;
        };
        $scope.subscribeSite = function(site) {
            if ($scope.isLogin === 'N') {
                location.href = '/rest/pl/fe/user/login';
            } else {
                var url = '/rest/pl/fe/site/siteCanSubscribe?site=' + site.siteid + '&_=' + (new Date() * 1);
                http2.get(url, function(rsp) {
                    var sites = rsp.data;
                    $uibModal.open({
                        templateUrl: 'subscribeSite.html',
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
                        var url = '/rest/pl/fe/site/subscribe?site=' + site.siteid;
                        sites = [];

                        selected.forEach(function(mySite) {
                            sites.push(mySite.id);
                        });
                        url += '&subscriber=' + sites.join(',');
                        http2.get(url, function(rsp) {});
                    });
                });
            }
        };
        http2.get('/rest/home/get', function(rsp) {
            platform = rsp.data.platform;
            if (platform.home_page === false) {
                location.href = '/rest/pl/fe';
            } else {
                http2.get('/rest/pl/fe/user/auth/isLogin', function(rsp) {
                    $scope.isLogin = rsp.data;
                });
                $scope.platform = platform;
                $scope.shiftPage('home');
            }
        });
    }]);

    /*bootstrap*/
    angular._lazyLoadModule('home');

    return ngApp;
});