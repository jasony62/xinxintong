define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlStat', ['$scope', '$location', 'http2', '$timeout', '$q', '$uibModal', 'srvEnrollApp', 'srvEnrollRound', 'srvRecordConverter', 'srvChart', function($scope, $location, http2, $timeout, $q, $uibModal, srvEnrollApp, srvEnrollRound, srvRecordConverter, srvChart) {
        var _rid, _oChartConfig, _cacheOfRecordsBySchema;

        function _schemasForReport(oApp) {
            var aSchemas, aExclude;
            if (oApp.rpConfig && oApp.rpConfig.pl && oApp.rpConfig.pl.exclude && oApp.rpConfig.pl.exclude.length) {
                aExclude = oApp.rpConfig.pl.exclude;
                aSchemas = [];
                oApp._schemasForInput.forEach(function(oSchema) {
                    if (aExclude.indexOf(oSchema.id) === -1) {
                        aSchemas.push(oSchema);
                    }
                });
                return aSchemas;
            } else {
                return oApp._schemasForInput;
            }
        }

        function _getStatData(oApp) {
            var url;
            url = '/rest/pl/fe/matter/enroll/stat/get';
            url += '?site=' + oApp.siteid;
            url += '&app=' + oApp.id;
            url += '&rid=' + (_rid || '');
            http2.get(url, function(rsp) {
                var oStatData = {};
                oApp.dataSchemas.forEach(function(oSchema) {
                    var oStatBySchema;
                    if (oStatBySchema = rsp.data[oSchema.id]) {
                        oStatBySchema._schema = oSchema;
                        oStatData[oSchema.id] = oStatBySchema;
                        if (oStatBySchema.ops && oStatBySchema.sum > 0) {
                            oStatBySchema.ops.forEach(function(oDataByOp) {
                                oDataByOp.p = (new Number(oDataByOp.c / oStatBySchema.sum * 100)).toFixed(2) + '%';
                            });
                        }
                    }
                });
                _processStat($scope.stat = oStatData);
            });

        }

        function _processStat(oStatData) {
            $timeout(function() {
                var p, item, scoreSummary = [],
                    totalScoreSummary = 0,
                    avgScoreSummary = 0;
                for (p in oStatData) {
                    item = oStatData[p];
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
        }
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
        $scope.config = function() {
            $uibModal.open({
                templateUrl: 'config.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var oApp, marks, markSchemas, oPlConfig, oOpConfig;
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
                    // 标识项
                    markSchemas = [];
                    if (!oApp.assignedNickname || oApp.assignedNickname.valid === 'N') {
                        markSchemas.push({ title: "昵称", id: "nickname" });
                    }
                    oApp._schemasForInput.forEach(function(oSchema) {
                        if (/shorttext/.test(oSchema.type) || oSchema.id === '_round_id') {
                            markSchemas.push(oSchema);
                        }
                    });
                    $scope2.appMarkSchemas = angular.copy(markSchemas);
                    $scope2.markRows = {
                        selected: {},
                    };
                    // 组织者
                    $scope2.plExcludeRows = {
                        selected: {}
                    };
                    $scope2.plConfig = oPlConfig;
                    if (oPlConfig.exclude) {
                        oPlConfig.exclude.forEach(function(schemaId) {
                            $scope2.plExcludeRows.selected[schemaId] = true;
                        });
                    } else {
                        oPlConfig.exclude = [];
                    }
                    // 监督者
                    $scope2.opExcludeRows = {
                        selected: {}
                    };
                    $scope2.opConfig = oOpConfig;
                    if (oOpConfig.exclude) {
                        oOpConfig.exclude.forEach(function(schemaId) {
                            $scope2.opExcludeRows.selected[schemaId] = true;
                        });
                    } else {
                        oOpConfig.exclude = [];
                    }
                    marks.forEach(function(item, index) {
                        $scope2.markRows.selected[item.id] = true;
                    });
                    $scope2.ok = function() {
                        var oResult;
                        oResult = { marks: [], pl: oPlConfig, op: oOpConfig };
                        oPlConfig.exclude = Object.keys($scope2.plExcludeRows.selected);
                        oOpConfig.exclude = Object.keys($scope2.opExcludeRows.selected);
                        if (Object.keys($scope2.markRows.selected).length) {
                            markSchemas.forEach(function(oSchema) {
                                if ($scope2.markRows.selected[oSchema.id]) {
                                    oResult.marks.push({ id: oSchema.id, title: oSchema.title });
                                }
                            });
                        }
                        $mi.close(oResult);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                }],
                backdrop: 'static'
            }).result.then(function(oConfig) {
                $scope.app.rpConfig = oConfig;
                srvEnrollApp.update('rpConfig').then(function() { location.reload() });
            });
        };
        $scope.export = function() {
            var url, params = {};

            url = '/rest/pl/fe/matter/enroll/stat/export';
            url += '?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&rid=' + (_rid || '');

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
            srvRecordConverter.config(oApp.dataSchemas);

            $scope.chartConfig = _oChartConfig = (oApp.rpConfig && oApp.rpConfig.pl) || {
                number: 'Y',
                percentage: 'Y',
                label: 'number'
            };
            srvChart.config(_oChartConfig);

            $scope.schemasForReport = _schemasForReport(oApp);

            _getStatData(oApp);

            srvEnrollRound.list(_rid).then(function(result) {
                $scope.activeRound = result.active;
                $scope.checkedRound = result.checked;
                $scope.rounds = result.rounds;
                $scope.criteria.rid = _rid || $scope.activeRound.rid;
            });
        });
    }]);
});