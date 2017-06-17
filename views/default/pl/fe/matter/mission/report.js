define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlReport', ['$scope', '$uibModal', '$q', 'http2', 'srvMission', 'srvRecordConverter', function($scope, $uibModal, $q, http2, srvMission, srvRecordConverter) {
        function submitMatterSeqs() {
            var deferred = $q.defer(),
                matterSeqs = [];
            _showMatters.forEach(function(matter) {
                matterSeqs.push(matter._pk);
            });
            http2.post('/rest/pl/fe/matter/mission/matter/updateSeq?id=' + $scope.mission.id, matterSeqs, function(rsp) {
                deferred.resolve(rsp.data);
            });
            return deferred.promise;
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
                    var url = '/rest/pl/fe/matter/' + data.appType + '/get?site=' + mission.siteid + '&id=' + data.appId;
                    http2.get(url, function(rsp) {
                        mission.userApp = rsp.data;
                        if (mission.userApp.data_schemas && angular.isString(mission.userApp.data_schemas)) {
                            mission.userApp.data_schemas = JSON.parse(mission.userApp.data_schemas);
                        }
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
        $scope.chooseApps = function() {
            var mission = $scope.mission;
            $uibModal.open({
                templateUrl: 'chooseApps.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        var selected = [];
                        $scope2.matters.forEach(function(oMatter) {
                            if (oMatter._selected) {
                                selected.push(oMatter);
                            }
                        });
                        $mi.close(selected);
                    };
                    srvMission.matterList().then(function(matters) {
                        $scope2.matters = matters;
                    });
                }],
                backdrop: 'static'
            }).result.then(function(matters) {
                $scope.targetApps = matters;
                $scope.makeReport();
            });
        };
        $scope.makeReport = function() {
            var oMission, url, params;
            oMission = $scope.mission;
            url = '/rest/pl/fe/matter/mission/report/userAndApp?site=' + oMission.siteid + '&mission=' + oMission.id;
            params = {
                userSource: { id: oMission.user_app_id, type: oMission.user_app_type }
            };
            if ($scope.targetApps && $scope.targetApps.length) {
                params.apps = [];
                $scope.targetApps.forEach(function(oApp) {
                    params.apps.push({ id: oApp.id, type: oApp.type });
                });
            }
            http2.post(url, params, function(rsp) {
                $scope.report = rsp.data;
            });
        };
        $scope.hide = function(matter) {
            var url = '/rest/pl/fe/matter/mission/matter/update?id=' + $scope.mission.id + '&matterType=' + matter.type + '&matterId=' + matter.id;
            http2.post(url, { is_public: 'N' }, function(rsp) {
                matter.is_public = 'N';
                _showMatters.splice(_showMatters.indexOf(matter), 1);
                _hideMatters.push(matter);
            });
        };
        $scope.show = function(matter) {
            var url = '/rest/pl/fe/matter/mission/matter/update?id=' + $scope.mission.id + '&matterType=' + matter.type + '&matterId=' + matter.id;
            http2.post(url, { is_public: 'Y' }, function(rsp) {
                matter.is_public = 'Y';
                _hideMatters.splice(_hideMatters.indexOf(matter), 1);
                matter.seq = _showMatters.length;
                _showMatters.push(matter);
            });
        };
        $scope.moveUp = function(matter, index) {
            if (index === 0) return;
            _showMatters.splice(index, 1);
            _showMatters.splice(index - 1, 0, matter);
            matter.seq--;
            _showMatters[index].seq++;
            submitMatterSeqs().then(function() {});
        };
        $scope.moveDown = function(matter, index) {
            if (index === _showMatters.length - 1) return;
            _showMatters.splice(index, 1);
            _showMatters.splice(index + 1, 0, matter);
            matter.seq++;
            _showMatters[index].seq--;
            submitMatterSeqs().then(function() {});
        };
        $scope.$on('matters.orderChanged', function(e, moved) {
            var oldSeq = moved.seq,
                newSeq = _showMatters.indexOf(moved);
            if (newSeq < oldSeq) {
                moved.seq = newSeq;
                for (var i = newSeq + 1; i <= oldSeq; i++) {
                    _showMatters[i].seq++;
                }
                submitMatterSeqs().then(function() {});
            } else if (newSeq > oldSeq) {
                for (var i = oldSeq; i < newSeq; i++) {
                    _showMatters[i].seq--;
                }
                moved.seq = newSeq;
                submitMatterSeqs().then(function() {});
            }
        });
        var _showMatters, _hideMatters, _enrollAppSchemas;
        $scope.closeMatters = true;
        $scope.showMatters = _showMatters = [];
        $scope.hideMatters = _hideMatters = [];
        $scope.enrollAppSchemas = _enrollAppSchemas = {};
        $scope.$watch('mission', function(mission) {
            if (!mission) return;
            if (mission.userApp) {
                $scope.makeReport();
            }
            srvMission.matterList().then(function(matters) {
                matters.forEach(function(oMatter) {
                    if (oMatter.type === 'enroll') {
                        var schemas, schemasById;
                        if (oMatter.data_schemas) {
                            schemas = JSON.parse(oMatter.data_schemas);
                            schemasById = {};
                            schemas.forEach(function(schema) {
                                schemasById[schema.id] = schema;
                            });
                            _enrollAppSchemas[oMatter.id] = schemasById;
                        }
                    }
                    if (oMatter.is_public === 'Y') {
                        oMatter.seq = _showMatters.length;
                        _showMatters.push(oMatter);
                    } else {
                        _hideMatters.push(oMatter);
                    }
                });
            });
        });
        var _oResultSet, _del;
        $scope.resultSet = _oResultSet = {};
        $scope.tmsTableWrapReady = 'N';
        $scope.del = _del = [];
        $scope.doAttend = function(isCheck) {
            if (isCheck == 'Y') {
                _del = [];
                var keys = [];
                for (var i in $scope.recordsByApp) {
                    (Object.keys($scope.recordsByApp[i])).forEach(function(item, index) {
                        keys.push(item);
                    });
                }
                for (var i = $scope.showMatters.length - 1; i >= 0; i--) {
                    if (keys.indexOf($scope.showMatters[i].id) == -1) {
                        _del.push($scope.showMatters[i]);
                        $scope.showMatters.splice(i, 1);
                    }
                }
            } else {
                _del.forEach(function(item) {
                    $scope.showMatters.push(item);
                });
            }
        }
        $scope.doUserSearch = function() {
            // srvMission.userList(_oResultSet).then(function(result) {
            //     $scope.tmsTableWrapReady = 'Y';
            // });
        };
        $scope.doUserFilter = function(isCacnel) {
            _oResultSet.page.at = 1;
            isCacnel === true && (_oResultSet.criteria.keyword = '');
            $scope.doUserSearch();
        };
        $scope.doUserExport = function() {
            var url;
            url = '/rest/pl/fe/matter/mission/user/export';
            url += '?site=' + $scope.mission.siteid + '&mission=' + $scope.mission.id;
            window.open(url);
        }
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
                _oResultSet.users && _oResultSet.users.splice(0, _oResultSet.users.length);
            } else {
                $scope.doUserSearch();
            }
        });
    }]);
});
