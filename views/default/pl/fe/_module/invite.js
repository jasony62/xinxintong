define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlInvite', ['$scope', '$q', '$uibModal', 'srvInvite', 'http2', function($scope, $q, $uibModal, srvInvite, http2) {
        var _oInvite, _oLogPage, _oRelayPage;
        $scope.logPage = _oLogPage = {
            at: 1,
            size: 30,
            join: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.relayPage = _oRelayPage = {
            at: 1,
            size: 30,
            join: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.makeInvite = function() {
            srvInvite.make().then(function(oInvite) {
                $scope.invite = _oInvite = oInvite;
            });
        };
        $scope.closeInvite = function() {
            http2.get('/rest/pl/fe/invite/close?invite=' + _oInvite.id).then(function(rsp) {
                _oInvite.state = '0';
            });
        };
        $scope.openInvite = function() {
            http2.get('/rest/pl/fe/invite/open?invite=' + _oInvite.id).then(function(rsp) {
                _oInvite.state = '1';
            });
        };
        $scope.addCode = function() {
            srvInvite.addCode(_oInvite).then(function(oCode) {
                $scope.codes === undefined && ($scope.codes = []);
                $scope.codes.splice(0, 0, oCode);
            });
        };
        $scope.update = function(prop) {
            var posted;
            posted = {};
            posted[prop] = _oInvite[prop];
            http2.post('/rest/pl/fe/invite/update?invite=' + _oInvite.id, posted);
        };
        $scope.configCode = function(oCode) {
            $uibModal.open({
                templateUrl: 'codeEditor.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                    $scope.code = {};
                    ['stop', 'expire_at', 'max_count', 'remark'].forEach(function(prop) {
                        if (prop == 'expire_at') {
                            if (oCode[prop] == '0') {
                                $scope.isDate = 'N';
                            } else {
                                $scope.isDate = 'Y';
                                $scope.code['expire_at'] = oCode['expire_at'];
                            }
                        } else {
                            $scope.code[prop] = oCode[prop]
                        }
                    });
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        var regx = /^[0-9]\d*$/;
                        if ($scope.code.max_count === '' || (!regx.test($scope.code.max_count))) {
                            alert('请输入正确的使用次数值');
                            return false;
                        }
                        if ($scope.isDate == 'N') {
                            $scope.code.expire_at = '0';
                        }
                        $mi.close($scope.code);
                    };
                }]
            }).result.then(function(oNewCode) {
                http2.post('/rest/pl/fe/invite/code/update?code=' + oCode.id, oNewCode).then(function(rsp) {
                    ['stop', 'expire_at', 'max_count', 'remark'].forEach(function(prop) {
                        oCode[prop] = oNewCode[prop]
                    });
                });
            });
        };
        $scope.codeList = function() {
            var defer, url;
            defer = $q.defer();
            url = '/rest/pl/fe/invite/code/list?invite=' + _oInvite.id;
            http2.get(url).then(function(rsp) {
                $scope.codes = rsp.data;
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        $scope.logList = function() {
            var defer, url;
            defer = $q.defer();
            url = '/rest/pl/fe/invite/log/list?invite=' + _oInvite.id + _oLogPage.join();
            http2.get(url).then(function(rsp) {
                $scope.logs = rsp.data.logs;
                _oLogPage.total = rsp.data.total;
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        $scope.relayList = function() {
            var defer, url;
            defer = $q.defer();
            url = '/rest/pl/fe/invite/relayList?matter=' + _oInvite.matter_type + ',' + _oInvite.matter_id + _oRelayPage.join();
            http2.get(url).then(function(rsp) {
                $scope.relayInvites = rsp.data.invites;
                _oRelayPage.total = rsp.data.total;
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        srvInvite.get().then(function(oInvite) {
            $scope.invite = _oInvite = oInvite;
            if (oInvite) {
                if (_oInvite.require_code === 'Y') {
                    $scope.codeList().then(function() {
                        $scope.logList();
                        $scope.relayList();
                    });
                } else {
                    $scope.logList();
                    $scope.relayList();
                }
            }
        });
    }]);
});