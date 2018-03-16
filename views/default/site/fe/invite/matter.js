'use strict';
var ngApp = angular.module('app', ['ui.bootstrap', 'ui.tms', 'snsshare.ui.xxt']);
ngApp.controller('ctrlMain', ['$scope', '$uibModal', 'http2', 'tmsSnsShare', function($scope, $uibModal, http2, tmsSnsShare) {
    var _oNewInvite;
    $scope.newInvite = _oNewInvite = {};
    $scope.addInviteCode = function() {
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
            http2.post('/rest/site/fe/invite/code/add?invite=' + $scope.invite.id, oNewCode, function(rsp) {
                $scope.inviteCodes.splice(0, 0, rsp.data);
            });
        });
    };
    $scope.updateInvite = function(prop) {
        var posted = {};
        posted[prop] = _oNewInvite[prop];
        http2.post('/rest/site/fe/invite/update?invite=' + $scope.invite.id, posted, function(rsp) {
            $scope.invite[prop] = _oNewInvite[prop];
        });
    };
    http2.get('/rest/site/fe/user/get', function(rsp) {
        $scope.loginUser = rsp.data;
        if ($scope.loginUser.unionid) {
            http2.get('/rest/site/fe/invite/create' + location.search, function(rsp) {
                var oInvite = rsp.data;
                _oNewInvite.message = oInvite.message;
                $scope.invite = oInvite;
                http2.get('/rest/site/fe/user/invite/codeList?invite=' + oInvite.id, function(rsp) {
                    var codes = rsp.data;
                    $scope.inviteCodes = codes;
                });
                if (/MicroMessenger/i.test(navigator.userAgent)) {
                    $scope.wxAgent = true;
                    tmsSnsShare.config({
                        siteId: oInvite.matter_siteid,
                        logger: function(shareto) {},
                        jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage']
                    });
                    tmsSnsShare.set(oInvite.matter_title, oInvite.entryUrl, oInvite.matter_summary, oInvite.matter_pic);
                }
            });
        }
    });
}]);