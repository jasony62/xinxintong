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
            .otherwise(new RouteParam('report'));
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
        $scope.onlyReport = true;
        $scope.switchTo = function(view) {
            $location.path('/rest/site/op/matter/enroll/' + view);
        };
        http2.get('/rest/site/fe/user/get?site=' + siteId, function(rsp) {
            $scope.user = rsp.data;
            srvEnrollApp.opGet().then(function(data) {
                var oApp = data.app;
                // schemas
                var recordSchemas = [],
                    recordSchemas2 = [],
                    remarkableSchemas = [],
                    enrollDataSchemas = [],
                    groupDataSchemas = [],
                    numberSchemas = [];
                oApp.data_schemas.forEach(function(schema) {
                    if (schema.type !== 'html') {
                        recordSchemas.push(schema);
                        recordSchemas2.push(schema);
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
                $scope.$broadcast('site.op.matter.enroll.app.ready', data);
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
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});
