'use strict';
require('./votes.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlVotes', ['$scope', '$q', '$timeout', 'tmsLocation', 'http2', function($scope, $q, $timeout, LS, http2) {
    var _oApp;
    $scope.setAppActsAndNavs = function() {
        /*设置页面操作*/
        $scope.appActs = {
            addRecord: {}
        };
        /*设置页面导航*/
        var oAppNavs = {};
        if (_oApp.can_repos === 'Y') {
            oAppNavs.repos = {};
        }
        if (_oApp.can_rank === 'Y') {
            oAppNavs.rank = {};
        }
        if (_oApp.scenarioConfig && _oApp.scenarioConfig.can_action === 'Y') {
            oAppNavs.event = {};
        }
        if (Object.keys(oAppNavs)) {
            $scope.appNavs = oAppNavs;
        }
    };
    $scope.gotoOptionLink = function(oSchema, oOption) {
        if (oOption.ds && oOption.ds.ek && oSchema.dsOps && oSchema.dsOps.app && oSchema.dsOps.app.id) {
            location.href = LS.j('', 'site') + '&app=' + oSchema.dsOps.app.id + '&ek=' + oOption.ds.ek + '&page=cowork';
        }
    };
    $scope.getVotes = function() {
        var defer = $q.defer();
        http2.get(LS.j('votes/get', 'site', 'app')).then(function(rsp) {
            angular.forEach(rsp.data, function(oSchema) {
                var oOriginalSchema;
                if (oOriginalSchema = _oApp._schemasById[oSchema.id]) {
                    if (oSchema.ops && oSchema.ops.length) {
                        oSchema.ops.forEach(function(oOption) {
                            var oOriginalOption;
                            oOption.p = (oOption.c / oSchema.sum * 100).toFixed(2);
                            /* 从数据来源活动，查看详情 */
                            if (oOriginalSchema.dsOps && oOriginalSchema.showOpDsLink === 'Y') {
                                oSchema.dsOps = oOriginalSchema.dsOps;
                                for (var i = 0, ii = oOriginalSchema.ops.length; i < ii; i++) {
                                    oOriginalOption = oOriginalSchema.ops[i];
                                    if (oOption.v === oOriginalOption.v) {
                                        if (oOriginalOption.ds) {
                                            oOption.ds = oOriginalOption.ds;
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

        return defer.promise;
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        /* 设置页面分享信息 */
        $scope.setSnsShare();
        /* 设置页面导航和全局操作 */
        $scope.setAppActsAndNavs();
        /* 获取投票结果数据 */
        $scope.getVotes();
    });
}]);