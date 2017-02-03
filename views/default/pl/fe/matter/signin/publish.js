define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPublish', ['$scope', 'http2', 'mediagallery', 'srvApp', function($scope, http2, mediagallery, srvApp) {
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
        srvApp.get().then(function(app) {
            var url = '/rest/pl/fe/matter/signin/record/summary';
            url += '?site=' + app.siteid;
            url += '&app=' + app.id;
            http2.get(url, function(rsp) {
                $scope.summary = rsp.data;
            });
        });
    }]);
    ngApp.provider.controller('ctrlPreview', ['$scope', 'srvApp', function($scope, srvApp) {
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
        srvApp.get().then(function(app) {
            if (app.pages && app.pages.length) {
                $scope.gotoPage = function(page) {
                    var url = "/rest/pl/fe/matter/signin/page";
                    url += "?site=" + app.siteid;
                    url += "&id=" + app.id;
                    url += "&page=" + page.name;
                    location.href = url;
                };
                previewURL = '/rest/site/fe/matter/signin/preview?site=' + app.siteid + '&app=' + app.id + '&start=Y';
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
    ngApp.provider.controller('ctrlAccessRule', ['$scope', 'srvApp', function($scope, srvApp) {
        $scope.rule = {};
        $scope.update = function() {
            srvApp.update('entry_rule');
        };
        $scope.reset = function() {
            srvApp.resetEntryRule();
        };
        $scope.changeUserScope = function() {
            srvApp.changeUserScope($scope.rule.scope, $scope.sns, $scope.memberSchemas, $scope.jumpPages.defaultInput);
        };
        srvApp.get().then(function(app) {
            $scope.jumpPages = srvApp.jumpPages();
            $scope.rule.scope = app.entry_rule.scope || 'none';
        }, true);
    }]);
    /**
     * 签到轮次
     */
    ngApp.provider.controller('ctrlRound', ['$scope', 'srvApp', 'srvRound', function($scope, srvApp, srvRound) {
        $scope.batch = function() {
            srvRound.batch($scope.app).then(function(rounds) {
                $scope.rounds = rounds;
            });
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            data.obj[data.state] = data.value;
            $scope.update(data.obj, data.state);
        });
        $scope.add = function() {
            srvRound.add($scope.rounds);
        };
        $scope.update = function(round, prop) {
            srvRound.update(round, prop);
        };
        $scope.remove = function(round) {
            srvRound.remove(round, $scope.rounds);
        };
        $scope.qrcode = function(round) {
            srvRound.qrcode($scope.app, $scope.sns, round, $scope.app.entryUrl);
        };
        srvApp.get().then(function(app) {
            $scope.rounds = app.rounds;
        });
    }]);
    ngApp.provider.controller('ctrlOpUrl', ['$scope', 'srvQuickEntry', 'srvApp', function($scope, srvQuickEntry, srvApp) {
        var targetUrl;
        $scope.opEntry = {};
        srvApp.get().then(function(app) {
            targetUrl = 'http://' + location.host + '/rest/site/op/matter/signin?site=' + app.siteid + '&app=' + app.id;
            srvQuickEntry.get(targetUrl).then(function(entry) {
                if (entry) {
                    $scope.opEntry.url = 'http://' + location.host + '/q/' + entry.code;
                    $scope.opEntry.password = entry.password;
                }
            });
        });
        $scope.makeOpUrl = function() {
            srvQuickEntry.add(targetUrl).then(function(task) {
                $scope.app.op_short_url_code = task.code;
                $scope.update('op_short_url_code');
                $scope.opEntry.url = 'http://' + location.host + '/q/' + task.code;
            });
        };
        $scope.closeOpUrl = function() {
            srvQuickEntry.remove(targetUrl).then(function(task) {
                $scope.opEntry.url = '';
                $scope.app.op_short_url_code = '';
                $scope.update('op_short_url_code');
            });
        };
        $scope.configOpUrl = function(event, prop) {
            event.preventDefault();
            srvQuickEntry.config(targetUrl, {
                password: $scope.opEntry.password
            });
        };
    }]);
});
