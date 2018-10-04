define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTime', ['$scope', 'srvEnrollApp', 'srvEnrollRound', 'http2', function($scope, srvEnlApp, srvEnlRnd, http2) {
        var rounds, page;
        $scope.pageOfRound = page = {};
        $scope.rounds = rounds = [];
        srvEnlRnd.init(rounds, page)
        $scope.roundState = srvEnlRnd.RoundState;
        $scope.updateCron = function() {
            var oDefaultRound;
            /* 是否要替换默认的填写时段 */
            if (rounds.length === 1 && rounds[0].start_at == 0) {
                oDefaultRound = rounds[0];
                if (window.confirm('是否将现有默认填写时段，根据填写时段生成规则设置为第一个启用时段？')) {
                    http2.get('/rest/pl/fe/matter/enroll/round/activeByCron?app=' + $scope.app.id + '&rid=' + oDefaultRound.rid).then(function(rsp) {
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
            srvEnlRnd.list();
        };
        $scope.add = function() {
            srvEnlRnd.add();
        };
        $scope.edit = function(round) {
            srvEnlRnd.edit(round);
        };
        $scope.doSearchRound();
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope.app[data.state] = data.value;
            srvEnlApp.update(data.state);
        });
    }]);
    ngApp.provider.controller('ctrlRoundCron', ['$scope', 'http2', 'srvEnrollApp', 'tkEnrollApp', function($scope, http2, srvEnlApp, tkEnlApp) {
        var _oApp, _aCronRules, _byPeriods, _byIntervals;
        $scope.mdays = [];
        while ($scope.mdays.length < 28) {
            $scope.mdays.push('' + ($scope.mdays.length + 1));
        }
        $scope.editing = { modified: false };
        $scope.byPeriods = _byPeriods = [];
        $scope.byIntervals = _byIntervals = [];
        $scope.example = function(oRule) {
            http2.post('/rest/pl/fe/matter/enroll/round/getcron', { roundCron: oRule }).then(function(rsp) {
                oRule.case = rsp.data;
            });
        };
        $scope.changePeriod = function(oRule) {
            switch (oRule.period) {
                case 'W':
                    !oRule.wday && (oRule.wday = '1');
                    break;
                case 'M':
                    !oRule.mday && (oRule.mday = '1');
                    break;
            }!oRule.hour && (oRule.hour = '8');
        };
        $scope.addPeriod = function() {
            var oNewRule;
            oNewRule = {
                pattern: 'period',
                period: 'D',
                hour: '8',
                notweekend: true,
                enabled: 'N',
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
            tkEnlApp.update(_oApp, { roundCron: _aCronRules }).then(function(oNewApp) {
                http2.merge(_oApp.roundCron, oNewApp.roundCron);
                $scope.updateCron();
            });
        };
        srvEnlApp.get().then(function(oApp) {
            _oApp = oApp;
            _aCronRules = oApp.roundCron ? angular.copy(oApp.roundCron) : [];
            _aCronRules.forEach(function(oRule) {
                switch (oRule.pattern) {
                    case 'period':
                        _byPeriods.push(oRule);
                        break;
                    case 'interval':
                        _byIntervals.push(oRule);
                        break;
                }
                //$scope.example(oRule);
            });
            $scope.editing.rules = _aCronRules;
            $scope.$watch('editing.rules', function(newRules, oldRules) {
                console.log('ddd');
            }, true);
        });
    }]);
});