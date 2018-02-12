define(["angular"], function(angular) {
    'use strict';
    var siteId, sns, sceneid, matter, ngApp;
    siteId = location.search.match(/site=([^&]*)/)[1];
    sns = location.search.match(/sns=([^&]*)/)[1];
    sceneid = location.search.match(/sceneid=([^&]*)/) ? location.search.match(/sceneid=([^&]*)/)[1] : '';
    matter = location.search.match(/matter=([^&]*)/) ? location.search.match(/matter=([^&]*)/)[1] : '';
    ngApp = angular.module('app', ['page.ui.xxt', 'http.ui.xxt']);
    ngApp.config(['$controllerProvider', function($cp) {
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrlMain', ['$scope', 'tmsDynaPage', 'http2', function($scope, tmsDynaPage, http2) {
        var url, params;
        url = '/rest/site/fe/user/follow/pageGet?site=' + siteId + '&sns=' + sns;
        if (sceneid) {
            url += '&sceneid=' + sceneid;
        } else if (matter) {
            url += '&matter=' + matter;
        }
        $scope.userAgent = {};
        if (/MicroMessenger/i.test(navigator.userAgent)) {
            $scope.userAgent.wx = true;
        } else if (/YiXin/i.test(navigator.userAgent)) {
            $scope.userAgent.yx = true;
        }
        http2.get(url).then(function(rsp) {
            params = rsp.data;
            $scope.snsConfig = params.snsConfig;
            $scope.user = params.user;
            $scope.site = params.site;
            $scope.matter = params.matter;
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
            tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
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