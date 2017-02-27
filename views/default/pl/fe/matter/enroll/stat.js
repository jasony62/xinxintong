define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlStat', ['$scope', 'http2', '$timeout', '$q', 'srvApp', function($scope, http2, $timeout, $q, srvApp) {
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
                    url = '/rest/pl/fe/matter/enroll/record/list4Schema';
                    url += '?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
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
        $scope.export = function() {
            var url, params = {};

            url = '/rest/pl/fe/matter/enroll/stat/export';
            url += '?site=' + $scope.app.siteid + '&app=' + $scope.app.id;

            http2.post(url, params, function(rsp) {
                var blob;

                blob = new Blob([rsp.data], {
                    type: "application/vnd.ms-word;charset=utf-8;"
                });

                saveAs(blob, $scope.app.title + '.doc');
            });
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
        srvApp.get().then(function(app) {
            var url = '/rest/pl/fe/matter/enroll/stat/get';
            url += '?site=' + $scope.app.siteid;
            url += '&app=' + app.id;
            http2.get(url, function(rsp) {
                var stat = {};
                app.data_schemas.forEach(function(schema) {
                    if (rsp.data[schema.id]) {
                        rsp.data[schema.id]._schema = schema;
                        stat[schema.id] = rsp.data[schema.id];
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
        });
    }]);
});
