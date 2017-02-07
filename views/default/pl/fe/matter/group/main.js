define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', '$q', 'mattersgallery', 'noticebox', 'srvApp', function($scope, http2, $q, mattersgallery, noticebox, srvApp) {
        $scope.update = function(names) {
            srvApp.update(names).then(function(rsp) {
                noticebox.success('完成保存');
            });
        };
        $scope.choosePhase = function() {
            var phaseId = $scope.app.mission_phase_id,
                i, phase, newPhase;
            for (i = $scope.app.mission.phases.length - 1; i >= 0; i--) {
                phase = $scope.app.mission.phases[i];
                $scope.app.title = $scope.app.title.replace('-' + phase.title, '');
                if (phase.phase_id === phaseId) {
                    newPhase = phase;
                }
            }
            if (newPhase) {
                $scope.app.title += '-' + newPhase.title;
            }
            $scope.update(['mission_phase_id', 'title']);
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/group/remove?site=' + $scope.app.siteid + '&app=' + $scope.app.id, function(rsp) {
                    if ($scope.app.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.app.siteid + "&id=" + $scope.app.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.app.siteid;
                    }
                });
            }
        };
        $scope.assignMission = function() {
            mattersgallery.open($scope.app.siteid, function(matters, type) {
                var app;
                if (matters.length === 1) {
                    app = {
                        id: $scope.app.id,
                        type: 'group'
                    };
                    http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + $scope.app.siteid + '&id=' + matters[0].id, app, function(rsp) {
                        $scope.app.mission = rsp.data;
                        $scope.app.mission_id = rsp.data.id;
                        srvApp.update('mission_id');
                    });
                }
            }, {
                matterTypes: [{
                    value: 'mission',
                    title: '项目',
                    url: '/rest/pl/fe/matter'
                }],
                hasParent: false,
                singleMatter: true
            });
        };
        $scope.quitMission = function() {
            var oApp = $scope.app,
                matter = {
                    id: oApp.id,
                    type: 'group',
                    title: oApp.title
                };
            http2.post('/rest/pl/fe/matter/mission/matter/remove?site=' + oApp.siteid + '&id=' + oApp.mission_id, matter, function(rsp) {
                delete oApp.mission;
                oApp.mission_id = null;
                srvApp.update(['mission_id']);
            });
        };
    }]);
    ngApp.provider.controller('ctrlOpUrl', ['$scope', 'http2', 'srvQuickEntry', function($scope, http2, srvQuickEntry) {
        var targetUrl;
        $scope.opEntry = {};
        $scope.$watch('app', function(app) {
            if (!app) return;
            targetUrl = app.opUrl;
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
        $scope.gotoCode = function() {
            var app, url;
            app = $scope.app;
            if (app.page_code_name && app.page_code_name.length) {
                window.open('/rest/pl/fe/code?site=' + app.siteid + '&name=' + app.page_code_name, '_self');
            } else {
                url = '/rest/pl/fe/matter/group/page/create?site=' + app.siteid + '&app=' + app.id + '&scenario=' + app.scenario;
                http2.get(url, function(rsp) {
                    app.page_code_id = rsp.data.id;
                    app.page_code_name = rsp.data.name;
                    window.open('/rest/pl/fe/code?site=' + app.siteid + '&name=' + app.page_code_name, '_self');
                });
            }
        };
        $scope.resetCode = function() {
            var app, url;
            if (window.confirm('重置操作将丢失已做修改，确定？')) {
                app = $scope.app;
                url = '/rest/pl/fe/matter/group/page/reset?site=' + app.siteid + '&app=' + app.id + '&scenario=' + app.scenario;
                http2.get(url, function(rsp) {
                    window.open('/rest/pl/fe/code?site=' + app.siteid + '&name=' + app.page_code_name, '_self');
                });
            }
        };
    }]);
});
