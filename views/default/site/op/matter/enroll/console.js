'use strict';
define(["require", "angular", "util.site"], function(require, angular) {
    var ngApp = angular.module('app', ['util.site.tms']);
    ngApp.config(['$controllerProvider', function($cp) {
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', 'PageLoader', 'PageUrl', function($scope, $http, $timeout, PageLoader, PageUrl) {
        var PU;
        PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app']);
        $scope.getRecords = function() {
            $http.get(PU.j('record/list', 'site', 'app')).success(function(rsp) {
                if (rsp.err_code !== 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                $scope.records = rsp.data.records;
            });
        };
        $scope.value2Label = function(val, key) {
            var i, schema;
            if (val === undefined) return '';
            for (i = $scope.app.dataSchemas.length - 1; i >= 0; i--) {
                if ($scope.app.dataSchemas[i].id === key) {
                    schema = $scope.app.dataSchemas[i];
                    break;
                }
            }
            if (schema && schema.ops && schema.ops.length) {
                (function() {
                    var i, aVal, aLab = [];
                    aVal = val.split(',');
                    for (i = schema.ops.length - 1; i >= 0; i--) {
                        aVal.indexOf(schema.ops[i].v) !== -1 && aLab.push(schema.ops[i].l);
                    }
                    aLab.length && (val = aLab.join(','));
                })();
            }
            return val;
        };
        $http.get(PU.j('get', 'site', 'app')).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            $scope.app = rsp.data.app;
            PageLoader.render($scope, rsp.data.page, ngApp).then(function() {
                $scope.Page = rsp.data.page;
            });
            if ($scope.app.data_schemas && $scope.app.data_schemas.length) {
                $scope.app.dataSchemas = JSON.parse($scope.app.data_schemas);
            }
            $timeout(function() {
                $scope.$broadcast('xxt.app.enroll.ready');
            });
            $scope.getRecords();
            window.loading.finish();
        }).error(function(content, httpCode) {
            $scope.errmsg = content;
        });
    }]);
    ngApp.directive('dynamicHtml', function($compile) {
        return {
            restrict: 'EA',
            replace: true,
            link: function(scope, ele, attrs) {
                scope.$watch(attrs.dynamicHtml, function(html) {
                    if (html && html.length) {
                        ele.html(html);
                        $compile(ele.contents())(scope);
                    }
                });
            }
        };
    });
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});