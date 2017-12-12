define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', '$q', 'srvSite', 'noticebox', 'srvGroupApp', '$uibModal', 'srvTag', function($scope, http2, $q, srvSite, noticebox, srvGroupApp, $uibModal, srvTag) {
        $scope.update = function(names) {
            srvGroupApp.update(names).then(function(rsp) {
                noticebox.success('完成保存');
            });
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope.app[data.state] = data.value;
            $scope.update(data.state);
        });
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
            srvSite.openGallery({
                matterTypes: [{
                    value: 'mission',
                    title: '项目',
                    url: '/rest/pl/fe/matter'
                }],
                hasParent: false,
                singleMatter: true
            }).then(function(result) {
                var app;
                if (result.matters.length === 1) {
                    app = {
                        id: $scope.app.id,
                        type: 'group'
                    };
                    http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + $scope.app.siteid + '&id=' + result.matters[0].id, app, function(rsp) {
                        $scope.app.mission = rsp.data;
                        $scope.app.mission_id = rsp.data.id;
                        srvGroupApp.update('mission_id');
                    });
                }
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
                srvGroupApp.update(['mission_id']);
            });
        };
        $scope.tagMatter = function(subType) {
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.app, oTags, subType);
        };
    }]);
    ngApp.provider.controller('ctrlOpUrl', ['$scope', 'http2', 'srvGroupApp', 'srvQuickEntry', function($scope, http2, srvGroupApp, srvQuickEntry) {
        var targetUrl, opEntry;
        $scope.opEntry = opEntry = {};
        $scope.$watch('app', function(app) {
            if (!app) return;
            targetUrl = app.opUrl;
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
                srvGroupApp.update('op_short_url_code');
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
                srvGroupApp.update('op_short_url_code');
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