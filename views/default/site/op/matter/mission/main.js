define(['angular'], function(angular) {
    'use strict';
    var _siteId, _missionId, _accessToken;
    _siteId = location.search.match('site=([^&]*)')[1];
    _missionId = location.search.match('mission=([^&]*)')[1];
    _accessToken = location.search.match('accessToken=([^&]*)')[1];

    var ngApp = angular.module('app', ['ui.bootstrap', 'service.mission']);
    ngApp.config(['srvSiteProvider', 'srvOpMissionProvider', function(srvSiteProvider, srvOpMissionProvider) {
        srvSiteProvider.config(_siteId);
        srvOpMissionProvider.config(_siteId, _missionId, _accessToken);
    }]);
    ngApp.controller('ctrlMission', ['$scope', '$timeout', 'http2', 'srvSite', 'srvOpMission', 'srvRecordConverter', function($scope, $timeout, http2, srvSite, srvOpMission, srvRecordConverter) {
        function configUserApps() {
            var includeApps = [];
            $scope.report.orderedApps.forEach(function(matter) {
                includeApps.push({ id: matter.id, type: matter.type });
            });
            $scope.makeReport(includeApps);
        }
        var _enrollAppSchemas;
        $scope.enrollAppSchemas = _enrollAppSchemas = {};
        $scope.moveUp = function(matter, index) {
            var apps;
            if (index === 0) return;
            apps = $scope.report.orderedApps;
            apps.splice(index, 1);
            apps.splice(index - 1, 0, matter);
            apps[index].seq++;
            configUserApps();
        };
        $scope.moveDown = function(matter, index) {
            var apps;
            apps = $scope.report.orderedApps;
            if (index === apps.length - 1) return;
            apps.splice(index, 1);
            apps.splice(index + 1, 0, matter);
            apps[index].seq--;
            configUserApps();
        };
        $scope.removeUserApp = function(matter, index) {
            var apps;
            apps = $scope.report.orderedApps;
            apps.splice(index, 1);
            configUserApps();
        };
        $scope.chooseApps = function() {
            srvOpMission.chooseApps($scope.mission).then(function(apps) {
                $scope.makeReport(apps);
            });
        };
        $scope.makeReport = function(apps) {
            var oMission, url, params;
            oMission = $scope.mission;
            url = '/rest/site/op/matter/mission/report/userAndApp?site=' + oMission.siteid + '&mission=' + oMission.id + '&accessToken=' + _accessToken;
            params = {
                userSource: { id: oMission.user_app_id, type: oMission.user_app_type }
            };
            if (apps && apps.length) {
                params.apps = [];
                apps.forEach(function(oApp) {
                    params.apps.push({ id: oApp.id, type: oApp.type });
                });
            }
            http2.post(url, params, function(rsp) {
                $scope.report = rsp.data;
                rsp.data.orderedApps.forEach(function(oMatter) {
                    if (oMatter.type === 'enroll') {
                        var schemasById;
                        if (oMatter.dataSchemas) {
                            schemasById = {};
                            oMatter.dataSchemas.forEach(function(schema) {
                                schemasById[schema.id] = schema;
                            });
                            _enrollAppSchemas[oMatter.id] = schemasById;
                        }
                    }
                });
            });
        };
        $scope.chooseUser = function(oUser, oApp) {
            $scope.recordsByApp = {};
            $scope.activeUser = oUser;
            if (oUser.userid) {
                srvOpMission.recordByUser(oUser).then(function(records) {
                    var recordsByApp = {};
                    if (records) {
                        if (records.enroll && records.enroll.length) {
                            recordsByApp.enroll = {};
                            records.enroll.forEach(function(record) {
                                srvRecordConverter.forTable(record, $scope.enrollAppSchemas[record.aid]);
                                recordsByApp.enroll[record.aid] === undefined && (recordsByApp.enroll[record.aid] = []);
                                recordsByApp.enroll[record.aid].push(record);
                            });
                        }
                        if (records.signin && records.signin.length) {
                            recordsByApp.signin = {};
                            records.signin.forEach(function(record) {
                                recordsByApp.signin[record.aid] === undefined && (recordsByApp.signin[record.aid] = []);
                                recordsByApp.signin[record.aid].push(record);
                            });
                        }
                        if (records.group && records.group.length) {
                            recordsByApp.group = {};
                            records.group.forEach(function(record) {
                                recordsByApp.group[record.aid] === undefined && (recordsByApp.group[record.aid] = []);
                                recordsByApp.group[record.aid].push(record);
                            });
                        }
                    }
                    $scope.recordsByApp = recordsByApp;
                    $timeout(function() {
                        var eleList, eleApp, index = $scope.report.orderedApps.indexOf(oApp);
                        eleList = document.querySelector('#userApps');
                        eleApp = eleList.children[index];
                        eleList.parentNode.scrollTop = eleApp.offsetTop;
                        eleApp.classList.add('blink');
                        $timeout(function() {
                            eleApp.classList.remove('blink');
                        }, 1000);
                    });
                });
            }
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvOpMission.get().then(function(result) {
            $scope.mission = result.mission;
            $scope.makeReport();
            window.loading.finish();
        });
    }]);
    /*bootstrap*/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
