'use strict';
require('./share.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlShare', ['$scope', '$sce', 'tmsLocation', 'tmsSnsShare', 'http2', function($scope, $sce, LS, tmsSnsShare, http2) {
    function fnSetSnsShare(oApp, message, anchor) {
        function fnReadySnsShare() {
            if (window.__wxjs_environment === 'miniprogram') {
                return;
            }
            var sharelink;
            /* 设置活动的当前链接 */
            sharelink = 'http://' + location.host + LS.j('', 'site', 'app', 'ek') + '&page=cowork' + '#' + anchor;
            /* 分享次数计数器 */
            tmsSnsShare.config({
                siteId: oApp.siteid,
                logger: function(shareto) {},
                jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage']
            });
            tmsSnsShare.set(oApp.title, sharelink, message, oApp.pic);
        }
        if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
            if (!window.WeixinJSBridge || !WeixinJSBridge.invoke) {
                document.addEventListener('WeixinJSBridgeReady', fnReadySnsShare, false);
            } else {
                fnReadySnsShare();
            }
        }
    }

    var _oApp, _oUser;
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        _oUser = params.user;
        if (LS.s().data) {
            http2.get(LS.j('data/get', 'site', 'ek', 'data')).then(function(rsp) {
                var message, oRecord;
                oRecord = rsp.data;
                message = '$' + _oUser.nickname;
                message += ' 邀请你查看 ';
                message += _oUser.uid === oRecord.userid ? '他/她' : ('$' + oRecord.nickname);
                message += ' 填写的数据：';
                message += oRecord.verbose[oRecord.schema_id].value;
                $scope.message = message;
                fnSetSnsShare(_oApp, message, 'item-' + LS.s().data);
            });
        } else if (LS.s().remark) {
            http2.get(LS.j('remark/get', 'site', 'remark')).then(function(rsp) {
                var message, oRemark;
                oRemark = rsp.data;
                message = '$' + _oUser.nickname;
                message += ' 邀请你查看 ';
                message += _oUser.uid === oRemark.userid ? '他/她' : ('$' + oRemark.nickname);
                message += ' 的留言：';
                message += oRemark.content;
                $scope.message = message;
                fnSetSnsShare(_oApp, message, 'remark-' + oRemark.id);
            });
        }
    });
}]);