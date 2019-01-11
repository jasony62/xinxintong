'use strict';
require('./enroll.public.css');
require('./_asset/ui.round.js');

window.moduleAngularModules = ['round.ui.enroll'];

var ngApp = require('./main.js');
ngApp.controller('ctrlTask', ['$scope', '$sce', '$q', '$uibModal', 'http2', 'tmsLocation', '$timeout', 'noticebox', 'enlRound', function($scope, $sce, $q, $uibModal, http2, LS, $timeout, noticebox, enlRound) {
    function fnGetTasks(oRound) {
        http2.get(LS.j('task/list', 'site', 'app') + '&rid=' + oRound.rid).then(function(rsp) {
            if (rsp.data.question) {}
            if (rsp.data.answer) {}
            if (rsp.data.vote) {}
            if (rsp.data.score) {}
        });
    }
    var _oApp;
    $scope.shiftRound = function(oRound) {
        $scope.selectedRound = oRound;
        fnGetTasks(oRound);
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        var facRound = new enlRound(_oApp);
        facRound.list().then(function(oResult) {
            $scope.rounds = oResult.rounds;
            if ($scope.rounds.length) $scope.shiftRound($scope.rounds[0]);
        });
    });
}]);