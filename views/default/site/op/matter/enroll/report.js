'use strict';
var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms']);
ngApp.config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlApp', ['$scope', '$location', '$timeout', '$q', 'http2', function($scope, $location, $timeout, $q, http2) {
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
                cached._running = true;
                http2.get(url, function(rsp) {
                    cached._running = false;
                    cached.page = {
                        at: page.at,
                        size: page.size
                    };
                    if (schema.type === 'image') {
                        rsp.data.records.forEach(function(rec) {
                            if (rec.value) {
                                rec.value = rec.value.split(',');
                            }
                        });
                    } else if (schema.type === 'file') {
                        rsp.data.records.forEach(function(rec) {
                            if (rec.value) {
                                rec.value = JSON.parse(rec.value)
                            }
                        });
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
                return cached.records;
            }
        }
        _cacheOfRecordsBySchema.recordsBySchema(schema, page);
        return false;
    };

    var url = '/rest/site/op/matter/enroll/report/get';
    url += '?site=' + $scope.siteId;
    url += '&app=' + $scope.appId;
    url += '&accessToken=' + $scope.accessToken;

    http2.get(url, function(rsp) {
        var app, stat = {};

        app = rsp.data.app;
        app.data_schemas = JSON.parse(app.data_schemas);
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
    });
}]);
