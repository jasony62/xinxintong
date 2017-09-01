define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEntry', ['$scope', 'mediagallery', '$timeout', 'srvEnrollApp', function($scope, mediagallery, $timeout, srvEnrollApp) {
        $timeout(function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        });
        srvEnrollApp.get().then(function(app) {
            var oEntry;
            oEntry = {
                url: app.entryUrl,
                qrcode: '/rest/site/fe/matter/enroll/qrcode?site=' + app.siteid + '&url=' + encodeURIComponent(app.entryUrl),
                pages: []
            };
            $scope.entry = oEntry;
        });
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.app.pic = url + '?_=' + (new Date() * 1);
                    srvEnrollApp.update('pic');
                }
            };
            mediagallery.open($scope.app.siteid, options);
        };
        $scope.removePic = function() {
            $scope.app.pic = '';
            srvEnrollApp.update('pic');
        };
        $scope.downloadQrcode = function(url) {
            $('<a href="' + url + '" download="登记二维码.png"></a>')[0].click();
        };
    }]);
    ngApp.provider.controller('ctrlOpUrl', ['$scope', 'srvQuickEntry', 'srvEnrollApp', function($scope, srvQuickEntry, srvEnrollApp) {
        var targetUrl, host, opEntry;
        $scope.opEntry = opEntry = {};
        srvEnrollApp.get().then(function(app) {
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
                srvEnrollApp.update('op_short_url_code');
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
                srvEnrollApp.update('op_short_url_code');
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
    ngApp.provider.controller('ctrlRpUrl', ['$scope', 'srvQuickEntry', 'srvEnrollApp', function($scope, srvQuickEntry, srvEnrollApp) {
        var targetUrl, host, rpEntry;
        $scope.rpEntry = rpEntry = {};
        srvEnrollApp.get().then(function(app) {
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
                srvEnrollApp.update('rp_short_url_code');
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
                srvEnrollApp.update('rp_short_url_code');
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
    /**
     * 微信二维码
     */
    ngApp.provider.controller('ctrlWxQrcode', ['$scope', 'http2', function($scope, http2) {
        $scope.create = function() {
            var url;
            url = '/rest/pl/fe/site/sns/wx/qrcode/create?site=' + $scope.app.siteid;
            url += '&matter_type=enroll&matter_id=' + $scope.app.id;
            url += '&expire=864000';
            http2.get(url, function(rsp) {
                $scope.qrcode = rsp.data;
            });
        };
        $scope.download = function() {
            $('<a href="' + $scope.qrcode.pic + '" download="微信登记二维码.jpeg"></a>')[0].click();
        };
        http2.get('/rest/pl/fe/matter/enroll/wxQrcode?site=' + $scope.app.siteid + '&app=' + $scope.app.id, function(rsp) {
            var qrcodes = rsp.data;
            $scope.qrcode = qrcodes.length ? qrcodes[0] : false;
        });
    }]);
    ngApp.provider.controller('ctrlReceiver', ['$scope', 'http2', '$interval', '$uibModal', 'srvEnrollApp', function($scope, http2, $interval, $uibModal, srvEnrollApp) {
        function listReceivers(app) {
            http2.get(baseURL + 'list?site=' + app.siteid + '&app=' + app.id, function(rsp) {
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

        var baseURL = '/rest/pl/fe/matter/enroll/receiver/';
        $scope.qrcodeShown = false;
        $scope.qrcode = function(snsName) {
            if ($scope.qrcodeShown === false) {
                var url = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/createOneOff';
                url += '?site=' + $scope.app.siteid;
                url += '&matter_type=enrollreceiver';
                url += '&matter_id=' + $scope.app.id;
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
                                            http2.get('/rest/pl/fe/matter/enroll/receiver/afterJoin?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&timestamp=' + qrcode.create_at, function(rsp) {
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
            http2.get(baseURL + 'remove?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&receiver=' + receiver.id, function(rsp) {
                $scope.receivers.splice($scope.receivers.indexOf(receiver), 1);
            });
        };
        $scope.chooseQy = function() {
            $uibModal.open({
                templateUrl: 'chooseUser.html',
                controller: 'ctrlChooseUser',
            }).result.then(function(data) {
                var app = $scope.app,
                    url;
                url = '/rest/pl/fe/matter/enroll/receiver/add';
                url += '?site=' + app.siteid;
                url += '&app=' + app.id;
                http2.post(url, data, function(rsp) {
                    listReceivers(app);
                });
            })
        };
        var oTimerTask;
        $scope.timerTask = oTimerTask = {
            report: {
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
                    matter: { id: $scope.app.id, type: 'enroll' },
                    task: { model: 'report' }
                }
                http2.post('/rest/pl/fe/matter/timer/create?site=' + $scope.app.siteid, oConfig, function(rsp) {
                    oOneTask.state = 'Y';
                    oOneTask.taskId = rsp.data.id;
                    oOneTask.task = {};
                    ['pattern', 'min', 'hour', 'wday', 'mday', 'mon', 'left_count', 'enabled'].forEach(function(prop) {
                        oOneTask.task[prop] = '' + rsp.data[prop];
                    });
                });
            } else {
                http2.get('/rest/pl/fe/matter/timer/remove?site=' + $scope.app.siteid + '&id=' + oOneTask.taskId, function(rsp) {
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
                http2.post('/rest/pl/fe/matter/timer/update?site=' + $scope.app.siteid + '&id=' + oOneTask.taskId, oOneTask.task, function(rsp) {
                    ['min', 'hour', 'wday', 'mday', 'mon', 'left_count'].forEach(function(prop) {
                        oOneTask.task[prop] = '' + rsp.data[prop];
                    });
                    oOneTask.modified = false;
                });
            }
        };
        srvEnrollApp.get().then(function(app) {
            listReceivers(app);
            http2.get('/rest/pl/fe/matter/timer/byMatter?site=' + app.siteid + '&type=enroll&id=' + app.id, function(rsp) {
                rsp.data.forEach(function(oTask) {
                    oTimerTask[oTask.task_model].state = 'Y';
                    oTimerTask[oTask.task_model].taskId = oTask.id;
                    oTimerTask[oTask.task_model].task = {};
                    ['pattern', 'min', 'hour', 'wday', 'mday', 'mon', 'left_count', 'enabled'].forEach(function(prop) {
                        oTimerTask[oTask.task_model].task[prop] = oTask[prop];
                    });
                    $scope.$watch('timerTask.report', function(oUpdTask, oOldTask) {
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
    ngApp.provider.controller('ctrlChooseUser', ['$scope', '$uibModalInstance', 'http2', 'srvEnrollApp', function($scope, $mi, http2, srvEnrollApp) {
        $scope.page = {
            at: 1,
            size: 15,
            total: 0,
            param: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.search = function(name) {
            var url = '/rest/pl/fe/matter/enroll/receiver/qymem';
            url += '?site=' + $scope.app.siteid;
            url += '&' + $scope.page.param();
            http2.post(url, { keyword: name }, function(rsp) {
                $scope.users = rsp.data.data;
                $scope.page.total = rsp.data.total;
            });
        }
        $scope.doSearch = function(page, name) {
            var url;
            page && ($scope.page.at = page);
            url = '/rest/pl/fe/matter/enroll/receiver/qymem';
            url += '?site=' + $scope.app.siteid;
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
        srvEnrollApp.get().then(function(app) {
            $scope.app = app;
            $scope.doSearch(1);
        });
    }]);
});