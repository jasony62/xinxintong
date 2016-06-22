'use strict';
define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlPage', ['$scope', '$location', 'http2', '$uibModal', '$timeout', '$q', function($scope, $location, http2, $uibModal, $timeout, $q) {
        $scope.updPage = function(page, names) {
            var defer = $q.defer(),
                url, p = {};
            angular.isString(names) && (names = [names]);
            $scope.$root.progmsg = '正在保存页面...';
            angular.forEach(names, function(name) {
                p[name] = name === 'html' ? encodeURIComponent(page[name]) : page[name];
            });
            url = '/rest/pl/fe/matter/enroll/page/update';
            url += '?site=' + $scope.siteId;
            url += '&app=' + $scope.id;
            url += '&pid=' + page.id;
            url += '&cname=' + page.code_name;
            http2.post(url, p, function(rsp) {
                page.$$modified = false;
                $scope.$root.progmsg = '';
                defer.resolve();
            });
            return defer.promise;
        };
        $scope.empty = function() {
            $scope.ep.html = '';
            $scope.ep.data_schemas = [];
            $scope.ep.act_schemas = [];
            $scope.updPage($scope.ep, ['html', 'data_schemas', 'act_schemas']);
        };
        $scope.delPage = function() {
            if (window.confirm('确定删除？')) {
                var url = '/rest/pl/fe/matter/enroll/page/remove';
                url += '?site=' + $scope.siteId;
                url += '&app=' + $scope.id;
                url += '&pid=' + $scope.ep.id;
                http2.get(url, function(rsp) {
                    $scope.app.pages.splice($scope.app.pages.indexOf($scope.ep), 1);
                    history.back();
                });
            }
        };
        window.onbeforeunload = function(e) {
            var i, p, message, modified;
            modified = false;
            for (i in $scope.app.pages) {
                p = $scope.app.pages[i];
                if (p.$$modified) {
                    modified = true;
                    break;
                }
            }
            if (modified) {
                message = '已经修改的页面还没有保存',
                    e = e || window.event;
                if (e) {
                    e.returnValue = message;
                }
                return message;
            }
        };
        $scope.$watch('app.pages', function(pages) {
            var current = $location.search().page,
                dataSchemas, others = [];
            if (!pages || pages.length === 0) return;
            angular.forEach(pages, function(p) {
                if (p.name === current) {
                    $scope.ep = p;
                }
            });
        });
    }]);
    ngApp.provider.controller('ctrlPageSchema', ['$scope', function($scope) {}]);
    ngApp.provider.controller('ctrlInputSchema', ['$scope', function($scope) {}]);
    ngApp.provider.controller('ctrlViewSchema', ['$scope', function($scope) {
        $scope.$watch('ep', function(ep) {
            if (!ep) return;
            $scope.dataSchemas = ep.data_schemas;
            $scope.actSchemas = ep.act_schemas;
        });
    }]);
});