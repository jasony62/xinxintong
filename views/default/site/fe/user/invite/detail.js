'use strict';

var ngApp = angular.module('app', ['ui.bootstrap', 'ui.tms', 'http.ui.xxt', 'snsshare.ui.xxt', 'directive.enroll']);
ngApp.controller('ctrlInvite', ['$scope', '$q', '$uibModal', 'http2', 'tmsSnsShare', function($scope, $q, $uibModal, http2, tmsSnsShare) {
    var _inviteId, _oInvite, _oNewInvite, _oPage;
    _inviteId = location.search.match('invite=(.*)')[1];
    $scope.newInvite = _oNewInvite = {};
    $scope.page = _oPage = {
        at: 1,
        size: 10,
        join: function() {
            return '&page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.update = function(prop) {
        var posted;
        posted = {};
        posted[prop] = _oNewInvite[prop];
        http2.post('/rest/site/fe/invite/update?invite=' + _oInvite.id, posted).then(function() {
            _oInvite[prop] = _oNewInvite[prop];
        });
    };
    $scope.addCode = function() {
        $uibModal.open({
            templateUrl: 'codeEditor.html',
            backdrop: 'static',
            controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                $scope.code = {};
                $scope.isDate = 'N';
                $scope.state = 'add';
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    var regx = /^[0-9]\d*$/;
                    if ($scope.code.max_count !== '' && (!regx.test($scope.code.max_count))) {
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
            http2.post('/rest/site/fe/invite/code/add?invite=' + _oInvite.id, oNewCode).then(function(rsp) {
                $scope.codes === undefined && ($scope.codes = []);
                $scope.codes.splice(0, 0, rsp.data);
            });
        });
    };
    $scope.configCode = function(oCode) {
        $uibModal.open({
            templateUrl: 'codeEditor.html',
            backdrop: 'static',
            controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                $scope.code = {};
                $scope.state = 'config';
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
            http2.post('/rest/site/fe/invite/code/update?code=' + oCode.id, oNewCode).then(function(rsp) {
                ['stop', 'expire_at', 'max_count', 'remark'].forEach(function(prop) {
                    oCode[prop] = oNewCode[prop]
                });
            });
        });
    };
    $scope.codeList = function() {
        var defer, url;
        defer = $q.defer();
        url = '/rest/site/fe/user/invite/codeList?invite=' + _inviteId;
        http2.get(url).then(function(rsp) {
            $scope.codes = rsp.data;
            defer.resolve(rsp.data);
        });
        return defer.promise;
    };
    $scope.gotoLog = function(oInviteCode) {
        location.href = '/rest/site/fe/user/invite/log?inviteCode=' + oInviteCode.id;
    };
    http2.get('/rest/site/fe/user/invite/get?invite=' + _inviteId).then(function(rsp) {
        $scope.invite = _oInvite = rsp.data;
        if (/MicroMessenger/i.test(navigator.userAgent)) {
            $scope.wxAgent = true;
            tmsSnsShare.config({
                siteId: _oInvite.matter_siteid,
                logger: function(shareto) {},
                jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage']
            });
            tmsSnsShare.set(_oInvite.matter_title, _oInvite.entryUrl, _oInvite.matter_summary, _oInvite.matter_pic);
        }
        _oNewInvite.message = _oInvite.message;
        $scope.newInvite = _oNewInvite;
        $scope.codeList().then(function() {
            var eleLoading;
            eleLoading = document.querySelector('.loading');
            eleLoading.parentNode.removeChild(eleLoading);
        });
    });
}]);