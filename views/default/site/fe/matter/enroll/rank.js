'use strict';
require('./rank.css');

require('./_asset/ui.round.js');

window.moduleAngularModules = ['round.ui.enroll'];

var ngApp = require('./main.js');
ngApp.controller('ctrlRank', ['$scope', '$q', '$sce', 'http2', 'tmsLocation', 'enlRound', function($scope, $q, $sce, http2, LS, enlRound) {
    function fnRoundTitle(aRids) {
        var defer;
        defer = $q.defer();
        if (aRids.indexOf('ALL') !== -1) {
            defer.resolve('全部轮次');
        } else {
            var titles;
            http2.get('/rest/site/fe/matter/enroll/round/get?site=' + oApp.siteid + '&app=' + oApp.id + '&rid=' + aRids).then(function(rsp) {
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
        switch (oAppState.criteria.obj) {
            case 'user':
                http2.post('/rest/site/fe/matter/enroll/rank/userByApp?site=' + oApp.siteid + '&app=' + oApp.id, oAppState.criteria).then(function(rsp) {
                    defer.resolve(rsp.data)
                });
                break;
            case 'group':
                http2.post('/rest/site/fe/matter/enroll/rank/groupByApp?site=' + oApp.siteid + '&app=' + oApp.id, oAppState.criteria).then(function(rsp) {
                    defer.resolve(rsp.data)
                });
                break;
            default:
                http2.post('/rest/site/fe/matter/enroll/rank/schemaByApp?site=' + oApp.siteid + '&app=' + oApp.id + '&schema=' + oAppState.criteria.obj, oAppState.criteria).then(function(rsp) {
                    defer.resolve(rsp.data)
                });
                break;
        }
        return defer.promise;
    }
    var oApp, oAppState;
    $scope.doSearch = function(pageAt) {
        if (pageAt) {
            oAppState.page.at = pageAt;
        }
        list().then(function(data) {
            var oSchema;
            switch (oAppState.criteria.obj) {
                case 'user':
                    if (data.users) {
                        data.users.forEach(function(user) {
                            user.headimgurl = user.headimgurl ? user.headimgurl : '/static/img/avatar.png';
                            $scope.users.push(user);
                        });
                    }
                    break;
                case 'group':
                    if (data.groups) {
                        data.groups.forEach(function(group) {
                            $scope.groups.push(group);
                        });
                    }
                    break;
                default:
                    data.forEach(function(oOp) {
                        $scope.schemaOps.push(oOp);
                    });
                    break;
            }
            oAppState.page.total = data.total;
            angular.element(document).ready(function() {
                $scope.showFolder();
            });
        });
    };
    $scope.changeCriteria = function() {
        $scope.users = [];
        $scope.groups = [];
        $scope.schemaOps = [];
        $scope.doSearch(1);
    };
    $scope.doRound = function(rid) {
        if (rid == 'more') {
            $scope.setRound();
        } else {
            $scope.changeCriteria();
        }
    };
    /**
     * 设置轮次条件
     */
    $scope.setRound = function() {
        (new enlRound($scope.app)).pick(oAppState.criteria.round).then(function(oResult) {
            oAppState.criteria.round = oResult.ids;
            $scope.checkedRoundTitles = oResult.titles;
            $scope.changeCriteria();
        });
    };
    $scope.showFolder = function() {
        var strBox, lastEle;
        strBox = document.querySelectorAll('.content');
        angular.forEach(strBox, function(str) {
            if (str.offsetHeight >= 43) {
                lastEle = str.parentNode.parentNode.lastElementChild;
                lastEle.classList.remove('hidden');
                str.classList.add('text-cut');
            }
        });
    }
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oConfig, rankItems, dataSchemas;
        oApp = params.app;
        dataSchemas = oApp.dynaDataSchemas;
        $scope.rankSchemas = dataSchemas.filter(function(oSchema) { return oSchema.type === 'single'; });
        /* 排行显示内容设置 */
        rankItems = ['enroll', 'remark', 'like', 'remark_other', 'do_like', 'total_coin', 'score', 'average_score', 'vote_schema', 'vote_cowork'];
        oConfig = {};
        rankItems.forEach(function(item) {
            oConfig[item] = true;
        });
        if (oApp.rankConfig) {
            if (oApp.rankConfig.scope) {
                rankItems.forEach(function(item) {
                    oConfig[item] = !!oApp.rankConfig.scope[item];
                });
            }
        }
        $scope.config = oConfig;
        /* 恢复上一次访问的状态 */
        if (window.localStorage) {
            $scope.$watch('appState', function(nv) {
                if (nv) {
                    window.localStorage.setItem("site.fe.matter.enroll.rank.appState", JSON.stringify(nv));
                }
            }, true);
            if (oAppState = window.localStorage.getItem("site.fe.matter.enroll.rank.appState")) {
                oAppState = JSON.parse(oAppState);
                if (!oAppState.aid || oAppState.aid !== oApp.id) {
                    oAppState = null;
                } else if (oAppState.criteria.obj === 'group') {
                    if (!oApp.entryRule.group.id) {
                        oAppState = null;
                    }
                }
            }
        }
        if (!oAppState) {
            oAppState = {
                aid: oApp.id,
                criteria: {
                    obj: oApp.rankConfig.defaultObj ? oApp.rankConfig.defaultObj : 'user',
                    orderby: oApp.rankConfig.defaultItem ? oApp.rankConfig.defaultItem : 'enroll',
                    agreed: 'all',
                    round: ['ALL']
                },
                page: {
                    at: 1,
                    size: 12
                }
            };
        }
        (new enlRound(oApp)).getRoundTitle(oAppState.criteria.round).then(function(titles) {
            $scope.checkedRoundTitles = titles;
        });
        $scope.appState = oAppState;
        $scope.$watch('appState.criteria.obj', function(oNew, oOld) {
            if (oNew && oOld && oNew !== oOld) {
                switch (oNew) {
                    case 'user':
                        oAppState.criteria.orderby = oApp.rankConfig.defaultItem ? oApp.rankConfig.defaultItem : 'enroll';
                        break;
                    case 'group':
                        oAppState.criteria.orderby = oApp.rankConfig.defaultItem ? oApp.rankConfig.defaultItem : 'enroll';
                        break;
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