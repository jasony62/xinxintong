define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlStat', ['$scope', '$location', 'http2', '$timeout', '$q', '$uibModal', 'srvEnrollApp', 'srvEnrollRound', 'srvRecordConverter', 'srvChart', function($scope, $location, http2, $timeout, $q, $uibModal, srvEnrollApp, srvEnrollRound, srvRecordConverter, srvChart) {
        var _rid, _oChartConfig, _cacheOfRecordsBySchema;

        _rid = $location.search().rid;
        _cacheOfRecordsBySchema = {
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
                    url += '&schema=' + schema.id + '&page=' + page.at + '&size=' + page.size + '&rid=' + (_rid || '');
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
                            srvChart.drawNumPie(rsp.data, schema);
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
        $scope.config = function() {
            $uibModal.open({
                templateUrl: 'config.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var oApp, marks, oPlConfig, oOpConfig;
                    oApp = $scope.app;
                    marks = oApp.rpConfig.marks ? oApp.rpConfig.marks : [];
                    oPlConfig = oApp.rpConfig.pl ? oApp.rpConfig.pl : {
                        number: 'Y',
                        percentage: 'Y',
                        label: 'number',
                        exclude: []
                    };
                    oOpConfig = oApp.rpConfig.op ? oApp.rpConfig.op : {
                        number: 'Y',
                        percentage: 'Y',
                        label: 'number',
                        exclude: []
                    };
                    $scope2.dataSchemas = oApp._schemasForInput;
                    $scope2.appMarkSchemas = angular.copy($scope.markSchemas);
                    $scope2.markRows = {
                        selected: {},
                    };
                    $scope2.plConfig = oPlConfig;
                    $scope2.opExcludeRows = {
                        selected: {}
                    };
                    $scope2.opConfig = oOpConfig;
                    oOpConfig.exclude.forEach(function(schemaId) {
                        $scope2.opExcludeRows.selected[schemaId] = true;
                    });
                    marks.forEach(function(item, index) {
                        for (var i = 0; i < $scope2.appMarkSchemas.length; i++) {
                            if (item.id == $scope2.appMarkSchemas[i].id) {
                                $scope2.markRows.selected[i] = true;
                            }
                        }
                    });
                    $scope2.ok = function() {
                        oOpConfig.exclude = Object.keys($scope2.opExcludeRows.selected);
                        $mi.close({ marks: $scope2.markRows.selected, pl: oPlConfig, op: oOpConfig });
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                }],
                backdrop: 'static'
            }).result.then(function(result) {
                var selectedSchemas = [];
                for (var p in result.marks) {
                    if (result.marks[p] === true) {
                        selectedSchemas.push({
                            id: $scope.markSchemas[p].id,
                            name: $scope.markSchemas[p].title
                        });
                    }
                }
                $scope.app.rpConfig = {
                    marks: selectedSchemas,
                    pl: result.pl,
                    op: result.op
                };
                srvEnrollApp.update('rpConfig').then(function() { location.reload() });
            });
        };
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
                            $scope2.moreCriteria.rid = _rid || $scope.activeRound.rid;
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
        };
        srvEnrollApp.get().then(function(oApp) {
            var url;
            srvRecordConverter.config(oApp.dataSchemas);
            $scope.markSchemas = [];
            if (!oApp.assignedNickname || oApp.assignedNickname.valid === 'N') {
                $scope.markSchemas.push({ title: "昵称", id: "nickname" });
            }
            $scope.chartConfig = _oChartConfig = (oApp.rpConfig && oApp.rpConfig.pl) || {
                number: 'Y',
                percentage: 'Y',
                label: 'number'
            };
            srvChart.config(_oChartConfig);
            oApp.dataSchemas.forEach(function(oSchema) {
                if (/shorttext/.test(oSchema.type) || oSchema.id === '_round_id') {
                    $scope.markSchemas.push(oSchema);
                }
            })
            url = '/rest/pl/fe/matter/enroll/stat/get';
            url += '?site=' + oApp.siteid;
            url += '&app=' + oApp.id;
            url += '&rid=' + (_rid || '');
            http2.get(url, function(rsp) {
                var stat = {};
                oApp.dataSchemas.forEach(function(oSchema) {
                    var oStatBySchema;
                    if (oStatBySchema = rsp.data[oSchema.id]) {
                        oStatBySchema._schema = oSchema;
                        stat[oSchema.id] = oStatBySchema;
                        if (oStatBySchema.ops && oStatBySchema.sum > 0) {
                            oStatBySchema.ops.forEach(function(oDataByOp) {
                                oDataByOp.p = (new Number(oDataByOp.c / oStatBySchema.sum * 100)).toFixed(2) + '%';
                            });
                        }
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
                            srvChart.drawPieChart(item);
                        } else if (/multiple/.test(item._schema.type)) {
                            srvChart.drawBarChart(item);
                        } else if (/score/.test(item._schema.type)) {
                            if (item.ops.length) {
                                var totalScore = 0,
                                    avgScore = 0;
                                item.ops.forEach(function(op) {
                                    op.c = parseFloat(new Number(op.c).toFixed(2));
                                    totalScore += op.c;
                                });
                                srvChart.drawLineChart(item);
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
        srvEnrollRound.list(_rid).then(function(result) {
            $scope.activeRound = result.active;
            $scope.checkedRound = result.checked;
            $scope.rounds = result.rounds;
            $scope.criteria.rid = _rid || $scope.activeRound.rid;
        });
    }]);
});