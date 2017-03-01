define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPublish', ['$scope', 'http2', 'mediagallery', 'srvSigninApp', function($scope, http2, mediagallery, srvSigninApp) {
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.app.pic = url + '?_=' + (new Date() * 1);
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.app.siteid, options);
        };
        $scope.removePic = function() {
            $scope.app.pic = '';
            $scope.update('pic');
        };
        srvSigninApp.get().then(function(app) {
            var url = '/rest/pl/fe/matter/signin/opData';
            url += '?site=' + app.siteid;
            url += '&app=' + app.id;
            http2.get(url, function(rsp) {
                $scope.summary = rsp.data;
            });
        });
    }]);
    ngApp.provider.controller('ctrlPreview', ['$scope', 'srvSigninApp', function($scope, srvSigninApp) {
        function refresh() {
            $scope.previewURL = previewURL + '&openAt=' + params.openAt + '&page=' + params.page.name + '&_=' + (new Date() * 1);
        }

        var previewURL, params;
        $scope.params = params = {
            openAt: 'ontime'
        };
        $scope.showPage = function(page) {
            params.page = page;
        };
        srvSigninApp.get().then(function(app) {
            if (app.pages && app.pages.length) {
                $scope.gotoPage = function(page) {
                    var url = "/rest/pl/fe/matter/signin/page";
                    url += "?site=" + app.siteid;
                    url += "&id=" + app.id;
                    url += "&page=" + page.name;
                    location.href = url;
                };
                previewURL = '/rest/site/fe/matter/signin/preview?site=' + app.siteid + '&app=' + app.id;
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
    /**
     * 访问控制规则
     */
    ngApp.provider.controller('ctrlAccessRule', ['$scope', 'srvSigninApp', function($scope, srvSigninApp) {
        $scope.rule = {};
        $scope.update = function() {
            srvSigninApp.update('entry_rule');
        };
        $scope.reset = function() {
            srvSigninApp.resetEntryRule();
        };
        $scope.changeUserScope = function() {
            srvSigninApp.changeUserScope($scope.rule.scope, $scope.sns, $scope.memberSchemas, $scope.jumpPages.defaultInput);
        };
        srvSigninApp.get().then(function(app) {
            $scope.jumpPages = srvSigninApp.jumpPages();
            $scope.rule.scope = app.entry_rule.scope || 'none';
        }, true);
    }]);
    /**
     * 签到轮次
     */
    ngApp.provider.controller('ctrlRound', ['$scope', 'srvSigninApp', 'srvSigninRound', function($scope, srvSigninApp, srvSigninRound) {
        $scope.batch = function() {
            srvSigninRound.batch($scope.app).then(function(rounds) {
                $scope.rounds = rounds;
            });
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            data.obj[data.state] = data.value;
            $scope.update(data.obj, data.state);
        });
        $scope.add = function() {
            srvSigninRound.add($scope.rounds);
        };
        $scope.update = function(round, prop) {
            srvSigninRound.update(round, prop);
        };
        $scope.remove = function(round) {
            srvSigninRound.remove(round, $scope.rounds);
        };
        $scope.qrcode = function(round) {
            srvSigninRound.qrcode($scope.app, $scope.sns, round, $scope.app.entryUrl);
        };
        srvSigninApp.get().then(function(app) {
            $scope.rounds = app.rounds;
        });
    }]);
    ngApp.provider.controller('ctrlOpUrl', ['$scope', 'srvQuickEntry', 'srvSigninApp', function($scope, srvQuickEntry, srvSigninApp) {
        var targetUrl, opEntry;
        $scope.opEntry = opEntry = {};
        srvSigninApp.get().then(function(app) {
            targetUrl = 'http://' + location.host + '/rest/site/op/matter/signin?site=' + app.siteid + '&app=' + app.id;
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
                srvSigninApp.update('op_short_url_code');
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
                srvSigninApp.update('op_short_url_code');
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
});
