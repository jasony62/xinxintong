define(["angular"], function(angular) {
    'use strict';
    var siteId, sns, matter, ngApp;
    siteId = location.search.match(/site=([^&]*)/)[1];
    sns = location.search.match(/sns=([^&]*)/)[1];
    matter = location.search.match(/matter=([^&]*)/) ? location.search.match(/matter=([^&]*)/)[1] : '';
    ngApp = angular.module('app', ['page.ui.xxt', 'http.ui.xxt']);
    ngApp.config(['$controllerProvider', function($cp) {
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrlMain', ['$scope', 'tmsDynaPage', 'http2', function($scope, tmsDynaPage, http2) {
        var params;
        http2.get('/rest/site/fe/user/follow/pageGet?site=' + siteId + '&sns=' + sns + '&matter=' + matter).then(function(rsp) {
            params = rsp.data;
            $scope.snsConfig = params.snsConfig;
            $scope.user = params.user;
            tmsDynaPage.loadCode(ngApp, params.page).then(function() {
                $scope.page = params.page;
                if (params.matterQrcode && params.matterQrcode.pic) {
                    $scope.qrcode = params.matterQrcode.pic;
                } else {
                    $scope.qrcode = params.snsConfig.qrcode;
                }
                window.loading.finish();
            });
        });
        $scope.gotoLogin = function() {
            tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/login?site=' + siteId).then(function(data) {
                if (params.referer) {
                    location.href = params.referer;
                } else {
                    location.href = '/rest/site/fe/user?site=' + siteId;
                }
            });
        };
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });

    return ngApp;
});
