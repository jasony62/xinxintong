define(["angular", "xxt-page"], function(angular, codeAssembler) {
    'use strict';
    var ngApp = angular.module('tmpl', ['ui.bootstrap', 'ui.tms', 'discuss.ui.xxt']);
    ngApp.config(['$locationProvider', function($lp) {
        $lp.html5Mode(true);
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$location', '$uibModal', 'http2', function($scope, $location, $uibModal, http2) {
        var template, templateId;
        $scope.templateId = templateId = $location.search().template;
        $scope.contribute = function() {
            var url;
            url = '/rest/pl/fe/template/pushHome?template=' + templateId;
            http2.get(url, function(rsp) {});
        };
        $scope.favorTemplate = function() {
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
        $scope.useTemplate = function() {
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
        http2.get('/rest/pl/fe/user/auth/isLogin', function(rsp) {
            $scope.isLogin = rsp.data;
        });
        http2.get('/rest/site/fe/matter/template/get?template=' + $scope.templateId, function(rsp) {
            $scope.template = template = rsp.data;
        });
    }]);
    ngApp.controller('ctrlPreview', ['$scope', 'http2', function($scope, http2) {
        var previewURL,
            params = {
                pageAt: -1,
                hasPrev: false,
                hasNext: false,
                openAt: 'ontime'
            };
        $scope.nextPage = function() {
            params.pageAt++;
            params.hasPrev = true;
            params.hasNext = params.pageAt < $scope.app.pages.length - 1;
        };
        $scope.prevPage = function() {
            params.pageAt--;
            params.hasNext = true;
            params.hasPrev = params.pageAt > 0;
        };
        $scope.$watch('template', function(template) {
            if (template === undefined) return;
            if (!previewURL) {
                $scope.previewURL = previewURL = '/rest/site/fe/matter/enroll/preview?site=' + template.siteid + '&app=' + template.matter_id + '&start=Y';
            }
            http2.get('/rest/site/fe/matter/enroll/get?app=' + template.matter_id + '&site=' + template.siteid + '&cascaded=Y', function(rsp) {
                $scope.app = rsp.data.app;
                params.pageAt = 0;
                params.hasPrev = false;
                $scope.params = params;
                params.hasNext = !!$scope.app.pages.length;
            });
        });
        $scope.$watch('params', function(params) {
            if (params) {
                $scope.previewURL = previewURL + '&openAt=' + params.openAt + '&page=' + $scope.app.pages[params.pageAt].name;
            }
        }, true);
    }]);

    /*bootstrap*/
    angular._lazyLoadModule('tmpl');

    return ngApp;
});
