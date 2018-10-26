'use strict';

window.moduleAngularModules = ['schema.ui.xxt', 'sys.chart'];

var ngApp = require('./main.js');
ngApp.factory('facRound', ['http2', '$q', 'tmsLocation', function(http2, $q, LS) {
    var Round, _ins;
    Round = function(oApp) {
        this.oApp = oApp;
        this.oPage = {};
    };
    Round.prototype.list = function() {
        var deferred = $q.defer();
        http2.get(LS.j('round/list', 'site', 'app'), { page: this.oPage }).then(function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };

    return Round;
}]);
ngApp.controller('ctrlStat', ['$scope', '$timeout', '$uibModal', '$q', 'tmsLocation', 'http2', 'tmsSchema', 'srvChart', 'facRound', function($scope, $timeout, $uibModal, $q, LS, http2, tmsSchema, srvChart, facRound) {
    function fnRoundTitle(aRids) {
        var defer;
        defer = $q.defer();
        if (aRids.indexOf('ALL') !== -1) {
            defer.resolve('全部轮次');
        } else {
            var titles;
            http2.get(LS.j('round/get', 'site', 'app') + '&rid=' + aRids).then(function(rsp) {
                if (rsp.data.length === 1) {
                    titles = rsp.data[0].title;
                } else if (rsp.data.length === 2) {
                    titles = rsp.data[0].title + ',' + rsp.data[1].title;
                } else if (rsp.data.length > 2) {
                    titles = rsp.data[0].title + '-' + rsp.data[rsp.data.length - 1].title;
                }
                defer.resolve(titles);
            });
        }
        return defer.promise;
    }

    var _oChartConfig, _oCriteria;

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

            if (requireGet) {
                /member/.test(oSchema.id) && (oSchema.id = 'member');
                url = LS.j('record/list4Schema', 'site', 'app');
                url += '&schema=' + oSchema.id;
                url += '&rid=' + _oCriteria.round[0];
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

    $scope.setRound = function() {
        var oApp = $scope.app;
        $uibModal.open({
            templateUrl: 'setRound.html',
            backdrop: 'static',
            controller: ['$scope', '$uibModalInstance', 'facRound', function($scope2, $mi, facRound) {
                var oCheckedRounds;
                $scope2.facRound = new facRound(oApp);
                $scope2.pageOfRound = $scope2.facRound.oPage;
                $scope2.checkedRounds = oCheckedRounds = {};
                $scope2.countOfChecked = 0;
                $scope2.toggleCheckedRound = function(rid) {
                    if (rid === 'ALL') {
                        if (oCheckedRounds.ALL) {
                            $scope2.checkedRounds = oCheckedRounds = { ALL: true };
                        } else {
                            $scope2.checkedRounds = oCheckedRounds = {};
                        }
                    } else {
                        if (oCheckedRounds[rid]) {
                            delete oCheckedRounds.ALL;
                        } else {
                            delete oCheckedRounds[rid];
                        }
                    }
                    $scope2.countOfChecked = Object.keys(oCheckedRounds).length;
                };
                $scope2.clean = function() {
                    $scope2.checkedRounds = oCheckedRounds = {};
                };
                $scope2.ok = function() {
                    var checkedRoundIds = [];
                    if (Object.keys(oCheckedRounds).length) {
                        angular.forEach(oCheckedRounds, function(v, k) {
                            if (v) {
                                checkedRoundIds.push(k);
                            }
                        });
                    }
                    $mi.close(checkedRoundIds);
                };
                $scope2.cancel = function() {
                    $mi.dismiss('cancel');
                };
                $scope2.doSearchRound = function() {
                    $scope2.facRound.list().then(function(result) {
                        $scope2.activeRound = result.active;
                        if ($scope2.activeRound) {
                            var otherRounds = [];
                            result.rounds.forEach(function(oRound) {
                                if (oRound.rid !== $scope2.activeRound.rid) {
                                    otherRounds.push(oRound);
                                }
                            });
                            $scope2.rounds = otherRounds;
                        } else {
                            $scope2.rounds = result.rounds;
                        }

                    });
                };
                if (angular.isArray(_oCriteria.round)) {
                    if (_oCriteria.round.length) {
                        _oCriteria.round.forEach(function(rid) {
                            oCheckedRounds[rid] = true;;
                        });
                    }
                }
                $scope2.countOfChecked = Object.keys(oCheckedRounds).length;
                $scope2.doSearchRound();
            }]
        }).result.then(function(result) {
            location.href = LS.j('', 'site', 'app') + '&rid=' + result[0] + '&page=stat';
        });
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oApp = params['app'],
            rpSchemas = [],
            oStat = {},
            rpConfig = (oApp.rpConfig && oApp.rpConfig.op) ? oApp.rpConfig.op : undefined,
            aExcludeSchemas = (rpConfig && rpConfig.exclude) ? rpConfig.exclude : [];

        http2.get(LS.j('stat/get', 'site', 'app')).then(function(rsp) {
            oApp.dynaDataSchemas.forEach(function(oSchema) {
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
            $scope.stat = rsp.data;
            $timeout(function() {
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
            });

        });
        $scope.criteria = _oCriteria = {
            round: [oApp.appRound.rid]
        };
        fnRoundTitle(_oCriteria.round).then(function(titles) {
            $scope.checkedRoundTitles = titles;
        });
        $scope.app = oApp;
        $scope.rpSchemas = rpSchemas;
        $scope.chartConfig = _oChartConfig = rpConfig || {
            number: 'Y',
            percentage: 'Y',
            label: 'number'
        };
        tmsSchema.config(oApp.dynaDataSchemas);
        srvChart.config(_oChartConfig);
    });
}]);