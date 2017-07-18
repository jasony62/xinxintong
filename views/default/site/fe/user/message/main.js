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
        var page, filter;
        $scope.page = page = {
            at: 1,
            size: 10,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.filter = filter = {
            type: 'all'
        };
        $scope.doSearch = function() {
            var url;
            if(filter.type=='all') {
                url = '/rest/site/fe/user/message/list?' + page.j();
                http2.get(url, function(rsp) {
                    $scope.notice = rsp.data;
                });
            }else {
                srvUserNotice.uncloseList().then(function(result) {
                    $scope.notice = result;
                    $scope.page.total = result.page.total;
                });
            }
        };
        $scope.closeNotice = function(log) {
            srvUserNotice.closeNotice(log).then(function(rsp) {
                $scope.notice.logs.splice($scope.notice.logs.indexOf(log), 1);
                $scope.notice.page.total--;
            });
        };
        http2.get('/rest/site/fe/get?site=' + siteId, function(rsp) {
            $scope.site = rsp.data;
            window.loading.finish();
        });
        $scope.$watch('filter.type', function(nv) {
            if(!nv) return;
            $scope.doSearch();
        });
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});
