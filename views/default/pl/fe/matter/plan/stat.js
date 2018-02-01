define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlStat', ['$scope', '$location', 'http2', '$timeout', '$q', '$uibModal', 'srvPlanApp', 'srvRecordConverter', 'srvChart', function($scope, $location, http2, $timeout, $q, $uibModal, srvPlanApp, srvRecordConverter, srvChart) {
        var _oChartConfig, _cacheOfRecordsBySchema;

        function _schemasForReport(oApp, checkSchemas) {
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

        function _getStatData(oApp, checkSchemas) {
            var url;
            url = '/rest/pl/fe/matter/plan/stat/get';
            url += '?site=' + oApp.siteid;
            url += '&app=' + oApp.id;
            http2.get(url, function(rsp) {
                var oStatData = {};
                $scope.schemasForReport.forEach(function(oSchema) {
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
                    url = '/rest/pl/fe/matter/plan/task/listSchema';
                    url += '?app=' + $scope.app.id + '&checkSchmId=' + schema.id;
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
                            srvRecordConverter.forTable(record.task);
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
        $scope.config = function() {
            $uibModal.open({
                templateUrl: 'config.html',
                controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                    var oApp, oData, marks, markSchemas, oPlConfig, oOpConfig, oFilter,
                        _oTasks = {},
                        _oActions = {};
                    oApp = $scope.app;
                    $scope2.data = oData = $scope.data;
                    $scope2.filter = oFilter = {};
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
                    oApp.checkSchemas.forEach(function(oSchema) {
                        if (/shorttext/.test(oSchema.type)) {
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
                    if(oData.taskSchemas) {
                        oFilter.taskSchmId = oApp.rpConfig.taskSchmId ? oApp.rpConfig.taskSchmId : '';
                        oFilter.actSchmId = oApp.rpConfig.actSchmId ? oApp.rpConfig.actSchmId : '';
                        oData.taskSchemas.forEach(function(item, index){
                            _oTasks[item.id] = item;
                            item.actions.forEach(function(action, index) {
                                _oActions[action.id] = action;
                            });
                        });
                        $scope2.tasks = _oTasks;
                    };
                    $scope2.$watch('filter.taskSchmId', function(nv) {
                        if(!nv) {$scope2.appMarkSchemas = angular.copy(markSchemas);}
                    });
                    $scope2.$watch('filter.actSchmId', function(nv) {
                        if(!nv) return;
                        $scope2.appMarkSchemas = angular.copy(markSchemas);
                        _oActions[nv].checkSchemas.forEach(function(action) {
                            if (/shorttext/.test(action.type)) {
                                $scope2.appMarkSchemas.push(action);
                            }
                        });
                    });
                    $scope2.ok = function() {
                        var oResult, schemaId;
                        oResult = { marks: [], pl: oPlConfig, op: oOpConfig, taskSchmId: oFilter.taskSchmId, actSchmId: oFilter.actSchmId};
                        oPlConfig.exclude = [];
                        for (schemaId in $scope2.plExcludeRows.selected) {
                            if ($scope2.plExcludeRows.selected[schemaId]) {
                                oPlConfig.exclude.push(schemaId);
                            }
                        }
                        oOpConfig.exclude = [];
                        for (schemaId in $scope2.opExcludeRows.selected) {
                            if ($scope2.opExcludeRows.selected[schemaId]) {
                                oOpConfig.exclude.push(schemaId);
                            }
                        }
                        if (Object.keys($scope2.markRows.selected).length) {
                            $scope2.appMarkSchemas.forEach(function(oSchema) {
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
                srvPlanApp.update('rpConfig').then(function() { location.reload() });
            });
        };
        $scope.export = function() {
            var url, params = {};

            url = '/rest/pl/fe/matter/plan/stat/export';
            url += '?site=' + $scope.app.siteid + '&app=' + $scope.app.id;

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
        $scope.$watch('app', function(oApp) {
            if (!oApp) {
                return;
            }

            var url;
            url = '/rest/pl/fe/matter/plan/stat/getAppSchema?site=' + oApp.siteid + '&app=' + oApp.id;
            url += '&taskSchmId=' + (oApp.rpConfig.taskSchmId || '');
            url += '&actSchmId=' + (oApp.rpConfig.actSchmId || '');
            http2.get(url, function(rsp) {
                $scope.data = rsp.data;
                var inputSchemas = [], _taskSchemas;
                rsp.data.checkSchemas.forEach(function(schema) {
                    if (schema.type !== 'html') {
                        inputSchemas.push(schema);
                    }
                });
                oApp._schemasForInput = inputSchemas;

                srvRecordConverter.config(rsp.data.checkSchemas);

                $scope.chartConfig = _oChartConfig = (oApp.rpConfig && oApp.rpConfig.pl) || {
                    number: 'Y',
                    percentage: 'Y',
                    label: 'number'
                };
                srvChart.config(_oChartConfig);

                $scope.schemasForReport = _schemasForReport(oApp, rsp.data.checkSchemas);

                _getStatData(oApp, rsp.data.checkSchemas);
            });
        });
    }]);
});