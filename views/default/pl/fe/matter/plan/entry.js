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
        $scope.downloadQrcode = function(url) {
            $('<a href="' + url + '" download="' + $scope.app.title + '.png"></a>')[0].click();
        };
    }]);
    ngApp.provider.controller('ctrlOpUrl', ['$scope', 'srvQuickEntry', 'srvPlanApp', function($scope, srvQuickEntry, srvPlanApp) {
        var targetUrl, host, opEntry;
        $scope.opEntry = opEntry = {};
        srvPlanApp.get().then(function(app) {
            targetUrl = app.opUrl;
            host = targetUrl.match(/\/\/(\S+?)\//);
            host = host.length === 2 ? host[1] : location.host;
            srvQuickEntry.get(targetUrl).then(function(entry) {
                if (entry) {
                    opEntry.url = 'http://' + host + '/q/' + entry.code;
                    opEntry.password = entry.password;
                    opEntry.code = entry.code;
                    opEntry.can_favor = entry.can_favor;
                }
            });
        });
        $scope.makeOpUrl = function() {
            srvQuickEntry.add(targetUrl, $scope.app.title).then(function(task) {
                $scope.app.op_short_url_code = task.code;
                srvPlanApp.update('op_short_url_code');
                opEntry.url = 'http://' + host + '/q/' + task.code;
                opEntry.code = task.code;
            });
        };
        $scope.closeOpUrl = function() {
            srvQuickEntry.remove(targetUrl).then(function(task) {
                opEntry.url = '';
                opEntry.code = '';
                opEntry.can_favor = 'N';
                opEntry.password = '';
                $scope.app.op_short_url_code = '';
                srvPlanApp.update('op_short_url_code');
            });
        };
        $scope.configOpUrl = function(event, prop) {
            event.preventDefault();
            srvQuickEntry.config(targetUrl, {
                password: opEntry.password
            });
        };
        $scope.updCanFavor = function() {
            srvQuickEntry.update(opEntry.code, { can_favor: opEntry.can_favor });
        };
    }]);
    ngApp.provider.controller('ctrlRpUrl', ['$scope', 'srvQuickEntry', 'srvPlanApp', function($scope, srvQuickEntry, srvPlanApp) {
        var targetUrl, host, rpEntry;
        $scope.rpEntry = rpEntry = {};
        srvPlanApp.get().then(function(app) {
            targetUrl = app.rpUrl;
            host = targetUrl.match(/\/\/(\S+?)\//);
            host = host.length === 2 ? host[1] : location.host;
            srvQuickEntry.get(targetUrl).then(function(entry) {
                if (entry) {
                    rpEntry.url = 'http://' + host + '/q/' + entry.code;
                    rpEntry.password = entry.password;
                    rpEntry.code = entry.code;
                    rpEntry.can_favor = entry.can_favor;
                }
            });
        });
        $scope.makeOpUrl = function() {
            srvQuickEntry.add(targetUrl, $scope.app.title).then(function(task) {
                $scope.app.rp_short_url_code = task.code;
                srvPlanApp.update('rp_short_url_code');
                rpEntry.url = 'http://' + host + '/q/' + task.code;
                rpEntry.code = task.code;
            });
        };
        $scope.closeOpUrl = function() {
            srvQuickEntry.remove(targetUrl).then(function(task) {
                rpEntry.url = '';
                rpEntry.code = '';
                rpEntry.can_favor = 'N';
                rpEntry.password = '';
                $scope.app.rp_short_url_code = '';
                srvPlanApp.update('rp_short_url_code');
            });
        };
        $scope.configOpUrl = function(event, prop) {
            event.preventDefault();
            srvQuickEntry.config(targetUrl, {
                password: rpEntry.password
            });
        };
        $scope.updCanFavor = function() {
            srvQuickEntry.update(rpEntry.code, { can_favor: rpEntry.can_favor });
        };
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