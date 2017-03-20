define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPublish', ['$scope', 'mediagallery', '$timeout', 'srvEnrollApp', function($scope, mediagallery, $timeout, srvEnrollApp) {
        $timeout(function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        });
        srvEnrollApp.get().then(function(app) {
            var entry;
            entry = {
                url: app.entryUrl,
                qrcode: '/rest/site/fe/matter/enroll/qrcode?site=' + app.siteid + '&url=' + encodeURIComponent(app.entryUrl),
            };
            $scope.entry = entry;
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
        srvEnrollApp.summary().then(function(data) {
            $scope.summary = data;
        });
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope.app[data.state] = data.value;
            srvEnrollApp.update(data.state);
        });
    }]);
    ngApp.provider.controller('ctrlOpUrl', ['$scope', 'srvQuickEntry', 'srvEnrollApp', function($scope, srvQuickEntry, srvEnrollApp) {
        var targetUrl, opEntry;
        $scope.opEntry = opEntry = {};
        srvEnrollApp.get().then(function(app) {
            targetUrl = 'http://' + location.host + '/rest/site/op/matter/enroll?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            srvQuickEntry.get(targetUrl).then(function(entry) {
                if (entry) {
                    opEntry.url = 'http://' + location.host + '/q/' + entry.code;
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
                opEntry.url = 'http://' + location.host + '/q/' + task.code;
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
    ngApp.provider.controller('ctrlReportUrl', ['$scope', 'srvQuickEntry', 'srvEnrollApp', function($scope, srvQuickEntry, srvEnrollApp) {
        var targetUrl;
        $scope.reportEntry = {};
        srvEnrollApp.get().then(function(app) {
            targetUrl = 'http://' + location.host + '/rest/site/op/matter/enroll/report?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            srvQuickEntry.get(targetUrl).then(function(entry) {
                if (entry) {
                    $scope.reportEntry.url = 'http://' + location.host + '/q/' + entry.code;
                    $scope.reportEntry.password = entry.password;
                }
            });
        });
        $scope.makeUrl = function() {
            srvQuickEntry.add(targetUrl).then(function(task) {
                $scope.app.rp_short_url_code = task.code;
                srvEnrollApp.update('rp_short_url_code');
                $scope.reportEntry.url = 'http://' + location.host + '/q/' + task.code;
            });
        };
        $scope.closeUrl = function() {
            srvQuickEntry.remove(targetUrl).then(function(task) {
                $scope.reportEntry.url = '';
                $scope.app.rp_short_url_code = '';
                srvEnrollApp.update('rp_short_url_code');
            });
        };
        $scope.configUrl = function(event, prop) {
            event.preventDefault();
            srvQuickEntry.config(targetUrl, {
                password: $scope.reportEntry.password
            });
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
    /**
     * 访问控制规则
     */
    ngApp.provider.controller('ctrlAccessRule', ['$scope', 'http2', 'srvEnrollApp', function($scope, http2, srvEnrollApp) {
        $scope.rule = {};
        $scope.isInputPage = function(pageName) {
            if (!$scope.app) {
                return false;
            }
            for (var i in $scope.app.pages) {
                if ($scope.app.pages[i].name === pageName && $scope.app.pages[i].type === 'I') {
                    return true;
                }
            }
            return false;
        };
        $scope.reset = function() {
            srvEnrollApp.resetEntryRule();
        };
        $scope.changeUserScope = function() {
            srvEnrollApp.changeUserScope($scope.rule.scope, $scope.sns, $scope.memberSchemas, $scope.jumpPages.defaultInput);
        };
        srvEnrollApp.get().then(function(app) {
            $scope.jumpPages = srvEnrollApp.jumpPages();
            $scope.rule.scope = app.entry_rule.scope || 'none';
        }, true);
    }]);
    ngApp.provider.controller('ctrlPreview', ['$scope', 'srvEnrollApp', function($scope, srvEnrollApp) {
        function refresh() {
            $scope.previewURL = previewURL + '&openAt=' + params.openAt + '&page=' + params.page.name + '&_=' + (new Date() * 1);
        }
        var previewURL, params;
        $scope.params = params = {
            openAt: 'ontime',
        };
        $scope.showPage = function(page) {
            params.page = page;
        };
        srvEnrollApp.get().then(function(app) {
            if (app.pages && app.pages.length) {
                $scope.gotoPage = function(page) {
                    var url = "/rest/pl/fe/matter/enroll/page";
                    url += "?site=" + app.siteid;
                    url += "&id=" + app.id;
                    url += "&page=" + page.name;
                    location.href = url;
                };
                previewURL = '/rest/site/fe/matter/enroll/preview?site=' + app.siteid + '&app=' + app.id + '&start=Y';
                params.page = app.pages[0];
                $scope.$watch('params', function() {
                    refresh();
                }, true);
                $scope.$watch('app.use_site_header', function(nv, ov) {
                    nv !== ov && refresh();
                });
                $scope.$watch('app.use_site_footer', function(nv, ov) {
                    nv !== ov && refresh();
                });
                $scope.$watch('app.use_mission_header', function(nv, ov) {
                    nv !== ov && refresh();
                });
                $scope.$watch('app.use_mission_header', function(nv, ov) {
                    nv !== ov && refresh();
                });
            }
        });
    }]);
    ngApp.provider.controller('ctrlRound', ['$scope', 'srvEnrollRound', function($scope, srvEnrollRound) {
        $scope.roundState = srvEnrollRound.RoundState;
        srvEnrollRound.list().then(function(rounds) {
            $scope.rounds = rounds;
        });
        $scope.add = function() {
            srvEnrollRound.add();
        };
        $scope.edit = function(round) {
            srvEnrollRound.edit(round);
        };
    }]);
    ngApp.provider.controller('ctrlCron', ['$scope', 'http2', function($scope, http2) {
        $scope.mdays = [];
        while ($scope.mdays.length < 28) {
            $scope.mdays.push('' + ($scope.mdays.length + 1));
        }
        $scope.$watch('app.roundCron', function(cron) {
            if (cron) {
                $scope.cron = cron;
                if (!cron.period) {
                    cron.period = 'D';
                }
            }
        });
        $scope.$watch('app.roundCron.period', function(newPeriod, oldPeriod) {
            if (oldPeriod && oldPeriod !== newPeriod) {
                if (oldPeriod === 'W') {
                    $scope.cron.wday = '';
                } else if (oldPeriod === 'M') {
                    $scope.cron.mday = '';
                }
            }
            $scope.update('roundCron');
        });
    }]);
});
