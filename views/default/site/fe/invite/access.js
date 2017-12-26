'use strict';
var ngApp = angular.module('app', ['ui.bootstrap', 'ui.tms']);
ngApp.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
    $scope.requireLogin = false;
    $scope.data = {};
    $scope.submit = function() {
        http2.post('/rest/i/matterUrl?invite=' + $scope.invite.id, $scope.data, function(rsp) {
            location.href = rsp.data;
        });
    };
    http2.get('/rest/site/fe/user/get', function(rsp) {
        var oUser, unameType;
        $scope.loginUser = oUser = rsp.data;
        if (oUser.unionid && oUser.uname) {
            if (/^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9])\d{8}$/.test(oUser.uname)) {
                unameType = 'mobile';
            } else if (/^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/.test(oUser.uname)) {
                unameType = 'email';
            }
        }
        http2.get('/rest/site/fe/invite/get' + location.search, function(rsp) {
            var oInvite = rsp.data;
            $scope.invite = oInvite;
            if (oInvite.entryRule) {
                $scope.entryRule = oInvite.entryRule;
                if (oInvite.entryRule.scope === 'member') {
                    if (oInvite.entryRule.mschemas && oInvite.entryRule.mschemas.length) {
                        $scope.mschema = oInvite.entryRule.mschemas[0];
                        $scope.data.member = { schema_id: $scope.mschema.id };
                        if (unameType === 'mobile' && $scope.mschema.attrs.mobile) {
                            $scope.data.member.mobile = oUser.uname;
                        } else if (unameType === 'email' && $scope.mschema.attrs.email) {
                            $scope.data.member.email = oUser.uname;
                        }
                        $scope.requireLogin = true;
                    }
                }
            }
        });
    });
}]);