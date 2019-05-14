'use strict';
require('./rank.css');

require('./_asset/ui.round.js');

window.moduleAngularModules = ['round.ui.enroll'];

var ngApp = require('./main.js');
ngApp.controller('ctrlRank', ['$scope', '$q', '$sce', 'http2', 'tmsLocation', 'enlRound', function ($scope, $q, $sce, http2, LS, enlRound) {
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
    $scope.$on('xxt.app.enroll.ready', function (event, params) {
        var oRankConfig, oConfig, rankItems, dataSchemas;
        oApp = params.app;
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
                    return oRankConfig.schemas.indexOf(oSchema.id) !== -1;
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
        $scope.setSnsShare();
        /*设置页面操作*/
        $scope.setPopAct(['addRecord'], 'rank');
        /*设置页面导航*/
        $scope.setPopNav(['repos', 'kanban', 'favor', 'event'], 'rank');
        /*页面阅读日志*/
        $scope.logAccess();
    });
}]);