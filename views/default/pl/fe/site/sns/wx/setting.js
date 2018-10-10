define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlSetting', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
        $scope.url = location.protocol + '//' + location.host + '/rest/site/sns/wx/api?site=' + $scope.siteId;
        $scope.update = function(name) {
            var p = {};
            p[name] = $scope.wx[name];
            http2.post('/rest/pl/fe/site/sns/wx/update?site=' + $scope.siteId, p).then(function(rsp) {
                if (name === 'token') {
                    $scope.wx.joined = 'N';
                }
            });
        };
        $scope.setQrcode = function() {
            var options = {
                callback: function(url) {
                    $scope.wx.qrcode = url + '?_=' + (new Date()) * 1;
                    $scope.update('qrcode');
                }
            };
            mediagallery.open($scope.siteId, options);
        };
        $scope.removeQrcode = function() {
            $scope.wx.qrcode = '';
            $scope.update('qrcode');
        };
        $scope.checkJoin = function() {
            http2.get('/rest/pl/fe/site/sns/wx/checkJoin?site=' + $scope.siteId).then(function(rsp) {
                if (rsp.data === 'Y') {
                    $scope.wx.joined = 'Y';
                }
            });
        };
        $scope.reset = function() {
            $scope.wx.joined = 'N';
            $scope.update('joined');
        };
    }]);
});