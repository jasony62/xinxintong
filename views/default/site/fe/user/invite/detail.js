'use strict';

var ngApp = angular.module('app', ['ui.bootstrap', 'ui.tms', 'snsshare.ui.xxt']);
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
        http2.post('/rest/site/fe/invite/update?invite=' + _oInvite.id, posted, function() {
            _oInvite[prop] = _oNewInvite[prop];
        });
    };
    $scope.addCode = function() {
        $uibModal.open({
            templateUrl: 'codeEditor.html',
            backdrop: 'static',
            controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                $scope.code = {};
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close($scope.code);
                };
            }]
        }).result.then(function(oNewCode) {
            http2.post('/rest/site/fe/invite/code/add?invite=' + _oInvite.id, oNewCode, function(rsp) {
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
                ['remark'].forEach(function(prop) {
                    $scope.code[prop] = oCode[prop]
                });
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close($scope.code);
                };
            }]
        }).result.then(function(oNewCode) {
            http2.post('/rest/site/fe/invite/code/update?code=' + oCode.id, oNewCode, function(rsp) {
                ['remark'].forEach(function(prop) {
                    oCode[prop] = oNewCode[prop]
                });
            });
        });
    };
    $scope.codeList = function() {
        var defer, url;
        defer = $q.defer();
        url = '/rest/site/fe/user/invite/codeList?invite=' + _inviteId;
        http2.get(url, function(rsp) {
            $scope.codes = rsp.data;
            defer.resolve(rsp.data);
        });
        return defer.promise;
    };
    $scope.gotoLog = function(oInviteCode) {
        location.href = '/rest/site/fe/user/invite/log?inviteCode=' + oInviteCode.id;
    };
    http2.get('/rest/site/fe/user/invite/get?invite=' + _inviteId, function(rsp) {
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