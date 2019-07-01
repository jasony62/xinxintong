define(['frame'], function (ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTime', ['$scope', '$uibModal', 'http2', 'noticebox', 'CstApp', 'srvEnrollApp', 'srvEnrollRound', function ($scope, $uibModal, http2, noticebox, CstApp, srvEnlApp, srvEnlRnd) {
        function fnSetSyncMissionRound(bSetAppRound) {
            if (_oOptions.sync_mission_round === 'Y') {
                if (bSetAppRound) {
                    http2.get('/rest/pl/fe/matter/enroll/round/syncMissionRound?app=' + $scope.app.id, {
                        autoBreak: false
                    }).then(function (rsp) {
                        $scope.app.sync_mission_round = 'Y';
                        $scope.doSearchRound();
                    }, function (rsp) {
                        _oOptions.sync_mission_round = 'N';
                    });
                } else {
                    http2.post('/rest/pl/fe/matter/enroll/update?app=' + $scope.app.id, {
                        sync_mission_round: 'Y'
                    }).then(function (rsp) {
                        $scope.app.sync_mission_round = 'Y';
                        $scope.doSearchRound();
                    });
                }
            } else {
                http2.post('/rest/pl/fe/matter/enroll/update?app=' + $scope.app.id, {
                    sync_mission_round: 'N'
                }).then(function (rsp) {
                    $scope.app.sync_mission_round = 'N';
                });
            }
        }
        var _rounds, _oOptions;
        $scope.pageOfRound = {};
        $scope.options = _oOptions = {
            sync_mission_round: 'N'
        };
        $scope.rounds = _rounds = [];
        srvEnlRnd.init(_rounds, $scope.pageOfRound);
        $scope.roundPurpose = CstApp.options.round.purpose;
        $scope.updateCron = function () {
            var oDefaultRound;
            /* 是否要替换默认的填写时段 */
            if (_rounds.length === 1 && _rounds[0].start_at == 0) {
                oDefaultRound = _rounds[0];
                noticebox.confirm('是否将现有默认填写时段，根据填写时段生成规则设置为第一个启用时段？').then(function () {
                    http2.get('/rest/pl/fe/matter/enroll/round/activeByCron?app=' + $scope.app.id + '&rid=' + oDefaultRound.rid).then(function (rsp) {
                        angular.extend(oDefaultRound, rsp.data);
                    });
                }, function () {
                    $scope.doSearchRound();
                });
            } else {
                $scope.doSearchRound();
            }
        };
        $scope.doSearchRound = function () {
            srvEnlRnd.list();
        };
        $scope.add = function () {
            srvEnlRnd.add();
        };
        $scope.edit = function (oRound) {
            srvEnlRnd.edit(oRound);
        };
        $scope.remove = function (oRound) {
            noticebox.confirm('删除轮次【' + oRound.title + '】，确定？').then(function () {
                srvEnlRnd.remove(oRound);
            });
        };
        $scope.toggleSyncMissionRound = function () {
            if (_oOptions.sync_mission_round === 'Y' && _rounds.length === 1 && _rounds[0].start_at == 0) {
                $uibModal.open({
                    templateUrl: 'syncWithMissionRound.html',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        $scope2.result = {
                            setAppRound: false
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.ok = function () {
                            $mi.close($scope2.result);
                        };
                    }]
                }).result.then(function (oResult) {
                    fnSetSyncMissionRound(oResult.setAppRound);
                }, function () {
                    _oOptions.sync_mission_round = 'N';
                });
            } else {
                fnSetSyncMissionRound();
            }
        };
        $scope.doSearchRound();
        srvEnlApp.get().then(function (oApp) {
            $scope.options.sync_mission_round = oApp.sync_mission_round;
        });
    }]);
    ngApp.provider.controller('ctrlRoundCron', ['$scope', 'http2', 'srvEnrollApp', 'tkEnrollApp', 'tkRoundCron', function ($scope, http2, srvEnlApp, tkEnlApp, tkRndCron) {
        var _oApp;
        $scope.save = function () {
            tkEnlApp.update(_oApp, {
                roundCron: tkRndCron.editing.rules
            }).then(function (oNewApp) {
                http2.merge(_oApp.roundCron, oNewApp.roundCron);
                tkRndCron.editing.modified = false;
                $scope.updateCron();
            });
        };
        srvEnlApp.get().then(function (oApp) {
            _oApp = oApp;
            $scope.tkRndCron = tkRndCron.init(oApp);
        });
    }]);
});