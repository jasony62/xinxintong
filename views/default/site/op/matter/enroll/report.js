'use strict';
define(["require", "angular", "enrollService"], function(require, angular) {
    var ls, siteId, appId, accessId, ngApp;
    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    appId = ls.match(/[\?&]app=([^&]*)/)[1];
    accessId = ls.match(/[\?&]accessToken=([^&]*)/)[1];

    ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter', 'service.enroll', 'sys.chart']);
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
                var oApp = data;
                // schemas
                var recordSchemas = [],
                    recordSchemas2 = [],
                    remarkableSchemas = [],
                    enrollDataSchemas = [],
                    groupDataSchemas = [],
                    numberSchemas = [];
                oApp.dataSchemas.forEach(function(schema) {
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
    ngApp.controller('ctrlReport', ['$scope', '$location', '$uibModal', '$timeout', '$q', 'http2', 'srvOpEnrollRound', 'srvRecordConverter', 'srvChart', function($scope, $location, $uibModal, $timeout, $q, http2, srvOpEnrollRound, srvRecordConverter, srvChart) {
        var rid, _oChartConfig, ls = $location.search();

        $scope.appId = ls.app;
        $scope.siteId = ls.site;
        $scope.accessToken = ls.accessToken;
        rid = ls.rid;

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
                            srvChart.drawNumPieChart(rsp.data, schema);
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
            var oApp = rsp.data.app,
                rpSchemas = [],
                oStat = {},
                rpConfig = (oApp.rpConfig && oApp.rpConfig.op) ? oApp.rpConfig.op : undefined,
                aExcludeSchemas = (rpConfig && rpConfig.exclude) ? rpConfig.exclude : [];

            srvRecordConverter.config(oApp.dataSchemas);
            oApp.dataSchemas.forEach(function(oSchema) {
                var oStatBySchema;
                if (aExcludeSchemas.indexOf(oSchema.id) === -1) {
                    if (oSchema.type !== 'html') {
                        // 报表中包含的题目
                        rpSchemas.push(oSchema);
                        // 报表中包含统计数据的题目
                        if (oStatBySchema = rsp.data.stat[oSchema.id]) {
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
            $scope.app = oApp;
            $scope.rpSchemas = rpSchemas;
            $scope.stat = oStat;
            $scope.chartConfig = _oChartConfig = rpConfig || {
                number: 'Y',
                percentage: 'Y',
                label: 'number'
            };
            srvChart.config(_oChartConfig);
            $timeout(function() {
                var item, scoreSummary = [],
                    totalScoreSummary = 0,
                    avgScoreSummary = 0;
                for (var p in oStat) {
                    item = oStat[p];
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
                            $scope2.moreCriteria.rid = rid || $scope.activeRound.rid;
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
            $scope.criteria.rid = rid || $scope.activeRound.rid;
        });
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});