define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlReport', ['$scope', '$uibModal', '$q', '$timeout', 'http2', 'srvSite', 'srvMission', 'tmsSchema', function($scope, $uibModal, $q, $timeout, http2, srvSite, srvMission, tmsSchema) {
        function configUserApps() {
            var includeApps = [];
            $scope.report.orderedApps.forEach(function(matter) {
                includeApps.push({ id: matter.id, type: matter.type });
            });
            $scope.makeReport(includeApps);
        }
        $scope.assignUserApp = function() {
            var mission = $scope.mission;
            $uibModal.open({
                templateUrl: 'assignUserApp.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.data = {
                        appId: '',
                        appType: 'group'
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                    $scope2.$watch('data.appType', function(appType) {
                        if (appType) {
                            if (appType === 'mschema') {
                                srvSite.memberSchemaList(mission, true).then(function(aMemberSchemas) {
                                    $scope2.apps = aMemberSchemas;
                                });
                            } else {
                                var url = '/rest/pl/fe/matter/' + appType + '/list?mission=' + mission.id;
                                http2.get(url).then(function(rsp) {
                                    $scope2.apps = rsp.data.apps;
                                });
                            }
                        }
                    });
                }],
                backdrop: 'static'
            }).result.then(function(data) {
                mission.user_app_id = data.appId;
                mission.user_app_type = data.appType;
                $scope.update(['user_app_id', 'user_app_type']).then(function(rsp) {
                    if (data.appType === 'mschema') {
                        var url = '/rest/pl/fe/matter/mission/get?id=' + mission.id;
                        http2.get(url).then(function(rsp) {
                            mission.userApp = rsp.data.userApp;
                            $scope.makeReport();
                        });
                    } else {
                        var key = data.appType == 'enroll' ? 'app' : 'id';
                        var url = '/rest/pl/fe/matter/' + data.appType + '/get?site=' + mission.siteid + '&' + key + '=' + data.appId;
                        http2.get(url).then(function(rsp) {
                            mission.userApp = rsp.data;
                            if (mission.userApp.data_schemas && angular.isString(mission.userApp.data_schemas)) {
                                mission.userApp.data_schemas = JSON.parse(mission.userApp.data_schemas);
                            }
                            $scope.makeReport();
                        });
                    }
                });
            });
        };
        $scope.cancelUserApp = function() {
            var mission = $scope.mission;
            mission.user_app_id = '';
            mission.user_app_type = '';
            $scope.update(['user_app_id', 'user_app_type']).then(function() {
                delete mission.userApp;
                http2.post('/rest/pl/fe/matter/mission/report/configUpdate?mission=' + mission.id, { apps: [] }).then(function(rsp) {
                    if (mission.reportConfig) {
                        mission.reportConfig.include_apps = [];
                    }
                });
            });
        };
        $scope.chooseContents = function() {
            srvMission.chooseContents($scope.mission, $scope.report).then(function(results) {
                $scope.makeReport(results);
            });
        };
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
        $scope.makeReport = function(results) {
            var oMission, mapOfUnionSchemaById = {},
                url, params;
            oMission = $scope.mission;
            url = '/rest/pl/fe/matter/mission/report/userAndApp?site=' + oMission.siteid + '&mission=' + oMission.id;
            params = {
                defaultConfig: { apps: [], show_schema: [] },
                userSource: { id: oMission.user_app_id, type: oMission.user_app_type }
            };
            if (results && results.app && results.app.length) {
                results.app.forEach(function(oApp) {
                    params.defaultConfig.apps.push({ id: oApp.id, type: oApp.type });
                });
            };
            if (results && results.mark && results.mark.length) {
                results.mark.forEach(function(schema) {
                    if (schema.id == '_round_id') {
                        params.defaultConfig.show_schema.push({ id: schema.id, title: schema.title, type: schema.type, ops: schema.ops });
                    } else {
                        params.defaultConfig.show_schema.push({ id: schema.id, title: schema.title, type: schema.type });
                    }
                });
            };
            http2.post(url, params).then(function(rsp) {
                if (rsp.data) {
                    $scope.dataSchemas = rsp.data.show_schema.length > 0 ? rsp.data.show_schema : oMission.userApp.dataSchemas;
                    $scope.dataSchemas.forEach(function(schema) {
                        mapOfUnionSchemaById[schema.id] = schema;
                    });
                    rsp.data.users.forEach(function(user) {
                        if (user.show_schema_data) {
                            user.show_schema_data.data = angular.copy(user.show_schema_data);
                        }
                        tmsSchema.forTable(user.show_schema_data, mapOfUnionSchemaById);
                    });
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
                }
            });
        };
        $scope.exportReport = function() {
            var oMission, url;
            oMission = $scope.mission;
            url = '/rest/pl/fe/matter/mission/report/export?site=' + oMission.siteid + '&mission=' + oMission.id;
            window.open(url);
        };
        var _enrollAppSchemas;
        $scope.enrollAppSchemas = _enrollAppSchemas = {};
        $scope.$watch('mission', function(mission) {
            if (!mission) return;
            if (mission.userApp) {
                $scope.makeReport();
            }
        });
        $scope.chooseUser = function(oUser, oApp) {
            $scope.recordsByApp = {};
            $scope.activeUser = oUser;
            if (oUser.userid) {
                srvMission.recordByUser(oUser).then(function(records) {
                    var recordsByApp = {};
                    if (records) {
                        if (records.enroll && records.enroll.length) {
                            recordsByApp.enroll = {};
                            records.enroll.forEach(function(record) {
                                tmsSchema.forTable(record, $scope.enrollAppSchemas[record.aid]);
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
                        if (eleApp = eleList.children[index]) {
                            eleList.parentNode.scrollTop = eleApp.offsetTop;
                            eleApp.classList.add('blink');
                            $timeout(function() {
                                eleApp.classList.remove('blink');
                            }, 1000);
                        }
                    });
                });
            }
        };
        $scope.gotoDetail = function(app) {
            var url;
            url = '/rest/pl/fe/matter/' + app.type;
            switch (app.type) {
                case 'enroll':
                case 'signin':
                    url += '/record';
                    break;
                case 'group':
                    url += '/player';
                    break;
                default:
                    break;
            }
            url += '?site=' + $scope.mission.siteid;
            url += '&id=' + app.id;
            location.href = url;
        };
    }]);
});