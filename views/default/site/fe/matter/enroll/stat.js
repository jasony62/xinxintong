'use strict';

require('./_asset/ui.round.js');

window.moduleAngularModules = ['round.ui.enroll', 'schema.ui.xxt', 'sys.chart'];

var ngApp = require('./main.js');
ngApp.controller('ctrlStat', ['$scope', '$timeout', '$uibModal', '$q', 'tmsLocation', 'http2', 'tmsSchema', 'srvChart', 'enlRound', function($scope, $timeout, $uibModal, $q, LS, http2, tmsSchema, srvChart, enlRound) {
    var _oApp, _oChartConfig, _oCriteria, _facRound;

    var _oCacheOfRecordsBySchema = {
        recordsBySchema: function(oSchema, oPage) {
            var deferred = $q.defer(),
                oCached,
                requireGet = false,
                url;

            if (oCached = _oCacheOfRecordsBySchema[oSchema.id]) {
                if (oCached.page && oCached.page.at === oPage.at) {
                    records = oCached.records;
                    deferred.resolve(records);
                } else {
                    if (oCached._running) {
                        deferred.resolve(false);
                        return false;
                    }
                    requireGet = true;
                }
            } else {
                oCached = {};
                _oCacheOfRecordsBySchema[oSchema.id] = oCached;
                requireGet = true;
            }

            if (requireGet && _oCriteria.round && _oCriteria.round.rid) {
                /member/.test(oSchema.id) && (oSchema.id = 'member');
                url = LS.j('record/list4Schema', 'site', 'app');
                url += '&schema=' + oSchema.id;
                url += '&rid=' + _oCriteria.round.rid;
                oCached._running = true;
                http2.get(url, { page: oPage }).then(function(rsp) {
                    oCached._running = false;
                    oCached.page = {
                        at: oPage.at,
                        size: oPage.size
                    };
                    if (rsp.data && rsp.data.records) {
                        rsp.data.records.forEach(function(oRecord) {
                            tmsSchema.forTable(oRecord);
                        });
                        if (oSchema.number && oSchema.number == 'Y') {
                            oCached.sum = rsp.data.sum;
                            srvChart.drawNumPieChart(rsp.data, schema);
                        }
                        oCached.records = rsp.data.records;
                        oCached.page.total = rsp.data.total;
                    }
                    deferred.resolve(rsp.data);
                });
            }

            return deferred.promise;
        }
    };
    $scope.getRecords = function(oSchema, oPage) {
        var oCached;
        if (oCached = _oCacheOfRecordsBySchema[oSchema.id]) {
            if (oCached.page && oCached.page.at === oPage.at) {
                oPage.total = oCached.page.total;
                return oCached;
            }
        }
        _oCacheOfRecordsBySchema.recordsBySchema(oSchema, oPage);
        return false;
    };
    $scope.shiftRound = function(oRound) {
        location.href = LS.j('', 'site', 'app') + '&rid=' + oRound.rid + '&page=stat';
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        function fnDrawChart() {
            var item, scoreSummary = [],
                totalScoreSummary = 0,
                avgScoreSummary = 0;
            for (var p in oStat) {
                item = oStat[p];
                if (/single/.test(item._schema.type)) {
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
        }

        var _oApp = params['app'],
            rpSchemas = [],
            oStat = {},
            rpConfig = (_oApp.rpConfig && _oApp.rpConfig.op) ? _oApp.rpConfig.op : undefined,
            aExcludeSchemas = (rpConfig && rpConfig.exclude) ? rpConfig.exclude : [];

        $scope.app = _oApp;
        $scope.criteria = _oCriteria = {};
        $scope.chartConfig = _oChartConfig = rpConfig || {
            number: 'Y',
            percentage: 'Y',
            label: 'number'
        };
        tmsSchema.config(_oApp.dynaDataSchemas);
        srvChart.config(_oChartConfig);
        _facRound = new enlRound(_oApp);
        _facRound.get([LS.s().rid ? LS.s().rid : _oApp.appRound.rid]).then(function(aRounds) {
            if (aRounds.length !== 1) {
                return;
            }
            _oCriteria.round = aRounds[0];
            http2.get(LS.j('stat/get', 'site', 'app') + '&rid=' + _oCriteria.round.rid).then(function(rsp) {
                _oApp.dynaDataSchemas.forEach(function(oSchema) {
                    var oStatBySchema;
                    if (aExcludeSchemas.indexOf(oSchema.id) === -1) {
                        if (oSchema.type !== 'html') {
                            // 报表中包含的题目
                            rpSchemas.push(oSchema);
                            // 报表中包含统计数据的题目
                            if (oStatBySchema = rsp.data[oSchema.id]) {
                                oStatBySchema._schema = oSchema;
                                oStat[oSchema.id] = oStatBySchema;
                                if (oStatBySchema.ops && oStatBySchema.sum > 0) {
                                    oStatBySchema.ops.forEach(function(oDataByOp) {
                                        oDataByOp.p = (new Number(oDataByOp.c / oStatBySchema.sum * 100)).toFixed(2) + '%';
                                    });
                                }
                            }
                        }
                    }
                });
                $scope.rpSchemas = rpSchemas;
                $scope.stat = rsp.data;
                $timeout(fnDrawChart);
            });
        });
        _facRound.list().then(function(oResult) {
            $scope.rounds = oResult.rounds;
        });
        /*设置页面操作*/
        $scope.appActs = {
            addRecord: {}
        };
        /*设置页面导航*/
        var oAppNavs = {
            length: 0
        };
        if (_oApp.scenarioConfig) {
            if (_oApp.scenarioConfig.can_repos === 'Y') {
                oAppNavs.repos = {};
                oAppNavs.length++;
            }
            if (_oApp.scenarioConfig.can_action === 'Y') {
                oAppNavs.event = {};
                oAppNavs.length++;
            }
            if (_oApp.scenarioConfig.can_rank === 'Y') {
                oAppNavs.rank = {};
                oAppNavs.length++;
            }
        }
        if (Object.keys(oAppNavs).length) {
            $scope.appNavs = oAppNavs;
        }
    });
}]);