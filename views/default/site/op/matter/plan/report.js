'use strict';
define(["require", "angular", "planService"], function(require, angular) {
    var ls, siteId, appId, accessId, ngApp;
    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    appId = ls.match(/[\?&]app=([^&]*)/)[1];
    accessId = ls.match(/[\?&]accessToken=([^&]*)/)[1];

    ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'schema.ui.xxt', 'service.matter', 'service.plan', 'sys.chart']);
    ngApp.constant('cstApp', {});
    ngApp.config(['$locationProvider', '$routeProvider', '$uibTooltipProvider', 'srvPlanAppProvider', 'srvPlanRecordProvider', function($locationProvider, $routeProvider, $uibTooltipProvider, srvPlanAppProvider, srvPlanRecordProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/site/op/matter/plan/');
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
        srvPlanAppProvider.config(siteId, appId, accessId);
        srvPlanRecordProvider.config(siteId, appId);
    }]);
    ngApp.controller('ctrlApp', ['$scope', '$timeout', '$location', 'http2', 'srvPlanApp', function($scope, $timeout, $location, http2, srvPlanApp) {
        var cachedStatus, lastCachedStatus;
        $scope.onlyReport = true;
        $scope.switchTo = function(view) {
            $location.path('/rest/site/op/matter/plan/' + view);
        };
        http2.get('/rest/site/fe/user/get?site=' + siteId, function(rsp) {
            $scope.user = rsp.data;
            srvPlanApp.opGet().then(function(oApp) {
                oApp.scenario = 'quiz';
                $scope.app = oApp;
                /*上一次访问状态*/
                if (window.localStorage) {
                    if (cachedStatus = window.localStorage.getItem("site.op.matter.plan.console")) {
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
                        window.localStorage.setItem("site.op.matter.plan.console", JSON.stringify(cachedStatus));
                    }, 6000);
                }
                $scope.$broadcast('site.op.matter.plan.app.ready', oApp);
            });
        });
    }]);
    ngApp.controller('ctrlReport', ['$scope', '$location', '$uibModal', '$timeout', '$q', 'http2', 'srvChart', function($scope, $location, $uibModal, $timeout, $q, http2, srvChart) {
        var _oChartConfig, ls = $location.search();

        $scope.appId = ls.app;
        $scope.siteId = ls.site;
        $scope.accessToken = ls.accessToken;

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
                            tmsSchema.forTable(record);
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

        $scope.$watch('app', function(app) {
            if(!app) return;

            var url = '/rest/site/op/matter/plan/task/get';
            url += '?site=' + $scope.siteId;
            url += '&app=' + $scope.appId;
            url += '&accessToken=' + $scope.accessToken;
            url += '&taskSchmId=' + (app.rpConfig.taskSchmId || '');
            url += '&actSchmId=' + (app.rpConfig.actSchmId || '');
            url += '&renewCache=Y';

            http2.get(url, function(rsp) {
                var oApp = rsp.data,
                    rpSchemas = [],
                    oStat = {},
                    rpConfig = (app.rpConfig && app.rpConfig.op) ? app.rpConfig.op : undefined,
                    aExcludeSchemas = (rpConfig && rpConfig.exclude) ? rpConfig.exclude : [];

                tmsSchema.config(oApp.checkSchemas);
                oApp.checkSchemas.forEach(function(oSchema) {
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
                        }
                    }
                });
                window.loading.finish();
            });
    });
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});