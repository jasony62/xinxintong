'use strict';
define(["require", "angular", "util.site"], function(require, angular) {
    var app = angular.module('app', ['util.site.tms']);
    app.config(['$controllerProvider', function($cp) {
        app.provider = {
            controller: $cp.register
        };
    }]);
    app.controller('ctrl', ['$scope', '$http', '$timeout', 'PageLoader', 'PageUrl', function($scope, $http, $timeout, PageLoader, PageUrl) {
        var PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app']);
        $scope.getRecords = function() {
            $http.get(PU.j('record/list', 'site', 'app')).success(function(rsp) {
                if (rsp.err_code !== 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                $scope.records = rsp.data.records;
            });
        };
        $scope.entryURL = 'http://' + location.host + '/rest/site/fe/matter/enroll?site=' + PU.params.site + '&app=' + PU.params.app;
        $scope.entryQrcode = '/rest/pl/fe/matter/enroll/qrcode?url=' + encodeURIComponent($scope.entryURL);
        $http.get(PU.j('pageGet', 'site', 'app')).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            PageLoader.render($scope, rsp.data).then(function() {
                $scope.Page = rsp.data;
            })
            $timeout(function() {
                $scope.$broadcast('xxt.app.enroll.ready');
            });
            $scope.getRecords();
            window.loading.finish();
        }).error(function(content, httpCode) {
            $scope.errmsg = content;
        });
    }]);
    app.directive('dynamicHtml', function($compile) {
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