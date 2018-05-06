'use strict';
require('./favor.css');

var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.controller('ctrlFavor', ['$scope', 'http2', 'tmsLocation', function($scope, http2, LS) {
    $scope.subView = 'repos.html';
    $scope.addTopic = function() {
        http2.get(LS.j('topic/add', 'site', 'app')).then(function(rsp) {
            $scope.$broadcast('xxt.matter.enroll.favor.topic.add', rsp.data);
        });
    };
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        /* 设置页面分享信息 */
        $scope.setSnsShare(); // 应该禁止分享
        /*设置页面导航*/
        var oAppNavs = {};
        if (oApp.can_rank === 'Y') {
            oAppNavs.rank = {};
        }
        if (oApp.scenarioConfig && oApp.scenarioConfig.can_action === 'Y') {
            /* 设置活动事件提醒 */
            http2.get(LS.j('notice/count', 'site', 'app')).then(function(rsp) {
                $scope.noticeCount = rsp.data;
            });
            oAppNavs.action = {};
        }
        if (Object.keys(oAppNavs).length) {
            $scope.appNavs = oAppNavs;
        }
    });
}]);
/**
 * 记录
 */
ngApp.controller('ctrlRepos', ['$scope', '$sce', '$q', '$uibModal', 'http2', 'tmsLocation', '$timeout', 'tmsDynaPage', 'noticebox', function($scope, $sce, $q, $uibModal, http2, LS, $timeout, tmsDynaPage, noticebox) {
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
    var _oApp, _oPage, _oFilter, _oCriteria, _oShareableSchemas, _coworkRequireLikeNum, _oMocker;
    _coworkRequireLikeNum = 0; // 记录获得多少个赞，才能开启协作填写
    $scope.page = _oPage = { at: 1, size: 12, total: 0 };
    $scope.filter = _oFilter = {}; // 过滤条件
    $scope.criteria = _oCriteria = { rid: 'all', creator: false, favored: true, agreed: 'all', orderby: 'lastest' }; // 数据查询条件
    $scope.schemas = _oShareableSchemas = {}; // 支持分享的题目
    $scope.repos = []; // 分享的记录
    $scope.reposLoading = false;
    $scope.recordList = function(pageAt) {
        var url, deferred;
        deferred = $q.defer();
        if (pageAt) {
            _oPage.at = pageAt;
        } else {
            _oPage.at++;
        }
        if (_oPage.at == 1) {
            $scope.repos = [];
            _oPage.total = 0;
        }
        url = LS.j('repos/recordList', 'site', 'app');
        url += '&page=' + _oPage.at + '&size=' + _oPage.size;
        $scope.reposLoading = true;
        http2.post(url, _oCriteria).then(function(result) {
            _oPage.total = result.data.total;
            if (result.data.records) {
                result.data.records.forEach(function(oRecord) {
                    var oSchema, schemaData;
                    for (var schemaId in _oShareableSchemas) {
                        oSchema = _oShareableSchemas[schemaId];
                        if (schemaData = oRecord.data[oSchema.id]) {
                            switch (oSchema.type) {
                                case 'longtext':
                                    oRecord.data[oSchema.id] = ngApp.oUtilSchema.txtSubstitute(schemaData);
                                    break;
                                case 'url':
                                    schemaData._text = ngApp.oUtilSchema.urlSubstitute(schemaData);
                                    break;
                                case 'file':
                                case 'voice':
                                    schemaData.forEach(function(oFile) {
                                        if (oFile.url) {
                                            oFile.url = $sce.trustAsResourceUrl(oFile.url);
                                        }
                                    });
                                    break;
                            }
                        }
                    }
                    if (_coworkRequireLikeNum > oRecord.like_num) {
                        oRecord._coworkRequireLikeNum = (_coworkRequireLikeNum > oRecord.like_num ? _coworkRequireLikeNum - oRecord.like_num : 0);
                    }
                    oRecord._canAgree = fnCanAgreeRecord(oRecord, $scope.user);
                    $scope.repos.push(oRecord);
                });
            }
            tmsDynaPage.loadScript(['/static/js/hammer.min.js', '/asset/js/xxt.ui.picviewer.js']);
            $scope.reposLoading = false;
            deferred.resolve(result);
        });

        return deferred.promise;
    }
    $scope.likeRecord = function(oRecord) {
        var url;
        url = LS.j('record/like', 'site');
        url += '&ek=' + oRecord.enroll_key;
        http2.get(url).then(function(rsp) {
            oRecord.like_log = rsp.data.like_log;
            oRecord.like_num = rsp.data.like_num;
        });
    };
    $scope.remarkRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork#remarks';
        location.href = url;
    };
    $scope.coworkRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork';
        url += '#cowork';
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
            url = LS.j('record/favor', 'site');
            url += '&ek=' + oRecord.enroll_key;
            http2.get(url).then(function(rsp) {
                oRecord.favored = true;
            });
        } else {
            url = LS.j('record/unfavor', 'site');
            url += '&ek=' + oRecord.enroll_key;
            http2.get(url).then(function(rsp) {
                delete oRecord.favored;
            });
        }
    };
    $scope.shareRecord = function(oRecord) {
        location.href = LS.j('', 'site', 'app') + '&ek=' + oRecord.enroll_key + '&page=share';
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
    /* 为什么没有干掉 */
    $scope.value2Label = function(oSchema, value) {
        var val, aVal, aLab = [];
        if (val = value) {
            if (oSchema.ops && oSchema.ops.length) {
                aVal = val.split(',');
                oSchema.ops.forEach(function(op) {
                    aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                });
                val = aLab.join(',');
            }
        } else {
            val = '';
        }
        return $sce.trustAsHtml(val);
    };
    $scope.shiftAgreed = function(agreed) {
        _oCriteria.agreed = agreed;
        $scope.recordList(1);
    };
    /**
     * 选取专题
     */
    $scope.assignTopic = function(oRecord) {
        $uibModal.open({
            templateUrl: 'assignTopic.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var _aCheckedTopicIds;
                _aCheckedTopicIds = [];
                $scope2.checkTopic = function(oTopic, index) {
                    oTopic.checked ? _aCheckedTopicIds.push(oTopic.id) : _aCheckedTopicIds.splice(index, 1);
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() { $mi.close(_aCheckedTopicIds); };
                http2.get(LS.j('topic/byRecord', 'site') + '&record=' + oRecord.id).then(function(rsp) {
                    rsp.data.forEach(function(oTopic) {
                        _aCheckedTopicIds.push(oTopic.topic_id);
                    });
                    http2.get(LS.j('topic/list', 'site', 'app')).then(function(rsp) {
                        rsp.data.topics.forEach(function(oTopic) {
                            oTopic.checked = _aCheckedTopicIds.indexOf(oTopic.id) !== -1;
                        });
                        $scope2.topics = rsp.data.topics;
                    });
                });
            }],
            backdrop: 'static',
            windowClass: 'modal-opt-topic auto-height',
        }).result.then(function(aCheckedTopicIds) {
            http2.post(LS.j('topic/assign', 'site') + '&record=' + oRecord.id, { topic: aCheckedTopicIds }).then(function(rsp) {});
        });
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
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        _oApp = oApp;
        /* 活动任务 */
        if (_oApp.actionRule) {
            /* 开启协作填写需要的点赞数 */
            if (_oApp.actionRule.record && _oApp.actionRule.record.cowork && _oApp.actionRule.record.cowork.pre) {
                if (_oApp.actionRule.record.cowork.pre.record && _oApp.actionRule.record.cowork.pre.record.likeNum !== undefined) {
                    _coworkRequireLikeNum = parseInt(_oApp.actionRule.record.cowork.pre.record.likeNum);
                }
            }
        }
        _oApp.dataSchemas.forEach(function(schema) {
            if (schema.shareable && schema.shareable === 'Y') {
                _oShareableSchemas[schema.id] = schema;
            }
        });
        $scope.recordList(1);
    });
}]);
/**
 * 专题
 */
ngApp.controller('ctrlTopic', ['$scope', '$uibModal', 'http2', 'tmsLocation', 'noticebox', function($scope, $uibModal, http2, LS, noticebox) {
    var _topics;
    $scope.editTopic = function(oTopic) {
        $uibModal.open({
            templateUrl: 'editTopic.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var _oUpdated;
                _oUpdated = {
                    title: oTopic.title,
                    summary: oTopic.summary,
                };
                $scope2.topic = _oUpdated;
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() { $mi.close(_oUpdated); };
            }],
            backdrop: 'static',
            windowClass: 'modal-edit-topic auto-height',
        }).result.then(function(oUpdated) {
            http2.post(LS.j('topic/update', 'site') + '&topic=' + oTopic.id, oUpdated).then(function(rsp) {
                angular.extend(oTopic, oUpdated);
            });
        });
    };
    $scope.removeTopic = function(oTopic, index) {
        noticebox.confirm('删除专题【' + oTopic.title + '】，确定？').then(function() {
            http2.get(LS.j('topic/remove', 'site') + '&topic=' + oTopic.id).then(function(rsp) {
                _topics.splice(index, 1);
            });
        });
    };
    $scope.shareTopic = function(oTopic) {
        location.href = LS.j('', 'site', 'app') + '&topic=' + oTopic.id + '&page=share';
    };
    $scope.$on('xxt.matter.enroll.favor.topic.add', function(event, oNewTopic) {
        _topics.splice(0, 0, oNewTopic);
    });
    http2.get(LS.j('topic/list', 'site', 'app')).then(function(rsp) {
        $scope.topics = _topics = rsp.data.topics;
    });
}]);