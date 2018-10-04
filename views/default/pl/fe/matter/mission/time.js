define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTime', ['$scope', 'srvMissionRound', 'http2', function($scope, srvMisRnd, http2) {
        var rounds, page;
        $scope.pageOfRound = page = {};
        $scope.rounds = rounds = [];
        srvMisRnd.init(rounds, page)
        $scope.roundState = srvMisRnd.RoundState;
        $scope.updateCron = function() {
            var oDefaultRound;
            /* 是否要替换默认的填写时段 */
            if (rounds.length === 1 && rounds[0].start_at == 0) {
                oDefaultRound = rounds[0];
                if (window.confirm('是否将现有默认填写时段，根据填写时段生成规则设置为第一个启用时段？')) {
                    http2.get('/rest/pl/fe/matter/mission/round/activeByCron?mission=' + $scope.mission.id + '&rid=' + oDefaultRound.rid).then(function(rsp) {
                        angular.extend(oDefaultRound, rsp.data);
                    });
                } else {
                    $scope.doSearchRound();
                }
            } else {
                $scope.doSearchRound();
            }
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
    ngApp.provider.controller('ctrlRoundCron', ['$scope', 'http2', 'srvMission', function($scope, http2, srvMission) {
        var _oMission, _aCronRules, _byPeriods, _byIntervals;
        $scope.mdays = [];
        while ($scope.mdays.length < 28) {
            $scope.mdays.push('' + ($scope.mdays.length + 1));
        }
        $scope.byPeriods = _byPeriods = [];
        $scope.byIntervals = _byIntervals = [];
        $scope.example = function(oRule) {
            http2.post('/rest/pl/fe/matter/mission/round/getcron', { roundCron: oRule }).then(function(rsp) {
                oRule.case = rsp.data;
            });
        };
        $scope.changePeriod = function(oRule) {
            if (oRule.period !== 'W') {
                oRule.wday = '';
            }
            if (oRule.period !== 'M') {
                oRule.mday = '';
            }
        };
        $scope.addPeriod = function() {
            var oNewRule;
            oNewRule = {
                pattern: 'period',
                period: 'D',
                hour: 8,
                end_hour: 23,
                notweekend: true
            };
            _byPeriods.push(oNewRule);
            _aCronRules.push(oNewRule);
            $scope.example(oNewRule);
        };
        $scope.removePeriod = function(rule) {
            _byPeriods.splice(_byPeriods.indexOf(rule), 1);
            _aCronRules.splice(_aCronRules.indexOf(rule), 1);
        };
        $scope.addInterval = function() {
            var oNewRule;
            oNewRule = {
                pattern: 'interval',
                start_at: parseInt(new Date * 1 / 1000),
            };
            _byIntervals.push(oNewRule);
            _aCronRules.push(oNewRule);
        };
        $scope.removeInterval = function(rule) {
            _byIntervals.splice(_byIntervals.indexOf(rule), 1);
            _aCronRules.splice(_aCronRules.indexOf(rule), 1);
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, oData) {
            oData.obj[oData.state] = oData.value;
            $scope.example(oData.obj);
        });
        $scope.ok = function() {
            _aCronRules.forEach(function(oRule) {
                //delete oRule.case;
            });
            srvMission.submit({ round_cron: _aCronRules }).then(function(oNewMis) {
                http2.merge(_oMission.roundCron, oNewMis.roundCron);
                $scope.updateCron();
            });
        };
        srvMission.get().then(function(oMission) {
            _oMission = oMission;
            _aCronRules = oMission.roundCron ? angular.copy(oMission.roundCron) : [];
            _aCronRules.forEach(function(oRule) {
                switch (oRule.pattern) {
                    case 'period':
                        _byPeriods.push(oRule);
                        break;
                    case 'interval':
                        _byIntervals.push(oRule);
                        break;
                }
                $scope.example(oRule);
            });
        });
    }]);
});