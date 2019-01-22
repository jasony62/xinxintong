'use strict';
require('../../../../../../asset/css/buttons.css');
require('./enroll.public.css');

require('./_asset/ui.repos.js');
require('./_asset/ui.score.js');
require('./_asset/ui.tag.js');
require('./_asset/ui.topic.js');

window.moduleAngularModules = ['repos.ui.enroll', 'score.ui.enroll', 'tag.ui.enroll', 'topic.ui.enroll'];

var ngApp = require('./main.js');
ngApp.controller('ctrlTopic', ['$scope', '$sce', '$q', '$uibModal', 'http2', 'tmsLocation', '$timeout', 'picviewer', 'noticebox', function($scope, $sce, $q, $uibModal, http2, LS, $timeout, picviewer, noticebox) {
    /* 是否可以对记录进行表态 */
    function fnCanAgreeRecord(oRecord, oUser) {
        if (oUser.is_leader) {
            if (oUser.is_leader === 'S') {
                return true;
            }
            if (oUser.is_leader === 'Y') {
                if (oUser.group_id === oRecord.group_id) {
                    return true;
                } else if (oUser.is_editor && oUser.is_editor === 'Y') {
                    return true;
                }
            }
        }
        return false;
    }

    function fnGetTopic() {
        var url;
        url = LS.j('topic/get', 'site', 'app', 'topic');
        return http2.get(url);
    }

    $scope.shareTopic = function() {
        var url;
        url = LS.j('', 'site', 'app') + '&topic=' + $scope.topic.id + '&page=share';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };
    $scope.quitTopic = function(oRecord) {
        http2.post(LS.j('topic/removeRec', 'site') + '&topic=' + $scope.topic.id, { record: oRecord.id }).then(function(rsp) {
            $scope.repos.splice($scope.repos.indexOf(oRecord), 1);
            _oPage.total--;
        });
    };
    $scope.coworkRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork';
        location.href = url;
    };

    var _oApp, _oPage, _oCriteria, _oShareableSchemas, shareby;
    shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
    _oCriteria = { rid: 'all', creator: false, favored: true, agreed: 'all', orderby: 'lastest' }; // 数据查询条件
    $scope.page = _oPage = {};
    $scope.schemas = _oShareableSchemas = {}; // 支持分享的题目
    $scope.repos = []; // 分享的记录
    $scope.reposLoading = false;
    $scope.recordList = function(pageAt) {
        var url, deferred;
        deferred = $q.defer();
        if (pageAt)
            _oPage.at = pageAt;
        else
            _oPage.at++;
        if (_oPage.at == 1) {
            $scope.repos = [];
            _oPage.total = 0;
        }
        url = LS.j('repos/recordByTopic', 'site', 'app', 'topic');
        url += '&page=' + _oPage.at + '&size=' + _oPage.size;
        $scope.reposLoading = true;
        http2.get(url, { page: _oPage }).then(function(result) {
            if (result.data.records) {
                result.data.records.forEach(function(oRecord) {
                    oRecord._canAgree = fnCanAgreeRecord(oRecord, $scope.user);
                    $scope.repos.push(oRecord);
                });
            }
            $timeout(function() {
                var imgs;
                if (imgs = document.querySelectorAll('.data img')) {
                    picviewer.init(imgs);
                }
            });
            $scope.reposLoading = false;
            deferred.resolve(result);
        });

        return deferred.promise;
    };
    $scope.likeRecord = function(oRecord) {
        var url;
        url = LS.j('record/like', 'site');
        url += '&ek=' + oRecord.enroll_key;
        http2.get(url).then(function(rsp) {
            oRecord.like_log = rsp.data.like_log;
            oRecord.like_num = rsp.data.like_num;
        });
    };
    $scope.dislikeRecord = function(oRecord) {
        var url;
        url = LS.j('record/dislike', 'site');
        url += '&ek=' + oRecord.enroll_key;
        http2.get(url).then(function(rsp) {
            oRecord.dislike_log = rsp.data.dislike_log;
            oRecord.dislike_num = rsp.data.dislike_num;
        });
    };
    $scope.remarkRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork#remarks';
        location.href = url;
    };
    $scope.setAgreed = function(oRecord, value) {
        var url;
        if (oRecord.agreed !== value) {
            url = LS.j('record/agree', 'site');
            url += '&ek=' + oRecord.enroll_key;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRecord.agreed = value;
            });
        }
    };
    $scope.favorRecord = function(oRecord) {
        var url;
        if (!oRecord.favored) {
            url = LS.j('favor/add', 'site');
            url += '&ek=' + oRecord.enroll_key;
            http2.get(url).then(function(rsp) {
                oRecord.favored = true;
                noticebox.info('收藏成功');
            });
        } else {
            noticebox.confirm('取消收藏，确定？').then(function() {
                url = LS.j('favor/remove', 'site');
                url += '&ek=' + oRecord.enroll_key;
                http2.get(url).then(function(rsp) {
                    delete oRecord.favored;
                });
            });
        }
    };
    $scope.shareRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app') + '&ek=' + oRecord.enroll_key + '&page=share';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };
    $scope.editRecord = function(event, oRecord) {
        if (oRecord.userid !== $scope.user.uid) {
            noticebox.warn('不允许编辑其他用户提交的记录');
            return;
        }
        var page;
        for (var i in $scope.app.pages) {
            var oPage = $scope.app.pages[i];
            if (oPage.type === 'I') {
                page = oPage.name;
                break;
            }
        }
        $scope.gotoPage(event, page, oRecord.enroll_key);
    };
    $scope.spyRecordsScroll = true; // 监控滚动事件
    $scope.recordsScrollToBottom = function() {
        if ($scope.repos.length < $scope.page.total) {
            $scope.recordList().then(function() {
                $timeout(function() {
                    if ($scope.repos.length < $scope.page.total) {
                        $scope.spyRecordsScroll = true;
                    }
                });
            });
        }
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oApp, oUser;
        _oApp = oApp = params.app;
        oUser = params.user;
        oApp.dynaDataSchemas.forEach(function(schema) {
            if (schema.shareable && schema.shareable === 'Y') {
                _oShareableSchemas[schema.id] = schema;
            }
        });
        /*设置页面操作*/
        $scope.setPopAct(['addRecord'], 'topic');
        /*设置页面导航*/
        $scope.setPopNav(['repos'], 'topic');
        fnGetTopic().then(function(rsp) {
            $scope.topic = rsp.data;
            /* 设置页面分享信息 */
            $scope.setSnsShare(null, { topic: LS.s().topic }, { target_type: 'topic', target_id: rsp.data.id, title: rsp.data.title }); // 应该禁止分享
            /*页面阅读日志*/
            $scope.logAccess({ target_type: 'topic', target_id: rsp.data.id, title: rsp.data.title });
            $scope.recordList(1);
        });
    });
}]);