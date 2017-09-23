define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlStat', ['$scope', '$location', 'http2', '$timeout', '$q', '$uibModal', 'srvEnrollApp', 'srvEnrollRound', 'srvRecordConverter', function($scope, $location, http2, $timeout, $q, $uibModal, srvEnrollApp, srvEnrollRound, srvRecordConverter) {
        var rid = $location.search().rid;

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

        function drawNumPie(item, schema) {
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

        var _cacheOfRecordsBySchema = {
            recordsBySchema: function(schema, page) {
                var deferred = $q.defer(),
                    cached,
                    markNames,
                    requireGet = false,
                    url;

                if (cached = _cacheOfRecordsBySchema[schema.id]) {
                    if (cached.page && cached.page.at === page.at) {
                        deferred.resolve(cached);
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
                    url += '&schema=' + schema.id + '&page=' + page.at + '&size=' + page.size + '&rid=' + (rid ? rid : '');
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
                            drawNumPie(rsp.data, schema);
                        }
                        cached.records = rsp.data.records;
                        page.total = rsp.data.total;
                        deferred.resolve(cached);
                    });
                }

                return deferred.promise;
            }
        };
        $scope.criteria = {
            rid: ''
        };
        $scope.export = function() {
            var url, params = {};

            url = '/rest/pl/fe/matter/enroll/stat/export';
            url += '?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&rid=' + (rid ? rid : '');

            window.open(url);
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
        $scope.show = function() {
            $uibModal.open({
                templateUrl: 'showCondition.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var marks = $scope.app.rpConfig.marks ? $scope.app.rpConfig.marks : [];
                    $scope2.appMarkSchemas = angular.copy($scope.markSchemas);
                    $scope2.rows = {
                        selected: {},
                        reset: function() {
                            this.selected = {};
                        }
                    };
                    marks.forEach(function(item, index) {
                        for (var i = 0; i < $scope2.appMarkSchemas.length; i++) {
                            if (item.id == $scope2.appMarkSchemas[i].id) {
                                $scope2.rows.selected[i] = true;
                            }
                        }
                    });
                    $scope2.ok = function() {
                        $mi.close($scope2.rows);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                }],
                backdrop: 'static'
            }).result.then(function(result) {
                var selectedSchemas = [];
                for (var p in result.selected) {
                    if (result.selected[p] === true) {
                        selectedSchemas.push({
                            id: $scope.markSchemas[p].id,
                            name: $scope.markSchemas[p].title
                        });
                    }
                }
                $scope.app.rpConfig = {
                    marks: selectedSchemas
                };
                srvEnrollApp.update('rpConfig').then(function() { location.reload() });
            });
        }
        $scope.doRound = function(rid) {
            if (rid == 'more') {
                $scope.moreRounds();
            } else {
                location.href = '/rest/pl/fe/matter/enroll/stat?site=' + $scope.app.siteid + '&id=' + $scope.app.id + '&rid=' + rid;
            }
        };
        $scope.moreRounds = function() {
            $uibModal.open({
                templateUrl: 'moreRound.html',
                backdrop: 'static',
                controller: ['$scope', '$uibModalInstance', 'srvEnrollRound', function($scope2, $mi, srvEnrollRound) {
                    $scope2.moreCriteria = {
                        rid: ''
                    }
                    $scope2.doSearchRound = function() {
                        srvEnrollRound.list().then(function(result) {
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
                location.href = '/rest/pl/fe/matter/enroll/stat?site=' + $scope.app.siteid + '&id=' + $scope.app.id + '&rid=' + result;
            });
        }
        srvEnrollApp.get().then(function(app) {
            var url;
            srvRecordConverter.config(app.dataSchemas);
            $scope.markSchemas = [{ title: "昵称", id: "nickname" }];
            app.dataSchemas.forEach(function(schema) {
                if (['multiple', 'score', 'image', 'location', 'file', 'date'].indexOf(schema.type) === -1) {
                    $scope.markSchemas.push(schema);
                }
            })
            url = '/rest/pl/fe/matter/enroll/stat/get';
            url += '?site=' + app.siteid;
            url += '&app=' + app.id;
            url += '&rid=' + (rid ? rid : '');
            http2.get(url, function(rsp) {
                var stat = {};
                app.dataSchemas.forEach(function(schema) {
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
        srvEnrollRound.list(rid).then(function(result) {
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
});