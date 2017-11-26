define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEntry', ['$scope', 'http2', 'mediagallery', 'srvSigninApp', function($scope, http2, mediagallery, srvSigninApp) {
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            if (/app\./.test(data.state)) {
                $scope.app[data.state.split('.')[1]] = data.value;
                srvSigninApp.update(data.state);
            }
        });
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.app.pic = url + '?_=' + (new Date * 1);
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.app.siteid, options);
        };
        $scope.removePic = function() {
            $scope.app.pic = '';
            $scope.update('pic');
        };
        srvSigninApp.get().then(function(oApp) {
            var url = '/rest/pl/fe/matter/signin/opData';
            url += '?site=' + oApp.siteid;
            url += '&app=' + oApp.id;
            http2.get(url, function(rsp) {
                $scope.summary = rsp.data;
            });
        });
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
            var prop;
            if (/round\./.test(data.state)) {
                prop = data.state.split('.')[1];
                data.obj[prop] = data.value;
                $scope.update(data.obj, prop);
            }
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
        var targetUrl, host, opEntry;
        $scope.opEntry = opEntry = {};
        srvSigninApp.get().then(function(app) {
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
                srvSigninApp.update('op_short_url_code');
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