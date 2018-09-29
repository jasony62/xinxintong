'use strict';
require('./votes.css');

var ngApp = require('./main.js');
ngApp.factory('Round', ['http2', '$q', function(http2, $q) {
    var Round, _ins;
    Round = function(oApp) {
        this.oApp = oApp;
        this.oPage = {
            at: 1,
            size: 12,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
    };
    Round.prototype.list = function() {
        var _this = this,
            deferred = $q.defer(),
            url;

        url = '/rest/site/fe/matter/enroll/round/list?site=' + this.oApp.siteid + '&app=' + this.oApp.id;
        url += this.oPage.j();
        http2.get(url).then(function(rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            _this.oPage.total = rsp.data.total;
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    return {
        ins: function(oApp) {
            _ins = _ins ? _ins : new Round(oApp);
            return _ins;
        }
    };
}]);
ngApp.controller('ctrlVotes', ['$scope', '$q', '$timeout', 'tmsLocation', 'http2', 'Round', function($scope, $q, $timeout, LS, http2, srvRound) {
    var _oApp, _facRound, _oCriteria, _oFilter;
    $scope.criteria = _oCriteria = {}; // 数据查询条件
    $scope.filter = _oFilter = {}; // 过滤条件
    $scope.setAppActsAndNavs = function() {
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
            if (_oApp.scenarioConfig.can_rank === 'Y') {
                oAppNavs.rank = {};
                oAppNavs.length++;
            }
            if (_oApp.scenarioConfig.can_action === 'Y') {
                oAppNavs.event = {};
                oAppNavs.length++;
            }
        }
        if (Object.keys(oAppNavs)) {
            $scope.appNavs = oAppNavs;
        }
    };
    $scope.gotoOptionLink = function(oSchema, oOption) {
        if (oOption.referRecord && oOption.referRecord.ds && oOption.referRecord.ds.ek && oSchema.dsOps && oSchema.dsOps.app && oSchema.dsOps.app.id) {
            location.href = LS.j('', 'site') + '&app=' + oSchema.dsOps.app.id + '&ek=' + oOption.referRecord.ds.ek + '&page=cowork';
        }
    };
    /* 获得投票结果 */
    $scope.getVotes = function() {
        var defer = $q.defer();
        /* 每个轮次的动态选项不一样，需要根据轮次获取动态选项 */
        http2.get(LS.j('schema/get', 'site', 'app') + '&rid=' + _oCriteria.rid).then(function(rsp) {
            var url, oSchemasById;
            oSchemasById = {};
            rsp.data.forEach(function(oSchema) {
                oSchemasById[oSchema.id] = oSchema;
            });
            url = LS.j('votes/get', 'site', 'app');
            if (_oCriteria.rid) {
                url += '&rid=' + _oCriteria.rid;
            }
            http2.get(url).then(function(rsp) {
                angular.forEach(rsp.data, function(oSchema) {
                    var oOriginalSchema;
                    if (oOriginalSchema = oSchemasById[oSchema.id]) {
                        if (oSchema.ops && oSchema.ops.length) {
                            oSchema.ops.forEach(function(oOption) {
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
                        oSchema.ops = oSchema.ops.sort(function(a, b) {
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
    $scope.shiftRound = function(oRound) {
        _oFilter.round = oRound;
        _oCriteria.rid = oRound ? oRound.rid : 'all';
        $scope.getVotes();
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        /* 设置页面分享信息 */
        $scope.setSnsShare();
        /* 设置页面导航和全局操作 */
        $scope.setAppActsAndNavs();
        $scope.facRound = _facRound = srvRound.ins(_oApp);
        _facRound.list().then(function(result) {
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
    });
}]);