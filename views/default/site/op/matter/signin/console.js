'use strict';
define(["require", "angular", "util.site"], function(require, angular) {
    var ngApp = angular.module('app', ['util.site.tms']);
    ngApp.config(['$controllerProvider', function($cp) {
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', 'PageLoader', 'PageUrl', function($scope, $http, $timeout, PageLoader, PageUrl) {
        var PU, signinURL;
        PU = PageUrl.ins('/rest/site/op/matter/signin', ['site', 'app']);
        $scope.getRecords = function() {
            $http.get(PU.j('record/list', 'site', 'app')).success(function(rsp) {
                if (rsp.err_code !== 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                $scope.records = rsp.data.records;
            });
        };
        $scope.signinURL = 'http://' + location.host + '/rest/site/fe/matter/signin?site=' + PU.params.site + '&app=' + PU.params.app;
        $scope.signinQrcode = '/rest/pl/fe/matter/signin/qrcode?url=' + encodeURIComponent($scope.signinURL);
        $scope.value2Label = function(val, key) {
            var i, j, s, aVal, aLab = [],
                schemas = $scope.data_schemas;
            if (val === undefined) return '';
            for (i = 0, j = schemas.length; i < j; i++) {
                s = schemas[i];
                if (schemas[i].id === key) {
                    s = schemas[i];
                    break;
                }
            }
            if (s && s.ops && s.ops.length) {
                aVal = val.split(',');
                for (i = 0, j = s.ops.length; i < j; i++) {
                    aVal.indexOf(s.ops[i].v) !== -1 && aLab.push(s.ops[i].label);
                }
                if (aLab.length) return aLab.join(',');
            }
            return val;
        };
        $http.get(PU.j('get', 'site', 'app')).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            $scope.app = rsp.data.app;
            $scope.data_schemas = JSON.parse(rsp.data.app.data_schemas);
            PageLoader.render($scope, rsp.data.page).then(function() {
                $scope.Page = rsp.data.page;
            })
            $timeout(function() {
                $scope.$broadcast('xxt.app.signin.ready');
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