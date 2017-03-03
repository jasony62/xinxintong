define(['frame', 'enrollService', 'signinService'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlUser', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        var _missionApps, _enrollAppSchemas;
        $scope.missionApps = _missionApps = {};
        $scope.enrollAppSchemas = _enrollAppSchemas = {};
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
                            var url = '/rest/pl/fe/matter/' + appType + '/list?mission=' + mission.id;
                            http2.get(url, function(rsp) {
                                $scope2.apps = rsp.data.apps;
                            });
                        }
                    });
                }],
                backdrop: 'static'
            }).result.then(function(data) {
                mission.user_app_id = data.appId;
                mission.user_app_type = data.appType;
                $scope.update(['user_app_id', 'user_app_type']).then(function(rsp) {
                    var url = '/rest/pl/fe/matter/' + data.appType + '/get?site=' + mission.siteid + '&id=' + data.appId;
                    http2.get(url, function(rsp) {
                        mission.userApp = rsp.data;
                    });
                });
            });
        };
        $scope.cancelUserApp = function() {
            var mission = $scope.mission;
            mission.user_app_id = '';
            mission.user_app_type = '';
            $scope.update(['user_app_id', 'user_app_type']).then(function() {
                delete mission.userApp;
            });
        };
        $scope.$watch('mission', function(mission) {
            if (!mission) return;
            http2.get('/rest/pl/fe/matter/enroll/list?mission=' + mission.id, function(rsp) {
                _missionApps.enroll = rsp.data.apps;
                _missionApps.enroll.forEach(function(app) {
                    var schemas, schemasById;
                    if (app.data_schemas) {
                        schemas = JSON.parse(app.data_schemas);
                        schemasById = {};
                        schemas.forEach(function(schema) {
                            schemasById[schema.id] = schema;
                        });
                        _enrollAppSchemas[app.id] = schemasById;
                    }
                });
            });
            http2.get('/rest/pl/fe/matter/signin/list?mission=' + mission.id + '&cascaded=round', function(rsp) {
                _missionApps.signin = {};
                rsp.data.apps.forEach(function(app) {
                    _missionApps.signin[app.id] = app;
                });
            });
            http2.get('/rest/pl/fe/matter/group/list?mission=' + mission.id, function(rsp) {
                _missionApps.group = rsp.data.apps;
            });
        });
    }]);
    ngApp.provider.controller('ctrlUserAction', ['$scope', 'srvMission', 'srvEnrollRecord', 'srvSigninRecord', 'srvRecordConverter', function($scope, srvMission, srvEnrollRecord, srvSigninRecord, srvRecordConverter) {
        var _oUserPage, _users;
        $scope.oUserPage = _oUserPage = {};
        $scope.users = _users = [];
        $scope.tmsTableWrapReady = 'N';
        $scope.chooseUser = function(user) {
            $scope.recordsByApp = {};
            $scope.activeUser = user;
            if (user.userid) {
                srvMission.recordByUser(user).then(function(records) {
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
                _users.splice(0, _users.length);
            } else {
                if (userApp.data_schemas && angular.isString(userApp.data_schemas)) {
                    userApp.data_schemas = JSON.parse(userApp.data_schemas);
                }
                if (userApp.type === 'enroll') {
                    srvEnrollRecord.init(userApp, _oUserPage, {}, _users);
                    srvEnrollRecord.search(1).then(function(data) {
                        $scope.tmsTableWrapReady = 'Y';
                    });
                } else if (userApp.type === 'signin') {
                    srvSigninRecord.init(userApp, _oUserPage, {}, _users);
                    srvSigninRecord.search(1).then(function(data) {
                        $scope.tmsTableWrapReady = 'Y';
                    });
                }
            }
        });
    }]);
});
