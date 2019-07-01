'use strict';
require('./enroll.public.css');

require('./_asset/ui.repos.record.js');
require('./_asset/ui.repos.cowork.js');
require('./_asset/ui.bottom.nav.js');
require('./_asset/ui.tag.js');
require('./_asset/ui.topic.js');
require('./_asset/ui.assoc.js');
require('./_asset/ui.round.js');
require('./_asset/ui.dropdown.js');
require('./_asset/ui.filter.js');
require('./_asset/ui.tree.js');
require('./_asset/ui.task.js');

window.moduleAngularModules = ['nav.bottom.ui', 'tree.ui', 'filter.ui', 'dropdown.ui', 'round.ui.enroll', 'history.ui.enroll', 'record.repos.ui.enroll', 'cowork.repos.ui.enroll', 'tag.ui.enroll', 'topic.ui.enroll', 'assoc.ui.enroll', 'task.ui.enroll'];

var ngApp = require('./main.js');
ngApp.controller('ctrlRepos', ['$scope', '$uibModal', 'http2', 'tmsLocation', 'enlRound', '$timeout', 'noticebox', 'enlTag', 'enlTopic', 'enlService', 'enlTask', function ($scope, $uibModal, http2, LS, enlRound, $timeout, noticebox, enlTag, enlTopic, enlService, enlTask) {
    var _oApp, _facRound, _aShareableSchemas;
    $scope.schemas = _aShareableSchemas = []; // 支持分享的题目
    $scope.activeDirSchemas = {};
    $scope.schemaCounter = 0;
    $scope.activeNav = '';
    $scope.addRecord = function (event) {
        $scope.$parent.addRecord(event);
    };
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

    function fnAssignTag(oRecord) {
        enlTag.assignTag(oRecord).then(function (rsp) {
            if (rsp.data.user && rsp.data.user.length) {
                oRecord.userTags = rsp.data.user;
            } else {
                delete oRecord.userTags;
            }
        });
    }
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
    /* 关闭任务提示 */
    $scope.closeTask = function (index) {
        $scope.tasks.splice(index, 1);
    };
    $scope.gotoTask = function (oTask) {
        if (oTask && oTask.topic && oTask.topic.id)
            location.href = LS.j('', 'site', 'app') + '&topic=' + oTask.topic.id + '&page=topic';
    };
    $scope.openTaskModel = function () {
        $uibModal.open({
            templateUrl: 'task.html',
            resolve: {
                tasks: function () {
                    return $scope.tasks;
                }
            },
            controller: ['$scope', 'tasks', '$uibModalInstance', function ($scope2, tasks, $mi) {
                $scope2.tasks = tasks;
                $scope2.tasks.forEach(function (oTask) {
                    if (oTask.data.state === 'IP') {
                        $scope2.currentTask = oTask;
                    }
                });
                $scope2.gotoTask = function (oTask) {
                    if (oTask) {
                        if (oTask.data.type === 'baseline') {
                            location.href = LS.j('', 'site', 'app') + '&rid=' + oTask.rid + '&page=enroll';
                        } else if (oTask.data.topic && oTask.data.topic.id) {
                            location.href = LS.j('', 'site', 'app') + '&topic=' + oTask.data.topic.id + '&page=topic';
                        }
                    }
                };
                $scope2.addRecord = function (event) {
                    $scope.addRecord(event);
                };
                $scope2.cancel = function () {
                    $mi.close();
                };
            }],
            size: 'md',
            backdrop: 'static',
        });
    };
    $scope.advCriteriaStatus = {
        opened: !$scope.isSmallLayout,
        dirOpen: false
    };
    $scope.$on('transfer.view', function (event, data) {
        $scope.activeView = data;
    });
    $scope.$on('xxt.app.enroll.ready', function (event, params) {
        var tasks, popActs;
        _oApp = params.app;
        if (window.sessionStorage.length) {
            var cacheData, _cPage;
            cacheData = JSON.parse(window.sessionStorage.listStorage);
            $scope.schemaCounter = cacheData.schemaCounter;
            $scope.tasks = cacheData.tasks;
            $scope.navs = cacheData.navs;
            $scope.activeNav = cacheData.activeNav;
            $scope.activeView = cacheData.activeView;
            $scope.rounds = cacheData.rounds;
            $scope.schemas = cacheData.schemas;
            $scope.dirSchemas = cacheData.dirSchemas;
            $scope.activeDirSchemas = cacheData.currentDirs;
            if ($scope.dirSchemas && $scope.dirSchemas.length) {
                $scope.advCriteriaStatus.dirOpen = true;
            }
        } else {
            new enlTask($scope.app).list(null).then(function (ipTasks) {
                if (ipTasks.length) {
                    var flag = false;
                    ipTasks.forEach(function (oTask) {
                        if (oTask.state === 'IP') {
                            flag = true;
                        };
                        switch (oTask.type) {
                            case 'question':
                                tasks.push({
                                    type: 'info',
                                    msg: oTask.toString(),
                                    time: oTask.timeFormat(),
                                    id: 'record.data.question',
                                    name: '提问',
                                    data: oTask
                                });
                                break;
                            case 'answer':
                                tasks.push({
                                    type: 'info',
                                    msg: oTask.toString(),
                                    time: oTask.timeFormat(),
                                    id: 'record.data.answer',
                                    name: '回答',
                                    data: oTask
                                });
                                break;
                            case 'vote':
                                tasks.push({
                                    type: 'info',
                                    msg: oTask.toString(),
                                    time: oTask.timeFormat(),
                                    id: 'record.data.vote',
                                    name: '投票',
                                    data: oTask
                                });
                                break;
                            case 'score':
                                tasks.push({
                                    type: 'info',
                                    msg: oTask.toString(),
                                    time: oTask.timeFormat(),
                                    id: 'record.data.score',
                                    name: '打分',
                                    data: oTask
                                });
                                break;
                        }
                    });
                    if (flag) {
                        $scope.openTaskModel();
                    }
                }
            });
            $scope.tasks = tasks = [];
            $scope.facRound = _facRound = new enlRound(_oApp);
            _facRound.list().then(function (result) {
                $scope.rounds = result.rounds;
            });
            _oApp.dynaDataSchemas.forEach(function (oSchema) {
                if (oSchema.shareable === 'Y') {
                    $scope.schemaCounter++;
                    _aShareableSchemas.push(oSchema);
                }
                if (Object.keys(oSchema).indexOf('cowork') !== -1 && oSchema.cowork === 'Y') {
                    $scope.schemaCounter--;
                }
            });
            /* 作为分类目录的题目 */
            http2.get(LS.j('repos/dirSchemasGet', 'site', 'app')).then(function (rsp) {
                $scope.dirSchemas = rsp.data;
                if ($scope.dirSchemas && $scope.dirSchemas.length) {
                    $scope.advCriteriaStatus.dirOpen = true;
                }
            });
            /* 请求导航 */
            http2.get(LS.j('navs', 'site', 'app')).then(function (rsp) {
                $scope.navs = rsp.data;
                $scope.navs.forEach(function (nav) {
                    if (nav.type === 'repos') {
                        nav.views.forEach(function (view) {
                            view.url = '/views/default/site/fe/matter/enroll/home/' + view.type + '.html';
                            if (nav.defaultView.type === view.type) {
                                $scope.activeView = view;
                            }
                        });
                    }
                });
            });
        }
        /* 设置页面分享信息 */
        $scope.setSnsShare(null, null, {
            target_type: 'repos',
            target_id: _oApp.id
        });
        /* 页面阅读日志 */
        $scope.logAccess({
            target_type: 'repos',
            target_id: _oApp.id
        });
        /* 用户信息 */
        enlService.user().then(function (data) {
            $scope.user = data;
            var groupOthersById = {};
            if (data.groupOthers && data.groupOthers.length) {
                data.groupOthers.forEach(function (oOther) {
                    groupOthersById[oOther.userid] = oOther;
                });
            }
            $scope.groupOthers = groupOthersById;
        });
    });
}]);
ngApp.controller('ctrlReposRecord', ['$scope', '$timeout', '$q', 'http2', 'noticebox', 'tmsLocation', 'picviewer', 'enlAssoc', function ($scope, $timeout, $q, http2, noticebox, LS, picviewer, enlAssoc) {
    function fnGetCriteria(datas) {
        $scope.singleFilters = [];
        $scope.multiFilters = [];
        angular.forEach(datas, function (data, index) {
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
    $scope.filter = _oFilter = {
        isFilter: false
    };
    $scope.criteria = _oCriteria = {};
    $scope.repos = [];
    $scope.reposLoading = false;
    $scope.appendToEle = angular.element(document.querySelector('#nav_container'));
    $scope.viewTo = function ($event, view) {
        $scope.$emit('transfer.view', view);
    };
    $scope.getCriteria = function () {
        var url;
        url = LS.j('repos/criteriaGet', 'site', 'app') + '&viewType=record';
        http2.get(url).then(function (rsp) {
            if (rsp.data) {
                fnGetCriteria(rsp.data);
            }
            $scope.recordList(1);
        });
    };
    $scope.recordList = function (pageAt) {
        var url, deferred;
        deferred = $q.defer();

        pageAt ? _oPage.at = pageAt : _oPage.at++;
        if (_oPage.at == 1) {
            $scope.repos = [];
            _oPage.total = 0;
        }
        url = LS.j('repos/recordList', 'site', 'app');
        $scope.reposLoading = true;
        http2.post(url, _oCriteria, {
            page: _oPage
        }).then(function (result) {
            if (result.data.records) {
                result.data.records.forEach(function (oRecord) {
                    $scope.repos.push(oRecord);
                });
            }
            $timeout(function () {
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
    $scope.dirClicked = function (oDir, active) {
        _oCriteria.data = {};
        if (oDir) {
            _oCriteria.data[oDir.schema_id] = oDir.op.v;
        }
        $scope.activeDirSchemas = active;
        $scope.recordList(1);
    };
    $scope.shiftMenu = function (criteria) {
        _oCriteria[criteria.type] = criteria.id;
        $scope.recordList(1);
    };
    $scope.confirm = function (filterOpt) {
        $scope.recordList(1).then(function () {
            http2.get(LS.j('repos/criteriaGet', 'site', 'app')).then(function (rsp) {
                if (rsp.data) {
                    var _oNew = [];
                    angular.forEach(rsp.data, function (data) {
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
    $scope.shiftTip = function (type) {
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
    $scope.shiftTag = function (oTag, bToggle) {
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
        sessionStorage.setItem('listStorageY', document.documentElement.scrollTop);
        var cacheData = {
            'schemaCounter': $scope.schemaCounter,
            'singleFilters': $scope.singleFilters,
            'multiFilters': $scope.multiFilters,
            'page': $scope.page,
            'currentFilter': $scope.filter,
            'currentCriteria': $scope.criteria,
            'navs': $scope.navs,
            'activeNav': $scope.activeNav,
            'activeView': $scope.activeView,
            'schemas': $scope.schemas,
            'rounds': $scope.rounds,
            'tasks': $scope.tasks,
            'topics': $scope.topics,
            'dirSchemas': $scope.dirSchemas,
            'currentDirs': $scope.activeDirSchemas
        };
        sessionStorage.setItem('listStorage', JSON.stringify(cacheData));
    }
    $scope.remarkRecord = function (oRecord, event) {
        event.stopPropagation();
        event.preventDefault();

        addToCache();
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork';
        location.href = url;
    };
    $scope.shareRecord = function (oRecord) {
        var url, shareby;
        url = LS.j('', 'site', 'app') + '&ek=' + oRecord.enroll_key + '&page=share';
        shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };

    $scope.editRecord = function (event, oRecord) {
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
    $scope.copyRecord = function (event, oRecord) {
        enlAssoc.copy($scope.app, {
            id: oRecord.id,
            type: 'record'
        });
    };

    $scope.spyRecordsScroll = true; // 监控滚动事件
    $scope.recordsScrollToBottom = function () {
        if ($scope.repos.length < $scope.page.total) {
            $scope.recordList().then(function () {
                $timeout(function () {
                    if ($scope.repos.length < $scope.page.total) {
                        $scope.spyRecordsScroll = true;
                    }
                });
            });
        }
    };

    function _getNewRepos(at) {
        $scope.recordList(at).then(function () {
            if (at == _cPage.at) {
                $timeout(function () {
                    document.documentElement.scrollTop = parseInt(window.sessionStorage.listStorageY);
                    window.sessionStorage.clear();
                });
            }
        });

    }
    if (window.sessionStorage.length && $scope.activeView.type === 'record') {
        var cacheData, _cPage;
        cacheData = JSON.parse(window.sessionStorage.listStorage);
        $scope.singleFilters = cacheData.singleFilters;
        $scope.multiFilters = cacheData.multiFilters;
        $scope.filter = cacheData.currentFilter;
        $scope.criteria = _oCriteria = cacheData.currentCriteria;
        _cPage = cacheData.page;

        for (var i = 1; i <= _cPage.at; i++) {
            _getNewRepos(i);
        }
    } else {
        $scope.getCriteria();
    }
}]);
ngApp.controller('ctrlReposCowork', ['$scope', '$timeout', '$q', 'http2', 'tmsLocation', 'picviewer', function ($scope, $timeout, $q, http2, LS, picviewer) {
    function fnGetCriteria(datas) {
        $scope.singleFilters = [];
        $scope.multiFilters = [];
        angular.forEach(datas, function (data, index) {
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
    $scope.filter = _oFilter = {
        isFilter: false
    };
    $scope.criteria = _oCriteria = {};
    $scope.repos = [];
    $scope.reposLoading = false;
    $scope.appendToEle = angular.element(document.querySelector('#nav_container'));
    $scope.viewTo = function ($event, view) {
        $scope.$emit('transfer.view', view);
    };
    $scope.getCriteria = function () {
        var url;
        url = LS.j('repos/criteriaGet', 'site', 'app') + '&viewType=coworkData';
        http2.get(url).then(function (rsp) {
            if (rsp.data) {
                fnGetCriteria(rsp.data);
            }
            $scope.recordList(1);
        });
    };
    $scope.recordList = function (pageAt) {
        var url, deferred;
        deferred = $q.defer();

        pageAt ? _oPage.at = pageAt : _oPage.at++;
        if (_oPage.at == 1) {
            $scope.repos = [];
            _oPage.total = 0;
        }
        url = LS.j('repos/coworkDataList', 'site', 'app');
        $scope.reposLoading = true;
        http2.post(url, _oCriteria, {
            page: _oPage
        }).then(function (result) {
            if (result.data.recordDatas) {
                result.data.recordDatas.forEach(function (oRecord) {
                    $scope.repos.push(oRecord);
                });
            }
            $timeout(function () {
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
    $scope.dirClicked = function (oDir, active) {
        _oCriteria.data = {};
        if (oDir) {
            _oCriteria.data[oDir.schema_id] = oDir.op.v;
        }
        $scope.activeDirSchemas = active;
        $scope.recordList(1);
    };
    $scope.shiftMenu = function (criteria) {
        _oCriteria[criteria.type] = criteria.id;
        $scope.recordList(1);
    };
    $scope.confirm = function (filterOpt) {
        $scope.recordList(1).then(function () {
            var url = LS.j('repos/criteriaGet', 'site', 'app') + '&viewType=coworkData';
            http2.get(url).then(function (rsp) {
                if (rsp.data) {
                    var _oNew = [];
                    angular.forEach(rsp.data, function (data) {
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
    $scope.shiftTip = function (type) {
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
    $scope.gotoAssoc = function (oEntity, event) {
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
        sessionStorage.setItem('listStorageY', document.documentElement.scrollTop);
        var cacheData = {
            'schemaCounter': $scope.schemaCounter,
            'singleFilters': $scope.singleFilters,
            'multiFilters': $scope.multiFilters,
            'page': $scope.page,
            'currentFilter': $scope.filter,
            'currentCriteria': $scope.criteria,
            'navs': $scope.navs,
            'activeNav': $scope.activeNav,
            'activeView': $scope.activeView,
            'schemas': $scope.schemas,
            'rounds': $scope.rounds,
            'tasks': $scope.tasks,
            'topics': $scope.topics,
            'dirSchemas': $scope.dirSchemas,
            'currentDirs': $scope.activeDirSchemas
        }
        sessionStorage.setItem('listStorage', JSON.stringify(cacheData));
    };
    $scope.remarkRecord = function (oRecord, event) {
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
    $scope.recordsScrollToBottom = function () {
        if ($scope.repos.length < $scope.page.total) {
            $scope.recordList().then(function () {
                $timeout(function () {
                    if ($scope.repos.length < $scope.page.total) {
                        $scope.spyRecordsScroll = true;
                    }
                });
            });
        }
    };
    $scope.$on('to-child', function (event, data) {
        $scope.dirClicked(data[0], data[1]);
    });
    if (window.sessionStorage.length && $scope.activeView.type === 'cowork') {
        var cacheData, _cPage;
        cacheData = JSON.parse(window.sessionStorage.listStorage);
        $scope.singleFilters = cacheData.singleFilters;
        $scope.multiFilters = cacheData.multiFilters;
        $scope.filter = cacheData.currentFilter;
        $scope.criteria = _oCriteria = cacheData.currentCriteria;
        _cPage = cacheData.page;

        function _getNewRepos(at) {
            $scope.recordList(at).then(function () {
                if (at == _cPage.at) {
                    $timeout(function () {
                        document.documentElement.scrollTop = parseInt(window.sessionStorage.listStorageY);
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
ngApp.controller('ctrlReposTopic', ['$scope', '$q', 'http2', '$timeout', 'tmsLocation', function ($scope, $q, http2, $timeout, LS) {
    function fnGetCriteria(datas) {
        $scope.singleFilters = [];
        $scope.multiFilters = [];
        angular.forEach(datas, function (data, index) {
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
    $scope.filter = _oFilter = {
        isFilter: false
    };
    $scope.criteria = _oCriteria = {};
    $scope.appendToEle = angular.element(document.querySelector('#nav_container'));
    $scope.viewTo = function ($event, view) {
        $scope.$emit('transfer.view', view);
    };

    function addToCache() {
        sessionStorage.setItem('listStorageY', document.documentElement.scrollTop);
        var cacheData = {
            'schemaCounter': $scope.schemaCounter,
            'singleFilters': $scope.singleFilters,
            'multiFilters': $scope.multiFilters,
            'currentFilter': $scope.filter,
            'currentCriteria': $scope.criteria,
            'navs': $scope.navs,
            'activeNav': $scope.activeNav,
            'activeView': $scope.activeView,
            'schemas': $scope.schemas,
            'rounds': $scope.rounds,
            'tasks': $scope.tasks,
            'topics': $scope.topics,
            'dirSchemas': $scope.dirSchemas,
            'currentDirs': $scope.activeDirSchemas
        }
        sessionStorage.setItem('listStorage', JSON.stringify(cacheData));
    };
    $scope.getCriteria = function () {
        var url;
        url = LS.j('repos/criteriaGet', 'site', 'app') + '&viewType=topic';
        http2.get(url).then(function (rsp) {
            if (rsp.data) {
                fnGetCriteria(rsp.data);
            }
            $scope.recordList(1);
        });
    };
    $scope.shiftMenu = function (criteria) {
        _oCriteria[criteria.type] = criteria.id;
        $scope.recordList(1);
    };
    $scope.shiftTip = function (type) {
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
    $scope.confirm = function (filterOpt) {
        $scope.recordList(1).then(function () {
            var url = LS.j('repos/criteriaGet', 'site', 'app') + '&viewType=topic';
            http2.get(url).then(function (rsp) {
                if (rsp.data) {
                    var _oNew = [];
                    angular.forEach(rsp.data, function (data) {
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
    $scope.recordList = function (pageAt) {
        var deferred = $q.defer();
        http2.post(LS.j('topic/listAll', 'site', 'app'), _oCriteria).then(function (rsp) {
            if (rsp.data && rsp.data.topics) {
                $scope.topics = rsp.data.topics;
            }
            deferred.resolve(rsp);
        });
        return deferred.promise;
    }
    $scope.gotoTopic = function (oTopic, event) {
        event.stopPropagation();
        event.preventDefault();

        addToCache();
        location.href = LS.j('', 'site', 'app') + '&topic=' + oTopic.id + '&page=topic';
    };
    if (window.sessionStorage.length && $scope.activeView.type === 'topic') {
        var cacheData;
        cacheData = JSON.parse(window.sessionStorage.listStorage);
        $scope.singleFilters = cacheData.singleFilters;
        $scope.multiFilters = cacheData.multiFilters;
        $scope.filter = cacheData.currentFilter;
        $scope.criteria = _oCriteria = cacheData.currentCriteria;
        $scope.topics = cacheData.topics;
        $timeout(function () {
            document.documentElement.scrollTop = parseInt(window.sessionStorage.listStorageY);
            window.sessionStorage.clear();
        });
    } else {
        $scope.getCriteria();
    }
}]);