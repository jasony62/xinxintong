'use strict';
define(['main'], function(ngApp) {
    ngApp.provider.controller('ctrlSetting', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
        $scope.update = function(name) {
            var p = {};
            p[name] = $scope.wx[name];
            http2.post('/rest/pl/be/sns/wx/update?site=' + $scope.wx.plid, p).then(function(rsp) {
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
            mediagallery.open($scope.wx.plid, options);
        };
        $scope.removeQrcode = function() {
            $scope.wx.qrcode = '';
            $scope.update('qrcode');
        };
        $scope.checkJoin = function() {
            http2.get('/rest/pl/be/sns/wx/checkJoin?site=' + $scope.wx.plid).then(function(rsp) {
                if (rsp.data === 'Y') {
                    $scope.wx.joined = 'Y';
                }
            });
        };
        $scope.reset = function() {
            $scope.wx.joined = 'N';
            $scope.update('joined');
        };
        $scope.$watch('wx', function(wx) {
            if (!wx) return;
            $scope.url = location.protocol + '//' + location.host + '/rest/site/sns/wx/api?site=platform';
        });
        $scope.syncUsers = function() {
            $scope.$root.progmsg = '开始更新';
            $scope.backRunning = true
            var finish = 0;
            var doRefresh = function(step, nextOpenid) {
                var url, params;
                url = '/rest/pl/be/sns/wx/user/syncUsers?site=platform';
                params = [];
                step && params.push('step=' + step);
                nextOpenid && params.push('nextOpenid=' + nextOpenid);
                params.length && (url += '?' + params.join('&'));
                http2.get(url).then(function(rsp) {
                    if (angular.isObject(rsp) && rsp.err_code === 0) {
                        if (rsp.data.left > 0) {
                            doRefresh(rsp.data.step, rsp.data.nextOpenid);
                        } else if (rsp.data.nextOpenid) {
                            doRefresh(0, rsp.data.nextOpenid);
                        } else {
                            $scope.backRunning = false;
                        }
                        finish += rsp.data.finish;
                        $scope.$root.progmsg = '更新数量：' + finish + '/' + rsp.data.total;
                    }
                }, {
                    autoBreak: false
                });
            };
            doRefresh(0);
        };
        $scope.syncGroups = function() {
            $scope.backRunning = true;
            http2.get('/rest/pl/be/sns/wx/user/syncGroups?site=platform').then(function(rsp) {
                $scope.backRunning = false;
                $scope.$root.progmsg = '更新用户分组数量：' + rsp.data;
            });
        };
    }]);
});