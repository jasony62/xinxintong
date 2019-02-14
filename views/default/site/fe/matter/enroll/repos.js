'use strict';
require('./enroll.public.css');

require('./_asset/ui.repos.js');
require('./_asset/ui.tag.js');
require('./_asset/ui.topic.js');
require('./_asset/ui.assoc.js');
require('./_asset/ui.round.js');
require('./_asset/ui.dropdown.js');
require('./_asset/ui.filter.js');
require('./_asset/ui.tree.js');
require('./_asset/ui.task.js');

window.moduleAngularModules = ['tree.ui', 'filter.ui', 'dropdown.ui', 'round.ui.enroll', 'repos.ui.enroll', 'tag.ui.enroll', 'topic.ui.enroll', 'assoc.ui.enroll', 'task.ui.enroll'];

var ngApp = require('./main.js');
ngApp.controller('ctrlRepos', ['$scope', '$parse', '$sce', '$q', '$uibModal', 'http2', 'tmsLocation', 'enlRound', '$timeout', 'picviewer', 'noticebox', 'enlTag', 'enlTopic', 'enlAssoc', 'enlService', 'enlTask', function($scope, $parse, $sce, $q, $uibModal, http2, LS, enlRound, $timeout, picviewer, noticebox, enlTag, enlTopic, enlAssoc, enlService, enlTask) {
    var _oApp, _facRound, _aShareableSchemas;
    $scope.schemas = _aShareableSchemas = []; // 支持分享的题目
    $scope.activeDirSchemas = {};
    $scope.tabs = [{ 'title': '记录', 'id': 'record', 'url': '/views/default/site/fe/matter/enroll/template/repos-recordSchema.html' }];
    $scope.tabClick = function(view) {
        $scope.selectedTab = view;
    }
    $scope.addRecord = function(event) {
        $scope.$parent.addRecord(event);
    };
    $scope.dirClicked = function(oDir, active) {
        if ($scope.selectedTab.id !== 'topic') {
            $scope.$broadcast('to-child', { 0: oDir, 1: active });
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
    /* 关闭任务提示 */
    $scope.closeTask = function(index) {
        $scope.tasks.splice(index, 1);
    };
    $scope.gotoTask = function(oTask) {
        if (oTask && oTask.topic && oTask.topic.id)
            location.href = LS.j('', 'site', 'app') + '&topic=' + oTask.topic.id + '&page=topic';
    };
    $scope.advCriteriaStatus = {
        opened: !$scope.isSmallLayout,
        dirOpen: false
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var tasks, popActs;
        _oApp = params.app;
        if (window.sessionStorage.length) {
            var cacheData, _cPage;
            cacheData = JSON.parse(window.sessionStorage.listStorage);
            $scope.tasks = cacheData.tasks;
            $scope.tabs = cacheData.tabs;
            $scope.selectedTab = cacheData.selectedTab;
            $scope.rounds = cacheData.rounds;
            $scope.schemas = cacheData.schemas;
            $scope.dirSchemas = cacheData.dirSchemas;
            $scope.activeDirSchemas = cacheData.currentDirs;
            if ($scope.dirSchemas && $scope.dirSchemas.length) {
                $scope.advCriteriaStatus.dirOpen = true;
            }
        } else {
            $scope.selectedTab = $scope.tabs[0];
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
            }
            new enlTask($scope.app).list(null, 'IP').then(function(ipTasks) {
                if (ipTasks.length) {
                    ipTasks.forEach(function(oTask) {
                        switch (oTask.type) {
                            case 'question':
                                tasks.push({ type: 'info', msg: oTask.toString(), id: 'record.data.question', data: oTask });
                                break;
                            case 'answer':
                                tasks.push({ type: 'info', msg: oTask.toString(), id: 'record.data.answer', data: oTask });
                                break;
                            case 'vote':
                                tasks.push({ type: 'info', msg: oTask.toString(), id: 'record.data.vote', data: oTask });
                                popActs.push('voteRecData');
                                break;
                            case 'score':
                                tasks.push({ type: 'info', msg: oTask.toString(), id: 'record.data.score', data: oTask });
                                popActs.push('scoreSchema');
                                break;
                        }
                    });
                }
            });
            $scope.tasks = tasks = [];
            $scope.facRound = _facRound = new enlRound(_oApp);
            _facRound.list().then(function(result) {
                $scope.rounds = result.rounds;
            });
            _oApp.dynaDataSchemas.forEach(function(oSchema) {
                if (oSchema.shareable === 'Y') {
                    _aShareableSchemas.push(oSchema);
                }
                if (Object.keys(oSchema).indexOf('cowork') !== -1 && oSchema.cowork === 'Y') {
                    $scope.tabs[0].title = '问题';
                    $scope.tabs.push({ 'title': '答案', 'id': 'coworkData', 'url': '/views/default/site/fe/matter/enroll/template/repos-coworkSchema.html' });
                    $scope.selectedTab = $scope.tabs[1];
                }
            });
            /* 共享专题 */
            http2.get(LS.j('topic/listPublic', 'site', 'app')).then(function(rsp) {
                if (rsp.data && rsp.data.topics && rsp.data.topics.length) {
                    $scope.topics = rsp.data.topics;
                    $scope.tabs.push({ 'title': '专题', 'id': 'topic', 'url': '/views/default/site/fe/matter/enroll/template/repos-publicTopic.html' });
                }
            });
            /* 作为分类目录的题目 */
            http2.get(LS.j('repos/dirSchemasGet', 'site', 'app')).then(function(rsp) {
                $scope.dirSchemas = rsp.data;
                if ($scope.dirSchemas && $scope.dirSchemas.length) {
                    $scope.advCriteriaStatus.dirOpen = true;
                }
            });

        }
        /* 活动任务 */
        if (_oApp.actionRule) {
            /* 开启协作填写需要的点赞数 */
            if (_oApp.actionRule.record && _oApp.actionRule.record.cowork && _oApp.actionRule.record.cowork.pre) {
                if (_oApp.actionRule.record.cowork.pre.record && _oApp.actionRule.record.cowork.pre.record.likeNum !== undefined) {
                    _coworkRequireLikeNum = parseInt(_oApp.actionRule.record.cowork.pre.record.likeNum);
                }
            }
        }
        if (_oApp.reposConfig && _oApp.reposConfig.defaultOrder) {
            _oCriteria.orderby = _oApp.reposConfig.defaultOrder;
        }

        /* 设置页面分享信息 */
        $scope.setSnsShare(null, null, { target_type: 'repos', target_id: _oApp.id });
        /* 设置页面操作 */
        popActs = ['addRecord'];
        $scope.setPopAct(popActs, 'repos', {
            func: {
                voteRecData: $scope.voteRecData,
                scoreSchema: $scope.scoreSchema,
            }
        });
        /* 设置页面导航 */
        $scope.setPopNav(['rank', 'kanban', 'event', 'favor', 'task'], 'repos');
        /* 页面阅读日志 */
        $scope.logAccess({ target_type: 'repos', target_id: _oApp.id });
        /* 用户信息 */
        enlService.user().then(function(data) {
            $scope.user = data;
            var groupOthersById = {};
            if (data.groupOthers && data.groupOthers.length) {
                data.groupOthers.forEach(function(oOther) {
                    groupOthersById[oOther.userid] = oOther;
                });
            }
            $scope.groupOthers = groupOthersById;
        });
    });
}]);
ngApp.controller('ctrlRecordSchema', ['$scope', '$timeout', '$q', 'http2', 'noticebox', 'tmsLocation', 'picviewer', 'enlAssoc', function($scope, $timeout, $q, http2, noticebox, LS, picviewer, enlAssoc) {
    function fnGetCriteria(datas) {
        $scope.singleFilters = [];
        $scope.multiFilters = [];
        angular.forEach(datas, function(data, index) {
            _oCriteria[data.type] = data.default.id;
            if (data.type === 'orderby') {
                $scope.singleFilters.push(data);
            } else {
                $scope.multiFilters.push(data);
                _oFilter[data.type] = data.default.id;
            }
        });
    }
    var _oPage, _oFilter, _oCriteria;
    $scope.page = _oPage = {};
    $scope.filter = _oFilter = { isFilter: false };
    $scope.criteria = _oCriteria = {};
    $scope.repos = [];
    $scope.reposLoading = false;
    $scope.appendToEle = angular.element(document.querySelector('#filterQuick'));
    $scope.getCriteria = function() {
        var url;
        url = LS.j('repos/criteriaGet', 'site', 'app') + '&viewType=record';
        http2.get(url).then(function(rsp) {
            if (rsp.data) {
                fnGetCriteria(rsp.data);
            }
            $scope.recordList(1);
        });
    };
    $scope.recordList = function(pageAt) {
        var url, deferred;
        deferred = $q.defer();

        pageAt ? _oPage.at = pageAt : _oPage.at++;
        if (_oPage.at == 1) {
            $scope.repos = [];
            _oPage.total = 0;
        }
        url = LS.j('repos/recordList', 'site', 'app');
        $scope.reposLoading = true;
        http2.post(url, _oCriteria, { page: _oPage }).then(function(result) {
            if (result.data.records) {
                result.data.records.forEach(function(oRecord) {
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
    $scope.dirClicked = function(oDir, active) {
        _oCriteria.data = {};
        if (oDir) {
            _oCriteria.data[oDir.schema_id] = oDir.op.v;
        }
        $scope.activeDirSchemas = active;
        $scope.recordList(1);
    };
    $scope.shiftMenu = function(criteria) {
        _oCriteria[criteria.type] = criteria.id;
        $scope.recordList(1);
    };
    $scope.confirm = function(filterOpt) {
        $scope.recordList(1).then(function() {
            http2.get(LS.j('repos/criteriaGet', 'site', 'app')).then(function(rsp) {
                if (rsp.data) {
                    var _oNew = [];
                    angular.forEach(rsp.data, function(data) {
                        if (data.type === 'orderby') {
                            return false;
                        } else {
                            _oNew.push(data);
                        }
                    });
                    http2.merge($scope.multiFilters, _oNew);
                }
            });
        });
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
    };
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

    function addToCache() {
        sessionStorage.setItem('listStorageY', document.getElementById('repos').scrollTop);
        var cacheData = {
            'singleFilters': $scope.singleFilters,
            'multiFilters': $scope.multiFilters,
            'page': $scope.page,
            'currentFilter': $scope.filter,
            'currentCriteria': $scope.criteria,
            'tabs': $scope.tabs,
            'selectedTab': $scope.selectedTab,
            'schemas': $scope.schemas,
            'rounds': $scope.rounds,
            'tasks': $scope.tasks,
            'topics': $scope.topics,
            'dirSchemas': $scope.dirSchemas,
            'currentDirs': $scope.activeDirSchemas
        }
        sessionStorage.setItem('listStorage', JSON.stringify(cacheData));
    };
    $scope.remarkRecord = function(oRecord, event) {
        event.stopPropagation();
        event.preventDefault();

        addToCache();
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork';
        location.href = url;
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
    $scope.copyRecord = function(event, oRecord) {
        enlAssoc.copy($scope.app, { id: oRecord.id, type: 'record' });
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
    $scope.$on('to-child', function(event, data) {
        $scope.dirClicked(data[0], data[1]);
    });
    if (window.sessionStorage.length && $scope.selectedTab.id === 'record') {
        var cacheData, _cPage;
        cacheData = JSON.parse(window.sessionStorage.listStorage);
        $scope.singleFilters = cacheData.singleFilters;
        $scope.multiFilters = cacheData.multiFilters;
        $scope.filter = cacheData.currentFilter;
        $scope.criteria = _oCriteria = cacheData.currentCriteria;
        _cPage = cacheData.page;

        function _getNewRepos(at) {
            $scope.recordList(at).then(function() {
                if (at == _cPage.at) {
                    $timeout(function() {
                        document.getElementById('repos').scrollTop = parseInt(window.sessionStorage.listStorageY);
                        window.sessionStorage.clear();
                    });
                }
            });

        }
        for (var i = 1; i <= _cPage.at; i++) {
            _getNewRepos(i);
        }
    } else {
        $scope.getCriteria();
    }
}]);
ngApp.controller('ctrlCoworkSchema', ['$scope', '$timeout', '$q', 'http2', 'tmsLocation', 'picviewer', function($scope, $timeout, $q, http2, LS, picviewer) {
    function fnGetCriteria(datas) {
        $scope.singleFilters = [];
        $scope.multiFilters = [];
        angular.forEach(datas, function(data, index) {
            _oCriteria[data.type] = data.default.id;
            if (data.type === 'orderby') {
                $scope.singleFilters.push(data);
            } else {
                $scope.multiFilters.push(data);
                _oFilter[data.type] = data.default.id;
            }
        });
    }
    var _oPage, _oFilter, _oCriteria, _coworkRequireLikeNum;
    _coworkRequireLikeNum = 0; // 记录获得多少个赞，才能开启协作填写
    $scope.page = _oPage = {};
    $scope.filter = _oFilter = { isFilter: false };
    $scope.criteria = _oCriteria = {};
    $scope.repos = [];
    $scope.reposLoading = false;
    $scope.appendToEle = angular.element(document.querySelector('#filterQuick'));
    $scope.getCriteria = function() {
        var url;
        url = LS.j('repos/criteriaGet', 'site', 'app') + '&viewType=coworkData';
        http2.get(url).then(function(rsp) {
            if (rsp.data) {
                fnGetCriteria(rsp.data);
            }
            $scope.recordList(1);
        });
    };
    $scope.recordList = function(pageAt) {
        var url, deferred;
        deferred = $q.defer();

        pageAt ? _oPage.at = pageAt : _oPage.at++;
        if (_oPage.at == 1) {
            $scope.repos = [];
            _oPage.total = 0;
        }
        url = LS.j('repos/coworkDataList', 'site', 'app');
        $scope.reposLoading = true;
        http2.post(url, _oCriteria, { page: _oPage }).then(function(result) {
            if (result.data.recordDatas) {
                result.data.recordDatas.forEach(function(oRecord) {
                    if (_coworkRequireLikeNum > oRecord.like_num) {
                        oRecord._coworkRequireLikeNum = (_coworkRequireLikeNum > oRecord.like_num ? _coworkRequireLikeNum - oRecord.like_num : 0);
                    }
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
    $scope.dirClicked = function(oDir, active) {
        _oCriteria.data = {};
        if (oDir) {
            _oCriteria.data[oDir.schema_id] = oDir.op.v;
        }
        $scope.activeDirSchemas = active;
        $scope.recordList(1);
    };
    $scope.shiftMenu = function(criteria) {
        _oCriteria[criteria.type] = criteria.id;
        $scope.recordList(1);
    };
    $scope.confirm = function(filterOpt) {
        $scope.recordList(1).then(function() {
            var url = LS.j('repos/criteriaGet', 'site', 'app') + '&viewType=coworkData';
            http2.get(url).then(function(rsp) {
                if (rsp.data) {
                    var _oNew = [];
                    angular.forEach(rsp.data, function(data) {
                        if (data.type === 'orderby') {
                            return false;
                        } else {
                            _oNew.push(data);
                        }
                    });
                    http2.merge($scope.multiFilters, _oNew);
                }
            });
        });
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
    };
    $scope.gotoAssoc = function(oEntity, event) {
        event.stopPropagation();

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

    function addToCache() {
        sessionStorage.setItem('listStorageY', document.getElementById('repos').scrollTop);
        var cacheData = {
            'singleFilters': $scope.singleFilters,
            'multiFilters': $scope.multiFilters,
            'page': $scope.page,
            'currentFilter': $scope.filter,
            'currentCriteria': $scope.criteria,
            'tabs': $scope.tabs,
            'selectedTab': $scope.selectedTab,
            'schemas': $scope.schemas,
            'rounds': $scope.rounds,
            'tasks': $scope.tasks,
            'topics': $scope.topics,
            'dirSchemas': $scope.dirSchemas,
            'currentDirs': $scope.activeDirSchemas
        }
        sessionStorage.setItem('listStorage', JSON.stringify(cacheData));
    };
    $scope.remarkRecord = function(oRecord, event) {
        event.stopPropagation();
        event.preventDefault();

        addToCache();
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork';
        location.href = url;
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
    $scope.$on('to-child', function(event, data) {
        $scope.dirClicked(data[0], data[1]);
    });
    if (window.sessionStorage.length && $scope.selectedTab.id === 'coworkData') {
        var cacheData, _cPage;
        cacheData = JSON.parse(window.sessionStorage.listStorage);
        $scope.singleFilters = cacheData.singleFilters;
        $scope.multiFilters = cacheData.multiFilters;
        $scope.filter = cacheData.currentFilter;
        $scope.criteria = _oCriteria = cacheData.currentCriteria;
        _cPage = cacheData.page;

        function _getNewRepos(at) {
            $scope.recordList(at).then(function() {
                if (at == _cPage.at) {
                    $timeout(function() {
                        document.getElementById('repos').scrollTop = parseInt(window.sessionStorage.listStorageY);
                        window.sessionStorage.clear();
                    });
                }
            });

        }
        for (var i = 1; i <= _cPage.at; i++) {
            _getNewRepos(i);
        }
    } else {
        $scope.getCriteria();
    }
}]);
ngApp.controller('ctrlPublicTopic', ['$scope', 'http2', '$timeout', 'tmsLocation', function($scope, http2, $timeout, LS) {
    function fnGetCriteria(datas) {
        $scope.singleFilters = [];
        $scope.multiFilters = [];
        angular.forEach(datas, function(data, index) {
            _oCriteria[data.type] = data.default.id;
            if (data.type === 'orderby') {
                $scope.singleFilters.push(data);
            } else {
                $scope.multiFilters.push(data);
                _oFilter[data.type] = data.default.id;
            }
        });
    }
    var _oFilter, _oCriteria;
    $scope.filter = _oFilter = { isFilter: false };
    $scope.criteria = _oCriteria = {};

    function addToCache() {
        sessionStorage.setItem('listStorageY', document.getElementById('topic').scrollTop);
        var cacheData = {
            'singleFilters': $scope.singleFilters,
            'multiFilters': $scope.multiFilters,
            'currentFilter': $scope.filter,
            'currentCriteria': $scope.criteria,
            'tabs': $scope.tabs,
            'selectedTab': $scope.selectedTab,
            'schemas': $scope.schemas,
            'rounds': $scope.rounds,
            'tasks': $scope.tasks,
            'topics': $scope.topics,
            'dirSchemas': $scope.dirSchemas,
            'currentDirs': $scope.activeDirSchemas
        }
        sessionStorage.setItem('listStorage', JSON.stringify(cacheData));
    };
    $scope.getCriteria = function() {
        var url;
        url = LS.j('repos/criteriaGet', 'site', 'app') + '&viewType=topic';
        http2.get(url).then(function(rsp) {
            if (rsp.data) {
                fnGetCriteria(rsp.data);
            }
        });
    };
    $scope.shiftMenu = function(criteria) {
        _oCriteria[criteria.type] = criteria.id;
        $scope.recordList(1);
    };
    $scope.recordList = function(pageAt) {
        http2.post(LS.j('topic/listPublic', 'site', 'app'), _oCriteria).then(function(rsp) {
            if (rsp.data && rsp.data.topics && rsp.data.topics.length) {
                $scope.topics = rsp.data.topics;
            }
        });
    }
    $scope.gotoTopic = function(oTopic, event) {
        event.stopPropagation();
        event.preventDefault();

        addToCache();
        location.href = LS.j('', 'site', 'app') + '&topic=' + oTopic.id + '&page=topic';
    };
    if (window.sessionStorage.length && $scope.selectedTab.id === 'topic') {
        var cacheData;
        cacheData = JSON.parse(window.sessionStorage.listStorage);
        $scope.singleFilters = cacheData.singleFilters;
        $scope.multiFilters = cacheData.multiFilters;
        $scope.filter = cacheData.currentFilter;
        $scope.criteria = _oCriteria = cacheData.currentCriteria;
        $scope.topics = cacheData.topics;
        $timeout(function() {
            document.getElementById('topic').scrollTop = parseInt(window.sessionStorage.listStorageY);
            window.sessionStorage.clear();
        });
    } else {
        $scope.getCriteria();
    }
}]);