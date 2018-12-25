'use strict';
require('../../../../../asset/js/xxt.ui.http.js');
require('../../../../../asset/js/xxt.ui.share.js');

var ngApp = angular.module('app', ['ui.bootstrap', 'ui.tms', 'http.ui.xxt', 'snsshare.ui.xxt']);
ngApp.controller('ctrlMain', ['$scope', 'http2', 'tmsSnsShare', function($scope, http2, tmsSnsShare) {
    $scope.requireLogin = false;
    $scope.data = {};
    $scope.submit = function() {
        http2.post('/rest/i/matterUrl?invite=' + $scope.invite.id, $scope.data).then(function(rsp) {
            location.href = rsp.data;
        });
    };
    http2.get('/rest/site/fe/user/get').then(function(rsp) {
        var oUser, unameType, inviteCode;
        $scope.loginUser = oUser = rsp.data;
        inviteCode = location.search.match(/code=([^&]*)/)[1];
        if (oUser.unionid && oUser.uname) {
            if (/^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9])\d{8}$/.test(oUser.uname)) {
                unameType = 'mobile';
            } else if (/^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/.test(oUser.uname)) {
                unameType = 'email';
            }
        }
        http2.get('/rest/site/fe/invite/get?inviteCode=' + inviteCode).then(function(rsp) {
            var oInvite = rsp.data;
            $scope.invite = oInvite;
            /* 设置分享 */
            if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
                var shareid, sharelink, shareby;
                shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
                shareid = $scope.loginUser.uid + '_' + (new Date() * 1);
                sharelink = location.href + "&shareby=" + shareid;
                tmsSnsShare.config({
                    siteId: oInvite.matter_siteid,
                    jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage']
                });
                tmsSnsShare.set(oInvite.matter_title, sharelink, oInvite.matter_summary, oInvite.matter_pic);
            }
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