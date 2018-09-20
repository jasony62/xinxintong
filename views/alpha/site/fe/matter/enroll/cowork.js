'use strict';
require('./enroll.public.css');
require('../../../../../../asset/js/xxt.ui.image.js');
require('../../../../../../asset/js/xxt.ui.editor.js');
require('../../../../../../asset/js/xxt.ui.trace.js');
require('./_asset/ui.repos.js');
require('./_asset/ui.tag.js');
require('./_asset/ui.topic.js');
require('./_asset/ui.assoc.js');

window.moduleAngularModules = ['editor.ui.xxt', 'trace.ui.xxt', 'repos.ui.enroll', 'tag.ui.enroll', 'topic.ui.enroll', 'assoc.ui.enroll'];

var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.controller('ctrlCowork', ['$scope', '$q', '$timeout', '$location', '$anchorScroll', '$sce', '$uibModal', 'tmsLocation', 'http2', 'noticebox', 'tmsDynaPage', 'enlTag', 'enlTopic', 'enlAssoc', function($scope, $q, $timeout, $location, $anchorScroll, $sce, $uibModal, LS, http2, noticebox, tmsDynaPage, enlTag, enlTopic, enlAssoc) {
    function listRemarks() {
        var url;
        url = LS.j('remark/list', 'site', 'ek', 'schema', 'data');
        if (_oMocker.role) {
            url += '&role=' + _oMocker.role;
        }
        http2.get(url).then(function(rsp) {
            var remarks, oRemark, oUpperRemark, oRemarks;
            remarks = rsp.data.remarks;
            if (remarks && remarks.length) {
                oRemarks = {};
                remarks.forEach(function(oRemark) {
                    oRemarks[oRemark.id] = oRemark;
                });
                for (var i = remarks.length - 1; i >= 0; i--) {
                    oRemark = remarks[i];
                    if (oRemark.content) {
                        oRemark.content = oRemark.content.replace(/\n/g, '<br/>');
                    }
                    if (oRemark.data) {
                        oRemark.reply = '<a href="#item-' + oRemark.data.id + '">回复' + oRemark.nickname + '的' + ($scope.schemasById[oRemark.data.schema_id] ? $scope.schemasById[oRemark.data.schema_id].title : '数据') + (oRemark.data.multitext_seq > 0 ? (' #' + oRemark.data.multitext_seq) : '') + '</a>';
                    } else if (oRemark.remark_id !== '0') {
                        if (oUpperRemark = oRemarks[oRemark.remark_id]) {
                            oRemark.reply = '<a href="#remark-' + oRemark.remark_id + '">回复' + oUpperRemark.nickname + '的留言 #' + oUpperRemark.seq_in_record + '</a>';
                        }
                    }
                }
            }
            $scope.remarks = remarks;
            if ($location.hash() === 'remarks') {
                $timeout(function() {
                    $anchorScroll.yOffset = 30;
                    $anchorScroll();
                });
            } else if (/remark-.+/.test($location.hash())) {
                $timeout(function() {
                    var elRemark;
                    if (elRemark = document.querySelector('#' + $location.hash())) {
                        $anchorScroll();
                        elRemark.classList.toggle('blink', true);
                        $timeout(function() {
                            elRemark.classList.toggle('blink', false);
                        }, 1000);
                    }
                });
            }
        });
    }

    function addRemark(content, oRemark) {
        var url;
        url = LS.j('remark/add', 'site', 'ek', 'data');
        if (oRemark) {
            url += '&remark=' + oRemark.id;
        }
        return http2.post(url, { content: content });
    }
    /**
     * 加载整条记录
     */
    function fnLoadRecord(aCoworkSchemas, oUser) {
        var oDeferred;
        oDeferred = $q.defer();
        http2.get(LS.j('repos/recordGet', 'site', 'app', 'ek')).then(function(rsp) {
            var oRecord;
            oRecord = rsp.data;
            oRecord._canAgree = fnCanAgreeRecord(oRecord, _oUser);
            $scope.record = oRecord
            /* 设置页面分享信息 */
            $scope.setSnsShare(oRecord, null, { target_type: 'cowork', target_id: oRecord.id });
            /*页面阅读日志*/
            $scope.logAccess({ target_type: 'cowork', target_id: oRecord.id });
            /* 加载协作填写数据 */
            if (aCoworkSchemas.length) {
                oRecord.verbose = {};
                fnLoadCowork(oRecord, aCoworkSchemas);
            }
            // 留言任务说明
            http2.get(LS.j('remark/task', 'site', 'app') + '&ek=' + oRecord.enroll_key).then(function(rsp) {
                if (rsp.data && rsp.data.length) {
                    rsp.data.forEach(function(oRule) {
                        $scope.remarkTasks.push({ type: 'info', msg: oRule.desc, id: oRule.id, coin: oRule.coin ? oRule.coin : 0 });
                    });
                }
            });
            //
            listRemarks();
            //
            oDeferred.resolve(oRecord);
        });

        return oDeferred.promise;
    }
    /**
     * 加载关联数据
     */
    function fnLoadAssoc(oRecord) {
        var oDeferred;
        oDeferred = $q.defer();
        http2.get(LS.j('assoc/byRecord', 'site', 'ek')).then(function(rsp) {
            if (rsp.data.length) {
                oRecord.assocs = [];
                rsp.data.forEach(function(oAssoc) {
                    if (oAssoc.entity_a_type === 'record' && oAssoc.entity_a_id == oRecord.id) {
                        if (oAssoc.log && oAssoc.log.assoc_text) {
                            oAssoc.assoc_text = oAssoc.log.assoc_text;
                        }
                        oRecord.assocs.push(oAssoc);
                    }
                });
            }
            oDeferred.resolve();
        });

        return oDeferred.promise;
    }
    /**
     * 加载协作填写数据
     */
    function fnLoadCowork(oRecord, aCoworkSchemas, bJumpTask) {
        var url, anchorItemId;
        if (/item-.+/.test($location.hash())) {
            anchorItemId = $location.hash().substr(5);
        }
        $scope.coworkTasks.splice(0, $scope.coworkTasks.length);
        aCoworkSchemas.forEach(function(oSchema) {
            url = LS.j('cowork/task', 'site', 'app', 'ek') + '&schema=' + oSchema.id;
            http2.get(url).then(function(rsp) {
                if (rsp.data && rsp.data.length) {
                    rsp.data.forEach(function(oRule) {
                        $scope.coworkTasks.push({ type: 'info', msg: oRule.desc, id: oRule.id, coin: oRule.coin ? oRule.coin : 0 });
                    });
                }
                url = LS.j('data/get', 'site', 'ek') + '&schema=' + oSchema.id + '&cascaded=Y';
                if (_oMocker.role) {
                    url += '&role=' + _oMocker.role;
                }
                http2.get(url, { autoBreak: false, autoNotice: false }).then(function(rsp) {
                    var bRequireAnchorScroll;
                    oRecord.verbose[oSchema.id] = rsp.data.verbose[oSchema.id];
                    oRecord.verbose[oSchema.id].items.forEach(function(oItem) {
                        if (oItem.userid !== $scope.user.uid) {
                            oItem._others = true;
                        }
                        if (anchorItemId && oItem.id === anchorItemId) {
                            bRequireAnchorScroll = true;
                        }
                    });
                    if (bRequireAnchorScroll) {
                        $timeout(function() {
                            var elItem;
                            $anchorScroll();
                            elItem = document.querySelector('#item-' + anchorItemId);
                            elItem.classList.toggle('blink', true);
                            $timeout(function() {
                                elItem.classList.toggle('blink', false);
                            }, 1000);
                        });
                    }
                });
            });
        });
    }

    function fnAfterRecordLoad(oRecord, oUser) {
        /*设置任务提示*/
        if (_oApp.actionRule) {
            var oCoworkRule;
            oCoworkRule = $scope.ruleCowork(oRecord);
            if (oCoworkRule) {
                $scope.coworkTasks.push({ type: 'info', msg: oCoworkRule.desc, id: 'record.cowork.pre' });
            }
        }
        /*设置页面操作*/
        $scope.appActs = {};
        /* 允许添加记录 */
        if (_oApp.actionRule && _oApp.actionRule.record && _oApp.actionRule.record.submit && _oApp.actionRule.record.submit.pre && _oApp.actionRule.record.submit.pre.editor) {
            if (oUser.is_editor && oUser.is_editor === 'Y') {
                $scope.appActs.addRecord = {};
            }
        } else {
            $scope.appActs.addRecord = {};
        }
        /* 是否允许切换用户角色 */
        if (oUser) {
            if (oUser.is_editor && oUser.is_editor === 'Y') {
                $scope.appActs.mockAsVisitor = { mocker: 'mocker' };
            }
            if (oUser.is_leader && /Y|S/.test(oUser.is_leader)) {
                $scope.appActs.mockAsMember = { mocker: 'mocker' };
            }
        }
        $scope.appActs.length = Object.keys($scope.appActs).length;
        /*设置页面导航*/
        var oAppNavs = {
            favor: {}
        };
        if (_oApp.can_repos === 'Y') {
            oAppNavs.repos = {};
        }
        if (_oApp.can_rank === 'Y') {
            oAppNavs.rank = {};
        }
        if (_oApp.scenarioConfig && _oApp.scenarioConfig.can_action === 'Y') {
            oAppNavs.event = {};
        }
        if (Object.keys(oAppNavs).length) {
            $scope.appNavs = oAppNavs;
        }
    }
    /* 是否可以对记录进行表态 */
    function fnCanAgreeRecord(oRecord, oUser) {
        if (_oMocker.role && /visitor|member/.test(_oMocker.role)) {
            return false;
        }
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

    function fnAppendRemark(oNewRemark, oUpperRemark) {
        var oNewRemark;
        oNewRemark.content = oNewRemark.content.replace(/\\n/g, '<br/>');
        if (oUpperRemark) {
            oNewRemark.reply = '<a href="#remark-' + oUpperRemark.id + '">回复' + oUpperRemark.nickname + '的留言 #' + oUpperRemark.seq_in_record + '</a>';
        }
        $scope.remarks.push(oNewRemark);
        if (!oUpperRemark) {
            $scope.record.rec_remark_num++;
        }
        $timeout(function() {
            var elRemark;
            $location.hash('remark-' + oNewRemark.id);
            $anchorScroll();
            elRemark = document.querySelector('#remark-' + oNewRemark.id);
            elRemark.classList.toggle('blink', true);
            $timeout(function() {
                elRemark.classList.toggle('blink', false);
            }, 1000);
        });
    }

    if (!LS.s().ek) {
        noticebox.error('参数不完整');
        return;
    }
    var _oApp, _oUser, ek, _oMocker, shareby;
    ek = LS.s().ek;
    shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
    $scope.coworkTasks = [];
    $scope.remarkTasks = [];
    $scope.newRemark = {};
    $scope.mocker = _oMocker = {}; // 用户自己指定的角色
    $scope.favorStack = {
        guiding: false,
        start: function(record, timer) {
            this.guiding = true;
            this.record = record;
            this.timer = timer;
        },
        end: function() {
            this.guiding = false;
            delete this.record;
            delete this.timer;
        }
    };
    $scope.copyRecord = function(oRecord) {
        enlAssoc.copy($scope.app, { id: oRecord.id, type: 'record' });
    };
    $scope.pasteRecord = function(oRecord) {
        enlAssoc.paste($scope.user, oRecord, { id: oRecord.id, type: 'record' }).then(function(oNewAssoc) {
            if (!oRecord.assocs) oRecord.assocs = [];
            if (oNewAssoc.log) oNewAssoc.assoc_text = oNewAssoc.log.assoc_text;
            oRecord.assocs.push(oNewAssoc);
        });

    };
    $scope.removeAssoc = function(oAssoc) {
        noticebox.confirm('取消关联，确定？').then(function() {
            http2.get(LS.j('assoc/unlink', 'site') + '&assoc=' + oAssoc.id).then(function() {
                $scope.record.assocs.splice($scope.record.assocs.indexOf(oAssoc), 1);
            });
        });
    };
    $scope.editAssoc = function(oAssoc) {
        enlAssoc.update($scope.user, oAssoc);
    };
    $scope.favorRecord = function(oRecord) {
        var url;
        if (!oRecord.favored) {
            url = LS.j('favor/add', 'site');
            url += '&ek=' + oRecord.enroll_key;
            http2.get(url).then(function(rsp) {
                oRecord.favored = true;
                $scope.favorStack.start(oRecord, $timeout(function() {
                    $scope.favorStack.end();
                }, 3000));
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

    function fnAssignTag(oRecord) {
        enlTag.assignTag(oRecord).then(function(rsp) {
            if (rsp.data.user && rsp.data.user.length) {
                oRecord.userTags = rsp.data.user;
            } else {
                delete oRecord.userTags;
            }
        });
    }
    $scope.assignTag = function(oRecord) {
        if (oRecord) {
            fnAssignTag(oRecord);
        } else {
            $scope.favorStack.timer && $timeout.cancel($scope.favorStack.timer);
            if (oRecord = $scope.favorStack.record) {
                fnAssignTag(oRecord);
            }
            $scope.favorStack.end();
        }
    };

    function fnAssignTopic(oRecord) {
        http2.get(LS.j('topic/list', 'site', 'app')).then(function(rsp) {
            var topics;
            if (rsp.data.total === 0) {
                location.href = LS.j('', 'site', 'app') + '&page=favor#topic';
            } else {
                topics = rsp.data.topics;
                enlTopic.assignTopic(oRecord);
            }
        });
    }
    $scope.assignTopic = function(oRecord) {
        if (oRecord) {
            fnAssignTopic(oRecord);
        } else {
            $scope.favorStack.timer && $timeout.cancel($scope.favorStack.timer);
            if (oRecord = $scope.favorStack.record) {
                fnAssignTopic(oRecord);
            }
            $scope.favorStack.end();
        }
    };
    $scope.mockAsVisitor = function(event, bMock) {
        _oMocker.role = bMock ? 'visitor' : '';
        $scope.record._canAgree = fnCanAgreeRecord($scope.record, $scope.user);
        fnLoadCowork($scope.record, $scope.coworkSchemas);
        listRemarks();
    };
    $scope.mockAsMember = function(event, bMock) {
        _oMocker.role = bMock ? 'member' : '';
        $scope.record._canAgree = fnCanAgreeRecord($scope.record, $scope.user);
        fnLoadCowork($scope.record, $scope.coworkSchemas);
        listRemarks();
    };
    $scope.ruleCowork = function(oRecord) {
        var desc, gap;
        if (_oApp.actionRule) {
            var actionRule;
            actionRule = _oApp.actionRule;
            if (actionRule.record && actionRule.record.cowork && actionRule.record.cowork.pre) {
                if (actionRule.record.cowork.pre.record && actionRule.record.cowork.pre.record.likeNum) {
                    if (actionRule.record.cowork.pre.record.likeNum > oRecord.like_num) {
                        gap = actionRule.record.cowork.pre.record.likeNum - oRecord.like_num;
                        if (actionRule.record.cowork.pre.desc) {
                            desc = actionRule.record.cowork.pre.desc;
                        }
                    }
                }
            }
        }
        if (!desc) {
            return false;
        }
        return { desc: desc, gap: gap };
    };
    $scope.setAgreed = function(value) {
        var url, oRecord;
        oRecord = $scope.record;
        if (oRecord.agreed !== value) {
            url = LS.j('record/agree', 'site', 'ek');
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRecord.agreed = value;
            });
        }
    };
    $scope.agreeRemark = function(oRemark, value) {
        var url;
        if (oRemark.agreed !== value) {
            url = LS.j('remark/agree', 'site');
            url += '&remark=' + oRemark.id;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRemark.agreed = rsp.data;
            });
        }
    };
    $scope.likeRemark = function(oRemark) {
        var url;
        url = LS.j('remark/like', 'site');
        url += '&remark=' + oRemark.id;
        http2.get(url).then(function(rsp) {
            oRemark.like_log = rsp.data.like_log;
            oRemark.like_num = rsp.data.like_num;
        });
    };
    $scope.dislikeRemark = function(oRemark) {
        var url;
        url = LS.j('remark/dislike', 'site');
        url += '&remark=' + oRemark.id;
        http2.get(url).then(function(rsp) {
            oRemark.dislike_log = rsp.data.dislike_log;
            oRemark.dislike_num = rsp.data.dislike_num;
        });
    };
    $scope.coworkAsRemark = function(oSchema, index) {
        var oRecData, oItem;
        oRecData = $scope.record.verbose[oSchema.id];
        oItem = oRecData.items[index];
        noticebox.confirm('将填写项转为留言，确定？').then(function() {
            http2.get(LS.j('cowork/asRemark', 'site') + '&item=' + oItem.id).then(function(rsp) {
                oRecData.items.splice(index, 1);
                fnAppendRemark(rsp.data);
            });
        });
    };
    $scope.remarkAsCowork = function(oRemark) {
        var url, oSchema;
        url = LS.j('remark/asCowork', 'site');
        url += '&remark=' + oRemark.id;
        if ($scope.coworkSchemas.length === 1) {
            oSchema = $scope.coworkSchemas[0];
            url += '&schema=' + oSchema.id;
            http2.get(url).then(function(rsp) {
                var oItem;
                oItem = rsp.data;
                $scope.record.verbose[oSchema.id].items.push(oItem);
                $location.hash('item-' + oItem.id);
                $timeout(function() {
                    var elItem;
                    $anchorScroll();
                    elItem = document.querySelector('#item-' + oItem.id);
                    elItem.classList.toggle('blink', true);
                    $timeout(function() {
                        elItem.classList.toggle('blink', false);
                    }, 1000);
                });
            });
        } else {
            alert('需要指定对应的题目！');
        }
    };
    $scope.writeRemark = function(oUpperRemark) {
        $uibModal.open({
            templateUrl: 'writeRemark.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {
                    content: ''
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({ content: content });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function(data) {
            addRemark(data.content, oUpperRemark).then(function(rsp) {
                fnAppendRemark(rsp.data, oUpperRemark);
            });
        });
    };
    $scope.editRemark = function(oRemark) {
        $uibModal.open({
            templateUrl: 'writeRemark.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {
                    content: oRemark.content
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({ content: content });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function(data) {
            http2.post(LS.j('remark/update', 'site') + '&remark=' + oRemark.id, { content: data.content }).then(function(rsp) {
                oRemark.content = data.content;
            });
        });
    };
    $scope.removeRemark = function(oRemark) {
        noticebox.confirm('撤销留言，确定？').then(function() {
            http2.post(LS.j('remark/remove', 'site') + '&remark=' + oRemark.id).then(function(rsp) {
                $scope.remarks.splice($scope.remarks.indexOf(oRemark), 1);
            });
        });
    };
    $scope.likeRecord = function() {
        var oRecord;
        oRecord = $scope.record;
        http2.get(LS.j('record/like', 'site', 'ek')).then(function(rsp) {
            oRecord.like_log = rsp.data.like_log;
            oRecord.like_num = rsp.data.like_num;
        });
    };
    $scope.dislikeRecord = function() {
        var oRecord;
        oRecord = $scope.record;
        http2.get(LS.j('record/dislike', 'site', 'ek')).then(function(rsp) {
            oRecord.dislike_log = rsp.data.dislike_log;
            oRecord.dislike_num = rsp.data.dislike_num;
        });
    };
    $scope.editRecord = function(event) {
        if ($scope.record.userid !== $scope.user.uid) {
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
        $scope.gotoPage(event, page, $scope.record.enroll_key);
    };
    $scope.removeRecord = function(event,oRecord) {
        if(oRecord.userid != $scope.user.uid) {
            noticebox.warn('不允许编辑其他用户提交的记录');
            return;
        }
        var url;
        url = '/rest/site/fe/matter/enroll/record/remove?app=' +  _oApp.id + '&ek=' + oRecord.enroll_key;
        http2.get(url).then(function(rsp) {
            if(rsp.data.err_code==0) {
                noticebox.success('删除成功');
            }
        })
    };
    $scope.shareRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app') + '&ek=' + oRecord.enroll_key + '&page=share';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };
    $scope.likeItem = function(oItem) {
        http2.get(LS.j('data/like', 'site') + '&data=' + oItem.id).then(function(rsp) {
            oItem.like_log = rsp.data.like_log;
            oItem.like_num = rsp.data.like_num;
        });
    };
    $scope.dislikeItem = function(oItem) {
        http2.get(LS.j('data/dislike', 'site') + '&data=' + oItem.id).then(function(rsp) {
            oItem.dislike_log = rsp.data.dislike_log;
            oItem.dislike_num = rsp.data.dislike_num;
        });
    };
    $scope.gotoUpper = function(upperId) {
        var elRemark, offsetTop, parentNode;
        elRemark = document.querySelector('#remark-' + upperId);
        offsetTop = elRemark.offsetTop;
        parentNode = elRemark.parentNode;
        while (parentNode && parentNode.tagName !== 'BODY') {
            offsetTop += parentNode.offsetTop;
            parentNode = parentNode.parentNode;
        }
        document.body.scrollTop = offsetTop - 40;
        elRemark.classList.add('blink');
        $timeout(function() {
            elRemark.classList.remove('blink');
        }, 1000);
    };
    /* 关闭任务提示 */
    $scope.closeCoworkTask = function(index) {
        $scope.coworkTasks.splice(index, 1);
    };
    $scope.closeRemarkTask = function(index) {
        $scope.remarkTasks.splice(index, 1);
    };
    $scope.shareRemark = function(oRemark) {
        var url;
        url = LS.j('', 'site', 'app', 'ek') + '&remark=' + oRemark.id + '&page=share';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };
    $scope.gotoAssoc = function(oEntity) {
        var url;
        switch (oEntity.type) {
            case 'record':
                if (oEntity.enroll_key) {
                    url = LS.j('', 'site', 'app', 'page') + '&ek=' + oEntity.enroll_key;
                }
                break;
            case 'topic':
                url = LS.j('', 'site', 'app') + '&page=topic' + '&topic=' + oEntity.id;
                break;
        }
        if (url) {
            location.href = url;
        }
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        $scope.isVisible = params.app.scenarioConfig.hiddenSchemaTitle.cowork;
        var oSchemasById, aCoworkSchemas, aVisibleSchemas;
        _oApp = params.app;
        _oUser = params.user;
        aVisibleSchemas = [];
        aCoworkSchemas = [];
        oSchemasById = {};
        
        console.log($scope.isVisible);
        _oApp.dynaDataSchemas.forEach(function(oSchema) {
            if (oSchema.cowork === 'Y') {
                aCoworkSchemas.push(oSchema);
            } else if (oSchema.shareable && oSchema.shareable === 'Y') {
                aVisibleSchemas.push(oSchema);
            }
            oSchemasById[oSchema.id] = oSchema;
        });
        $scope.schemasById = oSchemasById;
        $scope.visibleSchemas = aVisibleSchemas;
        $scope.coworkSchemas = aCoworkSchemas;
        fnLoadRecord(aCoworkSchemas).then(function(oRecord) {
            if (_oApp.scenarioConfig && _oApp.scenarioConfig.can_assoc === 'Y') {
                fnLoadAssoc(oRecord).then(function() {
                    fnAfterRecordLoad(oRecord, _oUser);
                });
            } else {
                fnAfterRecordLoad(oRecord, _oUser);
            }
        });
    });
}]);
/**
 * 协作题
 */
ngApp.controller('ctrlCoworkData', ['$scope', '$timeout', '$anchorScroll', '$uibModal', 'tmsLocation', 'http2', 'noticebox', function($scope, $timeout, $anchorScroll, $uibModal, LS, http2, noticebox) {
    $scope.canSubmitCowork = true; // 是否允许提交协作数据
    $scope.addItem = function(oSchema) {
        var oCoworkRule;
        if (oCoworkRule = $scope.ruleCowork($scope.record)) {
            noticebox.warn(oCoworkRule.desc);
            return;
        }
        $uibModal.open({
            templateUrl: 'writeItem.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {
                    content: ''
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({ content: content });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function(data) {
            var oRecData, oNewItem, url;
            oRecData = $scope.record.verbose[oSchema.id];
            oNewItem = {
                value: data.content
            };
            url = LS.j('cowork/add', 'site');
            url += '&ek=' + $scope.record.enroll_key + '&schema=' + oSchema.id;
            http2.post(url, oNewItem).then(function(rsp) {
                var oNewItem;
                oNewItem = rsp.data[0];
                oNewItem.nickname = '我';
                if (oRecData) {
                    oRecData.items.push(oNewItem);
                } else if (rsp.data[1]) {
                    oRecData = $scope.record.verbose[oSchema.id] = rsp.data[1];
                    oRecData.items = [oNewItem];
                }
            });
        });
    };
    $scope.editItem = function(oSchema, index) {
        var oRecData, oItem;
        oRecData = $scope.record.verbose[oSchema.id];
        oItem = oRecData.items[index];
        $uibModal.open({
            templateUrl: 'writeItem.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {
                    content: oItem.value
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({ content: content });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function(data) {
            var oNewItem;
            oNewItem = {
                value: data.content
            };
            http2.post(LS.j('cowork/update', 'site') + '&data=' + oRecData.id + '&item=' + oItem.id, oNewItem).then(function(rsp) {
                oItem.value = data.content;
            });
        });
    };
    $scope.removeItem = function(oSchema, index) {
        var oRecData, oItem;
        oRecData = $scope.record.verbose[oSchema.id];
        oItem = oRecData.items[index];
        noticebox.confirm('删除填写项，确定？').then(function() {
            http2.get(LS.j('cowork/remove', 'site') + '&item=' + oItem.id).then(function(rsp) {
                oRecData.items.splice(index, 1);
            });
        });
    };
    $scope.agreeItem = function(oItem, value) {
        console.log(oItem);
        var url;
        if (oItem.agreed !== value) {
            url = LS.j('data/agree', 'site', 'ek') + '&data=' + oItem.id + '&schema=' + oItem.schema_id;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oItem.agreed = value;
            });
        }
    };
    $scope.writeItemRemark = function(oItem) {
        var itemRemarks;
        if ($scope.remarks && $scope.remarks.length) {
            itemRemarks = [];
            $scope.remarks.forEach(function(oRemark) {
                if (oRemark.data_id && oRemark.data_id === oItem.id) {
                    itemRemarks.push(oRemark);
                }
            });
        }
        $uibModal.open({
            templateUrl: 'writeRemark.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.remarks = itemRemarks;
                $scope2.data = {
                    content: ''
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({ content: content });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function(data) {
            http2.post(LS.j('remark/add', 'site', 'ek') + '&data=' + oItem.id, { content: data.content }).then(function(rsp) {
                var oNewRemark;
                oNewRemark = rsp.data;
                oNewRemark.data = oItem;
                oNewRemark.content = oNewRemark.content.replace(/\\n/g, '<br/>');
                $scope.remarks.splice(0, 0, oNewRemark);
                $timeout(function() {
                    var elRemark, parentNode, offsetTop;
                    elRemark = document.querySelector('#remark-' + oNewRemark.id);
                    parentNode = elRemark.parentNode;
                    while (parentNode && parentNode.tagName !== 'BODY') {
                        offsetTop += parentNode.offsetTop;
                        parentNode = parentNode.parentNode;
                    }
                    document.body.scrollTop = offsetTop - 40;
                    elRemark.classList.add('blink');
                    $timeout(function() {
                        elRemark.classList.remove('blink');
                    }, 1000);
                });
            });
        });
    };
    $scope.shareItem = function(oItem) {
        var url, shareby;
        url = LS.j('', 'site', 'app', 'ek') + '&data=' + oItem.id + '&page=share';
        shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };
    $scope.$watch('record', function(oRecord) {
        if (oRecord) {
            $scope.constraint = $scope.ruleCowork(oRecord);
        }
        var oActionRule;
        if ($scope.app) {
            if (oActionRule = $scope.app.actionRule) {
                if (oActionRule.cowork && oActionRule.cowork.submit && oActionRule.cowork.submit.pre && oActionRule.cowork.submit.pre.editor) {
                    if (!$scope.user.is_editor || $scope.user.is_editor !== 'Y') {
                        $scope.canSubmitCowork = false;
                    }
                }
            }
        }
    }, true);
}]);