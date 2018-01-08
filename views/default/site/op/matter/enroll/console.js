'use strict';
define(["require", "angular", "enrollService"], function(require, angular) {
    var ls, siteId, appId, accessId, ngApp;
    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    appId = ls.match(/[\?&]app=([^&]*)/)[1];
    accessId = ls.match(/[\?&]accessToken=([^&]*)/)[1];

    ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter', 'service.enroll']);
    ngApp.constant('cstApp', {});
    ngApp.config(['$locationProvider', '$routeProvider', '$uibTooltipProvider', 'srvEnrollAppProvider', 'srvOpEnrollRecordProvider', 'srvEnrollRecordProvider', 'srvOpEnrollRoundProvider', function($locationProvider, $routeProvider, $uibTooltipProvider, srvEnrollAppProvider, srvOpEnrollRecordProvider, srvEnrollRecordProvider, srvOpEnrollRoundProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/site/op/matter/enroll/');
            this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
        };
        $routeProvider
            .when('/rest/site/op/matter/enroll/list', new RouteParam('list'))
            .when('/rest/site/op/matter/enroll/report', new RouteParam('report'))
            .when('/rest/site/op/matter/enroll/record', new RouteParam('record'))
            .when('/rest/site/op/matter/enroll/remarks', new RouteParam('remarks'))
            .otherwise(new RouteParam('list'));
        //
        $locationProvider.html5Mode(true);
        //
        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });
        //
        srvEnrollAppProvider.config(siteId, appId, accessId);
        srvOpEnrollRecordProvider.config(siteId, appId, accessId);
        srvOpEnrollRoundProvider.config(siteId, appId, accessId);
        srvEnrollRecordProvider.config(siteId, appId);
    }]);
    ngApp.controller('ctrlApp', ['$scope', '$timeout', '$location', 'http2', 'srvEnrollApp', function($scope, $timeout, $location, http2, srvEnrollApp) {
        var cachedStatus, lastCachedStatus;
        $scope.switchTo = function(view) {
            $location.path('/rest/site/op/matter/enroll/' + view);
        };
        http2.get('/rest/site/fe/user/get?site=' + siteId, function(rsp) {
            $scope.user = rsp.data;
            srvEnrollApp.opGet().then(function(oApp) {
                // schemas
                var recordSchemas = [],
                    recordSchemas2 = [],
                    remarkableSchemas = [],
                    imageSchemas = [],
                    enrollDataSchemas = [],
                    groupDataSchemas = [],
                    numberSchemas = [];
                oApp.dataSchemas.forEach(function(schema) {
                    if (schema.type !== 'html') {
                        recordSchemas.push(schema);
                        recordSchemas2.push(schema);
                    }
                    if (schema.type === 'image') {
                        imageSchemas.push(schema);
                    }
                    if (schema.remarkable && schema.remarkable === 'Y') {
                        remarkableSchemas.push(schema);
                        recordSchemas2.push({ type: 'remark', title: '评论数', id: schema.id });
                        recordSchemas2.push({ type: 'agreed', title: '设置态度', id: schema.id });
                    }
                    if (schema.format && schema.format === 'number') {
                        numberSchemas.push(schema);
                    }
                });
                $scope.recordSchemas = recordSchemas;
                $scope.recordSchemas2 = recordSchemas2;
                $scope.remarkableSchemas = remarkableSchemas;
                $scope.imageSchemas = imageSchemas;
                $scope.numberSchemas = numberSchemas;
                oApp._schemasFromEnrollApp.forEach(function(schema) {
                    if (schema.type !== 'html') {
                        enrollDataSchemas.push(schema);
                    }
                });
                $scope.enrollDataSchemas = enrollDataSchemas;
                oApp._schemasFromGroupApp.forEach(function(schema) {
                    if (schema.type !== 'html') {
                        groupDataSchemas.push(schema);
                    }
                });
                $scope.app = oApp;
                $scope.groupDataSchemas = groupDataSchemas;
                /*上一次访问状态*/
                if (window.localStorage) {
                    if (cachedStatus = window.localStorage.getItem("site.op.matter.enroll.console")) {
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
                        window.localStorage.setItem("site.op.matter.enroll.console", JSON.stringify(cachedStatus));
                    }, 6000);
                }
                $scope.$broadcast('site.op.matter.enroll.app.ready', oApp);
            });
        });
    }]);
    ngApp.controller('ctrlList', ['$scope', '$location', 'srvOpEnrollRecord', 'srvEnrollApp', function($scope, $location, srvOpEnrollRecord, srvEnrollApp) {
        function fnSum4Schema() {
            var sum4SchemaAtPage;
            $scope.sum4SchemaAtPage = sum4SchemaAtPage = {};
            if ($scope.numberSchemas.length) {
                srvOpEnrollRecord.sum4Schema().then(function(result) {
                    $scope.sum4Schema = result;
                    for (var p in result) {
                        if ($scope.records.length) {
                            $scope.records.forEach(function(oRecord) {
                                if (sum4SchemaAtPage[p]) {
                                    sum4SchemaAtPage[p] += oRecord.data[p] ? parseInt(oRecord.data[p]) : 0;
                                } else {
                                    sum4SchemaAtPage[p] = oRecord.data[p] ? parseInt(oRecord.data[p]) : 0;
                                }
                            });
                        } else {
                            sum4SchemaAtPage[p] = 0;
                        }
                    }
                });
            }
        }
        var execStatus = {};
        $scope.switchToRecord = function(event, oRecord) {
            if ($scope.user.unionid) {
                var oSearch = $location.search();
                oSearch.ek = oRecord.enroll_key;
                $location.path('/rest/site/op/matter/enroll/record').search(oSearch);
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
                window.sessionStorage.setItem('site.op.matter.enroll.pendingByLogin', method);
            }
            location.href = '/rest/site/fe/user/login?site=' + siteId;
        };
        $scope.getRecords = function(pageNumber) {
            $scope.rows.reset();
            srvOpEnrollRecord.search(pageNumber).then(function() {
                fnSum4Schema();
            });
        };
        $scope.setAgreed = function(oRecord, schemaId) {
            srvOpEnrollRecord.agree(oRecord, schemaId, oRecord.verbose.data[schemaId].agreed);
        };
        $scope.removeRecord = function(event, oRecord) {
            if ($scope.user.unionid) {
                srvOpEnrollRecord.remove(oRecord);
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
                    if (execStatus.pendingByLogin && execStatus.pendingByLogin.name === 'removeRecord') {
                        delete execStatus.pendingByLogin;
                    }
                    document.body.removeEventListener('click', fnClosePopover);
                };
                document.body.addEventListener('click', fnClosePopover);
                execStatus.pendingByLogin = { name: 'removeRecord', args: [null, oRecord] };
            }
        };
        $scope.batchVerify = function() {
            srvOpEnrollRecord.batchVerify($scope.rows);
        };
        $scope.filter = function() {
            srvOpEnrollRecord.filter().then(function() {
                $scope.rows.reset();
                fnSum4Schema();
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
                while (index < $scope.records.length) {
                    $scope.rows.selected[index++] = true;
                }
            } else if (checked === 'N') {
                $scope.rows.selected = {};
            }
        });
        $scope.bShowImage = false;
        $scope.page = {}; // 分页条件
        $scope.criteria = {}; // 过滤条件
        $scope.records = []; // 登记记录
        $scope.tmsTableWrapReady = 'N';
        $scope.$watch('user', function(oUser) {
            if (!oUser) return;
            if (window.sessionStorage) {
                var pendingByLogin;
                if (pendingByLogin = window.sessionStorage.getItem('site.op.matter.enroll.pendingByLogin')) {
                    window.sessionStorage.removeItem('site.op.matter.enroll.pendingByLogin');
                    if (oUser.loginExpire) {
                        pendingByLogin = JSON.parse(pendingByLogin);
                        $scope[pendingByLogin.name].apply($scope, pendingByLogin.args || []);
                    }
                }
            }
            $scope.$watch('app', function(app) {
                if (!app) return;
                srvOpEnrollRecord.init(app, $scope.page, $scope.criteria, $scope.records);
                // schemas
                $scope.tmsTableWrapReady = 'Y';
                $scope.getRecords();
                window.loading.finish();
            });
        });
    }]);
    ngApp.controller('ctrlRecord', ['$scope', '$timeout', '$location', 'srvEnrollApp', 'srvEnrollRecord', 'srvRecordConverter', function($scope, $timeout, $location, srvEnrollApp, srvEnrollRecord, srvRecordConverter) {
        function _quizScore(oRecord) {
            if (oRecord.verbose) {
                for (var schemaId in oRecord.verbose) {
                    oQuizScore[schemaId] = oRecord.verbose[schemaId].score;
                }
                oBeforeQuizScore = angular.copy(oQuizScore);
            }
        }
        function _items(schema) {
            var _item = {};
            angular.forEach(oBeforeRecord.verbose[schema.id].items, function(item) {
                _item[item.id] = item;
                oBeforeRecord.verbose[schema.id]._items = _item;
            });
        }
        var oApp, oRecord, oBeforeRecord, oQuizScore, oBeforeQuizScore;

        $scope.scoreRangeArray = function(schema) {
            var arr = [];
            if (schema.range && schema.range.length === 2) {
                for (var i = schema.range[0]; i <= schema.range[1]; i++) {
                    arr.push('' + i);
                }
            }
            return arr;
        };
        $scope.save = function() {
            var updated = {};

            if (oRecord.aTags) {
                updated.tags = oRecord.aTags.join(',');
                oRecord.tags = updated.tags;
            }
            updated.comment = oRecord.comment; //oRecord 信息
            updated.verified = oRecord.verified;
            updated.rid = oRecord.rid;
            if (oRecord.enroll_key) {
                if (!angular.equals(oRecord.data, oBeforeRecord.data)) {
                    updated.data = oRecord.data;
                }
                if (!angular.equals(oRecord.supplement, oBeforeRecord.supplement)) {
                    updated.supplement = oRecord.supplement;
                }
                if (!angular.equals(oQuizScore, oBeforeQuizScore)) {
                    updated.quizScore = oQuizScore;
                }
                srvEnrollRecord.update(oRecord, updated).then(function(newRecord) {
                    if (oApp.scenario === 'quiz') {
                        _quizScore(newRecord);
                    }
                });
            } else {
                updated.data = oRecord.data;
                updated.supplement = oRecord.supplement;
                updated.quizScore = oQuizScore;
                srvEnrollRecord.add(updated).then(function(newRecord) {
                    oRecord.enroll_key = newRecord.enroll_key;
                    oRecord.enroll_at = newRecord.enroll_at;
                    $location.search({ site: oApp.siteid, id: oApp.id, ek: newRecord.enroll_key });
                    if (oApp.scenario === 'quiz') {
                        _quizScore(newRecord);
                    }
                });
            }
            oBeforeRecord = angular.copy(oRecord);
        };

        var schemaRemarks;
        $scope.newRemark = {};
        $scope.schemaRemarks = schemaRemarks = {};
        $scope.openedRemarksSchema = false;
        $scope.switchSchemaRemarks = function(schema, itemId) {
            $scope.openedRemarksSchema = schema;
            srvEnrollRecord.listRemark(oRecord.enroll_key, schema.id, itemId).then(function(result) {
                schemaRemarks[schema.id] = result.remarks;
            });
        };
        $scope.addRemark = function(oSchema) {
            srvEnrollRecord.addRemark(oRecord.enroll_key, oSchema ? oSchema.id : null, $scope.newRemark).then(function(remark) {
                if (oSchema) {
                    !schemaRemarks[oSchema.id] && (schemaRemarks[oSchema.id] = []);
                    schemaRemarks[oSchema.id].push(remark);
                    if (oRecord.verbose[oSchema.id] === undefined) {
                        oRecord.verbose[oSchema.id] = {};
                    }
                    oRecord.verbose[oSchema.id].remark_num = schemaRemarks[oSchema.id].length;
                } else {
                    $scope.remarks.push(remark);
                }
                $scope.newRemark.content = '';
            });
        };
        $scope.agree = function(oRecord, oSchema, oAgreed, oItemId) {
            srvEnrollRecord.agree(oRecord.enroll_key, oSchema.id, oAgreed, oItemId).then(function() {});
        };
        $scope.agreeRemark = function(oRemark) {
            srvEnrollRecord.agreeRemark(oRemark.id, oRemark.agreed).then(function() {});
        };
        $scope.$watch('app', function(app) {
            if (!app) return;
            srvEnrollRecord.get($location.search().ek).then(function(data) {
                oApp = app;
                oBeforeRecord = data;
                if (oBeforeRecord.data) {
                    oApp.dataSchemas.forEach(function(schema) {
                        if (oBeforeRecord.data[schema.id]) {
                            srvRecordConverter.forEdit(schema, oBeforeRecord.data);
                            if(schema.type=='multitext') {
                                _items(schema);
                            }
                        }
                    });
                    oApp._schemasFromEnrollApp.forEach(function(schema) {
                        if (oBeforeRecord.data[schema.id]) {
                            srvRecordConverter.forEdit(schema, oBeforeRecord.data);
                        }
                    });
                    oApp._schemasFromGroupApp.forEach(function(schema) {
                        if (oBeforeRecord.data[schema.id]) {
                            srvRecordConverter.forEdit(schema, oBeforeRecord.data);
                        }
                    });
                }
                /* 点评数据 */
                var remarkableSchemas = [];
                oApp.dataSchemas.forEach(function(schema) {
                    if (schema.remarkable === 'Y') {
                        schema._open = false;
                        oBeforeRecord.verbose && oBeforeRecord.verbose[schema.id] && (schema.summary = oBeforeRecord.verbose[schema.id]);
                        remarkableSchemas.push(schema);
                    }
                });
                $scope.remarkableSchemas = remarkableSchemas;
                $scope.record = oRecord = angular.copy(oBeforeRecord);
                window.loading.finish();
            });
        });
    }]);
    ngApp.controller('ctrlReport', ['$scope', '$location', '$uibModal', '$timeout', '$q', 'http2', 'srvOpEnrollRound', 'srvRecordConverter', function($scope, $location, $uibModal, $timeout, $q, http2, srvOpEnrollRound, srvRecordConverter) {
        var rid, ls = $location.search();

        $scope.appId = ls.app;
        $scope.siteId = ls.site;
        $scope.accessToken = ls.accessToken;
        rid = ls.rid;

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
                    url = '/rest/site/op/matter/enroll/record/list4Schema';
                    url += '?site=' + $scope.siteId + '&app=' + $scope.app.id;
                    url += '&accessToken=' + $scope.accessToken;
                    url += '&schema=' + schema.id + '&page=' + page.at + '&size=' + page.size;
                    url += '&rid=' + (rid ? rid : '');
                    cached._running = true;
                    http2.get(url, function(rsp) {
                        cached._running = false;
                        cached.page = {
                            at: page.at,
                            size: page.size
                        };
                        rsp.data.records.forEach(function(record) {
                            srvRecordConverter.forTable(record);
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

        var url = '/rest/site/op/matter/enroll/report/get';
        url += '?site=' + $scope.siteId;
        url += '&app=' + $scope.appId;
        url += '&accessToken=' + $scope.accessToken;
        url += '&rid=' + (rid ? rid : '');

        http2.get(url, function(rsp) {
            var app, stat = {};

            app = rsp.data.app;
            srvRecordConverter.config(app.data_schemas);
            app.dataSchemas.forEach(function(schema) {
                if (rsp.data.stat[schema.id]) {
                    rsp.data.stat[schema.id]._schema = schema;
                    stat[schema.id] = rsp.data.stat[schema.id];
                }
            });
            $scope.app = app;
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
                    } else if (/score/.test(item._schema.type)) {
                        if (item.ops.length) {
                            var totalScore = 0,
                                avgScore = 0;
                            item.ops.forEach(function(op) {
                                op.c = parseFloat(new Number(op.c).toFixed(2));
                                totalScore += op.c;
                            });
                            drawLineChart(item);
                            // 添加题目平均分
                            avgScore = parseFloat(new Number(totalScore / item.ops.length).toFixed(2));
                            item.ops.push({
                                l: '本项平均分',
                                c: avgScore
                            });
                            scoreSummary.push({
                                l: item._schema.title,
                                c: avgScore
                            });
                            totalScoreSummary += avgScore;
                        }
                    }
                }
                if (scoreSummary.length) {
                    avgScoreSummary = parseFloat(new Number(totalScoreSummary / scoreSummary.length).toFixed(2));
                    scoreSummary.push({
                        l: '所有打分项总平均分',
                        c: avgScoreSummary
                    });
                    scoreSummary.push({
                        l: '所有打分项合计',
                        c: parseFloat(new Number(totalScoreSummary).toFixed(2))
                    });
                    $scope.scoreSummary = scoreSummary;
                }
            });
            window.loading.finish();
        });

        $scope.criteria = {
            rid: ''
        };
        $scope.doRound = function(rid) {
            if (rid == 'more') {
                $scope.moreRounds();
            } else {
                location.href = '/rest/site/op/matter/enroll?site=' + $scope.siteId + '&app=' + $scope.appId + '&accessToken=' + $scope.accessToken + '&rid=' + rid + '#report';
            }
        };
        $scope.moreRounds = function() {
            $uibModal.open({
                templateUrl: 'moreRound.html',
                backdrop: 'static',
                controller: ['$scope', '$uibModalInstance', 'srvOpEnrollRound', function($scope2, $mi, srvOpEnrollRound) {
                    $scope2.moreCriteria = {
                        rid: ''
                    }
                    $scope2.doSearchRound = function() {
                        srvOpEnrollRound.list().then(function(result) {
                            $scope2.activeRound = result.active;
                            $scope2.rounds = result.rounds;
                            $scope2.pageOfRound = result.page;
                            if (rid) {
                                if (rid === 'ALL') {
                                    $scope2.moreCriteria.rid = 'ALL';
                                } else {
                                    $scope2.rounds.forEach(function(round) {
                                        if (round.rid == rid) {
                                            $scope2.moreCriteria.rid = rid;
                                        }
                                    });
                                }
                            } else {
                                $scope2.moreCriteria.rid = $scope.activeRound.rid;
                            }
                        });
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.moreCriteria.rid);
                    }
                    $scope2.doSearchRound();
                }]
            }).result.then(function(result) {
                location.href = '/rest/site/op/matter/enroll?site=' + $scope.siteId + '&app=' + $scope.appId + '&accessToken=' + $scope.accessToken + '&rid=' + result + '#report';
            });
        };
        srvOpEnrollRound.list(rid).then(function(result) {
            $scope.activeRound = result.active;
            $scope.checkedRound = result.checked;
            $scope.rounds = result.rounds;
            if (rid) {
                if (rid === 'ALL') {
                    $scope.criteria.rid = 'ALL';
                } else if (rid == $scope.checkedRound.rid) {
                    $scope.criteria.rid = $scope.checkedRound.rid;
                } else {
                    $scope.rounds.forEach(function(round) {
                        if (round.rid == rid) {
                            $scope.criteria.rid = rid;
                        }
                    });
                }
            } else {
                $scope.criteria.rid = $scope.activeRound.rid;
            }
        });
    }]);
    ngApp.controller('ctrlRemarks', ['$scope', '$location', '$q', '$uibModal', 'http2', 'srvOpEnrollRecord', 'srvRecordConverter', function($scope, $location, $q, $uibModal, http2, srvEnrollRecord, srvRecordConverter) {
        function list(oPage) {
            var defer,
                url;

            defer = $q.defer();
            url = '/rest/site/op/matter/enroll/remark/byApp?site=' + $location.search().site + '&accessToken=' + $location.search().accessToken + '&app=' + $location.search().app + '&' + oPage.j();
            http2.post(url, oCriteria, function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;

        }

        var oAgreedLabel, oPage, oCriteria;
        oAgreedLabel = { 'Y': '推荐', 'N': '屏蔽', 'A': '接受' };
        $scope.page = oPage = {
            at: 1,
            size: 30,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size
            }
        };
        oCriteria = {};
        $scope.criteria = oCriteria = {
            orderby: 'create_at'
        };
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
                while (index < $scope.remarks.length) {
                    $scope.rows.selected[index++] = true;
                }
            } else if (checked === 'N') {
                $scope.rows.selected = {};
            }
        });
        $scope.doSearch = function(pageAt) {
            if (pageAt) {
                oPage.at = pageAt;
            }
            list(oPage).then(function(result) {
                $scope.remarks = result.remarks;
                $scope.remarks.forEach(function(oRemark) {
                    oRemark._agreed = oAgreedLabel[oRemark.agreed] || '未表态';
                });
                for (var ek in result.records) {
                    srvRecordConverter.forTable(result.records[ek], $scope.app._schemasById);
                }
                $scope.records = result.records;
                oPage.total = result.total;
                oPage.numbers = [];
                for (var i = 1, ii = Math.ceil(oPage.total / oPage.size); i <= ii; i++) {
                    oPage.numbers.push(i);
                }
            });
        };
        $scope.filter = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/remarkFilter.html?_=1',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    http2.get('/rest/site/op/matter/enroll/user/enrollee?site=' + $location.search().site + '&accessToken' + $location.search().accessToken + '&app=' + $location.search().app, function(rsp) {
                        $scope2.enrollees = rsp.data.users;
                    });
                    http2.get('/rest/site/op/matter/enroll/user/remarker?site=' + $location.search().site + '&accessToken=' + $location.search().accessToken + '&app=' + $location.search().app, function(rsp) {
                        $scope2.remarkers = rsp.data.users;
                    });
                    $scope2.criteria = oCriteria;
                    $scope2.ok = function() {
                        $mi.close($scope2.criteria);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                }],
                windowClass: 'auto-height',
                backdrop: 'static',
            }).result.then(function(oCriteria) {
                $scope.doSearch(1);
            });
        };
        $scope.chooseOrderby = function(orderby) {
            oCriteria.orderby = orderby;
            $scope.doSearch(1);
        };
        $scope.setAgreed = function(result) {
            var remarkIds = [];
            for (var i in $scope.rows.selected) {
                if ($scope.rows.selected[i]) {
                    remarkIds.push($scope.remarks[i].id);
                }
            }
            srvEnrollRecord.agreeRemark(remarkIds, result).then(function(rsp) {
                for (var i in $scope.rows.selected) {
                    if ($scope.rows.selected[i]) {
                        $scope.remarks[i].agreed = result;
                        $scope.remarks[i]._agreed = oAgreedLabel[result] || '未表态';
                    }
                }
            });
        };
        $scope.gotoRemark = function(oRemark) {
            var oSearch = $location.search();
            oSearch.ek = oRemark.enroll_key;
            oSearch.schema = oRemark.schema_id;
            oSearch.remark = oRemark.id;
            $location.path('/rest/site/op/matter/enroll/record');
        };
        $scope.$watch('app', function(nv) {
            if (nv) {
                $scope.doSearch(1);
                window.loading.finish();
            }
        });
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});