define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPublish', ['$scope', '$q', 'http2', 'mediagallery', function($scope, $q, http2, mediagallery) {
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
        $scope.summaryOfRecords = function() {
            var deferred = $q.defer(),
                url = '/rest/pl/fe/matter/signin/record/summary';
            url += '?site=' + $scope.app.siteid;
            url += '&app=' + $scope.app.id;
            http2.get(url, function(rsp) {
                deferred.resolve(rsp.data);
            });
            return deferred.promise;
        };
        $scope.summaryOfRecords().then(function(data) {
            $scope.summary = data;
        });
    }]);
    ngApp.provider.controller('ctrlPreview', ['$scope', function($scope) {
        var previewURL = '/rest/site/fe/matter/signin/preview?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&start=Y',
            params = {
                openAt: 'ontime'
            };
        $scope.showPage = function(page) {
            params.page = page;
        };
        $scope.gotoPage = function(page) {
            var url = "/rest/pl/fe/matter/signin/page";
            url += "?site=" + $scope.app.siteid;
            url += "&id=" + $scope.app.id;
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
        $scope.$watch('app', function(app) {
            if (!app) return;
            $scope.jumpPages = srvApp.jumpPages();
            $scope.rule.scope = app.entry_rule.scope || 'none';
        }, true);
    }]);
    /**
     * 签到轮次
     */
    ngApp.provider.controller('ctrlRound', ['$scope', 'srvRound', function($scope, srvRound) {
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
        $scope.$watch('app', function(app) {
            if (app) {
                $scope.rounds = app.rounds;
            }
        });
    }]);
    ngApp.provider.controller('ctrlOpUrl', ['$scope', 'srvQuickEntry', function($scope, srvQuickEntry) {
        var targetUrl;
        $scope.opEntry = {};
        $scope.$watch('app', function(app) {
            if (!app) return;
            targetUrl = 'http://' + location.host + '/rest/site/op/matter/signin?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
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
