define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlReport', ['$scope', '$uibModal', '$q', '$timeout', 'http2', 'srvSite', 'srvMission', 'srvRecordConverter', function($scope, $uibModal, $q, $timeout, http2, srvSite, srvMission, srvRecordConverter) {
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
                        appType: 'enroll'
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
                                http2.get(url, function(rsp) {
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
                        var url = '/rest/pl/fe/site/member/schema/get?site=' + mission.siteid + '&mschema=' + data.appId;
                        http2.get(url, function(rsp) {
                            mission.userApp = rsp.data;
                            $scope.makeReport();
                        });
                    } else {
                        var url = '/rest/pl/fe/matter/' + data.appType + '/get?site=' + mission.siteid + '&id=' + data.appId;
                        http2.get(url, function(rsp) {
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
                http2.post('/rest/pl/fe/matter/mission/report/configUpdate?mission=' + mission.id, { apps: [] }, function(rsp) {
                    mission.reportConfig.include_apps = [];
                });
            });
        };
        $scope.chooseApps = function() {
            $uibModal.open({
                templateUrl: 'chooseApps.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var oCriteria, oReportConfig, oIncludeApps = {};
                    $scope2.criteria = oCriteria = {
                        mission_phase_id: ''
                    };
                    if (oReportConfig = $scope.mission.reportConfig) {
                        if (oReportConfig.include_apps) {
                            oReportConfig.include_apps.forEach(function(oApp) {
                                oIncludeApps[oApp.type + oApp.id] = true;
                            });
                        }
                    }
                    // 选中的记录
                    $scope2.rows = {
                        allSelected: 'N',
                        selected: {},
                        reset: function() {
                            this.allSelected = 'N';
                            this.selected = {};
                        }
                    };
                    $scope2.$watch('rows.allSelected', function(checked) {
                        var index = 0;
                        if (checked === 'Y') {
                            while (index < $scope2.matters.length) {
                                $scope2.rows.selected[index++] = true;
                            }
                        } else if (checked === 'N') {
                            $scope2.rows.selected = {};
                        }
                    });
                    $scope2.doSearch = function() {
                        $scope2.rows.reset();
                        srvMission.matterList(oCriteria).then(function(matters) {
                            $scope2.matters = matters;
                            if (matters && matters.length) {
                                matters.forEach(function(oMatter, index) {
                                    if (oIncludeApps[oMatter.type + oMatter.id]) {
                                        $scope2.rows.selected[index] = true;
                                    }
                                });
                            }
                        });
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        var selected = [];
                        for (var i in $scope2.rows.selected) {
                            if ($scope2.rows.selected[i]) {
                                selected.push($scope2.matters[i]);
                            }
                        }
                        $mi.close(selected);
                    };
                    http2.get('/rest/pl/fe/matter/mission/phase/list?mission=' + $scope.mission.id, function(rsp) {
                        $scope2.phases = rsp.data;
                    });
                    $scope2.doSearch();
                }],
                backdrop: 'static'
            }).result.then(function(apps) {
                $scope.makeReport(apps);
            });
        };
        $scope.moveUp = function(matter, index) {
            var apps, rpConfig;
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
        $scope.makeReport = function(apps) {
            var oMission, url, params;
            oMission = $scope.mission;
            url = '/rest/pl/fe/matter/mission/report/userAndApp?site=' + oMission.siteid + '&mission=' + oMission.id;
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
                    if ($scope.mission.reportConfig) {

                    }
                });
            });
        };
        $scope.exportReport = function() {
            var oMission, url;
            oMission = $scope.mission;
            url = '/rest/pl/fe/matter/mission/report/export?site=' + oMission.siteid + '&mission=' + oMission.id;
            window.open(url);
        };
        var _enrollAppSchemas;
        $scope.closeMatters = true;
        $scope.enrollAppSchemas = _enrollAppSchemas = {};
        $scope.$watch('mission', function(mission) {
            if (!mission) return;
            if (mission.userApp) {
                $scope.makeReport();
            }
        });
        var _oResultSet;
        $scope.resultSet = _oResultSet = {};
        $scope.tmsTableWrapReady = 'N';
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
            url += '?site=' + app.siteid;
            url += '&id=' + app.id;
            location.href = url;
        };
        $scope.$watch('mission.userApp', function(userApp) {
            if (!userApp) {
                _oResultSet.users && _oResultSet.users.splice(0, _oResultSet.users.length);
            }
        });
    }]);
});
