'use strict';
require('./summary.css');
require('./_asset/ui.bottom.nav.js');
require('./_asset/ui.round.js');

window.moduleAngularModules = ['nav.bottom.ui', 'round.ui.enroll', 'schema.ui.xxt', 'sys.chart', 'ngRoute'];

var ngApp = require('./main.js');
ngApp.config(['$routeProvider', function ($routeProvider) {
    $routeProvider
        .when('/rest/site/fe/matter/enroll/summary/votes', {
            template: require('./summary/votes.html'),
            controller: 'ctrlSummaryVotes'
        })
        .when('/rest/site/fe/matter/enroll/summary/marks', {
            template: require('./summary/marks.html'),
            controller: 'ctrlSummaryMarks'
        })
        .when('/rest/site/fe/matter/enroll/summary/stat', {
            template: require('./summary/stat.html'),
            controller: 'ctrlSummaryStat'
        })
        .otherwise({
            template: require('./summary/rank.html'),
            controller: 'ctrlSummaryRank'
        });
}]);
ngApp.controller('ctrlSummary', ['$scope', 'tmsLocation', '$location', 'http2', function ($scope, LS, $location, http2) {
    $scope.activeNav = '';
    $scope.viewTo = function (event, subView) {
        $scope.activeView = subView;
        var url = '/rest/site/fe/matter/enroll/summary/' + subView.type;
        LS.path(url);
    };
    $scope.$on('$locationChangeSuccess', function (event, currentRoute) {
        var subView = currentRoute.match(/([^\/]+?)\?/);
        $scope.subView = subView[1] === 'rank' ? 'rank' : subView[1];
    });
    $scope.$on('xxt.app.enroll.ready', function (event, params) {
        $scope.oApp = params.app;
        http2.get(LS.j('navs', 'site', 'app')).then(function (rsp) {
            $scope.navs = rsp.data;
        });
    });
}]);
ngApp.controller('ctrlSummaryRank', ['$scope', '$q', '$sce', 'tmsLocation', 'http2', 'enlRound', function ($scope, $q, $sce, LS, http2, enlRound) {
    function fnRoundTitle(aRids) {
        var defer;
        defer = $q.defer();
        if (aRids.indexOf('ALL') !== -1) {
            defer.resolve('全部轮次');
        } else {
            var titles;
            http2.get('/rest/site/fe/matter/enroll/round/get?site=' + oApp.siteid + '&app=' + oApp.id + '&rid=' + aRids).then(function (rsp) {
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

    function list() {
        var defer = $q.defer();
        switch (oAppState.dimension) {
            case 'user':
                http2.post('/rest/site/fe/matter/enroll/rank/userByApp?site=' + oApp.siteid + '&app=' + oApp.id, oAppState.criteria).then(function (rsp) {
                    defer.resolve(rsp.data)
                });
                break;
            case 'group':
                http2.post('/rest/site/fe/matter/enroll/rank/groupByApp?site=' + oApp.siteid + '&app=' + oApp.id, oAppState.criteria).then(function (rsp) {
                    defer.resolve(rsp.data)
                });
                break;
            case 'schema':
                http2.post('/rest/site/fe/matter/enroll/rank/schemaByApp?site=' + oApp.siteid + '&app=' + oApp.id + '&schema=' + oAppState.criteria.obj, oAppState.criteria).then(function (rsp) {
                    defer.resolve(rsp.data)
                });
                break;
        }
        return defer.promise;
    }

    var oApp, oAppState;
    $scope.doSearch = function () {
        list().then(function (data) {
            switch (oAppState.dimension) {
                case 'user':
                    if (data.users) {
                        data.users.forEach(function (user) {
                            user.headimgurl = user.headimgurl ? user.headimgurl : '/static/img/avatar.png';
                            if (oAppState.criteria.orderby === 'score') {
                                user.rankVal = user.score;
                            } else if (/^schema_/.test(oAppState.criteria.orderby)) {
                                user.rankVal = user[oAppState.criteria.orderby + '_sum'];
                            } else if ('total_coin' === oAppState.criteria.orderby) {
                                user.rankVal = user.user_total_coin;
                            } else {
                                user.rankVal = user[oAppState.criteria.orderby + '_num'];
                            }
                            $scope.users.push(user);
                        });
                    }
                    break;
                case 'group':
                    if (data.groups) {
                        data.groups.forEach(function (group) {
                            $scope.groups.push(group);
                        });
                    }
                    break;
                case 'schema':
                    data.forEach(function (oOp) {
                        $scope.schemaOps.push(oOp);
                    });
                    break;
            }
        });
    };
    $scope.changeCriteria = function () {
        $scope.users = [];
        $scope.groups = [];
        $scope.schemaOps = [];
        $scope.doSearch(1);
    };
    $scope.doRound = function (rid) {
        if (rid == 'more') {
            $scope.setRound();
        } else {
            $scope.changeCriteria();
        }
    };
    /**
     * 设置轮次条件
     */
    $scope.setRound = function () {
        (new enlRound($scope.app)).pick(oAppState.criteria.round).then(function (oResult) {
            oAppState.criteria.round = oResult.ids;
            $scope.checkedRoundTitles = oResult.titles;
            $scope.changeCriteria();
        });
    };
    $scope.$watch('oApp', function (nv) {
        if (!nv) {
            return;
        }
        oApp = nv;
        var oRankConfig, oConfig, rankItems, dataSchemas;
        dataSchemas = oApp.dynaDataSchemas;
        /* 排行显示内容设置 */
        rankItems = ['enroll', 'remark', 'like', 'remark_other', 'do_like', 'total_coin', 'score', 'average_score', 'vote_schema', 'vote_cowork'];
        oConfig = {};
        rankItems.forEach(function (item) {
            oConfig[item] = true;
        });
        if (oRankConfig = oApp.rankConfig) {
            if (oRankConfig.scope) {
                rankItems.forEach(function (item) {
                    oConfig[item] = !!oRankConfig.scope[item];
                });
            }
            if (oRankConfig.schemas && oRankConfig.schemas.length)
                $scope.rankSchemas = dataSchemas.filter(function (oSchema) {
                    return oSchema.type === 'single' && oRankConfig.schemas.indexOf(oSchema.id) !== -1;
                });
            if (oRankConfig.scopeSchemas && oRankConfig.scopeSchemas.length)
                $scope.scopeSchemas = dataSchemas.filter(function (oSchema) {
                    return oSchema.type === 'shorttext' && oSchema.format === 'number' && oRankConfig.scopeSchemas.indexOf(oSchema.id) !== -1;
                });
        }
        $scope.config = oConfig;
        /* 恢复上一次访问的状态 */
        if (window.localStorage) {
            $scope.$watch('appState', function (nv) {
                if (nv)
                    window.localStorage.setItem("site.fe.matter.enroll.rank.appState", JSON.stringify(nv));
            }, true);
            if (oAppState = window.localStorage.getItem("site.fe.matter.enroll.rank.appState")) {
                oAppState = JSON.parse(oAppState);
                if (!oAppState.aid || oAppState.aid !== oApp.id) {
                    oAppState = null;
                } else if (oAppState.criteria.obj === 'group') {
                    if (!oApp.entryRule.group.id)
                        oAppState = null;
                }
            }
        }
        if (!oAppState)
            oAppState = {
                aid: oApp.id,
                criteria: {
                    orderby: oRankConfig.defaultItem ? oRankConfig.defaultItem : 'enroll',
                    agreed: 'all',
                    round: ['ALL']
                }
            };
        if (!oAppState.criteria.obj && oRankConfig.defaultObj) {
            oAppState.criteria.obj = oRankConfig.defaultObj;
        }
        if (/user|group/.test(oAppState.criteria.obj)) {
            oAppState.dimension = oAppState.criteria.obj;
        } else {
            if (oRankConfig.schemas && oRankConfig.schemas.length && oRankConfig.schemas.indexOf(oAppState.criteria.obj) !== -1)
                oAppState.dimension = 'schema';
            else
                oAppState.criteria.obj = oAppState.dimension = 'user';
        }

        (new enlRound(oApp)).getRoundTitle(oAppState.criteria.round).then(function (titles) {
            $scope.checkedRoundTitles = titles;
        });
        $scope.appState = oAppState;
        $scope.$watch('appState.criteria.obj', function (oNew, oOld) {
            if (oNew && oOld && oNew !== oOld) {
                switch (oNew) {
                    case 'user':
                        oAppState.dimension = 'user';
                        oAppState.criteria.orderby = oRankConfig.defaultItem ? oRankConfig.defaultItem : 'enroll';
                        break;
                    case 'group':
                        oAppState.dimension = 'group';
                        oAppState.criteria.orderby = oRankConfig.defaultItem ? oRankConfig.defaultItem : 'enroll';
                        break;
                    default:
                        oAppState.dimension = 'schema';
                        oAppState.criteria.orderby = oRankConfig.defaultItem ? oRankConfig.defaultItem : 'enroll';
                }
                $scope.changeCriteria();
            }
        });
        $scope.changeCriteria();
        /*设置页面分享信息*/
        $scope.setSnsShare(null, null, {
            target_type: 'rank',
            target_id: oApp.id
        });
        /*页面阅读日志*/
        $scope.logAccess({
            target_type: 'rank',
            target_id: oApp.id
        });
    });
}]);
ngApp.controller('ctrlSummaryVotes', ['$scope', '$q', '$timeout', 'tmsLocation', 'http2', 'enlRound', function ($scope, $q, $timeout, LS, http2, enlRound) {
    var _oApp, _facRound, _oCriteria, _oFilter;
    $scope.criteria = _oCriteria = {}; // 数据查询条件
    $scope.filter = _oFilter = {}; // 过滤条件
    $scope.gotoOptionLink = function (oSchema, oOption) {
        if (oOption.referRecord && oOption.referRecord.ds && oOption.referRecord.ds.ek && oSchema.dsOps && oSchema.dsOps.app && oSchema.dsOps.app.id) {
            location.href = LS.j('', 'site') + '&app=' + oSchema.dsOps.app.id + '&ek=' + oOption.referRecord.ds.ek + '&page=cowork';
        }
    };
    /* 获得投票结果 */
    $scope.getVotes = function () {
        var defer = $q.defer();
        /* 每个轮次的动态选项不一样，需要根据轮次获取动态选项 */
        http2.get(LS.j('schema/get', 'site', 'app') + '&rid=' + _oCriteria.rid).then(function (rsp) {
            var url, oSchemasById;
            oSchemasById = {};
            rsp.data.forEach(function (oSchema) {
                oSchemasById[oSchema.id] = oSchema;
            });
            url = LS.j('votes/get', 'site', 'app');
            if (_oCriteria.rid) {
                url += '&rid=' + _oCriteria.rid;
            }
            http2.get(url).then(function (rsp) {
                angular.forEach(rsp.data, function (oSchema) {
                    var oOriginalSchema;
                    if (oOriginalSchema = oSchemasById[oSchema.id]) {
                        if (oSchema.ops && oSchema.ops.length) {
                            oSchema.ops.forEach(function (oOption) {
                                var oOriginalOption;
                                oOption.p = oSchema.sum > 0 ? (oOption.c / oSchema.sum * 100).toFixed(2) : '';
                                /* 从数据来源活动，查看详情 */
                                if (oOriginalSchema.dsOps && oOriginalSchema.showOpDsLink === 'Y') {
                                    oSchema.dsOps = oOriginalSchema.dsOps;
                                    for (var i = 0, ii = oOriginalSchema.ops.length; i < ii; i++) {
                                        oOriginalOption = oOriginalSchema.ops[i];
                                        if (oOption.v === oOriginalOption.v) {
                                            if (oOriginalOption.referRecord) {
                                                oOption.referRecord = oOriginalOption.referRecord;
                                            }
                                            break;
                                        }
                                    }
                                }
                            });
                        }
                        /* 按获得的投票数量进行排序 */
                        oSchema.ops = oSchema.ops.sort(function (a, b) {
                            return b.c - a.c;
                        });
                    }
                });
                $scope.votes = rsp.data;
                defer.resolve($scope.votes);
            });
        });

        return defer.promise;
    };
    $scope.shiftRound = function (oRound) {
        _oFilter.round = oRound;
        _oCriteria.rid = oRound ? oRound.rid : 'all';
        $scope.getVotes();
    };
    $scope.$watch('app', function (oApp) {
        if (!oApp) {
            return;
        }
        _oApp = oApp;
        $scope.facRound = _facRound = new enlRound(_oApp);
        _facRound.list().then(function (result) {
            if (result.active) {
                for (var i = 0, ii = result.rounds.length; i < ii; i++) {
                    if (result.rounds[i].rid === result.active.rid) {
                        _oFilter.round = result.active;
                        _oCriteria.rid = result.active.rid;
                        break;
                    }
                }
            }
            $scope.rounds = result.rounds;
            /* 获取投票结果数据 */
            $scope.getVotes();
        });
        /* 设置页面分享信息 */
        $scope.setSnsShare();
    });
}]);
ngApp.controller('ctrlSummaryMarks', ['$scope', '$q', '$timeout', '$filter', 'tmsLocation', 'http2', 'enlRound', function ($scope, $q, $timeout, $filter, LS, http2, enlRound) {
    var _oApp, _facRound, _oCriteria, _oFilter;
    $scope.criteria = _oCriteria = {}; // 数据查询条件
    $scope.filter = _oFilter = {}; // 过滤条件
    $scope.gotoOptionLink = function (oSchema) {
        if (oSchema.dsSchema && oSchema.dsSchema.app && oSchema.dsSchema.app.id) {
            if (oSchema.referRecord && oSchema.referRecord.ds && oSchema.referRecord.ds.ek) {
                location.href = LS.j('', 'site') + '&app=' + oSchema.dsSchema.app.id + '&ek=' + oSchema.referRecord.ds.ek + '&page=cowork';
            }
        }
    };
    /* 获得投票结果 */
    $scope.getMarks = function () {
        var defer = $q.defer();
        /* 每个轮次的动态选项不一样，需要根据轮次获取动态选项 */
        http2.get(LS.j('schema/get', 'site', 'app') + '&rid=' + _oCriteria.rid).then(function (rsp) {
            var url, oSchemasById;
            oSchemasById = {};
            rsp.data.forEach(function (oSchema) {
                oSchemasById[oSchema.id] = oSchema;
            });
            url = LS.j('marks/get', 'site', 'app');
            if (_oCriteria.rid) {
                url += '&rid=' + _oCriteria.rid;
            }
            http2.get(url).then(function (rsp) {
                var marks = [];
                angular.forEach(rsp.data, function (oStat, schemaId) {
                    var oSchema;
                    if (oSchema = oSchemasById[schemaId]) {
                        oSchema._score = {
                            sum: oStat.sum,
                            avg: $filter('number')(oStat.sum / oStat.count, 2).replace('.00', '')
                        };
                        if (oSchema.ops && oSchema.ops.length) {
                            oSchema.ops.forEach(function (oOption) {
                                var opSum;
                                if (opSum = oStat[oOption.v]) {
                                    oOption._score = {
                                        sum: opSum,
                                        avg: $filter('number')(opSum / oStat.count, 2).replace('.00', '')
                                    }
                                }
                            });
                        }
                        marks.push(oSchema);
                    }
                });
                /* 按获得的投票数量进行排序 */
                marks = marks.sort(function (a, b) {
                    return b._score.sum - a._score.sum;
                });
                $scope.marks = marks;
                defer.resolve(marks);
            });
        });

        return defer.promise;
    };
    $scope.shiftRound = function (oRound) {
        _oFilter.round = oRound;
        _oCriteria.rid = oRound ? oRound.rid : 'all';
        $scope.getMarks();
    };
    $scope.$watch('app', function (oApp) {
        if (!oApp) {
            return;
        }
        _oApp = oApp;
        $scope.facRound = _facRound = new enlRound(_oApp);
        _facRound.list().then(function (result) {
            if (result.active) {
                for (var i = 0, ii = result.rounds.length; i < ii; i++) {
                    if (result.rounds[i].rid === result.active.rid) {
                        _oFilter.round = result.active;
                        _oCriteria.rid = result.active.rid;
                        break;
                    }
                }
            }
            $scope.rounds = result.rounds;
            /* 获取投票结果数据 */
            $scope.getMarks();
        });
        /* 设置页面分享信息 */
        $scope.setSnsShare();
    });
}]);
ngApp.controller('ctrlSummaryStat', ['$scope', '$timeout', '$uibModal', '$q', 'tmsLocation', 'http2', 'tmsSchema', 'srvChart', 'enlRound', function ($scope, $timeout, $uibModal, $q, LS, http2, tmsSchema, srvChart, enlRound) {
    var _oApp, _oChartConfig, _oCriteria, _facRound;

    var _oCacheOfRecordsBySchema = {
        recordsBySchema: function (oSchema, oPage) {
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
                http2.get(url, {
                    page: oPage
                }).then(function (rsp) {
                    oCached._running = false;
                    oCached.page = {
                        at: oPage.at,
                        size: oPage.size
                    };
                    if (rsp.data && rsp.data.records) {
                        rsp.data.records.forEach(function (oRecord) {
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
    $scope.getRecords = function (oSchema, oPage) {
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
    $scope.shiftRound = function (oRound) {
        location.href = LS.j('', 'site', 'app') + '&rid=' + oRound.rid + '&page=stat';
    };
    $scope.$watch('app', function (oApp) {
        if (!oApp) {
            return;
        }
        _oApp = oApp;

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
                        item.ops.forEach(function (op) {
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

        var rpSchemas = [],
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
        _facRound.get([LS.s().rid ? LS.s().rid : _oApp.appRound.rid]).then(function (aRounds) {
            if (aRounds.length !== 1) {
                return;
            }
            _oCriteria.round = aRounds[0];
            http2.get(LS.j('stat/get', 'site', 'app') + '&rid=' + _oCriteria.round.rid).then(function (rsp) {
                _oApp.dynaDataSchemas.forEach(function (oSchema) {
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
                                    oStatBySchema.ops.forEach(function (oDataByOp) {
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
        _facRound.list().then(function (oResult) {
            $scope.rounds = oResult.rounds;
        });

    });
}]);