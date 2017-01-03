define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPublish', ['$scope', 'mediagallery', '$timeout', function($scope, mediagallery, $timeout) {
        $timeout(function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        });
        $scope.$watch('app', function(app) {
            if (!app) return;
            var entry;
            entry = {
                url: $scope.url,
                qrcode: '/rest/site/fe/matter/enroll/qrcode?site=' + $scope.siteId + '&url=' + encodeURIComponent($scope.url),
            };
            $scope.entry = entry;
        });
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.app.pic = url + '?_=' + (new Date()) * 1;
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.siteId, options);
        };
        $scope.removePic = function() {
            $scope.app.pic = '';
            $scope.update('pic');
        };
        $scope.downloadQrcode = function(url) {
            $('<a href="' + url + '" download="登记二维码.png"></a>')[0].click();
        };
        $scope.summaryOfRecords().then(function(data) {
            $scope.summary = data;
        });
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope.app[data.state] = data.value;
            $scope.update(data.state);
        });
    }]);
    ngApp.provider.controller('ctrlOpUrl', ['$scope', 'srvQuickEntry', function($scope, srvQuickEntry) {
        var targetUrl, persisted;
        $scope.opEntry = {};
        $scope.$watch('app', function(app) {
            if (!app) return;
            targetUrl = 'http://' + location.host + '/rest/site/op/matter/enroll?site=' + $scope.siteId + '&app=' + $scope.id;
            srvQuickEntry.get(targetUrl).then(function(entry) {
                if (entry) {
                    $scope.opEntry.url = 'http://' + location.host + '/q/' + entry.code;
                    $scope.opEntry.password = entry.password;
                    persisted = entry;
                }
            });
        });
        $scope.makeOpUrl = function() {
            srvQuickEntry.add(targetUrl).then(function(task) {
                $scope.opEntry.url = 'http://' + location.host + '/q/' + task.code;
            });
        };
        $scope.closeOpUrl = function() {
            srvQuickEntry.remove(targetUrl).then(function(task) {
                $scope.opEntry.url = '';
            });
        };
        $scope.configOpUrl = function(event, prop) {
            event.preventDefault();
            srvQuickEntry.config(targetUrl, {
                password: $scope.opEntry.password
            });
        };
    }]);
    ngApp.provider.controller('ctrlReportUrl', ['$scope', 'srvQuickEntry', function($scope, srvQuickEntry) {
        var targetUrl, persisted;
        $scope.reportEntry = {};
        $scope.$watch('app', function(app) {
            if (!app) return;
            targetUrl = 'http://' + location.host + '/rest/site/op/matter/enroll/report?site=' + $scope.siteId + '&app=' + $scope.id;
            srvQuickEntry.get(targetUrl).then(function(entry) {
                if (entry) {
                    $scope.reportEntry.url = 'http://' + location.host + '/q/' + entry.code;
                    $scope.reportEntry.password = entry.password;
                    persisted = entry;
                }
            });
        });
        $scope.makeUrl = function() {
            srvQuickEntry.add(targetUrl).then(function(task) {
                $scope.reportEntry.url = 'http://' + location.host + '/q/' + task.code;
            });
        };
        $scope.closeUrl = function() {
            srvQuickEntry.remove(targetUrl).then(function(task) {
                $scope.reportEntry.url = '';
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
            url = '/rest/pl/fe/site/sns/wx/qrcode/create?site=' + $scope.siteId;
            url += '&matter_type=enroll&matter_id=' + $scope.id;
            url += '&expire=864000';
            http2.get(url, function(rsp) {
                $scope.qrcode = rsp.data;
            });
        };
        $scope.download = function() {
            $('<a href="' + $scope.qrcode.pic + '" download="微信登记二维码.jpeg"></a>')[0].click();
        };
        http2.get('/rest/pl/fe/matter/enroll/wxQrcode?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
            var qrcodes = rsp.data;
            $scope.qrcode = qrcodes.length ? qrcodes[0] : false;
        });
    }]);
    /**
     * 访问控制规则
     */
    ngApp.provider.controller('ctrlAccessRule', ['$scope', 'http2', 'srvApp', function($scope, http2, srvApp) {
        $scope.rule = {};
        $scope.reset = function() {
            srvApp.resetEntryRule();
        };
        $scope.changeUserScope = function() {
            srvApp.changeUserScope($scope.rule.scope, $scope.sns, $scope.memberSchemas, $scope.jumpPages.defaultInput);
        };
        $scope.$watch('app', function(app) {
            if (!app) return;
            $scope.jumpPages = srvApp.jumpPages();
            $scope.rule.scope = app.entry_rule.scope || 'none';
        }, true);
    }]);
    ngApp.provider.controller('ctrlPreview', ['$scope', 'http2', function($scope, http2) {
        var previewURL = '/rest/site/fe/matter/enroll/preview?site=' + $scope.siteId + '&app=' + $scope.id + '&start=Y',
            params = {
                openAt: 'ontime'
            };
        $scope.showPage = function(page) {
            params.page = page;
        };
        $scope.gotoPage = function(page) {
            var url = "/rest/pl/fe/matter/enroll/page";
            url += "?site=" + $scope.siteId;
            url += "&id=" + $scope.id;
            url += "&page=" + page.name;
            location.href = url;
        };
        $scope.$watch('app.pages', function(pages) {
            if (pages) {
                params.page = pages[0];
                $scope.params = params;
                $scope.previewURL = previewURL + '&openAt=' + params.openAt + '&page=' + $scope.app.pages[0].name;
            }
        });
        $scope.$watch('app.use_site_header', function() {
            $scope.app && ($scope.previewURL = previewURL + '&openAt=' + params.openAt + '&page=' + params.page.name + '&_=' + (new Date() * 1));
        });
        $scope.$watch('app.use_site_footer', function() {
            $scope.app && ($scope.previewURL = previewURL + '&openAt=' + params.openAt + '&page=' + params.page.name + '&_=' + (new Date() * 1));
        });
        $scope.$watch('app.use_mission_header', function() {
            $scope.app && ($scope.previewURL = previewURL + '&openAt=' + params.openAt + '&page=' + params.page.name + '&_=' + (new Date() * 1));
        });
        $scope.$watch('app.use_mission_header', function() {
            $scope.app && ($scope.previewURL = previewURL + '&openAt=' + params.openAt + '&page=' + params.page.name + '&_=' + (new Date() * 1));
        });
        $scope.$watch('params', function(params) {
            if (params) {
                $scope.previewURL = previewURL + '&openAt=' + params.openAt + '&page=' + params.page.name;
            }
        }, true);
    }]);
    ngApp.provider.controller('ctrlRound', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.roundState = ['新建', '启用', '停止'];
        $scope.add = function() {
            $uibModal.open({
                templateUrl: 'roundEditor.html',
                backdrop: 'static',
                resolve: {
                    roundState: function() {
                        return $scope.roundState;
                    }
                },
                controller: ['$scope', '$uibModalInstance', 'roundState', function($scope, $mi, roundState) {
                    $scope.round = {
                        state: 0
                    };
                    $scope.roundState = roundState;
                    $scope.close = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        $mi.close($scope.round);
                    };
                    $scope.start = function() {
                        $scope.round.state = 1;
                        $mi.close($scope.round);
                    };
                }]
            }).result.then(function(newRound) {
                http2.post('/rest/pl/fe/matter/enroll/round/add?site=' + $scope.siteId + '&app=' + $scope.id, newRound, function(rsp) {
                    !$scope.app.rounds && ($scope.app.rounds = []);
                    if ($scope.app.rounds.length > 0 && rsp.data.state == 1) {
                        $scope.app.rounds[0].state = 2;
                    }
                    $scope.app.rounds.splice(0, 0, rsp.data);
                });
            });
        };
        $scope.open = function(round) {
            $uibModal.open({
                templateUrl: 'roundEditor.html',
                backdrop: 'static',
                resolve: {
                    roundState: function() {
                        return $scope.roundState;
                    }
                },
                controller: ['$scope', '$uibModalInstance', 'roundState', function($scope, $mi, roundState) {
                    $scope.round = angular.copy(round);
                    $scope.roundState = roundState;
                    $scope.close = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        $mi.close({
                            action: 'update',
                            data: $scope.round
                        });
                    };
                    $scope.remove = function() {
                        $mi.close({
                            action: 'remove'
                        });
                    };
                    $scope.stop = function() {
                        $scope.round.state = 2;
                        $mi.close({
                            action: 'update',
                            data: $scope.round
                        });
                    };
                    $scope.start = function() {
                        $scope.round.state = 1;
                        $mi.close({
                            action: 'update',
                            data: $scope.round
                        });
                    };
                }]
            }).result.then(function(rst) {
                var url;
                if (rst.action === 'update') {
                    url = '/rest/pl/fe/matter/enroll/round/update';
                    url += '?site=' + $scope.siteId;
                    url += '&app=' + $scope.id;
                    url += '&rid=' + round.rid;
                    http2.post(url, rst.data, function(rsp) {
                        if ($scope.app.rounds.length > 1 && rst.data.state == 1) {
                            $scope.app.rounds[1].state = 2;
                        }
                        angular.extend(round, rst.data);
                    });
                } else if (rst.action === 'remove') {
                    url = '/rest/pl/fe/matter/enroll/round/remove';
                    url += '?site=' + $scope.siteId;
                    url += '&app=' + $scope.id;
                    url += '&rid=' + round.rid;
                    http2.get(url, function(rsp) {
                        var i = $scope.app.rounds.indexOf(round);
                        $scope.app.rounds.splice(i, 1);
                    });
                }
            });
        };
    }]);
});
