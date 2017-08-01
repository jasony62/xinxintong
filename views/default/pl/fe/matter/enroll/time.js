define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTime', ['$scope', 'srvEnrollApp', 'srvEnrollRound', function($scope, srvEnlApp, srvEnlRnd) {
        var rounds, page;
        $scope.pageOfRound = page = {};
        $scope.rounds = rounds = [];
        srvEnlRnd.init(rounds, page)
        $scope.roundState = srvEnlRnd.RoundState;
        $scope.openCron = function() {
            srvEnlRnd.cron().then(function() {
                $scope.doSearchRound();
            });
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
});