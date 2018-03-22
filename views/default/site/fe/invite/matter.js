'use strict';
var ngApp = angular.module('app', ['ui.bootstrap', 'ui.tms', 'snsshare.ui.xxt', 'directive.enroll']);
ngApp.controller('ctrlMain', ['$scope', '$uibModal', 'http2', 'tmsSnsShare', function($scope, $uibModal, http2, tmsSnsShare) {
    var _oNewInvite, _oMatterList = {};
    $scope.newInvite = _oNewInvite = {};
    $scope.criteria = {id: ''};
    $scope.addInviteCode = function() {
        $uibModal.open({
            templateUrl: 'codeEditor.html',
            backdrop: 'static',
            controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                $scope.code = {};
                $scope.isDate = 'N';
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    var regx = /^[0-9]\d*$/;
                    if ($scope.code.max_count != '') {
                        if (!regx.test($scope.code.max_count)) {
                            alert( '请输入正确的使用次数值' );
                        }
                    }
                    if($scope.isDate=='N') {
                        $scope.code.expire_at = '0';
                    }
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
    $scope.$watch('criteria.id', function(nv) {
        if(!nv) return;
        http2.get('/rest/site/fe/invite/create?matter=' + _oMatterList[nv].type +',' + nv, function(rsp) {
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
    });
    http2.get('/rest/site/fe/user/get', function(rsp) {
        $scope.loginUser = rsp.data;
        if ($scope.loginUser.unionid) {
            http2.get('/rest/site/fe/invite/listInviteMatter' + location.search, function(rsp) {
                $scope.matterList = rsp.data;
                $scope.matterList.forEach(function(matter) {
                    _oMatterList[matter.id] = matter;
                });
            });
        }
    });
}]);