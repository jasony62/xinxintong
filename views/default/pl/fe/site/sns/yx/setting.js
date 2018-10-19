define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlSetting', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
        $scope.url = location.protocol + '//' + location.host + '/rest/site/sns/yx/api?site=' + $scope.siteId;
        $scope.update = function(name) {
            var p = {};
            p[name] = $scope.yx[name];
            http2.post('/rest/pl/fe/site/sns/yx/update?site=' + $scope.siteId, p).then(function(rsp) {
                if (name === 'token') {
                    $scope.yx.joined = 'N';
                }
            });
        };
        $scope.setQrcode = function() {
            var options = {
                callback: function(url) {
                    $scope.yx.qrcode = url + '?_=' + (new Date()) * 1;
                    $scope.update('qrcode');
                }
            };
            mediagallery.open($scope.siteId, options);
        };
        $scope.removeQrcode = function() {
            $scope.yx.qrcode = '';
            $scope.update('qrcode');
        };
        $scope.checkJoin = function() {
            http2.get('/rest/pl/fe/site/sns/yx/checkJoin?site=' + $scope.siteId).then(function(rsp) {
                if (rsp.data === 'Y') {
                    $scope.yx.joined = 'Y';
                }
            });
        };
        $scope.reset = function() {
            $scope.yx.joined = 'N';
            $scope.update('joined');
        };
    }]);
});