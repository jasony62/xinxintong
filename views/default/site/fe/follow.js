define(["angular", "xxt-page"], function(angular, uiPage) {
    'use strict';
    var siteId, sns, matter, ngApp;
    siteId = location.search.match(/site=([^&]*)/)[1];
    sns = location.search.match(/sns=([^&]*)/)[1];
    matter = location.search.match(/matter=([^&]*)/) ? location.search.match(/matter=([^&]*)/)[1] : '';
    ngApp = angular.module('follow', []);
    ngApp.config(['$controllerProvider', function($cp) {
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$http', function($scope, $http) {
        $scope.errmsg = '';
        $http.get('/rest/site/fe/followPageGet?site=' + siteId + '&sns=' + sns + '&matter=' + matter).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            var params = rsp.data;
            uiPage.loadCode(ngApp, params.page).then(function() {
                $scope.page = params.page;
                if (params.matterQrcode && params.matterQrcode.pic) {
                    $scope.qrcode = params.matterQrcode.pic;
                } else {
                    $scope.qrcode = params.snsConfig.qrcode;
                }
                window.loading.finish();
            });
        }).error(function(content, httpCode) {});
    }]);
    /* bootstrap angular app */
    angular._lazyLoadModule('follow');

    return ngApp;
});
