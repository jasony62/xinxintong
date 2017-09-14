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
	}]);
    ngApp.provider.controller('ctrlReceiver', ['$scope', 'http2', '$interval', '$uibModal', 'srvSite', function($scope, http2, $interval, $uibModal, srvSite) {
        var baseURL;
        $scope.$watch('mission', function(mission) {
            if (!mission) return;
            if(mission.user_app_type === 'enroll'){
                baseURL = '/rest/pl/fe/matter/enroll/receiver/';
            }else if(mission.user_app_type === 'signin'){
                baseURL = '/rest/pl/fe/matter/signin/receiver/';
            }
            listReceivers(mission);
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
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
        });

        function listReceivers(app) {
            http2.get(baseURL + 'list?site=' + app.siteid + '&app=' + app.user_app_id, function(rsp) {
                var map = { wx: '微信', yx: '易信', qy: '企业号' };
                rsp.data.forEach(function(receiver) {
                    if (receiver.sns_user) {
                        receiver.snsUser = JSON.parse(receiver.sns_user);
                        map[receiver.snsUser.src] && (receiver.snsUser.snsName = map[receiver.snsUser.src]);
                    }
                });
                $scope.receivers = rsp.data;
            });
        }

        $scope.qrcodeShown = false;
        $scope.qrcode = function(snsName) {
            if ($scope.qrcodeShown === false) {
                var url = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/createOneOff';
                url += '?site=' + $scope.mission.siteid;
                if ($scope.mission.user_app_id == '') {
                    alert('没有指定用户名单应用');
                    return;
                }else if($scope.mission.user_app_type === 'enroll'){
                    url += '&matter_type=enrollreceiver';
                }else if($scope.mission.user_app_type === 'signin'){
                    url += '&matter_type=signinreceiver';
                }else{
                    alert('暂时不支持除此应用类型');
                    return;
                }
                url += '&matter_id=' + $scope.mission.user_app_id;
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
                                            http2.get('/rest/pl/fe/matter/enroll/receiver/afterJoin?site=' + $scope.mission.siteid + '&app=' + $scope.mission.user_app_id + '&timestamp=' + qrcode.create_at, function(rsp) {
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
                if ($scope.mission.user_app_id == '') {
                    alert('没有指定用户名单应用');
                    return;
                }
                $("#yxQrcode").trigger('hide');
                $scope.qrcodeShown = false;
            }
        };
        $scope.remove = function(receiver) {
            http2.get(baseURL + 'remove?site=' + $scope.mission.siteid + '&app=' + $scope.mission.user_app_id + '&receiver=' + receiver.id, function(rsp) {
                $scope.receivers.splice($scope.receivers.indexOf(receiver), 1);
            });
        };
        $scope.chooseQy = function() {
            if ($scope.mission.user_app_id == '') {
                alert('没有指定用户名单应用');
                return;
            }
            $uibModal.open({
                templateUrl: 'chooseUser.html',
                controller: 'ctrlChooseUser',
            }).result.then(function(data) {
                var mission = $scope.mission,
                    url;
                url = baseURL + 'add';
                url += '?site=' + mission.siteid;
                url += '&app=' + mission.user_app_id;
                http2.post(url, data, function(rsp) {
                    listReceivers(mission);
                });
            })
        };
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
            if($scope.mission.user_app_id == ''){
                oOneTask.state = 'N';
                alert('没有指定用户名单应用');
                return;
            }
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
    ngApp.provider.controller('ctrlChooseUser', ['$scope', '$uibModalInstance', 'http2', 'srvMission', function($scope, $mi, http2, srvMission) {
        $scope.page = {
            at: 1,
            size: 15,
            total: 0,
            param: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.search = function(name) {
            var url;
            if($scope.mission.user_app_type === 'enroll'){
                url = '/rest/pl/fe/matter/enroll/receiver/qymem';
            }else if($scope.mission.user_app_type === 'signin'){
                url = '/rest/pl/fe/matter/signin/receiver/qymem';
            }
            url += '?site=' + $scope.mission.siteid;
            url += '&' + $scope.page.param();
            http2.post(url, { keyword: name }, function(rsp) {
                $scope.users = rsp.data.data;
                $scope.page.total = rsp.data.total;
            });
        }
        $scope.doSearch = function(page, name) {
            var url;
            page && ($scope.page.at = page);
            if($scope.mission.user_app_type === 'enroll'){
                url = '/rest/pl/fe/matter/enroll/receiver/qymem';
            }else if($scope.mission.user_app_type === 'signin'){
                url = '/rest/pl/fe/matter/signin/receiver/qymem';
            }
            url += '?site=' + $scope.mission.siteid;
            url += '&' + $scope.page.param();
            if (name) {
                http2.post(url, { keyword: name }, function(rsp) {
                    $scope.users = rsp.data.data;
                    $scope.page.total = rsp.data.total;
                })
            } else {
                http2.get(url, function(rsp) {
                    $scope.users = rsp.data.data;
                    $scope.page.total = rsp.data.total;
                });
            }
        }
        $scope.selected = [];
        var updateSelected = function(action, option) {
            if (action == 'add') {
                $scope.selected.push(option);

            }
            if (action == 'remove') {
                angular.forEach($scope.selected, function(item, index) {
                    if (item.uid == option.uid) {
                        $scope.selected.splice(index, 1);
                    }
                })
            }
        }
        $scope.updateSelection = function($event, data) {
            var checkbox = $event.target;
            var action = (checkbox.checked ? 'add' : 'remove');
            var option = {
                nickname: data.nickname,
                uid: data.userid
            };
            updateSelected(action, option);
        }
        $scope.ok = function() {
            $mi.close($scope.selected);
        };
        $scope.cancel = function() {
            $mi.dismiss();
        };
        srvMission.get().then(function(mission) {
            $scope.mission = mission;
            $scope.doSearch(1);
        });
    }]);
})