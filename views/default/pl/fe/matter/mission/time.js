define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTime', ['$scope', 'srvMissionRound', function($scope, srvMisRnd) {
        var rounds, page;
        $scope.pageOfRound = page = {};
        $scope.rounds = rounds = [];
        srvMisRnd.init(rounds, page)
        $scope.roundState = srvMisRnd.RoundState;
        $scope.openCron = function() {
            srvMisRnd.cron().then(function() {
                $scope.doSearchRound();
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