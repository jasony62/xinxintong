define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecommend', ['$scope', function($scope) {
        $scope.criteria = {
            category: 'article'
        };
    }]);
    ngApp.provider.controller('ctrlTemplate', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.page = {
            at: 1,
            size: 8,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.searchTemplate = function() {
            var url = '/rest/pl/fe/template/platform/list?matterType=enroll' + $scope.page.j();
            http2.get(url).then(function(rsp) {
                $scope.templates = rsp.data.templates;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.preview = function(template) {
            $uibModal.open({
                templateUrl: 'previewTemplate.html',
                controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        $mi.close($scope.data);
                    };
                }],
                backdrop: 'static'
            }).result.then(function(data) {
                var url = '/rest/pl/be/home/recommend/pushTemplate?template=' + template.id;
                http2.post(url, {}).then(function(rsp) {
                    template.push_home = 'Y';
                });
            });
        };
        $scope.pullHome = function(template) {
            var url = '/rest/pl/be/home/recommend/pullTemplate?template=' + template.id;
            http2.post(url, {}).then(function(rsp) {
                template.push_home = 'N';
            });
        };
        $scope.searchTemplate();
    }]);
    ngApp.provider.controller('ctrlMatter', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.page = {
            at: 1,
            size: 10,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.channelAddr = {
            c: '中间',
            r: '右侧'
        }
        $scope.searchMatter = function() {
            var url = '/rest/pl/be/home/recommend/listMatter?category=' + $scope.criteria.category + $scope.page.j();
            http2.get(url).then(function(rsp) {
                $scope.matters = rsp.data.matters;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.preview = function(oMatter) {
            $uibModal.open({
                templateUrl: 'previewMatter.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.matter = angular.copy(oMatter);
                    $scope2.filter = $scope.criteria;
                    $scope2.toMiddle = function() {
                        $mi.close({ 'home_group': 'c' });
                    }
                    $scope2.toRight = function() {
                        $mi.close({ 'home_group': 'r' });
                    }
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.pushHome = function() {
                        $mi.close();
                    };
                    $scope2.asGlobal = function() {
                        var url = '/rest/pl/be/home/recommend/asGlobal?application=' + oMatter.id;
                        http2.post(url, {}).then(function(rsp) {
                            oMatter.as_global = 'Y';
                            $mi.dismiss();
                        });
                    };
                }],
                backdrop: 'static'
            }).result.then(function(data) {
                var url = '/rest/pl/be/home/recommend/pushMatter?application=' + oMatter.id;
                if ($scope.criteria.category == 'channel') {
                    url += '&homeGroup=' + data.home_group;
                }
                http2.post(url, {}).then(function(rsp) {
                    oMatter.approved = 'Y';
                    if ($scope.criteria.category == 'channel') {
                        oMatter.home_group = data.home_group;
                    }
                });
            });
        };
        $scope.pullHome = function(oMatter) {
            var url = '/rest/pl/be/home/recommend/pullMatter?application=' + oMatter.id;
            http2.post(url, {}).then(function(rsp) {
                oMatter.approved = 'N';
            });
        };
        $scope.carryHome = function(oMatter) {
            var url = '/rest/pl/be/home/recommend/pushMatterTop?application=' + oMatter.id;
            http2.post(url, {}).then(function(rsp) {
                oMatter.weight = '1';
            });
        };
        $scope.cancelTop = function(oMatter) {
            var url = '/rest/pl/be/home/recommend/pullMatterTop?application=' + oMatter.id;
            http2.post(url, {}).then(function(rsp) {
                oMatter.weight = '0';
            });
        };
        $scope.cancelGlobal = function(oMatter) {
            var url = '/rest/pl/be/home/recommend/canelGlobal?application=' + oMatter.id;
            http2.post(url, {}).then(function(rsp) {
                oMatter.as_global = 'N';
            });
        };
        $scope.searchMatter();
    }]);
    ngApp.provider.controller('ctrlSite', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.page = {
            at: 1,
            size: 8,
            j: function() {
                return '?page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.searchApplication = function() {
            var url = '/rest/pl/be/home/recommend/listSite' + $scope.page.j();
            http2.get(url).then(function(rsp) {
                $scope.sites = rsp.data.sites;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.open = function(application) {
            $uibModal.open({
                templateUrl: 'previewMatter.html',
                controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        $mi.close($scope.data);
                    };
                }],
                backdrop: 'static'
            }).result.then(function(data) {
                var url = '/rest/pl/be/home/recommend/pushSite?application=' + application.id;
                http2.post(url, {}).then(function(rsp) {
                    application.approved = 'Y';
                });
            });
        };
        $scope.pullHome = function(application) {
            var url = '/rest/pl/be/home/recommend/pullSite?application=' + application.id;
            http2.post(url, {}).then(function(rsp) {
                application.approved = 'N';
            });
        };
        $scope.searchApplication();
    }]);
});