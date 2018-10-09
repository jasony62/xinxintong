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
    ngApp.provider.controller('ctrlRoundCron', ['$scope', 'http2', 'srvEnrollApp', 'tkEnrollApp', 'tkRoundCron', function($scope, http2, srvEnlApp, tkEnlApp, tkRndCron) {
        var _oApp, _aCronRules;
        $scope.save = function() {
            tkEnlApp.update(_oApp, { roundCron: _aCronRules }).then(function(oNewApp) {
                http2.merge(_oApp.roundCron, oNewApp.roundCron);
                tkRndCron.editing.modified = false;
                $scope.updateCron();
            });
        };
        srvEnlApp.get().then(function(oApp) {
            _oApp = oApp;
            _aCronRules = oApp.roundCron ? angular.copy(oApp.roundCron) : [];
            tkRndCron.init(_aCronRules);
            $scope.tkRndCron = tkRndCron;
        });
    }]);
});