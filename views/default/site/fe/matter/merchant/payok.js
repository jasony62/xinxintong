define(["require", "angular", "base", "cookie", "directive"], function(require, angular, ngApp, Cookies) {
    'use strict';
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
        var ls;
        ls = location.search;
        $scope.siteId = ls.match(/site=([^&]*)/)[1];
        $scope.shopId = ls.match(/shop=([^&]*)/)[1];
        $scope.errmsg = '';
        $scope.ready = false;
        $http.get('/rest/site/fe/matter/merchant/payok/pageGet?site=' + $scope.siteId + '&shop=' + $scope.shopId).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            var params;
            params = rsp.data;
            $scope.User = params.user;
            loadCss("/views/default/app/merchant/payok.css");
            window.setPage($scope, params.page);
            $timeout(function() {
                $scope.$broadcast('xxt.app.merchant.ready');
            });
        });
        $scope.closeWindow = function() {
            if (/MicroMessenger/i.test(navigator.userAgent)) {
                window.wx.closeWindow();
            } else if (/YiXin/i.test(navigator.userAgent)) {
                window.YixinJSBridge.call('closeWebView');
            }
        };
        $scope.gotoOrderlist = function() {
            location.href = '/rest/site/fe/matter/merchant/orderlist?site=' + $scope.siteId + '&shop=' + $scope.shopId;
        };
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});