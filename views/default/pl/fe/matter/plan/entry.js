define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEntry', ['$scope', '$timeout', 'srvPlanApp', function($scope, $timeout, srvPlanApp) {
        $timeout(function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        });
        srvPlanApp.get().then(function(oApp) {
            var oEntry;
            oEntry = {
                url: oApp.entryUrl,
                qrcode: '/rest/site/fe/matter/enroll/qrcode?site=' + oApp.siteid + '&url=' + encodeURIComponent(oApp.entryUrl),
            };
            $scope.entry = oEntry;
        });
    }]);
    ngApp.provider.controller('ctrlTimerNotice', ['$scope', 'http2', 'srvPlanApp', function($scope, http2, srvPlanApp) {
        var _oApp, _oTimerTask;
        $scope.timerTask = _oTimerTask = {
            remind: {
                modified: false,
                state: 'N'
            },
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            _oTimerTask[data.state].task.task_expire_at = data.value;
        });
        $scope.shiftTimerTask = function(model) {
            var oOneTask;
            oOneTask = _oTimerTask[model];
            if (oOneTask.state === 'Y') {
                var oConfig;
                oConfig = {
                    matter: { id: _oApp.id, type: 'plan' },
                    task: { model: model }
                }
                http2.post('/rest/pl/fe/matter/timer/create?site=' + _oApp.siteid, oConfig, function(rsp) {
                    oOneTask.state = 'Y';
                    oOneTask.taskId = rsp.data.id;
                    oOneTask.task = {};
                    ['pattern', 'min', 'hour', 'wday', 'mday', 'mon', 'left_count', 'task_expire_at', 'enabled', 'notweekend'].forEach(function(prop) {
                        oOneTask.task[prop] = '' + rsp.data[prop];
                    });
                    $scope.$watch('timerTask.' + model, function(oUpdTask, oOldTask) {
                        if (oUpdTask && oUpdTask.task) {
                            if (!angular.equals(oUpdTask.task, oOldTask.task)) {
                                oUpdTask.modified = true;
                            }
                        }
                    }, true);
                });
            } else {
                http2.get('/rest/pl/fe/matter/timer/remove?site=' + _oApp.siteid + '&id=' + oOneTask.taskId, function(rsp) {
                    oOneTask.state = 'N';
                    delete oOneTask.taskId;
                    delete oOneTask.task;
                });
            }
        };
        $scope.saveTimerTask = function(model) {
            var oOneTask;
            oOneTask = _oTimerTask[model];
            if (oOneTask.state === 'Y') {
                http2.post('/rest/pl/fe/matter/timer/update?site=' + _oApp.siteid + '&id=' + oOneTask.taskId, oOneTask.task, function(rsp) {
                    ['min', 'hour', 'wday', 'mday', 'mon', 'left_count'].forEach(function(prop) {
                        oOneTask.task[prop] = '' + rsp.data[prop];
                    });
                    oOneTask.modified = false;
                });
            }
        };
        srvPlanApp.get().then(function(oApp) {
            _oApp = oApp;
            http2.get('/rest/pl/fe/matter/timer/byMatter?site=' + oApp.siteid + '&type=plan&id=' + oApp.id, function(rsp) {
                rsp.data.forEach(function(oTask) {
                    _oTimerTask[oTask.task_model].state = 'Y';
                    _oTimerTask[oTask.task_model].taskId = oTask.id;
                    _oTimerTask[oTask.task_model].task = {};
                    ['pattern', 'min', 'hour', 'wday', 'mday', 'mon', 'left_count', 'task_expire_at', 'enabled', 'notweekend'].forEach(function(prop) {
                        _oTimerTask[oTask.task_model].task[prop] = oTask[prop];
                    });
                    $scope.$watch('timerTask.' + oTask.task_model, function(oUpdTask, oOldTask) {
                        if (oUpdTask && oUpdTask.task) {
                            if (!angular.equals(oUpdTask.task, oOldTask.task)) {
                                oUpdTask.modified = true;
                            }
                        }
                    }, true);
                });
            });
        });
    }]);
});