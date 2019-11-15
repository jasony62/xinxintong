'use strict';
require('./enroll.public.css');
require('../../../../../../asset/css/buttons.css');
require('../../../../../../asset/js/xxt.ui.image.js');
require('../../../../../../asset/js/xxt.ui.editor.js');
require('./_asset/ui.repos.js');
require('./_asset/ui.tag.js');
require('./_asset/ui.topic.js');
require('./_asset/ui.assoc.js');

require('./_asset/ui.task.js');

window.moduleAngularModules = ['task.ui.enroll', 'editor.ui.xxt', 'repos.ui.enroll', 'tag.ui.enroll', 'topic.ui.enroll', 'assoc.ui.enroll'];

var ngApp = require('./main.js');
ngApp.controller('ctrlCowork', ['$scope', '$q', '$timeout', '$location', '$anchorScroll', '$uibModal', 'tmsLocation', 'http2', 'noticebox', 'enlTag', 'enlTopic', 'enlAssoc', 'enlTask', 'picviewer', function ($scope, $q, $timeout, $location, $anchorScroll, $uibModal, LS, http2, noticebox, enlTag, enlTopic, enlAssoc, enlTask, picviewer) {
    /**
     * 加载整条记录
     */
    function fnLoadRecord(aCoworkSchemas) {
        var oDeferred;
        oDeferred = $q.defer();
        http2.get(LS.j('repos/recordGet', 'site', 'app', 'ek')).then(function (rsp) {
            var oRecord;
            oRecord = rsp.data;
            oRecord._canAgree = fnCanAgreeRecord(oRecord, _oUser);
            $scope.record = oRecord;
            /* 如果有图片，且图片是紧凑的，改为中等尺寸的 */
            if ($scope.imageSchemas && $scope.imageSchemas.length) {
                $scope.imageSchemas.forEach(function (oSchema) {
                    var imageUrls = oRecord.data[oSchema.id]
                    if (imageUrls) {
                        oRecord.data[oSchema.id] = imageUrls.replace('.compact.', '.medium.')
                    }
                })
            }
            /* 设置页面分享信息 */
            $scope.setSnsShare(oRecord, null, {
                target_type: 'cowork',
                target_id: oRecord.id
            });
            /*页面阅读日志*/
            $scope.logAccess({
                target_type: 'cowork',
                target_id: oRecord.id
            });
            /* 加载协作填写数据 */
            if (aCoworkSchemas.length) {
                oRecord.verbose = {};
                fnLoadCowork(oRecord, aCoworkSchemas);
            }
            //
            oDeferred.resolve(oRecord);
        });

        return oDeferred.promise;
    }
    /**
     * 加载关联数据
     */
    function fnLoadAssoc(oRecord, oCachedAssoc) {
        var oDeferred;
        oDeferred = $q.defer();
        http2.get(LS.j('assoc/byRecord', 'site', 'ek')).then(function (rsp) {
            if (rsp.data.length) {
                oRecord.assocs = [];
                rsp.data.forEach(function (oAssoc) {
                    if (oCachedAssoc[oAssoc.entity_a_type] === undefined)
                        oCachedAssoc[oAssoc.entity_a_type] = {};

                    switch (oAssoc.entity_a_type) {
                        case 'record':
                            if (oAssoc.entity_a_id == oRecord.id) {
                                if (oAssoc.log && oAssoc.log.assoc_text) {
                                    oAssoc.assoc_text = oAssoc.log.assoc_text;
                                }
                                oRecord.assocs.push(oAssoc);
                            }
                            break;
                        case 'data':
                            if (oCachedAssoc.data[oAssoc.entity_a_id] === undefined)
                                oCachedAssoc.data[oAssoc.entity_a_id] = [];
                            oCachedAssoc.data[oAssoc.entity_a_id].push(oAssoc);
                            break;
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
        aCoworkSchemas.forEach(function (oSchema) {
            url = LS.j('data/get', 'site', 'ek') + '&schema=' + oSchema.id + '&cascaded=Y';
            http2.get(url, {
                autoBreak: false,
                autoNotice: false
            }).then(function (rsp) {
                var bRequireAnchorScroll;
                oRecord.verbose[oSchema.id] = rsp.data.verbose[oSchema.id];
                oRecord.verbose[oSchema.id].items.forEach(function (oItem) {
                    if (oItem.userid !== $scope.user.uid) {
                        oItem._others = true;
                    }
                    if (anchorItemId && oItem.id === anchorItemId) {
                        bRequireAnchorScroll = true;
                    }
                });
                if (bRequireAnchorScroll) {
                    $timeout(function () {
                        var elItem;
                        $anchorScroll();
                        elItem = document.querySelector('#item-' + anchorItemId);
                        elItem.classList.toggle('blink', true);
                        $timeout(function () {
                            elItem.classList.toggle('blink', false);
                        }, 1000);
                    });
                }
            });
        });
    }

    function fnAfterRecordLoad(oRecord, oUser) {
        /*设置页面导航*/
        $scope.setPopNav(['repos', 'favor', 'rank', 'kanban', 'event'], 'cowork');
        /* 支持图片预览 */
        $timeout(function () {
            var imgs;
            if (imgs = document.querySelectorAll('.data img')) {
                picviewer.init(imgs);
            }
        });
    }
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
        $timeout(function () {
            var elRemark;
            $location.hash('remark-' + oNewRemark.id);
            $anchorScroll();
            elRemark = document.querySelector('#remark-' + oNewRemark.id);
            elRemark.classList.toggle('blink', true);
            $timeout(function () {
                elRemark.classList.toggle('blink', false);
            }, 1000);
        });
    }

    function fnAssignTag(oRecord) {
        enlTag.assignTag(oRecord).then(function (rsp) {
            if (rsp.data.user && rsp.data.user.length) {
                oRecord.userTags = rsp.data.user;
            } else {
                delete oRecord.userTags;
            }
        });
    }

    if (!LS.s().ek) {
        noticebox.error('参数不完整');
        return;
    }
    var _oApp, _oUser, _oAssocs, _shareby;
    _shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
    $scope.options = {
        forQuestionTask: false,
        forAnswerTask: false
    };
    $scope.newRemark = {};
    $scope.assocs = _oAssocs = {};
    $scope.favorStack = {
        guiding: false,
        start: function (record, timer) {
            this.guiding = true;
            this.record = record;
            this.timer = timer;
        },
        end: function () {
            this.guiding = false;
            delete this.record;
            delete this.timer;
        }
    };
    $scope.gotoHome = function () {
        location.href = "/rest/site/fe/matter/enroll?site=" + _oApp.siteid + "&app=" + _oApp.id + "&page=repos";
    };
    $scope.copyRecord = function (oRecord) {
        enlAssoc.copy($scope.app, {
            id: oRecord.id,
            type: 'record'
        });
    };
    $scope.pasteRecord = function (oRecord) {
        enlAssoc.paste($scope.user, oRecord, {
            id: oRecord.id,
            type: 'record'
        }).then(function (oNewAssoc) {
            if (!oRecord.assocs) oRecord.assocs = [];
            if (oNewAssoc.log) oNewAssoc.assoc_text = oNewAssoc.log.assoc_text;
            oRecord.assocs.push(oNewAssoc);
        });

    };
    $scope.removeAssoc = function (oAssoc) {
        noticebox.confirm('取消关联，确定？').then(function () {
            http2.get(LS.j('assoc/unlink', 'site') + '&assoc=' + oAssoc.id).then(function () {
                $scope.record.assocs.splice($scope.record.assocs.indexOf(oAssoc), 1);
            });
        });
    };
    $scope.editAssoc = function (oAssoc) {
        enlAssoc.update($scope.user, oAssoc);
    };
    $scope.favorRecord = function (oRecord) {
        var url;
        if (!oRecord.favored) {
            url = LS.j('favor/add', 'site');
            url += '&ek=' + oRecord.enroll_key;
            http2.get(url).then(function (rsp) {
                oRecord.favored = true;
                $scope.favorStack.start(oRecord, $timeout(function () {
                    $scope.favorStack.end();
                }, 3000));
            });
        } else {
            noticebox.confirm('取消收藏，确定？').then(function () {
                url = LS.j('favor/remove', 'site');
                url += '&ek=' + oRecord.enroll_key;
                http2.get(url).then(function (rsp) {
                    delete oRecord.favored;
                });
            });
        }
    };
    $scope.assignTag = function (oRecord) {
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
        http2.get(LS.j('topic/list', 'site', 'app')).then(function (rsp) {
            var topics;
            if (rsp.data.total === 0) {
                location.href = LS.j('', 'site', 'app') + '&page=favor#topic';
            } else {
                topics = rsp.data.topics;
                enlTopic.assignTopic(oRecord);
            }
        });
    }
    $scope.assignTopic = function (oRecord) {
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
    $scope.setAgreed = function (value) {
        var url, oRecord;
        oRecord = $scope.record;
        if (oRecord.agreed !== value) {
            url = LS.j('record/agree', 'site', 'ek');
            url += '&value=' + value;
            http2.get(url).then(function (rsp) {
                oRecord.agreed = value;
            });
        }
    };
    $scope.coworkAsRemark = function (oSchema, index) {
        var oRecData, oItem;
        oRecData = $scope.record.verbose[oSchema.id];
        oItem = oRecData.items[index];
        noticebox.confirm('将填写项转为留言，确定？').then(function () {
            http2.get(LS.j('cowork/asRemark', 'site') + '&item=' + oItem.id).then(function (rsp) {
                oRecData.items.splice(index, 1);
                fnAppendRemark(rsp.data);
            });
        });
    };
    $scope.remarkAsCowork = function (oRemark) {
        var url, oSchema;
        url = LS.j('remark/asCowork', 'site');
        url += '&remark=' + oRemark.id;
        if ($scope.coworkSchemas.length === 1) {
            oSchema = $scope.coworkSchemas[0];
            url += '&schema=' + oSchema.id;
            http2.get(url).then(function (rsp) {
                var oItem;
                oItem = rsp.data;
                $scope.record.verbose[oSchema.id].items.push(oItem);
                $location.hash('item-' + oItem.id);
                $timeout(function () {
                    var elItem;
                    $anchorScroll();
                    elItem = document.querySelector('#item-' + oItem.id);
                    elItem.classList.toggle('blink', true);
                    $timeout(function () {
                        elItem.classList.toggle('blink', false);
                    }, 1000);
                });
            });
        } else {
            alert('需要指定对应的题目！');
        }
    };
    $scope.listRemark = function (oRecord) {
        $scope.transferParam = {
            0: 'record',
            1: oRecord
        };
        $scope.selectedView.url = '/views/default/site/fe/matter/enroll/template/remark.html';
    };
    $scope.likeRecord = function () {
        if ($scope.setOperateLimit('like')) {
            var oRecord;
            oRecord = $scope.record;
            http2.get(LS.j('record/like', 'site', 'ek')).then(function (rsp) {
                oRecord.like_log = rsp.data.like_log;
                oRecord.like_num = rsp.data.like_num;
            });
        }
    };
    $scope.dislikeRecord = function () {
        if ($scope.setOperateLimit('like')) {
            var oRecord;
            oRecord = $scope.record;
            http2.get(LS.j('record/dislike', 'site', 'ek')).then(function (rsp) {
                oRecord.dislike_log = rsp.data.dislike_log;
                oRecord.dislike_num = rsp.data.dislike_num;
            });
        }
    };
    $scope.editRecord = function (event) {
        if ($scope.app.scenarioConfig.can_cowork !== 'Y' && $scope.record.userid !== $scope.user.uid && $scope.user.is_editor !== 'Y')
            return noticebox.warn('不允许编辑其他用户提交的记录');

        var page = $scope.app.pages.find(p => p.type === 'I')
        if (page)
            $scope.gotoPage(event, page, $scope.record.enroll_key);
    };
    $scope.shareRecord = function (oRecord) {
        var url;
        url = LS.j('', 'site', 'app') + '&ek=' + oRecord.enroll_key + '&page=share';
        if (_shareby) url += '&shareby=' + _shareby;
        location.href = url;
    };
    $scope.doQuestionTask = function (oRecord) {
        if ($scope.questionTasks.length === 1) {
            http2.post(LS.j('topic/assign', 'site') + '&record=' + oRecord.id + '&task=' + $scope.questionTasks[0].id, {}).then(function () {
                noticebox.success('操作成功！');
            });
        }
    };
    $scope.transmitRecord = function (oRecord) {
        $uibModal.open({
            templateUrl: 'transmitRecord.html',
            controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                $scope2.result = {};
                $scope2.transmitConfig = _oApp.transmitConfig;
                $scope2.cancel = function () {
                    $mi.dismiss();
                };
                $scope2.ok = function () {
                    if ($scope2.result.config) {
                        $mi.close($scope2.result);
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function (oResult) {
            var oConfig;
            if ((oConfig = oResult.config) && oConfig.id) {
                http2.get(LS.j('record/transmit', 'site') + '&ek=' + oRecord.enroll_key + '&transmit=' + oConfig.id).then(function (rsp) {
                    var oNewRec;
                    if (oResult.gotoNewRecord) {
                        oNewRec = rsp.data;
                        location.href = LS.j() + '?site=' + oNewRec.site + '&app=' + oNewRec.aid + '&ek=' + oNewRec.enroll_key + '&page=cowork';
                    } else {
                        noticebox.success('记录转发成功！');
                    }
                });
            }
        });
    };
    $scope.gotoUpper = function (upperId) {
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
        $timeout(function () {
            elRemark.classList.remove('blink');
        }, 1000);
    };
    /* 关闭任务提示 */
    $scope.closeCoworkTask = function (index) {
        $scope.coworkTasks.splice(index, 1);
    };
    $scope.closeRemarkTask = function (index) {
        $scope.remarkTasks.splice(index, 1);
    };
    $scope.gotoAssoc = function (oEntity) {
        var url;
        switch (oEntity.type) {
            case 'record':
                if (oEntity.enroll_key) url = LS.j('', 'site', 'app', 'page') + '&ek=' + oEntity.enroll_key;
                break;
            case 'topic':
                url = LS.j('', 'site', 'app') + '&page=topic' + '&topic=' + oEntity.id;
                break;
            case 'article':
                if (oEntity.entryUrl) url = oEntity.entryUrl;
                break;
        }
        if (url) location.href = url;
    };
    $scope.$on('transfer.param', function (event, data) {
        $scope.transferParam = data;
    });
    $scope.$on('xxt.app.enroll.ready', function (event, params) {
        var oSchemasById, aImageSchemas, aCoworkSchemas, aVisibleSchemas, templateUrl;
        _oApp = params.app;
        _oUser = params.user;
        aVisibleSchemas = [];
        aImageSchemas = [];
        aCoworkSchemas = [];
        oSchemasById = {};
        _oApp.dynaDataSchemas.forEach(function (oSchema) {
            if (oSchema.cowork === 'Y') {
                aCoworkSchemas.push(oSchema);
            } else if (oSchema.shareable && oSchema.shareable === 'Y') {
                aVisibleSchemas.push(oSchema);
            }
            if (oSchema.type === 'image') {
                aImageSchemas.push(oSchema)
            }
            oSchemasById[oSchema.id] = oSchema;
        });
        $scope.schemasById = oSchemasById;
        $scope.visibleSchemas = aVisibleSchemas;
        $scope.imageSchemas = aImageSchemas;
        $scope.coworkSchemas = aCoworkSchemas;
        if (aCoworkSchemas.length) {
            $scope.fileName = 'coworkData';
        } else {
            $scope.fileName = 'remark';
        }
        templateUrl = '/views/default/site/fe/matter/enroll/template/record-' + $scope.fileName + '.html?_=1'
        $scope.selectedView = {
            'url': templateUrl
        };
        fnLoadRecord(aCoworkSchemas).then(function (oRecord) {
            /* 通过留言完成提问任务 */
            new enlTask($scope.app).list('question', 'IP').then(function (tasks) {
                $scope.questionTasks = tasks;
            });
            new enlTask($scope.app).list('answer', 'IP', null, oRecord.enroll_key).then(function (tasks) {
                $scope.answerTasks = tasks;
            });
            if (_oApp.scenarioConfig && _oApp.scenarioConfig.can_assoc === 'Y') {
                fnLoadAssoc(oRecord, _oAssocs).then(function () {
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
ngApp.controller('ctrlCoworkData', ['$scope', '$timeout', '$anchorScroll', '$uibModal', 'tmsLocation', 'http2', 'noticebox', 'enlAssoc', function ($scope, $timeout, $anchorScroll, $uibModal, LS, http2, noticebox, enlAssoc) {
    $scope.addItem = function (oSchema) {
        if ($scope.setOperateLimit('add_cowork')) {
            $uibModal.open({
                templateUrl: 'writeItem.html',
                controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                    $scope2.data = {
                        content: ''
                    };
                    $scope2.cancel = function () {
                        $mi.dismiss();
                    };
                    $scope2.ok = function () {
                        var content;
                        if (window.tmsEditor && window.tmsEditor.finish) {
                            content = window.tmsEditor.finish();
                            $scope2.data.content = content;
                            $mi.close({
                                content: content
                            });
                        }
                    };
                }],
                windowClass: 'modal-remark auto-height',
                backdrop: 'static',
            }).result.then(function (data) {
                if (!data.content) return;
                var oRecData, oNewItem, url;
                oRecData = $scope.record.verbose[oSchema.id];
                oNewItem = {
                    value: data.content
                };
                url = LS.j('cowork/add', 'site');
                url += '&ek=' + $scope.record.enroll_key + '&schema=' + oSchema.id;
                if ($scope.options.forAnswerTask) url += '&task=' + $scope.options.forAnswerTask;
                http2.post(url, oNewItem).then(function (rsp) {
                    var oNewItem;
                    oNewItem = rsp.data.oNewItem;
                    oNewItem.nickname = '我';
                    if (oRecData) {
                        oRecData.items.push(oNewItem);
                    } else if (rsp.data.oRecData) {
                        oRecData = $scope.record.verbose[oSchema.id] = rsp.data.oRecData;
                        oRecData.items = [oNewItem];
                    }
                    if (rsp.data.coworkResult.user_total_coin) {
                        noticebox.info('您获得【' + rsp.data.coworkResult.user_total_coin + '】分');
                    }
                });
            });
        }
    };
    $scope.editItem = function (oSchema, index) {
        var oRecData, oItem;
        oRecData = $scope.record.verbose[oSchema.id];
        oItem = oRecData.items[index];
        $uibModal.open({
            templateUrl: 'writeItem.html',
            controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                $scope2.data = {
                    content: oItem.value
                };
                $scope2.cancel = function () {
                    $mi.dismiss();
                };
                $scope2.ok = function () {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({
                            content: content
                        });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function (data) {
            if (!data.content) return;
            var oNewItem;
            oNewItem = {
                value: data.content
            };
            http2.post(LS.j('cowork/update', 'site') + '&data=' + oRecData.id + '&item=' + oItem.id, oNewItem).then(function (rsp) {
                oItem.value = data.content;
            });
        });
    };
    $scope.removeItem = function (oSchema, index) {
        var oRecData, oItem;
        oRecData = $scope.record.verbose[oSchema.id];
        oItem = oRecData.items[index];
        noticebox.confirm('删除填写项，确定？').then(function () {
            http2.get(LS.j('cowork/remove', 'site') + '&item=' + oItem.id).then(function (rsp) {
                oRecData.items.splice(index, 1);
            });
        });
    };
    $scope.agreeItem = function (oItem, value) {
        var url;
        if (oItem.agreed !== value) {
            url = LS.j('data/agree', 'site', 'ek') + '&data=' + oItem.id + '&schema=' + oItem.schema_id;
            url += '&value=' + value;
            http2.get(url).then(function (rsp) {
                oItem.agreed = value;
            });
        }
    };
    $scope.likeItem = function (oItem) {
        if ($scope.setOperateLimit('like')) {
            http2.get(LS.j('data/like', 'site') + '&data=' + oItem.id).then(function (rsp) {
                oItem.like_log = rsp.data.like_log;
                oItem.like_num = rsp.data.like_num;
            });
        }
    };
    $scope.dislikeItem = function (oItem) {
        if ($scope.setOperateLimit('like')) {
            http2.get(LS.j('data/dislike', 'site') + '&data=' + oItem.id).then(function (rsp) {
                oItem.dislike_log = rsp.data.dislike_log;
                oItem.dislike_num = rsp.data.dislike_num;
            });
        }
    };
    $scope.listItemRemark = function (oItem) {
        $scope.$emit('transfer.param', {
            0: 'coworkData',
            1: oItem
        });
        $scope.selectedView.url = '/views/default/site/fe/matter/enroll/template/remark.html';
    };
    $scope.shareItem = function (oItem) {
        var url, shareby;
        url = LS.j('', 'site', 'app', 'ek') + '&data=' + oItem.id + '&page=share';
        shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };
    $scope.assocMatter = function (oItem) {
        enlAssoc.assocMatter($scope.user, $scope.record, {
            id: oItem.id,
            type: 'data'
        }).then(function (oAssoc) {
            var oCachedAssoc;
            oCachedAssoc = $scope.assocs;
            if (oCachedAssoc.data === undefined)
                oCachedAssoc.data = {};
            if (oCachedAssoc.data[oItem.id] === undefined)
                oCachedAssoc.data[oItem.id] = [];
            oCachedAssoc.data[oItem.id].push(oAssoc);
        });
    };
    $scope.removeItemAssoc = function (oItem, oAssoc) {
        noticebox.confirm('取消关联，确定？').then(function () {
            http2.get(LS.j('assoc/unlink', 'site') + '&assoc=' + oAssoc.id).then(function () {
                $scope.assocs.data[oItem.id].splice($scope.assocs.data[oItem.id].indexOf(oAssoc), 1);
            });
        });
    };
    $scope.doAnswerTask = function (oItem) {
        if ($scope.answerTasks && $scope.answerTasks.length) {
            if ($scope.answerTasks.length === 1) {
                http2.post(LS.j('topic/assign', 'site') + '&record=' + $scope.record.id + '&data=' + oItem.id + '&task=' + $scope.answerTasks[0].id, {}).then(function () {
                    noticebox.success('操作成功！');
                });
            }
        }
    };
}]);
/**
 * 留言
 */
ngApp.controller('ctrlRemark', ['$scope', '$location', '$uibModal', '$anchorScroll', '$timeout', 'http2', 'tmsLocation', 'noticebox', 'picviewer', function ($scope, $location, $uibModal, $anchorScroll, $timeout, http2, LS, noticebox, picviewer) {
    function addRemark(content, oRemark) {
        var url;
        url = LS.j('remark/add', 'site', 'ek', 'data');
        if (oRemark) url += '&remark=' + oRemark.id;
        if ($scope.options.forQuestionTask) url += '&task=' + $scope.options.forQuestionTask;

        return http2.post(url, {
            content: content
        });
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
        $timeout(function () {
            var elRemark;
            $location.hash('remark-' + oNewRemark.id);
            $anchorScroll();
            elRemark = document.querySelector('#remark-' + oNewRemark.id);
            elRemark.classList.toggle('blink', true);
            /* 支持图片预览 */
            $timeout(function () {
                var imgs;
                if (imgs = document.querySelectorAll('#remark-' + oNewRemark.id + ' img')) {
                    picviewer.init(imgs);
                }
            });
            $timeout(function () {
                elRemark.classList.toggle('blink', false);
            }, 1000);
        });
    }

    function listRemarks(type, data) {
        var url;
        url = LS.j('remark/list', 'site', 'ek', 'schema', 'data');

        if (type == 'record') {
            url += '&onlyRecord=true';
        } else if (type == 'coworkData') {
            url += data.id;
        }

        http2.get(url).then(function (rsp) {
            var remarks, oRemark, oUpperRemark, oCoworkRemark, oRemarks;
            remarks = rsp.data.remarks;
            if (remarks && remarks.length) {
                oRemarks = {};
                remarks.forEach(function (oRemark) {
                    oRemarks[oRemark.id] = oRemark;
                });
                for (var i = remarks.length - 1; i >= 0; i--) {
                    oRemark = remarks[i];
                    if (oRemark.content) {
                        oRemark.content = oRemark.content.replace(/\n/g, '<br/>');
                    }
                    if (oRemark.remark_id !== '0') {
                        if (oUpperRemark = oRemarks[oRemark.remark_id]) {
                            oRemark.reply = '<a href="#remark-' + oRemark.remark_id + '">回复' + oUpperRemark.nickname + '的留言 #' + (oRemark.data_id === '0' ? oUpperRemark.seq_in_record : oUpperRemark.seq_in_data) + '</a>';
                        }
                    }
                }
            }
            $scope.remarks = remarks;
            /* 支持图片预览 */
            $timeout(function () {
                var imgs;
                if (imgs = document.querySelectorAll('#remarks img')) {
                    picviewer.init(imgs);
                }
            });
            if ($location.hash() === 'remarks') {
                $timeout(function () {
                    $anchorScroll.yOffset = 30;
                    $anchorScroll();
                });
            } else if (/remark-.+/.test($location.hash())) {
                $timeout(function () {
                    var elRemark;
                    if (elRemark = document.querySelector('#' + $location.hash())) {
                        $anchorScroll();
                        elRemark.classList.toggle('blink', true);
                        $timeout(function () {
                            elRemark.classList.toggle('blink', false);
                        }, 1000);
                    }
                });
            }
        });
    }

    function writeRemark(oUpperRemark) {
        if ($scope.setOperateLimit('add_remark')) {
            $uibModal.open({
                templateUrl: 'writeRemark.html',
                controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                    $scope2.data = {
                        content: ''
                    };
                    $scope2.cancel = function () {
                        $mi.dismiss();
                    };
                    $scope2.ok = function () {
                        var content;
                        if (window.tmsEditor && window.tmsEditor.finish) {
                            content = window.tmsEditor.finish();
                            $scope2.data.content = content;
                            $mi.close({
                                content: content
                            });
                        }
                    };
                }],
                windowClass: 'modal-remark auto-height',
                backdrop: 'static',
            }).result.then(function (data) {
                if (!data.content) return;
                addRemark(data.content, oUpperRemark).then(function (rsp) {
                    fnAppendRemark(rsp.data, oUpperRemark);
                    if (rsp.data.remarkResult.user_total_coin) {
                        noticebox.info('您获得【' + rsp.data.remarkResult.user_total_coin + '】分');
                    }
                });
            });
        }
    }

    function writeItemRemark(oItem) {
        if ($scope.setOperateLimit('add_remark')) {
            var itemRemarks;
            if ($scope.remarks && $scope.remarks.length) {
                itemRemarks = [];
                $scope.remarks.forEach(function (oRemark) {
                    if (oRemark.data_id && oRemark.data_id === oItem.id) {
                        itemRemarks.push(oRemark);
                    }
                });
            }
            $uibModal.open({
                templateUrl: 'writeRemark.html',
                controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                    $scope2.remarks = itemRemarks;
                    $scope2.data = {
                        content: ''
                    };
                    $scope2.cancel = function () {
                        $mi.dismiss();
                    };
                    $scope2.ok = function () {
                        var content;
                        if (window.tmsEditor && window.tmsEditor.finish) {
                            content = window.tmsEditor.finish();
                            $scope2.data.content = content;
                            $mi.close({
                                content: content
                            });
                        }
                    };
                }],
                windowClass: 'modal-remark auto-height',
                backdrop: 'static',
            }).result.then(function (data) {
                if (!data.content) return;
                http2.post(LS.j('remark/add', 'site', 'ek') + '&data=' + oItem.id, {
                    content: data.content
                }).then(function (rsp) {
                    var oNewRemark;
                    oNewRemark = rsp.data;
                    oNewRemark.data = oItem;
                    oNewRemark.content = oNewRemark.content.replace(/\\n/g, '<br/>');
                    $scope.remarks.splice(0, 0, oNewRemark);
                    $timeout(function () {
                        var elRemark, parentNode, offsetTop;
                        elRemark = document.querySelector('#remark-' + oNewRemark.id);
                        parentNode = elRemark.parentNode;
                        while (parentNode && parentNode.tagName !== 'BODY') {
                            offsetTop += parentNode.offsetTop;
                            parentNode = parentNode.parentNode;
                        }
                        document.body.scrollTop = offsetTop - 40;
                        elRemark.classList.add('blink');
                        if (rsp.data.remarkResult.user_total_coin) {
                            noticebox.info('您获得【' + rsp.data.remarkResult.user_total_coin + '】分');
                        }
                        $timeout(function () {
                            elRemark.classList.remove('blink');
                        }, 1000);
                    });
                });
            });
        }
    }

    $scope.goback = function () {
        var templateUrl = '/views/default/site/fe/matter/enroll/template/record-' + $scope.fileName + '.html';
        $scope.selectedView.url = templateUrl;
    };
    $scope.editRemark = function (oRemark) {
        $uibModal.open({
            templateUrl: 'writeRemark.html',
            controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                $scope2.data = {
                    content: oRemark.content
                };
                $scope2.cancel = function () {
                    $mi.dismiss();
                };
                $scope2.ok = function () {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({
                            content: content
                        });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function (data) {
            http2.post(LS.j('remark/update', 'site') + '&remark=' + oRemark.id, {
                content: data.content
            }).then(function (rsp) {
                oRemark.content = data.content;
            });
        });
    };
    $scope.removeRemark = function (oRemark) {
        noticebox.confirm('撤销留言，确定？').then(function () {
            http2.post(LS.j('remark/remove', 'site') + '&remark=' + oRemark.id).then(function (rsp) {
                $scope.remarks.splice($scope.remarks.indexOf(oRemark), 1);
            });
        });
    };
    $scope.agreeRemark = function (oRemark, value) {
        var url;
        if (oRemark.agreed !== value) {
            url = LS.j('remark/agree', 'site');
            url += '&remark=' + oRemark.id;
            url += '&value=' + value;
            http2.get(url).then(function (rsp) {
                oRemark.agreed = rsp.data;
            });
        }
    };
    $scope.likeRemark = function (oRemark) {
        if ($scope.setOperateLimit('like')) {
            var url;
            url = LS.j('remark/like', 'site');
            url += '&remark=' + oRemark.id;
            http2.get(url).then(function (rsp) {
                oRemark.like_log = rsp.data.like_log;
                oRemark.like_num = rsp.data.like_num;
            });
        }
    };
    $scope.dislikeRemark = function (oRemark) {
        if ($scope.setOperateLimit('like')) {
            var url;
            url = LS.j('remark/dislike', 'site');
            url += '&remark=' + oRemark.id;
            http2.get(url).then(function (rsp) {
                oRemark.dislike_log = rsp.data.dislike_log;
                oRemark.dislike_num = rsp.data.dislike_num;
            });
        }
    };
    $scope.shareRemark = function (oRemark) {
        var url;
        url = LS.j('', 'site', 'app', 'ek') + '&remark=' + oRemark.id + '&page=share';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };
    $scope.writeRemark = function (oUpperRemark) {
        if (oUpperRemark) {
            writeRemark(oUpperRemark);
        } else {
            if (!oType) {
                writeRemark();
            } else {
                switch (oType) {
                    case 'record':
                        writeRemark();
                        break;
                    case 'coworkData':
                        writeItemRemark(oData);
                        break;
                    default:
                        break;
                }
            }
        }
    };
    var oType, oData;
    $scope.$watch('transferParam', function (nv) {
        if (!nv) {
            return false;
        }
        $scope.transferType = oType = nv[0];
        $scope.transferData = oData = nv[1];
        switch (oType) {
            case 'record':
                listRemarks('record');
                break;
            case 'coworkData':
                listRemarks('coworkData', oData);
                break;
            default:
                break;
        }
    });
    if ($scope.fileName == 'remark') {
        $scope.$watch('record', function (oRecord) {
            if (oRecord) {
                listRemarks();
            }
        }, true);
    }
}]);