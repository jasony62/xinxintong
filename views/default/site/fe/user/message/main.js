define(['require', 'angular'], function(require, angular) {
    'use strict';
    var siteId, ngApp;
    siteId = location.search.match('site=(.*)')[1];
    ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap', 'ui.tms', 'service.matter']);
    ngApp.config(['$controllerProvider', '$provide', '$compileProvider', function($controllerProvider, $provide, $compileProvider) {
        ngApp.provider = {
            controller: $controllerProvider.register,
            directive: $compileProvider.directive,
            service: $provide.service
        };
    }]);
    ngApp.controller('ctrlMain', ['$scope', 'http2', 'srvUserNotice', function($scope, http2, srvUserNotice) {
        $scope.closeNotice = function(log) {
            srvUserNotice.closeNotice(log).then(function(rsp) {
                $scope.notice.logs.splice($scope.notice.logs.indexOf(log), 1);
                $scope.notice.page.total--;
            });
        };
        srvUserNotice.uncloseList().then(function(result) {
            $scope.notice = result;
        });
        http2.get('/rest/site/fe/get?site=' + siteId, function(rsp) {
            $scope.site = rsp.data;
            window.loading.finish();
        });
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});
