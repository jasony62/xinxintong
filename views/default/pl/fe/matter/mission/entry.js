define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEntry', ['$scope', '$q', 'http2', 'srvQuickEntry', '$timeout', 'srvSite', function($scope, $q, http2, srvQuickEntry, $timeout, srvSite) {
        /* 加载从数据库中返回的定时任务 */
        function fnLoadDbTask(tasks, oDbTask) {
            var oLocalTask;
            oLocalTask = {
                id: oDbTask.id,
                task: {},
                modified: false
            };
            ['pattern', 'min', 'hour', 'wday', 'mday', 'mon', 'left_count', 'task_expire_at', 'enabled', 'notweekend'].forEach(function(prop) {
                oLocalTask.task[prop] = '' + oDbTask[prop];
            });
            tasks.push(oLocalTask);
            oTimerTask['t_' + oLocalTask.id] = oLocalTask;
            $scope.$watch('timerTask.t_' + oLocalTask.id, function(oUpdTask, oOldTask) {
                if (oUpdTask && oUpdTask.task) {
                    if (!angular.equals(oUpdTask.task, oOldTask.task)) {
                        oUpdTask.modified = true;
                    }
                }
            }, true);
        }
        var targetUrl, host, opEntry;
        $scope.opEntry = opEntry = {};
        $timeout(function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        });
        $scope.makeOpUrl = function() {
            srvQuickEntry.add(targetUrl, $scope.mission.title).then(function(task) {
                opEntry.url = location.protocol + '//' + host + '/q/' + task.code;
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
        var oTimerTask;
        $scope.timerTask = oTimerTask = {};
        /* 获得指定类型的定时任务 */
        $scope.timerTaskList = function(model) {
            var defer = $q.defer();
            http2.get('/rest/pl/fe/matter/timer/byMatter?type=mission&id=' + $scope.mission.id + '&model=' + model, function(rsp) {
                var tasks = [];
                rsp.data.forEach(function(oTask) {
                    fnLoadDbTask(tasks, oTask);
                });
                defer.resolve(tasks);
            });
            return defer.promise;
        };
        /* 定时任务结束时间 */
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            oTimerTask['t_' + data.state].task.task_expire_at = data.value;
        });
        /* 定时任务设置更新 */
        $scope.saveTimerTask = function(oOneTask) {
            http2.post('/rest/pl/fe/matter/timer/update?id=' + oOneTask.id, oOneTask.task, function(rsp) {
                ['min', 'hour', 'wday', 'mday', 'mon', 'left_count'].forEach(function(prop) {
                    oOneTask.task[prop] = '' + rsp.data[prop];
                });
                oOneTask.modified = false;
            });
        };
        /* 添加定时任务 */
        $scope.addTimerTask = function(tasks, model) {
            var oConfig;
            oConfig = {
                matter: { id: $scope.mission.id, type: 'mission' },
                task: { model: model }
            }
            http2.post('/rest/pl/fe/matter/timer/create', oConfig, function(rsp) {
                fnLoadDbTask(tasks, rsp.data);
            });
        };
        /* 删除定时任务 */
        $scope.delTimerTask = function(tasks, index) {
            var oOneTask = tasks[index];
            if (window.confirm('确定删除定时任务？')) {
                http2.get('/rest/pl/fe/matter/timer/remove?id=' + oOneTask.id, function(rsp) {
                    tasks.splice(index, 1);
                });
            }
        };
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            /* 监督人入口 */
            targetUrl = oMission.opUrl;
            host = targetUrl.match(/\/\/(\S+?)\//);
            host = host.length === 2 ? host[1] : location.host;
            srvQuickEntry.get(targetUrl).then(function(entry) {
                if (entry) {
                    opEntry.url = location.protocol + '//' + host + '/q/' + entry.code;
                    opEntry.password = entry.password;
                    opEntry.code = entry.code;
                    opEntry.can_favor = entry.can_favor;
                }
            });
            /* 项目通讯录 */
            srvSite.memberSchemaList(oMission, true).then(function(aMemberSchemas) {
                $scope.missionMschemas = aMemberSchemas;
            });
        });
    }]);
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'http2', 'srvSite', function($scope, $uibModal, http2, srvSite) {
        var oEntryRule;
        $scope.rule = {};
        $scope.changeUserScope = function() {
            switch (oEntryRule.scope) {
                case 'member':
                    oEntryRule.member === undefined && (oEntryRule.member = {});
                    break;
                case 'sns':
                    oEntryRule.sns === undefined && (oEntryRule.sns = {});
                    Object.keys($scope.sns).forEach(function(snsName) {
                        if (oEntryRule.sns[snsName] === undefined) {
                            oEntryRule.sns[snsName] = { entry: 'Y' };
                        }
                    });
                    break;
                default:
            }
            this.update('entry_rule');
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema($scope.mission).then(function(result) {
                var chosen;
                if (result && result.chosen) {
                    chosen = result.chosen;
                    $scope.mschemasById[chosen.id] = chosen;
                    if (!oEntryRule.member[chosen.id]) {
                        oEntryRule.member[chosen.id] = { entry: '' };
                        $scope.update('entry_rule');
                    }
                }
            });
        };
        $scope.editMschema = function(oMschema) {
            if (oMschema.matter_id === $scope.mission.id) {
                location.href = '/rest/pl/fe/matter/mission/mschema?site=' + $scope.mission.siteid + '&id=' + $scope.mission.id + '#' + oMschema.id;
            } else {
                location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.mission.siteid + '&mschema=' + oMschema.id;
            }
        };
        $scope.removeMschema = function(mschemaId) {
            if (oEntryRule.member[mschemaId]) {
                delete oEntryRule.member[mschemaId];
                $scope.update('entry_rule');
            }
        };
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsCount = Object.keys(oSns).length;
        });
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            $scope.rule = oEntryRule = oMission.entry_rule;
            srvSite.memberSchemaList(oMission).then(function(aMemberSchemas) {
                $scope.memberSchemas = aMemberSchemas;
                $scope.mschemasById = {};
                $scope.memberSchemas.forEach(function(mschema) {
                    $scope.mschemasById[mschema.id] = mschema;
                });
            });
        });
    }]);
    ngApp.provider.controller('ctrlRemind', ['$scope', 'http2', '$interval', '$uibModal', 'srvSite', function($scope, http2, $interval, $uibModal, srvSite) {
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            $scope.timerTaskList('remind').then(function(tasks) {
                $scope.tasks = tasks;
            });
        });
    }]);
    ngApp.provider.controller('ctrlReport', ['$scope', 'http2', '$interval', '$uibModal', 'srvSite', function($scope, http2, $interval, $uibModal, srvSite) {
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
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            listReceivers(oMission);
            /* 定时推送 */
            $scope.timerTaskList('report').then(function(tasks) {
                $scope.tasks = tasks;
            });
        });
    }]);
})