define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlInvoke', ['$scope', '$location', 'http2', 'noticebox', '$uibModal', function($scope, $location, http2, noticebox, $uibModal) {
        $scope.siteId = $location.search().site;
        $scope.flag = true;
        var secret;
        function dealSecet(password) {
            secret = password;
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
        $scope.creatSecret = function() {
            http2.get('/rest/pl/fe/site/invoke?creatSecret=' + $scope.siteId, function(rsp) {
                dealSecet(rsp.data.secret);
            });
        };
        $scope.showSecret = function() {
            $scope.invoke.secret = secret;
            $scope.flag = false;
        };
        $scope.restSecret = function() {
            http2.get('/rest/pl/fe/site/invoke?restSecret=' + $scope.siteId, function(rsp) {
                dealSecet(rsp.data.secret);
                noticebox.success('完成修改');
            });
        };
        $scope.doIP = function(ips) {
            $uibModal.open({
                templateUrl: 'ip.html',
                dropback: 'static',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    if(ips) {$scope2.ips = ips;}
                    $scope2.ok = function() {
                        var ips = [];
                        var isTrue = $scope2.ips.split(',').every(function(ip){
                            if(!dealIP(ip)) $scope2.tip = '当前的输入含有不合法的IP地址：' + ip;
                            return dealIP(ip);
                        });
                        isTrue && $mi.close($scope2.ips);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                }]
            }).result.then(function(ips) {
                console.log(ips);
            });
        };
        $scope.$watch('site', function(nv) {
            if(!nv) return;
            http2.get('/rest/pl/fe/site/invoke/get?site=' + nv.id, function(rsp) {
                $scope.invoke = rsp.data;
                dealSecet(rsp.data.secret);
            });
        });
    }]);
});