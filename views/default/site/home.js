define(["angular", "xxt-page"], function(angular, codeAssembler) {
    'use strict';
    var ngApp = angular.module('home', ['ui.bootstrap', 'ui.tms', 'discuss.ui.xxt']);
    ngApp.config(['$controllerProvider', function($cp) {
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
        var ls = location.search,
            siteId = ls.match(/site=([^&]*)/)[1];

        $scope.subscribe = function() {
            if (!$scope.siteUser || !$scope.siteUser.loginExpire) {
                if (window.sessionStorage) {
                    var method = JSON.stringify({
                        name: 'subscribe',
                        args: [$scope.site]
                    });
                    window.sessionStorage.setItem('xxt.site.home.auth.pending', method);
                }
                location.href = '/rest/site/fe/user/login?site=' + siteId;
            } else {
                var url = '/rest/site/fe/user/site/subscribe?site=' + siteId + '&target=' + siteId;
                http2.get(url, function(rsp) {
                    $scope.site._subscribed = 'Y';
                });
            }
        };
        $scope.unsubscribe = function() {
            var url = '/rest/site/fe/user/site/unsubscribe?site=' + siteId + '&target=' + siteId;
            http2.get(url, function(rsp) {
                $scope.site._subscribed = 'N';
            });
        };
        http2.get('/rest/site/home/get?site=' + siteId, function(rsp) {
            http2.get('/rest/site/fe/user/get?site=' + siteId, function(rsp) {
                $scope.siteUser = rsp.data;
                if ($scope.siteUser.loginExpire) {
                    if (window.sessionStorage) {
                        var pendingMethod;
                        if (pendingMethod = window.sessionStorage.getItem('xxt.site.home.auth.pending')) {
                            window.sessionStorage.removeItem('xxt.site.home.auth.pending');
                            pendingMethod = JSON.parse(pendingMethod);
                            $scope[pendingMethod.name].apply($scope, pendingMethod.args);
                        }
                    }
                }
            });
            codeAssembler.loadCode(ngApp, rsp.data.home_page).then(function() {
                $scope.site = rsp.data;
                $scope.page = rsp.data.home_page;
            });
        });
    }]);

    /*bootstrap*/
    angular._lazyLoadModule('home');

    return ngApp;
});
