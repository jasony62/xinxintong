'use strict';
define(["require", "angular", "planService"], function(require, angular) {
    var ls, siteId, appId, accessId, ngApp;
    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    appId = ls.match(/[\?&]app=([^&]*)/)[1];
    accessId = ls.match(/[\?&]accessToken=([^&]*)/)[1];

    ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'schema.ui.xxt', 'service.matter', 'service.plan']);
    ngApp.constant('cstApp', {});
    ngApp.config(['$locationProvider', '$routeProvider', '$uibTooltipProvider', 'srvPlanAppProvider', 'srvPlanRecordProvider', function($locationProvider, $routeProvider, $uibTooltipProvider, srvPlanAppProvider, srvPlanRecordProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/site/op/matter/plan/');
            this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
        };
        $routeProvider
            .when('/rest/site/op/matter/plan/task', new RouteParam('task'))
            .when('/rest/site/op/matter/plan/report', new RouteParam('report'))
            .when('/rest/site/op/matter/plan/taskDetail', new RouteParam('taskDetail'))
            .otherwise(new RouteParam('task'));
        //
        $locationProvider.html5Mode(true);
        //
        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });
        //
        srvPlanAppProvider.config(siteId, appId, accessId);
        srvPlanRecordProvider.config(siteId, appId);
    }]);
    ngApp.controller('ctrlApp', ['$scope', '$timeout', '$location', 'http2', 'srvPlanApp', function($scope, $timeout, $location, http2, srvPlanApp) {
        var cachedStatus, lastCachedStatus;
        $scope.switchTo = function(view) {
            $location.path('/rest/site/op/matter/plan/' + view);
        };
        $scope.update = function(props) {
            srvPlanApp.update(props);
        };
        http2.get('/rest/site/fe/user/get?site=' + siteId, function(rsp) {
            $scope.user = rsp.data;
            srvPlanApp.opGet().then(function(oApp) {
                oApp.scenario = 'quiz';
                $scope.app = oApp;
                /*上一次访问状态*/
                if (window.localStorage) {
                    if (cachedStatus = window.localStorage.getItem("site.op.matter.plan.console")) {
                        cachedStatus = JSON.parse(cachedStatus);
                        if (lastCachedStatus = cachedStatus[oApp.id]) {
                            $scope.lastCachedStatus = angular.copy(lastCachedStatus);
                        }
                    } else {
                        cachedStatus = {};
                    }
                    $timeout(function() {
                        !cachedStatus[oApp.id] && (cachedStatus[oApp.id] = {});
                        cachedStatus[oApp.id].lastAt = parseInt((new Date() * 1) / 1000);
                        window.localStorage.setItem("site.op.matter.plan.console", JSON.stringify(cachedStatus));
                    }, 6000);
                }
                $scope.$broadcast('site.op.matter.plan.app.ready', oApp);
            });
        });
    }]);
    ngApp.controller('ctrlTask', ['$scope', '$location', '$uibModal', 'http2', 'tmsSchema', function($scope, $location, $uibModal, http2, tmsSchema) {
        var execStatus = {};
        $scope.switchToRecord = function(event, task) {
            if ($scope.user.unionid) {
                var oSearch = $location.search();
                oSearch.task = task.id;
                $location.path('/rest/site/op/matter/plan/taskDetail').search(oSearch);
            } else if (event) {
                var popoverEvt, target, fnClosePopover;
                event.preventDefault();
                event.stopPropagation();
                target = event.target;
                popoverEvt = document.createEvent("HTMLEvents");
                popoverEvt.initEvent('show', true, false);
                target.dispatchEvent(popoverEvt);
                fnClosePopover = function() {
                    popoverEvt = document.createEvent("HTMLEvents");
                    popoverEvt.initEvent('hide', true, false);
                    target.dispatchEvent(popoverEvt);
                    if (execStatus.pendingByLogin && execStatus.pendingByLogin.name === 'switchToRecord') {
                        delete execStatus.pendingByLogin;
                    }
                    document.body.removeEventListener('click', fnClosePopover);
                };
                document.body.addEventListener('click', fnClosePopover);
                execStatus.pendingByLogin = { name: 'switchToRecord', args: [null, oRecord] };
            }
        };
        $scope.switchToLogin = function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (window.sessionStorage && execStatus.pendingByLogin) {
                var method = JSON.stringify(execStatus.pendingByLogin);
                window.sessionStorage.setItem('site.op.matter.plan.pendingByLogin', method);
            }
            location.href = '/rest/site/fe/user/login?site=' + siteId;
        };
        $scope.getRecords = function(pageNumber) {
            $scope.rows.reset();
            pageNumber && ($scope.page.at = pageNumber);
            var url = '/rest/site/op/matter/plan/task/list?site=' + _oApp.siteid + '&app=' + _oApp.id + '&accessToken=' + accessId + $scope.page.j();
            http2.post(url, _oCriteria, function(rsp) {
                var tasks, oSchemasById;
                tasks = rsp.data.tasks;
                oSchemasById = {};
                _oApp.checkSchemas.forEach(function(oSchema) {
                    oSchemasById[oSchema.id] = oSchema;
                });
                tasks.forEach(function(oTask) {
                    var oFirstAction, oFirstData;
                    if (oTask.actions.length) {
                        oFirstAction = oTask.actions[0];
                    }
                    if (oFirstAction && oTask.data && oTask.data[oFirstAction.id]) {
                        oFirstData = oTask.data[oFirstAction.id];
                        oFirstData = tmsSchema.forTable({ data: oFirstData }, oSchemasById);
                        oTask._data = oFirstData._data;
                    }
                });
                $scope.tasks = tasks;
                rsp.data.total && (_oPage.total = rsp.data.total);
                _oPage.setTotal(rsp.data.total);
            });
        };
        $scope.batchVerify = function() {
            var ids = [],
                selectedTasks = [];
            for (var p in $scope.rows.selected) {
                if ($scope.rows.selected[p] === true) {
                    ids.push($scope.tasks[p].id);
                    selectedTasks.push($scope.tasks[p]);
                }
            }
            if (ids.length) {
                http2.post('/rest/site/op/matter/plan/task/batchVerify?site=' + _oApp.siteid + '&app=' + _oApp.id + '&accessToken=' + accessId, {
                    ids: ids
                }, function(rsp) {
                    selectedTasks.forEach(function(oTask) {
                        oTask.verified = 'Y';
                    });
                });
            }
        };
        $scope.filter = function() {
            var that = this;
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/plan/component/planFilter.html?_=1',
                controller: 'ctrlPlanFilter',
                windowClass: 'auto-height',
                backdrop: 'static',
                resolve: {
                    tasks: function() {
                        return angular.copy(_oApp.taskSchemas);
                    },
                    dataSchemas: function() {
                        return angular.copy(_oApp.checkSchemas);
                    },
                    criteria: function() {
                        return angular.copy(that.criteria);
                    }
                }
            }).result.then(function(criteria) {
                angular.extend(that.criteria, criteria);
                that.getRecords(1);
            });
        };
        $scope.countSelected = function() {
            var count = 0;
            for (var p in $scope.rows.selected) {
                if ($scope.rows.selected[p] === true) {
                    count++;
                }
            }
            return count;
        };
        // 选中的记录
        $scope.rows = {
            allSelected: 'N',
            selected: {},
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
            }
        };
        $scope.$watch('rows.allSelected', function(checked) {
            var index = 0;
            if (checked === 'Y') {
                while (index < $scope.tasks.length) {
                    $scope.rows.selected[index++] = true;
                }
            } else if (checked === 'N') {
                $scope.rows.selected = {};
            }
        });
        $scope.bShowImage = false;
        var _oApp, _oPage, _oCriteria, _oGroup = {};
        $scope.page = _oPage = {
            at: 1,
            size: 10,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            },
            setTotal: function(total) {
                var lastNumber;
                this.total = total;
                this.numbers = [];
                lastNumber = this.total > 0 ? Math.ceil(this.total / this.size) : 1;
                for (var i = 1; i <= lastNumber; i++) {
                    this.numbers.push(i);
                }
            }
        }; // 分页条件
        $scope.criteria = _oCriteria = {
            record: {
                verified: '',
            },
            byTaskSchema: '',
            tags: [],
            data: {},
            keyword: ''
        };
        $scope.$watch('user', function(oUser) {
            if (!oUser) return;
            if (window.sessionStorage) {
                var pendingByLogin;
                if (pendingByLogin = window.sessionStorage.getItem('site.op.matter.plan.pendingByLogin')) {
                    window.sessionStorage.removeItem('site.op.matter.plan.pendingByLogin');
                    if (oUser.loginExpire) {
                        pendingByLogin = JSON.parse(pendingByLogin);
                        $scope[pendingByLogin.name].apply($scope, pendingByLogin.args || []);
                    }
                }
            }
            $scope.$watch('app', function(app) {
                if (!app) return;
                if (app.entryRule.scope.group && app.entryRule.scope.group == 'Y' && app.groupApp.rounds.length) {
                    app.groupApp.rounds.forEach(function(oTeam) {
                        _oGroup[oTeam.team_id] = round;
                    });
                }
                app._rounds = _oGroup;
                _oApp = app;
                // schemas
                $scope.getRecords(1);
                window.loading.finish();
            });
        });
    }]);
    ngApp.controller('ctrlTaskDetail', ['$scope', '$timeout', 'http2', '$q', 'noticebox', 'srvPlanRecord', 'tmsSchema', function($scope, $timeout, http2, $q, noticebox, srvPlanRecord, tmsSchema) {
        function doTask(seq) {
            var task = _oTasksOfBeforeSubmit[seq];
            task().then(function(rsp) {
                seq++;
                seq < _oTasksOfBeforeSubmit.length ? doTask(seq) : doSave();
            });
        }

        function doSave() {
            //oRecord 原始数据
            //updated 上传数据包
            var updated = {},
                url = '/rest/pl/fe/matter/plan/task/update' + location.search;
            updated.data = _oTask.data;
            updated.supplement = _oTask.supplement;
            http2.post(url, updated, function(rsp) {
                noticebox.success('完成保存');
            });
        }

        $scope.chooseImage = function(action, schema) {
            var data = _oTask.data;
            srvPlanRecord.chooseImage(schema.id).then(function(img) {
                !data[action.id][schema.id] && (data[action.id][schema.id] = []);
                data[action.id][schema.id].push(img);
            });
        };
        $scope.removeImage = function(field, index) {
            field.splice(index, 1);
        };
        $scope.chooseFile = function(action, schema) {
            var r, onSubmit;
            r = new Resumable({
                target: '/rest/site/fe/matter/plan/task/uploadFile?site=' + _oApp.siteid + '&app=' + _oApp.id,
                testChunks: false,
                chunkSize: 512 * 1024
            });
            onSubmit = function($scope) {
                var defer;
                defer = $q.defer();
                if (!r.files || r.files.length === 0)
                    defer.resolve('empty');
                r.on('progress', function() {
                    var phase, p;
                    p = r.progress();
                    var phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        $scope.progressOfUploadFile = Math.ceil(p * 100);
                    } else {
                        $scope.$apply(function() {
                            $scope.progressOfUploadFile = Math.ceil(p * 100);
                        });
                    }
                });
                r.on('complete', function() {
                    var phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        $scope.progressOfUploadFile = '完成';
                    } else {
                        $scope.$apply(function() {
                            $scope.progressOfUploadFile = '完成';
                        });
                    }
                    r.cancel();
                    defer.resolve('ok');
                });
                r.upload();
                return defer.promise;
            };
            $scope.beforeSubmit(function() {
                return onSubmit($scope);
            });
            var data = _oTask.data;
            var ele = document.createElement('input');
            ele.setAttribute('type', 'file');
            ele.addEventListener('change', function(evt) {
                var i, cnt, f;
                cnt = evt.target.files.length;
                for (i = 0; i < cnt; i++) {
                    f = evt.target.files[i];
                    r.addFile(f);
                    $scope.$apply(function() {
                        data[action.id] === undefined && (data[action.id] = {});
                        data[action.id][schema.id] === undefined && (data[action.id][schema.id] = []);
                        data[action.id][schema.id].push({
                            uniqueIdentifier: r.files[r.files.length - 1].uniqueIdentifier,
                            name: f.name,
                            size: f.size,
                            type: f.type,
                            url: ''
                        });
                    });
                }
                ele = null;
            }, true);
            ele.click();
        };
        $scope.removeFile = function(field, index) {
            field.splice(index, 1);
        };
        $scope.beforeSubmit = function(fn) {
            if (_oTasksOfBeforeSubmit.indexOf(fn) === -1) {
                _oTasksOfBeforeSubmit.push(fn);
            }
        };
        var _oApp, _oTask, _oUpdated, _oTasksOfBeforeSubmit;
        _oTasksOfBeforeSubmit = [];

        // 更新的任务数据
        _oUpdated = {};
        $scope.modified = false;
        $scope.updateTask = function(prop) {
            $scope.modified = true;
            _oUpdated[prop] = _oTask[prop];
        };
        $scope.saveTask = function() {
            http2.post('/rest/pl/fe/matter/plan/task/update' + location.search, _oUpdated, function(rsp) {
                $scope.modified = false;
            });
        };
        $scope.saveData = function() {
            _oTasksOfBeforeSubmit.length ? doTask(0) : doSave();
        };
        $scope.$watch('app', function(app) {
            if (!app) return;
            _oApp = app;
            http2.get('/rest/pl/fe/matter/plan/task/get' + location.search, function(rsp) {
                $scope.task = _oTask = rsp.data;
                $scope.data = _oTask.data;
                $scope.supplement = _oTask.supplement;
                _oTask.taskSchema.actions.forEach(function(oAction) {
                    if (_oApp.checkSchemas && _oApp.checkSchemas.length) {
                        oAction.checkSchemas = [].concat(_oApp.checkSchemas, oAction.checkSchemas);
                    }
                    oAction.checkSchemas.forEach(function(oSchema) {
                        tmsSchema.forEdit(oSchema, _oTask.data[oAction.id]);
                    });
                });
                window.loading.finish();
            });
        });
    }]);
    ngApp.controller('ctrlReport', ['$scope', '$location', '$uibModal', '$timeout', '$q', 'http2', 'tmsSchema', function($scope, $location, $uibModal, $timeout, $q, http2, tmsSchema) {
        var ls = $location.search();

        $scope.appId = ls.app;
        $scope.siteId = ls.site;
        $scope.accessToken = ls.accessToken;

        function drawBarChart(item) {
            var categories = [],
                series = [];

            item.ops.forEach(function(op) {
                categories.push(op.l);
                series.push(parseInt(op.c));
            });
            new Highcharts.Chart({
                chart: {
                    type: 'bar',
                    renderTo: item.id
                },
                title: {
                    text: '' //item.title
                },
                legend: {
                    enabled: false
                },
                xAxis: {
                    categories: categories
                },
                yAxis: {
                    'title': '',
                    allowDecimals: false
                },
                series: [{
                    name: '数量',
                    data: series
                }],
                lang: {
                    downloadJPEG: "下载JPEG 图片",
                    downloadPDF: "下载PDF文档",
                    downloadPNG: "下载PNG 图片",
                    downloadSVG: "下载SVG 矢量图",
                    printChart: "打印图片",
                    exportButtonTitle: "导出图片"
                }
            });
        }

        function drawPieChart(item) {
            var categories = [],
                series = [];

            item.ops.forEach(function(op) {
                series.push({
                    name: op.l,
                    y: parseInt(op.c)
                });
            });
            new Highcharts.Chart({
                chart: {
                    type: 'pie',
                    renderTo: item.id
                },
                title: {
                    text: '' //item.title
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>:{y}',
                            style: {
                                color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                            }
                        }
                    }
                },
                series: [{
                    name: '数量',
                    data: series
                }],
                lang: {
                    downloadJPEG: "下载JPEG 图片",
                    downloadPDF: "下载PDF文档",
                    downloadPNG: "下载PNG 图片",
                    downloadSVG: "下载SVG 矢量图",
                    printChart: "打印图片",
                    exportButtonTitle: "导出图片"
                }
            });
        }

        function drawLineChart(item) {
            var categories = [],
                data = [];

            item.ops.forEach(function(op) {
                categories.push(op.l);
                data.push(op.c);
            });
            new Highcharts.Chart({
                chart: {
                    type: 'line',
                    renderTo: item.id
                },
                title: {
                    text: '' //item.title,
                },
                xAxis: {
                    categories: categories
                },
                yAxis: {
                    title: {
                        text: '平均分'
                    },
                    plotLines: [{
                        value: 0,
                        width: 1,
                        color: '#808080'
                    }]
                },
                series: [{
                    name: item.title,
                    data: data
                }],
                lang: {
                    downloadJPEG: "下载JPEG 图片",
                    downloadPDF: "下载PDF文档",
                    downloadPNG: "下载PNG 图片",
                    downloadSVG: "下载SVG 矢量图",
                    printChart: "打印图片",
                    exportButtonTitle: "导出图片"
                }
            });
        }

        function drawNumPieChart(item, schema) {
            var categories = [],
                series = [],
                sum = 0,
                otherSum;
            item.records.forEach(function(record) {
                var recVal = record.data[schema.id] ? parseInt(record.data[schema.id]) : 0;
                sum += recVal;
                series.push({
                    name: recVal,
                    y: recVal
                });
            });
            otherSum = parseInt(item.sum) - sum;
            if (otherSum != 0) {
                series.push({ name: '其它', y: otherSum });
            }

            new Highcharts.Chart({
                chart: {
                    type: 'pie',
                    renderTo: schema.id
                },
                title: {
                    text: '' //schema.title
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                            style: {
                                color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                            }
                        }
                    }
                },
                series: [{
                    name: '所占百分比',
                    data: series
                }],
                lang: {
                    downloadJPEG: "下载JPEG 图片",
                    downloadPDF: "下载PDF文档",
                    downloadPNG: "下载PNG 图片",
                    downloadSVG: "下载SVG 矢量图",
                    printChart: "打印图片",
                    exportButtonTitle: "导出图片"
                }
            });
        }

        var _cacheOfRecordsBySchema = {
            recordsBySchema: function(schema, page) {
                var deferred = $q.defer(),
                    cached,
                    requireGet = false,
                    url;

                if (cached = _cacheOfRecordsBySchema[schema.id]) {
                    if (cached.page && cached.page.at === page.at) {
                        records = cached.records;
                        deferred.resolve(records);
                    } else {
                        if (cached._running) {
                            deferred.resolve(false);
                            return false;
                        }
                        requireGet = true;
                    }
                } else {
                    cached = {};
                    _cacheOfRecordsBySchema[schema.id] = cached;
                    requireGet = true;
                }

                if (requireGet) {
                    /member/.test(schema.id) && (schema.id = 'member');
                    url = '/rest/site/op/matter/plan/task/listSchema';
                    url += '?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&checkSchmId=' + schema.id;
                    url += '&accessToken=' + $scope.accessToken;
                    url += '&taskSchmId=' + ($scope.app.rpConfig.taskSchmId ? $scope.app.rpConfig.taskSchmId : '');
                    url += '&actSchmId=' + ($scope.app.rpConfig.actSchmId ? $scope.app.rpConfig.actSchmId : '');
                    url += '&page=' + page.at + '&size=' + page.size;
                    cached._running = true;
                    http2.get(url, function(rsp) {
                        cached._running = false;
                        cached.page = {
                            at: page.at,
                            size: page.size
                        };
                        rsp.data.records.forEach(function(record) {
                            tmsSchema.forTable(record.task);
                        });

                        if (schema.number && schema.number == 'Y') {
                            cached.sum = rsp.data.sum;
                            drawNumPieChart(rsp.data, schema);
                        }

                        cached.records = rsp.data.records;
                        cached.page.total = rsp.data.total;
                        deferred.resolve(rsp.data);
                    });
                }

                return deferred.promise;
            }
        };

        $scope.getRecords = function(schema, page) {
            var cached;
            if (cached = _cacheOfRecordsBySchema[schema.id]) {
                if (cached.page && cached.page.at === page.at) {
                    page.total = cached.page.total;
                    return cached;
                }
            }
            _cacheOfRecordsBySchema.recordsBySchema(schema, page);
            return false;
        };

        var url = '/rest/site/op/matter/plan/report/get';
        url += '?site=' + $scope.siteId;
        url += '&app=' + $scope.appId;
        url += '&accessToken=' + $scope.accessToken;

        http2.get(url, function(rsp) {
            var stat = {};
            $scope.data = rsp.data;
            tmsSchema.config(rsp.data.checkSchemas);
            rsp.data.checkSchemas.forEach(function(schema) {
                if (rsp.data.stat[schema.id]) {
                    rsp.data.stat[schema.id]._schema = schema;
                    stat[schema.id] = rsp.data.stat[schema.id];
                }
            });
            $scope.stat = stat;

            $timeout(function() {
                var p, item, scoreSummary = [],
                    totalScoreSummary = 0,
                    avgScoreSummary = 0;
                for (p in stat) {
                    item = stat[p];
                    if (/single|phase/.test(item._schema.type)) {
                        drawPieChart(item);
                    } else if (/multiple/.test(item._schema.type)) {
                        drawBarChart(item);
                    }
                }
            });
            window.loading.finish();
        });
    }]);
    ngApp.controller('ctrlPlanFilter', ['$scope', '$uibModalInstance', 'tasks', 'dataSchemas', 'criteria', function($scope, $mi, tasks, dataSchemas, lastCriteria) {
        var canFilteredSchemas = [];
        $scope.tasks = tasks;
        dataSchemas.forEach(function(schema) {
            if (false === /image|file|score|html/.test(schema.type) && schema.id.indexOf('member') !== 0) {
                canFilteredSchemas.push(schema);
            }
            if (/multiple/.test(schema.type)) {
                var options = {};
                if (lastCriteria.data[schema.id]) {
                    lastCriteria.data[schema.id].split(',').forEach(function(key) {
                        options[key] = true;
                    })
                }
                lastCriteria.data[schema.id] = options;
            }
            $scope.schemas = canFilteredSchemas;
            $scope.criteria = lastCriteria;
        });
        $scope.clean = function() {
            var criteria = $scope.criteria;
            if (criteria.record) {
                if (criteria.record.verified) {
                    criteria.record.verified = '';
                }
            }
            if (criteria.data) {
                angular.forEach(criteria.data, function(val, key) {
                    criteria.data[key] = '';
                });
            }
        };
        $scope.ok = function() {
            var criteria = $scope.criteria,
                optionCriteria;
            // 将单选题/多选题的结果拼成字符串
            canFilteredSchemas.forEach(function(schema) {
                var result;
                if (/multiple/.test(schema.type)) {
                    if ((optionCriteria = criteria.data[schema.id])) {
                        result = [];
                        Object.keys(optionCriteria).forEach(function(key) {
                            optionCriteria[key] && result.push(key);
                        });
                        criteria.data[schema.id] = result.join(',');
                    }
                }
            });
            $mi.close(criteria);
        };
        $scope.cancel = function() {
            $mi.dismiss('cancel');
        };
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});