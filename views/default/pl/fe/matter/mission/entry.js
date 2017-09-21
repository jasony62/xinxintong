define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEntry', ['$scope', 'http2', 'srvQuickEntry', '$timeout', function($scope, http2, srvQuickEntry, $timeout) {
        var targetUrl, host, opEntry;
        $scope.opEntry = opEntry = {};
        $scope.$watch('mission', function(mission) {
            if (!mission) return;
            targetUrl = mission.opUrl;
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
        $timeout(function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        });
        $scope.makeOpUrl = function() {
            srvQuickEntry.add(targetUrl, $scope.mission.title).then(function(task) {
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
        $scope.$watch('mission', function(mission) {
            if (!mission) return;
            http2.get('/rest/pl/fe/matter/timer/byMatter?site=' + mission.siteid + '&type=mission&id=' + mission.id, function(rsp) {
                rsp.data.forEach(function(oTask) {
                    oTimerTask[oTask.task_model].state = 'Y';
                    oTimerTask[oTask.task_model].taskId = oTask.id;
                    oTimerTask[oTask.task_model].task = {};
                    ['pattern', 'min', 'hour', 'wday', 'mday', 'mon', 'left_count', 'enabled'].forEach(function(prop) {
                        oTimerTask[oTask.task_model].task[prop] = oTask[prop];
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
        var oTimerTask;
        $scope.timerTask = oTimerTask = {
            report: {
                modified: false,
                state: 'N'
            },
            remind: {
                modified: false,
                state: 'N'
            },
        };
        $scope.shiftTimerTask = function(model) {
            var oOneTask;
            oOneTask = oTimerTask[model];
            if (oOneTask.state === 'Y') {
                var oConfig;
                oConfig = {
                    matter: { id: $scope.mission.id, type: 'mission' },
                    task: { model: model }
                }
                http2.post('/rest/pl/fe/matter/timer/create?site=' + $scope.mission.siteid, oConfig, function(rsp) {
                    oOneTask.state = 'Y';
                    oOneTask.taskId = rsp.data.id;
                    oOneTask.task = {};
                    ['pattern', 'min', 'hour', 'wday', 'mday', 'mon', 'left_count', 'enabled'].forEach(function(prop) {
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
                http2.get('/rest/pl/fe/matter/timer/remove?site=' + $scope.mission.siteid + '&id=' + oOneTask.taskId, function(rsp) {
                    oOneTask.state = 'N';
                    delete oOneTask.taskId;
                    delete oOneTask.task;
                });
            }
        };
        $scope.saveTimerTask = function(model) {
            var oOneTask;
            oOneTask = oTimerTask[model];
            if (oOneTask.state === 'Y') {
                http2.post('/rest/pl/fe/matter/timer/update?site=' + $scope.mission.siteid + '&id=' + oOneTask.taskId, oOneTask.task, function(rsp) {
                    ['min', 'hour', 'wday', 'mday', 'mon', 'left_count'].forEach(function(prop) {
                        oOneTask.task[prop] = '' + rsp.data[prop];
                    });
                    oOneTask.modified = false;
                });
            }
        };
    }]);
    ngApp.provider.controller('ctrlReceiver', ['$scope', 'http2', '$interval', '$uibModal', 'srvSite', function($scope, http2, $interval, $uibModal, srvSite) {
        var baseURL = '/rest/pl/fe/matter/mission/receiver/';
        function listReceivers(app) {
            http2.get(baseURL + 'list?site=' + app.siteid + '&app=' + app.id, function(rsp) {
                var map = { wx: '微信', yx: '易信' };
                rsp.data.forEach(function(receiver) {
                    if (receiver.sns_user) {
                        receiver.snsUser = JSON.parse(receiver.sns_user);
                        map[receiver.snsUser.src] && (receiver.snsUser.snsName = map[receiver.snsUser.src]);
                    }
                });
                $scope.receivers = rsp.data;
            });
        }

        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
        });
        $scope.qrcodeShown = false;
        $scope.qrcode = function(snsName) {
            if ($scope.qrcodeShown === false) {
                var url = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/createOneOff';
                url += '?site=' + $scope.mission.siteid;
                url += '&matter_type=missionreceiver';
                url += '&matter_id=' + $scope.mission.id;
                http2.get(url, function(rsp) {
                    var qrcode = rsp.data,
                        eleQrcode = $("#" + snsName + "Qrcode");
                    eleQrcode.trigger('show');
                    $scope.qrcodeURL = qrcode.pic;
                    $scope.qrcodeShown = true;
                    (function() {
                        var fnCheckQrcode, url2;
                        url2 = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/get';
                        url2 += '?site=' + qrcode.siteid;
                        url2 += '&id=' + rsp.data.id;
                        url2 += '&cascaded=N';
                        fnCheckQrcode = $interval(function() {
                            http2.get(url2, function(rsp) {
                                if (rsp.data == false) {
                                    $interval.cancel(fnCheckQrcode);
                                    eleQrcode.trigger('hide');
                                    $scope.qrcodeShown = false;
                                    (function() {
                                        var fnCheckReceiver;
                                        fnCheckReceiver = $interval(function() {
                                            http2.get('/rest/pl/fe/matter/mission/receiver/afterJoin?site=' + $scope.mission.siteid + '&app=' + $scope.mission.id + '&timestamp=' + qrcode.create_at, function(rsp) {
                                                if (rsp.data.length) {
                                                    $interval.cancel(fnCheckReceiver);
                                                    $scope.receivers = $scope.receivers.concat(rsp.data);
                                                }
                                            });
                                        }, 2000);
                                    })();
                                }
                            });
                        }, 2000);
                    })();
                });
            } else {
                $("#yxQrcode").trigger('hide');
                $scope.qrcodeShown = false;
            }
        };
        $scope.remove = function(receiver) {
            http2.get(baseURL + 'remove?site=' + $scope.mission.siteid + '&app=' + $scope.mission.id + '&receiver=' + receiver.id, function(rsp) {
                $scope.receivers.splice($scope.receivers.indexOf(receiver), 1);
            });
        };
        $scope.$watch('mission', function(mission) {
            if (!mission) return;
            listReceivers(mission);
        });
    }]);
})