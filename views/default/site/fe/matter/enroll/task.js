'use strict';
require('./enroll.public.css');
require('./task.css');
require('./_asset/ui.round.js');
require('./_asset/ui.task.js');

window.moduleAngularModules = ['round.ui.enroll', 'task.ui.enroll'];

var ngApp = require('./main.js');
ngApp.controller('ctrlTask', ['$scope', '$parse', '$q', '$uibModal', 'http2', 'tmsLocation', '$timeout', 'noticebox', 'enlRound', 'enlTask', function($scope, $parse, $q, $uibModal, http2, LS, $timeout, noticebox, enlRound, enlTask) {
    function fnGetTasks(oRound) {
        _tasks.splice(0, _tasks.length);
        _enlTask.list(null, null, oRound.rid).then(function(roundTasks) {
            if (roundTasks.length) {
                roundTasks.forEach(function(oTask) {
                    _tasks.push(oTask);
                });
            }
        });
    }
    var _oApp, _tasks, _enlTask;
    $scope.tasks = _tasks = [];
    $scope.Label = { task: { state: { 'IP': '进行中', 'BS': '未开始', 'AE': '已结束' } } };
    $scope.shiftRound = function(oRound) {
        $scope.selectedRound = oRound;
        fnGetTasks(oRound);
    };
    $scope.gotoTask = function(oTask) {
        if (oTask) {
            if (oTask.type === 'baseline') {
                location.href = LS.j('', 'site', 'app') + '&rid=' + oTask.rid + '&page=enroll';
            } else if (oTask.topic && oTask.topic.id) {
                location.href = LS.j('', 'site', 'app') + '&topic=' + oTask.topic.id + '&page=topic';
            }
        }
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        var facRound = new enlRound(_oApp);
        facRound.list().then(function(oResult) {
            $scope.rounds = oResult.rounds;
            if ($scope.rounds.length) $scope.shiftRound($scope.rounds[0]);
        });
        _enlTask = new enlTask(_oApp);
        /*设置页面导航*/
        $scope.setPopNav(['repos'], 'task');
    });
}]);