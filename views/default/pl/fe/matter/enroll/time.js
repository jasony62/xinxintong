define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTime', ['$scope', 'srvEnrollRound', 'http2', 'noticebox', 'cstApp', function($scope, srvEnlRnd, http2, noticebox, CstApp) {
        var rounds, page;
        $scope.pageOfRound = page = {};
        $scope.rounds = rounds = [];
        srvEnlRnd.init(rounds, page)
        $scope.roundState = CstApp.options.round.state;
        $scope.roundPurpose = CstApp.options.round.purpose;
        $scope.updateCron = function() {
            var oDefaultRound;
            /* 是否要替换默认的填写时段 */
            if (rounds.length === 1 && rounds[0].start_at == 0) {
                oDefaultRound = rounds[0];
                noticebox.confirm('是否将现有默认填写时段，根据填写时段生成规则设置为第一个启用时段？').then(function() {
                    http2.get('/rest/pl/fe/matter/enroll/round/activeByCron?app=' + $scope.app.id + '&rid=' + oDefaultRound.rid).then(function(rsp) {
                        angular.extend(oDefaultRound, rsp.data);
                    });
                }, function() { $scope.doSearchRound(); });
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
        $scope.edit = function(oRound) {
            srvEnlRnd.edit(oRound);
        };
        $scope.remove = function(oRound) {
            noticebox.confirm('删除轮次【' + oRound.title + '】，确定？').then(function() {
                srvEnlRnd.remove(oRound);
            });
        };
        $scope.doSearchRound();
    }]);
    ngApp.provider.controller('ctrlRoundCron', ['$scope', 'http2', 'srvEnrollApp', 'tkEnrollApp', 'tkRoundCron', function($scope, http2, srvEnlApp, tkEnlApp, tkRndCron) {
        var _oApp;
        $scope.save = function() {
            tkEnlApp.update(_oApp, { roundCron: tkRndCron.editing.rules }).then(function(oNewApp) {
                http2.merge(_oApp.roundCron, oNewApp.roundCron);
                tkRndCron.editing.modified = false;
                $scope.updateCron();
            });
        };
        srvEnlApp.get().then(function(oApp) {
            _oApp = oApp;
            $scope.tkRndCron = tkRndCron.init(oApp);
        });
    }]);
});