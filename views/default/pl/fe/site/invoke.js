define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlInvoke', ['$scope', '$location', 'http2', 'noticebox', '$uibModal', function($scope, $location, http2, noticebox, $uibModal) {
        $scope.siteId = $location.search().site;
        var secret;

        function dealSecet(password) {
            secret = password;
            $scope.flag = true;
            $scope.invoke.secret = password.substr(0, 4) + '****' + password.substr(28);
        };

        function dealIP(value) {
            var valid = /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(value);
            if (!valid) {
                return false;
            }
            return value.split('.').every(function(num) {
                if (num.length > 1 && num.charAt(0) === '0') {
                    return false;
                } else if (parseInt(num, 10) > 255) {
                    return false;
                }
                return true;
            });
        };
        $scope.createSecret = function() {
            http2.get('/rest/pl/fe/site/invoke/createSecret?site=' + $scope.siteId).then(function(rsp) {
                $scope.invoke.secret = rsp.data.secret;
            });
        };
        $scope.showSecret = function() {
            $scope.invoke.secret = secret;
            $scope.flag = false;
        };
        $scope.resetSecret = function() {
            var msg = "你确定要重置调用凭证(SiteSecret)吗？\r\r请注意：重置SiteSecret立即生效，所有使用旧SiteSecret的接口将立即失效。为确保调用的正常使用，请尽快更新SiteSecret信息";
            if (confirm(msg) == true) {
                http2.get('/rest/pl/fe/site/invoke/resetSecret?site=' + $scope.siteId).then(function(rsp) {
                    $scope.invoke.secret = rsp.data.secret;
                    $scope.flag = false;
                    noticebox.success('重置成功');
                });
            } else {
                return false;
            }
        };
        $scope.doIP = function(ips) {
            $uibModal.open({
                templateUrl: 'ip.html',
                dropback: 'static',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    if (ips) { $scope2.ips = ips.toString(); }
                    $scope2.ok = function() {
                        var ips = [];
                        var isTrue = $scope2.ips.split(',').every(function(ip) {
                            if (!dealIP(ip)) $scope2.tip = '当前的输入含有不合法的IP地址：' + ip;
                            return dealIP(ip);
                        });
                        isTrue && $mi.close($scope2.ips.split(','));
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                }]
            }).result.then(function(ips) {
                http2.post('/rest/pl/fe/site/invoke/update?site=' + $scope.siteId, { invokerIp: ips }).then(function(rsp) {
                    $scope.invoke.invokerIps = ips;
                    noticebox.success('保存成功');
                });
            });
        };
        $scope.$watch('site', function(nv) {
            if (!nv) return;
            http2.get('/rest/pl/fe/site/invoke/get?site=' + nv.id).then(function(rsp) {
                $scope.invoke = rsp.data;
                rsp.data.secret && dealSecet(rsp.data.secret);
            });
        });
    }]);
});