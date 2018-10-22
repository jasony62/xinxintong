define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTime', ['$scope', 'srvMissionRound', 'http2', function($scope, srvMisRnd, http2) {
        var rounds, page;
        $scope.pageOfRound = page = {};
        $scope.rounds = rounds = [];
        srvMisRnd.init(rounds, page)
        $scope.roundState = srvMisRnd.RoundState;
        $scope.openCron = function() {
            srvMisRnd.cron().then(function() {
                var oDefaultRound;
                /* 是否要替换默认的填写时段 */
                if (rounds.length === 1 && rounds[0].start_at == 0) {
                    oDefaultRound = rounds[0];
                    if (window.confirm('是否将现有默认填写时段，根据填写时段生成规则设置为第一个启用时段？')) {
                        http2.get('/rest/pl/fe/matter/mission/round/activeByCron?mission=' + $scope.mission.id + '&rid=' + oDefaultRound.rid, function(rsp) {
                            angular.extend(oDefaultRound, rsp.data);
                        });
                    } else {
                        $scope.doSearchRound();
                    }
                } else {
                    $scope.doSearchRound();
                }
            });
        };
        $scope.doSearchRound = function() {
            srvMisRnd.list();
        };
        $scope.add = function() {
            srvMisRnd.add();
        };
        $scope.edit = function(round) {
            srvMisRnd.edit(round);
        };
        $scope.doSearchRound();
    }]);
});