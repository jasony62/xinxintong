'use strict';
require('./enroll.public.css');

require('./_asset/ui.repos.js');
require('./_asset/ui.tag.js');
require('./_asset/ui.topic.js');
require('./_asset/ui.assoc.js');
require('./_asset/ui.round.js');
require('./_asset/ui.dropdown.js');
require('./_asset/ui.filter.js');

window.moduleAngularModules = ['filter.ui', 'dropdown.ui', 'round.ui.enroll', 'repos.ui.enroll', 'tag.ui.enroll', 'topic.ui.enroll', 'assoc.ui.enroll'];

var ngApp = require('./main.js');
ngApp.controller('ctrlRepos', ['$scope', '$parse', '$sce', '$q', '$uibModal', 'http2', 'tmsLocation', 'enlRound', '$timeout', 'picviewer', 'noticebox', 'enlTag', 'enlTopic', 'enlAssoc', 'enlService', function($scope, $parse, $sce, $q, $uibModal, http2, LS, enlRound, $timeout, picviewer, noticebox, enlTag, enlTopic, enlAssoc, enlService) {
    /* 是否可以对记录进行表态 */
    function fnCanAgreeRecord(oRecord, oUser) {
        if (_oMocker && _oMocker.role && /visitor|member/.test(_oMocker.role)) {
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
    var _oApp, _facRound, _oPage, _oFilter, _oCriteria, _oShareableSchemas, _coworkRequireLikeNum, _oTasks, _oMocker, _oUser;
    _coworkRequireLikeNum = 0; // 记录获得多少个赞，才能开启协作填写
    $scope.page = _oPage = {};
    $scope.filter = _oFilter = { isFilter: false }; // 过滤条件
    $scope.criteria = _oCriteria = {}; // 数据查询条件
    $scope.schemas = _oShareableSchemas = {}; // 支持分享的题目
    $scope.repos = []; // 分享的记录
    $scope.reposLoading = false;
    $scope.appendToEle = angular.element(document.querySelector('#filterQuick'));
    $scope.recordList = function(pageAt) {
        var url, deferred;
        deferred = $q.defer();
        pageAt ? _oPage.at = pageAt : _oPage.at++;
        if (_oPage.at == 1) {
            $scope.repos = [];
            _oPage.total = 0;
        }
        url = LS.j('repos/recordList', 'site', 'app');
        if (_oMocker && _oMocker.role) {
            url += '&role=' + _oMocker.role;
        }
        $scope.reposLoading = true;
        $scope.flag = false;
        http2.post(url, _oCriteria, { page: _oPage }).then(function(result) {
            if (result.data.records) {
                result.data.records.forEach(function(oRecord) {
                    if (_coworkRequireLikeNum > oRecord.like_num) {
                        oRecord._coworkRequireLikeNum = (_coworkRequireLikeNum > oRecord.like_num ? _coworkRequireLikeNum - oRecord.like_num : 0);
                    }
                    oRecord._canAgree = fnCanAgreeRecord(oRecord, _oUser);
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
    }
    $scope.likeRecord = function(oRecord) {
        if ($scope.setOperateLimit('like')) {
            var url;
            url = LS.j('record/like', 'site');
            url += '&ek=' + oRecord.enroll_key;
            http2.get(url).then(function(rsp) {
                oRecord.like_log = rsp.data.like_log;
                oRecord.like_num = rsp.data.like_num;
            });
        }
    };
    $scope.dislikeRecord = function(oRecord) {
        if ($scope.setOperateLimit('like')) {
            var url;
            url = LS.j('record/dislike', 'site');
            url += '&ek=' + oRecord.enroll_key;
            http2.get(url).then(function(rsp) {
                oRecord.dislike_log = rsp.data.dislike_log;
                oRecord.dislike_num = rsp.data.dislike_num;
            });
        }
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
    };
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
    };
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
    $scope.shareRecord = function(oRecord) {
        var url, shareby;
        url = LS.j('', 'site', 'app') + '&ek=' + oRecord.enroll_key + '&page=share';
        shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };
    $scope.addRecord = function(event) {
        $scope.$parent.addRecord(event);
    };
    $scope.editRecord = function(event, oRecord) {
        if (oRecord.userid !== _oUser.uid) {
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
    $scope.copyRecord = function(event, oRecord) {
        enlAssoc.copy($scope.app, { id: oRecord.id, type: 'record' });
    };
    $scope.confirm = function(filterOpt) {
        _oFilter = angular.extend(_oFilter, filterOpt.filter);
        _oCriteria = angular.extend(_oCriteria, filterOpt.criteria);
        $scope.recordList(1);
    };
    $scope.shiftMenu = function(criteria) {
        _oCriteria[criteria.type] = criteria.id;
        $scope.recordList(1);
    };
    $scope.shiftTip = function(type) {
        _oCriteria[type] = _oFilter[type] = null;
        function objectKeyIsNull(obj) {
            var empty = null;
            for (var i in obj) {
                if (i !== 'isFilter' && i !== 'tags') {
                    if (obj[i] === null) {
                        empty = true;
                    } else {
                        empty = false;
                        break;
                    }
                }

            }
            return empty;
        }
        if (objectKeyIsNull(_oFilter)) {
            _oFilter.isFilter = false;
        }
        $scope.recordList(1);
    }
    $scope.shiftTag = function(oTag, bToggle) {
        if (bToggle) {
            if (!_oFilter.tags) {
                _oFilter.tags = [oTag];
            } else {
                if (_oFilter.tags.indexOf(oTag) === -1) {
                    _oFilter.tags.push(oTag);
                }
            }
            if (!_oCriteria.tags) {
                _oCriteria.tags = [oTag.tag_id];
            } else {
                if (_oCriteria.tags.indexOf(oTag.tag_id) === -1) {
                    _oCriteria.tags.push(oTag.tag_id);
                }
            }
        } else {
            _oFilter.tags.splice(_oFilter.tags.indexOf(oTag), 1);
            _oCriteria.tags.splice(_oFilter.tags.indexOf(oTag.tag_id), 1);
        }
        $scope.recordList(1);
    };
    $scope.dirLevel = {
        active: function(oDir, level) {
            if (oDir) {
                oDir.opened = true;
                switch (level) {
                    case 1:
                        $scope.activeDir1 = oDir;
                        $scope.activeDir2 = '';
                        $scope.activeDir3 = '';
                        $scope.activeDir4 = '';
                        $scope.activeDir5 = '';
                        break;
                    case 2:
                        $scope.activeDir2 = oDir;
                        $scope.activeDir3 = '';
                        $scope.activeDir4 = '';
                        $scope.activeDir5 = '';
                        break;
                    case 3:
                        $scope.activeDir3 = oDir;

                        $scope.activeDir4 = '';
                        $scope.activeDir5 = '';
                        break;
                    case 4:
                        $scope.activeDir4 = oDir;

                        $scope.activeDir5 = '';
                        break;
                    case 5:
                        $scope.activeDir5 = oDir;
                        break;
                    default:
                }
            } else {
                $scope.activeDir1 = '';
                $scope.activeDir2 = '';
                $scope.activeDir3 = '';
                $scope.activeDir4 = '';
                $scope.activeDir5 = '';
            }
        },
        hideDir: function(oDir) {
            oDir.opened = false;
        },
        showDir: function(oDir) {
            oDir.opened = true;
        }
    };
    $scope.shiftDir = function(oDir, level) {
        _oCriteria.data = {};
        if (oDir) {
            _oCriteria.data[oDir.schema_id] = oDir.op.v;
            $scope.dirLevel.active(oDir, level);
        } else {
            $scope.dirLevel.active();
        }
        $scope.recordList(1);
    }
    /* 关闭任务提示 */
    $scope.closeTask = function(index) {
        $scope.tasks.splice(index, 1);
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
    $scope.voteRecData = function() {
        $uibModal.open({
            template: require('./_asset/vote-rec-data.html'),
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.vote = function(oRecData) {
                    http2.get(LS.j('task/vote', 'site') + '&data=' + oRecData.id).then(function(rsp) {
                        oRecData.voteResult.vote_num++;
                        oRecData.voteResult.vote_at = rsp.data[0].vote_at;
                        var remainder = rsp.data[1][0] - rsp.data[1][1];
                        if (remainder > 0) {
                            noticebox.success('还需要投出【' + remainder + '】票');
                        } else {
                            noticebox.success('已完成全部投票');
                        }
                    });
                };
                $scope2.unvote = function(oRecData) {
                    http2.get(LS.j('task/unvote', 'site') + '&data=' + oRecData.id).then(function(rsp) {
                        oRecData.voteResult.vote_num--;
                        oRecData.voteResult.vote_at = 0;
                        var remainder = rsp.data[0] - rsp.data[1];
                        if (remainder > 0) {
                            noticebox.success('还需要投出【' + remainder + '】票');
                        } else {
                            noticebox.success('已完成全部投票');
                        }
                    });
                };
                http2.get(LS.j('task/votingRecData', 'site', 'app')).then(function(rsp) {
                    $scope2.votingRecDatas = rsp.data[Object.keys(rsp.data)[0]];
                });
            }],
            backdrop: 'static',
            windowClass: 'auto-height'
        });
    };
    $scope.scoreSchema = function() {
        var _oScoreApp;
        _oScoreApp = $parse('score.configs[0].scoreApp')(_oTasks);
        if (!_oScoreApp || !_oScoreApp.id) return;
        $uibModal.open({
            template: require('./_asset/score-app.html'),
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var _oData, _oScoreRecord;
                $scope2.data = _oData = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.score = function(oSchema, opIndex, number) {
                    var oOption;

                    if (!(oOption = oSchema.ops[opIndex])) return;

                    if (_oData[oSchema.id] === undefined) {
                        _oData[oSchema.id] = {};
                        oSchema.ops.forEach(function(oOp) {
                            _oData[oSchema.id][oOp.v] = 0;
                        });
                    }

                    _oData[oSchema.id][oOption.v] = number;
                };
                $scope2.lessScore = function(oSchema, opIndex, number) {
                    var oOption;

                    if (!(oOption = oSchema.ops[opIndex])) return false;
                    if (_oData[oSchema.id] === undefined) {
                        return false;
                    }
                    return _oData[oSchema.id][oOption.v] >= number;
                };
                $scope2.submit = function() {
                    var url;
                    url = LS.j('record/submit', 'site') + '&app=' + _oScoreApp.id;
                    if (_oScoreRecord)
                        url += '&ek=' + _oScoreRecord.enroll_key;
                    http2.post(url, { data: _oData }, { autoBreak: false }).then(function(rsp) {
                        http2.post(LS.j('marks/renewReferScore', 'site') + '&app=' + _oScoreApp.id, {
                            /* 如何更新页面上已有的数据？ */
                        });
                    });
                };
                http2.get(LS.j('get', 'site') + '&app=' + _oScoreApp.id).then(function(rsp) {
                    _oScoreApp = rsp.data.app;
                    $scope2.schemas = _oScoreApp.dynaDataSchemas;
                    http2.get(LS.j('record/get', 'site') + '&app=' + _oScoreApp.id).then(function(rsp) {
                        if (rsp.data.enroll_key) {
                            _oScoreRecord = rsp.data;
                            http2.merge(_oData, _oScoreRecord.data);
                        }
                    });
                });
            }],
            backdrop: 'static',
            windowClass: 'auto-height'
        });
    };
    /* 显示公共专题列表 */
    $scope.showTopics = function(event) {
        $uibModal.open({
            template: require('./_asset/topic-list.html'),
            resolve: {
                topics: function() { return $scope.topics; }
            },
            controller: ['$scope', '$uibModalInstance', 'topics', function($scope2, $mi, topics) {
                $scope2.topics = topics;
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.gotoTopic = function(oTopic) {
                    location.href = LS.j('', 'site', 'app') + '&topic=' + oTopic.id + '&page=topic';
                };
            }],
            backdrop: 'static',
            windowClass: 'auto-height'
        });
    };
    $scope.advCriteriaStatus = {
        opened: !$scope.isSmallLayout,
        dirOpen: false
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        /* 活动任务 */
        var tasks, popActs;
        if (_oApp.actionRule) {
            /* 设置活动任务提示 */
            http2.get(LS.j('event/task', 'site', 'app')).then(function(rsp) {
                if (rsp.data && rsp.data.length) {
                    rsp.data.forEach(function(oRule) {
                        if (!oRule._ok) {
                            tasks.push({ type: 'info', msg: oRule.desc, id: oRule.id, gap: oRule._no ? oRule._no[0] : 0, coin: oRule.coin ? oRule.coin : 0 });
                        }
                    });
                }
            });
            /* 开启协作填写需要的点赞数 */
            if (_oApp.actionRule.record && _oApp.actionRule.record.cowork && _oApp.actionRule.record.cowork.pre) {
                if (_oApp.actionRule.record.cowork.pre.record && _oApp.actionRule.record.cowork.pre.record.likeNum !== undefined) {
                    _coworkRequireLikeNum = parseInt(_oApp.actionRule.record.cowork.pre.record.likeNum);
                }
            }
        }
        http2.get(LS.j('task/list', 'site', 'app')).then(function(rsp) {
            _oTasks = rsp.data;
            if (rsp.data.vote) {
                tasks.push({ type: 'info', msg: '有投票任务', id: 'record.data.vote' });
                popActs.push('voteRecData');
            }
            if (rsp.data.score) {
                tasks.push({ type: 'info', msg: '有打分任务', id: 'record.data.score' });
                popActs.push('scoreSchema');
            }
        });
        $scope.tasks = tasks = [];
        _oApp.dynaDataSchemas.forEach(function(oSchema) {
            if (oSchema.shareable && oSchema.shareable === 'Y')
                _oShareableSchemas[oSchema.id] = oSchema;
        });
        var groupOthersById = {};
        if (params.groupOthers && params.groupOthers.length) {
            params.groupOthers.forEach(function(oOther) {
                groupOthersById[oOther.userid] = oOther;
            });
        }
        $scope.groupOthers = groupOthersById;
        $scope.facRound = _facRound = new enlRound(_oApp);
        _facRound.list().then(function(result) {
            $scope.rounds = result.rounds;
        });
        if (_oApp.reposConfig && _oApp.reposConfig.defaultOrder) {
            _oCriteria.orderby = _oApp.reposConfig.defaultOrder;
        }
        /* 作为分类目录的题目 */
        http2.get(LS.j('repos/dirSchemasGet', 'site', 'app')).then(function(rsp) {
            $scope.dirSchemas = rsp.data;
            if ($scope.dirSchemas && $scope.dirSchemas.length) {
                $scope.advCriteriaStatus.dirOpen = true;
                var fnSetParentDir = function(oDir) {
                    if (oDir.op && oDir.op.childrenDir && oDir.op.childrenDir.length) {
                        oDir.op.childrenDir.forEach(function(oChildDir) {
                            oChildDir.parentDir = oDir;
                            fnSetParentDir(oChildDir);
                        });
                    }
                };
                $scope.dirSchemas.forEach(function(oDir) {
                    oDir.opened = false;
                    fnSetParentDir(oDir);
                });
            }
        });
        /* 共享专题 */
        http2.get(LS.j('topic/listPublic', 'site', 'app')).then(function(rsp) {
            if (rsp.data && rsp.data.topics) {
                $scope.topics = rsp.data.topics;
            }
        });
        /* 设置页面分享信息 */
        $scope.setSnsShare(null, null, { target_type: 'repos', target_id: _oApp.id });
        /* 设置页面操作 */
        popActs = ['addRecord', 'mocker'];
        $scope.setPopAct(popActs, 'repos', {
            func: {
                voteRecData: $scope.voteRecData,
                scoreSchema: $scope.scoreSchema,
            }
        });
        /* 设置页面导航 */
        $scope.setPopNav(['rank', 'kanban', 'event', 'favor'], 'repos');
        /* 页面阅读日志 */
        $scope.logAccess({ target_type: 'repos', target_id: _oApp.id });
        /* 用户信息 */
        enlService.user().then(function(data) {
            $scope.user = _oUser = data;
        });
        /* 作为可筛选的筛选项 */
        http2.get(LS.j('repos/criteriaGet', 'site', 'app')).then(function(rsp) {
            $scope.reposFilters = rsp.data;
            $scope.multiFilters = rsp.data.length>2 ? rsp.data.slice(2) : [];
            angular.forEach(rsp.data, function(data, index) {
                _oCriteria[data.type] = data.default.id;
                if(index > 1) {
                    _oFilter[data.type] = data.default.id;
                }
            });
            $scope.recordList(1);
        });
        $scope.$watch('mocker', function(nv, ov) {
            if (nv && nv !== ov) {
                _oMocker = nv;
                $scope.recordList(1);
            }
        }, true);
    });
}]);