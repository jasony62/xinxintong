define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlSetting', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
        $scope.url = location.protocol + '//' + location.host + '/rest/site/sns/qy/api?site=' + $scope.siteId;
        $scope.update = function(name) {
            var p = {};
            p[name] = $scope.qy[name];
            http2.post('/rest/pl/fe/site/sns/qy/update?site=' + $scope.siteId, p).then(function(rsp) {
                if (name === 'token') {
                    $scope.qy.joined = 'N';
                }
            });
        };
        $scope.setQrcode = function() {
            var options = {
                callback: function(url) {
                    $scope.qy.qrcode = url + '?_=' + (new Date() * 1);
                    $scope.update('qrcode');
                }
            };
            mediagallery.open($scope.siteId, options);
        };
        $scope.removeQrcode = function() {
            $scope.qy.qrcode = '';
            $scope.update('qrcode');
        };
        $scope.checkJoin = function() {
            http2.get('/rest/pl/fe/site/sns/qy/checkJoin?site=' + $scope.siteId).then(function(rsp) {
                if (rsp.data === 'Y') {
                    $scope.qy.joined = 'Y';
                }
            });
        };
        $scope.reset = function() {
            $scope.qy.joined = 'N';
            $scope.update('joined');
        };
    }]);
});