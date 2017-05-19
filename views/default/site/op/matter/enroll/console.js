'use strict';
define(["require", "angular", "util.site", "enrollService"], function(require, angular) {
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'util.site.tms', 'ui.tms', 'ui.xxt', 'service.matter', 'service.enroll']);
    ngApp.constant('cstApp', {});
    ngApp.config(['$locationProvider', '$controllerProvider', 'srvEnrollAppProvider', 'srvOpEnrollRecordProvider', 'srvOpEnrollRoundProvider', function($locationProvider, $cp, srvEnrollAppProvider, srvOpEnrollRecordProvider, srvOpEnrollRoundProvider) {
        ngApp.provider = {
            controller: $cp.register
        };
        (function() {
            var ls, siteId, appId, accessId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            appId = ls.match(/[\?&]app=([^&]*)/)[1];
            accessId = ls.match(/[\?&]accessToken=([^&]*)/)[1];
            //
            srvEnrollAppProvider.config(siteId, appId, accessId);
            srvOpEnrollRecordProvider.config(siteId, appId, accessId);
            srvOpEnrollRoundProvider.config(siteId, appId, accessId);
        })();
        $locationProvider.html5Mode(true);
    }]);
    ngApp.controller('ctrlApp', ['$scope', function($scope) {
        var appState;
        $scope.appState = appState = {};
        if (location.hash && location.hash === '#report') {
            appState.view = 'report';
        } else {
            appState.view = 'list';
        }
    }]);
    ngApp.controller('ctrlList', ['$scope', '$timeout', 'PageLoader', 'PageUrl', 'srvOpEnrollRecord', 'srvEnrollApp', function($scope, $timeout, PageLoader, PageUrl, srvOpEnrollRecord, srvEnrollApp) {
        var oRecord, oBeforeRecord, PU, params = location.search.match('site=(.*)')[1];
        PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app', 'accessToken']);

        $scope.editRecord = function(record) {
            var url;
            url = '/rest/pl/fe/matter/enroll/editor';
            url += '?site=' + PU.params.app.site;
            url += '&id=' + PU.params.app;
            url += '&ek=' + record.enroll_key;
            location.href = url;
        };
        $scope.getRecords = function(pageNumber) {
            $scope.rows.reset();
            srvOpEnrollRecord.search(pageNumber);
            srvOpEnrollRecord.sum4Schema().then(function(result) {
                $scope.sum4Schema = result;
            });
        };
        $scope.removeRecord = function(record) {
            srvOpEnrollRecord.remove(record);
        };
        $scope.batchVerify = function() {
            srvOpEnrollRecord.batchVerify($scope.rows);
        };
        $scope.filter = function() {
            srvOpEnrollRecord.filter().then(function() {
                $scope.rows.reset();
                srvOpEnrollRecord.sum4Schema().then(function(result) {
                    $scope.sum4Schema = result;
                });
            });
        };
        $scope.scoreRangeArray = function(schema) {
            var arr = [];
            if (schema.range && schema.range.length === 2) {
                for (var i = schema.range[0]; i <= schema.range[1]; i++) {
                    arr.push('' + i);
                }
            }
            return arr;
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
        $scope.page = {}; // 分页条件
        $scope.criteria = {}; // 过滤条件
        $scope.records = []; // 登记记录
        $scope.numberSchemas = []; // 数值型登记项
        $scope.tmsTableWrapReady = 'N';
        srvEnrollApp.opGet().then(function(data) {
            var app = data.app,
                pages = data.page;
            srvOpEnrollRecord.init(app, $scope.page, $scope.criteria, $scope.records);
            PageLoader.render($scope, pages, ngApp).then(function() {
                $scope.doc = pages;
            });
            // schemas
            var recordSchemas = [],
                enrollDataSchemas = [],
                groupDataSchemas = [];
            app.data_schemas.forEach(function(schema) {
                if (schema.type !== 'html') {
                    recordSchemas.push(schema);
                }
                if (schema.number && schema.number === 'Y') {
                    $scope.numberSchemas.push(schema);
                }
            });
            $scope.recordSchemas = recordSchemas;
            app._schemasFromEnrollApp.forEach(function(schema) {
                if (schema.type !== 'html') {
                    enrollDataSchemas.push(schema);
                }
            });
            $scope.enrollDataSchemas = enrollDataSchemas;
            app._schemasFromGroupApp.forEach(function(schema) {
                if (schema.type !== 'html') {
                    groupDataSchemas.push(schema);
                }
            });
            $scope.app = app;
            $scope.groupDataSchemas = groupDataSchemas;
            $scope.tmsTableWrapReady = 'Y';
            $scope.getRecords();
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
                    text: item.title
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
                    text: item.title
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
                    text: item.title,
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
                    text: schema.title
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
                        page.total = rsp.data.total;
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
            app.data_schemas = JSON.parse(app.data_schemas);
            srvRecordConverter.config(app.data_schemas);
            app.data_schemas.forEach(function(schema) {
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
                        })
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
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});
