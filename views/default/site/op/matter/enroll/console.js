'use strict';
define(["require", "angular", "util.site"], function(require, angular) {
    var ngApp = angular.module('app', ['util.site.tms']);
    ngApp.config(['$controllerProvider', function($cp) {
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', 'PageLoader', 'PageUrl', function($scope, $http, $timeout, PageLoader, PageUrl) {
        var PU, criteria = {
            join: function() {
                var params = '';
                if (this.verified && this.verified.length) {
                    params += '&verified=' + this.verified;
                }
                return params;
            }
        };

        PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app']);

        $scope.criteria = criteria;
        $scope.getRecords = function() {
            var url = PU.j('record/list', 'site', 'app');

            url += criteria.join();
            $http.post(url, $scope.criteria).success(function(rsp) {
                if (rsp.err_code !== 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                $scope.records = rsp.data.records;
            });
        };
        var schemasById = {};
        $scope.value2String = function(record, schemaId) {
            var schema, val;
            if (undefined !== (schema = schemasById[schemaId])) {
                if (schema.type === 'member' && record.data.member) {
                    val = record.data.member[schema.id.substr(7)] || '';
                } else {
                    val = record.data[schema.id] || '';
                    if (schema.ops && schema.ops.length) {
                        if (schema.type === 'score' && angular.isObject(val)) {
                            var label = '';
                            schema.ops.forEach(function(op, index) {
                                label += op.l + ':' + (val[op.v] ? val[op.v] : 0) + ' / ';
                            });
                            val = label.replace(/\s\/\s$/, '');
                        } else if (val.length) {
                            var aVal, aLab = [];
                            aVal = val.split(',');
                            schema.ops.forEach(function(op) {
                                aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                            });
                            if (aLab.length) val = aLab.join(',');
                        }
                    }
                }
            }

            return val;
        };
        $scope.scoreRangeArray = function(schema) {
            var arr = [];
            if (schema.range && schema.range.length === 2) {
                for (var i = schema.range[0]; i <= schema.range[1]; i++) {
                    arr.push('' + i);
                }
            }
            return arr;
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
                $scope.app.dataSchemas.forEach(function(schema) {
                    schemasById[schema.id] = schema;
                });
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