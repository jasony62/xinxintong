'use strict';

var ngApp = require('./main.js');
ngApp.controller('ctrlShare', ['$scope', '$sce', '$q', 'tmsLocation', 'tmsSnsShare', 'http2', function($scope, $sce, $q, LS, tmsSnsShare, http2) {
    function fnSetSnsShare(oApp, message, anchor) {
        function fnReadySnsShare() {
            if (window.__wxjs_environment === 'miniprogram') {
                return;
            }
            var sharelink, shareid, shareby, target_type, target_title;
            /* 设置活动的当前链接 */
            shareid = _oUser.uid + '_' + (new Date * 1);
            shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
            sharelink = location.protocol + '//' + location.host
            if (LS.s().topic) {
                target_type = 'topic';
                target_title = oApp.record.title;
                sharelink += LS.j('', 'site', 'app', 'topic') + '&page=topic&shareby=' + shareid;
            } else {
                target_type = 'cowork';
                target_title = oApp.title;
                sharelink += LS.j('', 'site', 'app', 'ek') + '&page=cowork&shareby=' + shareid;
                if (anchor) {
                    sharelink += '#' + anchor;
                }
            }
            /* 分享次数计数器 */
            tmsSnsShare.config({
                siteId: oApp.siteid,
                logger: function(shareto) {
                    var url;
                    url = "/rest/site/fe/matter/logShare";
                    url += "?shareid=" + shareid;
                    url += "&site=" + oApp.siteid;
                    url += "&id=" + oApp.id;
                    url += "&title=" + target_title;
                    url += "&type=enroll";
                    url += "&target_type=" + target_type;
                    url += "&target_id=" + oApp.record.id;
                    url += "&shareby=" + shareby;
                    url += "&shareto=" + shareto;
                    http2.get(url);
                },
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

    if (/MicroMessenger/i.test(navigator.userAgent)) {
        $scope.userAgent = 'wx';
    } else if (/Yixin/i.test(navigator.userAgent)) {
        $scope.userAgent = 'yx';
    }

    var _oApp, _oUser, _oOptions, _oMessage, _oDeferred;
    $scope.options = _oOptions = {
        canEditorAsAuthor: false, // 被分享内容的作者是否为编辑
        canEditorAsInviter: false,
        shareByEditor: false
    };
    _oMessage = {
        toString: function() {
            var msg;
            msg = this.inviter + '邀请你查看' + this.author + (this.relevent || '') + this.object;
            if (!/(\.|,|;|\?|!|。|，|；|？|！)$/.test(this.object)) {
                msg += '。';
            }
            return msg;
        }
    };
    _oDeferred = $q.defer();
    _oDeferred.promise.then(function(oMsg) {
        var message;
        $scope.message = message = oMsg.toString();
        fnSetSnsShare(_oApp, message, oMsg.anchor);
    });
    $scope.shiftAuthor = function() {
        if (_oOptions.editorAsAuthor) {
            _oMessage.defaultAuthor === undefined && (_oMessage.defaultAuthor = _oMessage.author);
            _oMessage.author = _oApp.actionRule.role.editor.nickname;
        } else {
            _oMessage.author = _oMessage.defaultAuthor;
        }
        $scope.message = _oMessage.toString();
        fnSetSnsShare(_oApp, $scope.message, _oMessage.anchor);
    };
    $scope.shiftInviter = function() {
        if (_oOptions.editorAsInviter) {
            _oMessage.defaultInviter === undefined && (_oMessage.defaultInviter = _oMessage.inviter);
            _oMessage.inviter = _oApp.actionRule.role.editor.nickname;
        } else {
            _oMessage.inviter = _oMessage.defaultInviter;
        }
        $scope.message = _oMessage.toString();
        fnSetSnsShare(_oApp, $scope.message, _oMessage.anchor);
    };
    /*不是用微信打开时，提供二维码*/
    if (!$scope.userAgent) {
        $scope.qrcode = LS.j('topic/qrcode', 'site') + '&url=' + encodeURIComponent(location.href);
    }
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oEditor;
        _oApp = params.app;
/*        _oUser = params.user;*/
        /* 用户信息 */
        enlService.user().then(function(data) {
            _oUser = data;
        });
        if (_oApp.actionRule && _oApp.actionRule.role && _oApp.actionRule.role.editor) {
            if (_oApp.actionRule.role.editor.group && _oApp.actionRule.role.editor.nickname) {
                oEditor = _oApp.actionRule.role.editor;
            }
        }
        if (oEditor && _oUser.is_editor === 'Y') {
            _oOptions.canEditorAsInviter = true;
        }
        if (LS.s().data) {
            http2.get(LS.j('data/get', 'site', 'ek', 'data')).then(function(rsp) {
                var oRecord, oRecData;
                oRecord = rsp.data;
                oRecData = oRecord.verbose[oRecord.schema_id];
                _oApp.record = rsp.data;
                _oMessage.inviter = _oUser.nickname;
                _oMessage.author = _oUser.uid === oRecData.userid ? 'ta' : (oRecData.nickname);
                _oMessage.relevent = '给';
                if (oRecord.userid !== oRecData.userid) {
                    _oMessage.relevent += oRecord.nickname;
                } else {
                    _oMessage.relevent += '自己';
                }
                _oMessage.object = '的回答：' + oRecData.value;
                _oMessage.anchor = 'item-' + LS.s().data;
                if (_oUser.is_editor === 'Y' && oEditor && oRecData.is_editor === 'Y' && _oUser.uid !== oRecData.userid) {
                    _oOptions.canEditorAsAuthor = true;
                }
                _oDeferred.resolve(_oMessage);
            });
        } else if (LS.s().remark) {
            http2.get(LS.j('remark/get', 'site', 'remark') + '&cascaded=Y').then(function(rsp) {
                var oRemark;
                oRemark = rsp.data;
                _oApp.record = rsp.data.record;
                _oMessage.inviter = _oUser.nickname;
                _oMessage.author = _oUser.uid === oRemark.userid ? 'ta' : (oRemark.nickname);
                if (oRemark.record) {
                    _oMessage.relevent = '给';
                    if (oRemark.record.userid !== oRemark.userid) {
                        _oMessage.relevent += oRemark.record.nickname;
                    } else {
                        _oMessage.relevent += '自己';
                    }
                    if (oRemark.data && oRemark.data.userid !== oRemark.record.userid) {
                        _oMessage.relevent += '和';
                        if (oRemark.data.userid !== oRemark.userid) {
                            _oMessage.relevent += oRemark.data.nickname;
                        } else {
                            _oMessage.relevent += '自己';
                        }
                    }
                }
                _oMessage.object = '的留言：' + oRemark.content;
                _oMessage.anchor = 'remark-' + oRemark.id;
                if (_oUser.is_editor === 'Y' && oEditor && oRemark.is_editor === 'Y' && _oUser.uid !== oRemark.userid) {
                    _oOptions.canEditorAsAuthor = true;
                }
                _oDeferred.resolve(_oMessage);
            });
        } else if (LS.s().ek) {
            http2.get(LS.j('repos/recordGet', 'site', 'app', 'ek')).then(function(rsp) {
                var oRecord;
                oRecord = rsp.data;
                _oApp.record = rsp.data;
                _oMessage.inviter = _oUser.nickname;
                _oMessage.author = _oUser.uid === oRecord.userid ? 'ta' : (oRecord.nickname);
                _oMessage.object = '的记录。';
                if (_oUser.is_editor === 'Y' && oEditor && oRecord.is_editor === 'Y' && _oUser.uid !== oRecord.userid) {
                    _oOptions.canEditorAsAuthor = true;
                }
                _oDeferred.resolve(_oMessage);
            });
        } else if (LS.s().topic) {
            http2.get(LS.j('topic/get', 'site', 'app', 'topic')).then(function(rsp) {
                var oTopic;
                oTopic = rsp.data;
                _oApp.record = rsp.data;
                _oMessage.inviter = _oUser.nickname;
                _oMessage.author = _oUser.unionid === oTopic.unionid ? 'ta' : (oTopic.nickname);
                _oMessage.object = '的专题。';
                if (_oUser.is_editor === 'Y' && oEditor && oTopic.is_editor === 'Y' && _oUser.unionid !== oTopic.unionid) {
                    _oOptions.canEditorAsAuthor = true;
                }
                _oDeferred.resolve(_oMessage);
            });
        }
    });
}]);