define(['require'], function(require) {
    angular.module('service.mission', ['ui.tms', 'service.matter']).
    provider('srvMission', function() {
        var _siteId, _missionId, _oMission, _getMissionDeferred;
        this.config = function(siteId, missionId) {
            _siteId = siteId;
            _missionId = missionId;
        };
        this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'tmsSchema', function($q, $uibModal, http2, noticebox, tmsSchema) {
            var _self = {
                get: function() {
                    var url;
                    if (_getMissionDeferred) {
                        return _getMissionDeferred.promise;
                    }
                    _getMissionDeferred = $q.defer();
                    url = '/rest/pl/fe/matter/mission/get?id=' + _missionId;
                    http2.get(url).then(function(rsp) {
                        var userApp;
                        _oMission = rsp.data;
                        _oMission.extattrs = (_oMission.extattrs && _oMission.extattrs.length) ? JSON.parse(_oMission.extattrs) : {};
                        if (userApp = _oMission.userApp) {
                            if (userApp.data_schemas && angular.isString(userApp.data_schemas)) {
                                userApp.data_schemas = JSON.parse(userApp.data_schemas);
                            }
                        }
                        _getMissionDeferred.resolve(_oMission);
                    });

                    return _getMissionDeferred.promise;
                },
                chooseContents: function(oMission, oReport) {
                    return $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/mission/component/chooseContents.html?_=1',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            var oCriteria, oIncludeApps = {},
                                oIncludeMarks = {};
                            $scope2.mission = oMission;
                            $scope2.criteria = oCriteria = {};
                            if (oReport.apps) {
                                oReport.apps.forEach(function(oApp) {
                                    oIncludeApps[oApp.type + oApp.id] = true;
                                });
                            }
                            if (oReport.show_schema) {
                                oReport.show_schema.forEach(function(oSchema) {
                                    oIncludeMarks[oSchema.title + oSchema.id] = true;
                                });
                            }
                            // 选中的记录
                            $scope2.markRows = {
                                selected: {},
                                reset: function() {
                                    this.selected = {};
                                }
                            };
                            $scope2.appRows = {
                                allSelected: 'N',
                                selected: {},
                                reset: function() {
                                    this.allSelected = 'N';
                                    this.selected = {};
                                }
                            };
                            $scope2.$watch('appRows.allSelected', function(checked) {
                                var index = 0;
                                if (checked === 'Y') {
                                    while (index < $scope2.matters.length) {
                                        $scope2.appRows.selected[index++] = true;
                                    }
                                } else if (checked === 'N') {
                                    $scope2.appRows.selected = {};
                                }
                            });
                            $scope2.doSearch = function() {
                                $scope2.appRows.reset();
                                $scope2.markRows.reset();

                                $scope2.appMarkSchemas = angular.copy(oMission.userApp.dataSchemas);
                                if ($scope2.appMarkSchemas && $scope2.appMarkSchemas.length) {
                                    $scope2.appMarkSchemas.forEach(function(schema, index) {
                                        if (oIncludeMarks[schema.title + schema.id]) {
                                            $scope2.markRows.selected[schema.id] = true;
                                        }
                                    });
                                }
                                _self.matterList(oCriteria).then(function(matters) {
                                    $scope2.matters = matters;
                                    if (matters && matters.length) {
                                        for (var i = 0; i < matters.length; i++) {
                                            if (matters[i].type == 'memberschema') {
                                                matters.splice(matters[i], 1);
                                                break;
                                            }
                                        };
                                        matters.forEach(function(oMatter, index) {
                                            if (oIncludeApps[oMatter.type + oMatter.id]) {
                                                $scope2.appRows.selected[index] = true;
                                            }
                                        });
                                    }
                                });
                            };
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope2.ok = function() {
                                var apps = [];
                                for (var i in $scope2.appRows.selected) {
                                    if ($scope2.appRows.selected[i]) {
                                        apps.push($scope2.matters[i]);
                                    }
                                }
                                var marks = [];
                                if (Object.keys($scope2.markRows.selected).length) {
                                    $scope2.appMarkSchemas.forEach(function(oSchema) {
                                        if ($scope2.markRows.selected[oSchema.id]) {
                                            marks.push(oSchema);
                                        }
                                    });
                                }
                                $mi.close({ app: apps, mark: marks });
                            };
                            $scope2.doSearch();
                        }],
                        backdrop: 'static'
                    }).result;
                },
                matterList: function(oCriteria) {
                    var deferred, url;
                    deferred = $q.defer();
                    !oCriteria && (oCriteria = {});
                    url = '/rest/pl/fe/matter/mission/matter/list?id=' + _missionId;
                    http2.post(url, oCriteria).then(function(rsp) {
                        deferred.resolve(rsp.data);
                    });
                    return deferred.promise;
                },
                matterCount: function() {
                    var deferred = $q.defer();
                    http2.get('/rest/pl/fe/matter/mission/matter/count?id=' + _missionId).then(function(rsp) {
                        deferred.resolve(parseInt(rsp.data));
                    });
                    return deferred.promise;
                },
                userList: function(oResultSet) {
                    var deferred = $q.defer(),
                        url;

                    if (Object.keys(oResultSet).length === 0) {
                        angular.extend(oResultSet, {
                            page: {
                                at: 1,
                                size: 30,
                                j: function() {
                                    return 'page=' + this.at + '&size=' + this.size;
                                },
                                offset: function() {
                                    return (this.at - 1) * this.size;
                                }
                            },
                            criteria: {},
                            users: []
                        });
                    }

                    _self.get().then(function(mission) {
                        //tmsSchema.config(mission.userApp.data_schemas);
                    });

                    url = '/rest/pl/fe/matter/mission/user/list?mission=' + _missionId;
                    url += '&' + oResultSet.page.j();
                    http2.post(url, oResultSet.criteria).then(function(rsp) {
                        var records = rsp.data.records;
                        oResultSet.users.splice(0, oResultSet.users.length);
                        if (records && records.length) {
                            records.forEach(function(record) {
                                tmsSchema.forTable(record);
                                oResultSet.users.push(record);
                            });
                        }
                        oResultSet.page.total = rsp.data.total;
                        deferred.resolve(rsp.data);
                    });
                    return deferred.promise;
                },
                recordByUser: function(user) {
                    var deferred = $q.defer();
                    if (user.userid) {
                        http2.get('/rest/pl/fe/matter/mission/report/recordByUser?mission=' + _missionId + '&user=' + user.userid).then(function(rsp) {
                            deferred.resolve(rsp.data);
                        });
                    } else {
                        alert('无法获得有效用户信息');
                    }
                    return deferred.promise;
                },
                submit: function(modifiedData) {
                    var defer = $q.defer();
                    http2.post('/rest/pl/fe/matter/mission/update?id=' + _missionId, modifiedData).then(function(rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                }
            }
            return _self;
        }];
    }).provider('srvMissionRound', function() {
        var _siteId, _missionId, _rounds, _oPage,
            _RestURL = '/rest/pl/fe/matter/mission/round/',
            RoundState = ['新建', '启用', '结束'];

        this.config = function(siteId, missionId) {
            _siteId = siteId;
            _missionId = missionId;
        };
        this.$get = ['$q', '$uibModal', 'http2', 'srvMission', function($q, $uibModal, http2, srvMission) {
            return {
                RoundState: RoundState,
                init: function(rounds, page) {
                    _rounds = rounds;
                    _oPage = page;
                    if (page.j === undefined) {
                        page.at = 1;
                        page.size = 10;
                        page.j = function() {
                            return 'page=' + this.at + '&size=' + this.size;
                        }
                    }
                },
                list: function(checkRid) {
                    var defer = $q.defer(),
                        url;
                    if (_rounds === undefined) {
                        _rounds = [];
                    }
                    if (_oPage === undefined) {
                        _oPage = {
                            at: 1,
                            size: 10,
                            j: function() {
                                return 'page=' + this.at + '&size=' + this.size;
                            }
                        };
                    }
                    url = _RestURL + 'list?site=' + _siteId + '&mission=' + _missionId + '&' + _oPage.j();
                    if (checkRid) {
                        url += '&checked=' + checkRid;
                    }
                    http2.get(url).then(function(rsp) {
                        var _checked;
                        _rounds.splice(0, _rounds.length);
                        rsp.data.rounds.forEach(function(rnd) {
                            rsp.data.active && (rnd._isActive = rnd.rid === rsp.data.active.rid);
                            _rounds.push(rnd);
                        });
                        _oPage.total = parseInt(rsp.data.total);
                        _checked = (rsp.data.checked ? rsp.data.checked : '');
                        defer.resolve({ rounds: _rounds, page: _oPage, active: rsp.data.active, checked: _checked });
                    });

                    return defer.promise;
                },
                add: function() {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/roundEditor.html?_=2',
                        backdrop: 'static',
                        controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                            $scope.round = {
                                state: '0',
                                start_at: '0'
                            };
                            $scope.roundState = RoundState;
                            $scope.$on('xxt.tms-datepicker.change', function(event, data) {
                                if (data.state === 'start_at') {
                                    if (data.obj[data.state] == 0 && data.value > 0) {
                                        $scope.round.state = '1';
                                    } else if (data.obj[data.state] > 0 && data.value == 0) {
                                        $scope.round.state = '0';
                                    }
                                }
                                data.obj[data.state] = data.value;
                            });
                            $scope.close = function() {
                                $mi.dismiss();
                            };
                            $scope.ok = function() {
                                $mi.close($scope.round);
                            };
                            $scope.start = function() {
                                $scope.round.state = 1;
                                $mi.close($scope.round);
                            };
                        }]
                    }).result.then(function(newRound) {
                        http2.post(_RestURL + 'add?site=' + _siteId + '&mission=' + _missionId, newRound).then(function(rsp) {
                            if (_rounds.length > 0 && rsp.data.state == 1) {
                                _rounds[0].state = 2;
                            }
                            _rounds.splice(0, 0, rsp.data);
                            _oPage.total++;
                        });
                    });
                },
                edit: function(round) {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/roundEditor.html?_=2',
                        backdrop: 'static',
                        controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                            $scope.round = { rid: round.rid, title: round.title, start_at: round.start_at, end_at: round.end_at, state: round.state };
                            $scope.roundState = RoundState;
                            $scope.$on('xxt.tms-datepicker.change', function(event, data) {
                                if (data.state === 'start_at') {
                                    if (data.obj[data.state] == 0 && data.value > 0) {
                                        $scope.round.state = '1';
                                    } else if (data.obj[data.state] > 0 && data.value == 0) {
                                        $scope.round.state = '0';
                                    }
                                }
                                data.obj[data.state] = data.value;
                            });
                            $scope.close = function() {
                                $mi.dismiss();
                            };
                            $scope.ok = function() {
                                $mi.close({
                                    action: 'update',
                                    data: $scope.round
                                });
                            };
                            $scope.remove = function() {
                                $mi.close({
                                    action: 'remove'
                                });
                            };
                            $scope.stop = function() {
                                $scope.round.state = '2';
                                $mi.close({
                                    action: 'update',
                                    data: $scope.round
                                });
                            };
                            $scope.start = function() {
                                $scope.round.state = '1';
                                $mi.close({
                                    action: 'update',
                                    data: $scope.round
                                });
                            };
                        }]
                    }).result.then(function(rst) {
                        var url = _RestURL;
                        if (rst.action === 'update') {
                            url += 'update?site=' + _siteId + '&mission=' + _missionId + '&rid=' + round.rid;
                            http2.post(url, rst.data).then(function(rsp) {
                                if (_rounds.length > 1 && rst.data.state === '1') {
                                    _rounds[1].state = '2';
                                }
                                angular.extend(round, rsp.data);
                            });
                        } else if (rst.action === 'remove') {
                            url += 'remove?site=' + _siteId + '&mission=' + _missionId + '&rid=' + round.rid;
                            http2.get(url).then(function(rsp) {
                                _rounds.splice(_rounds.indexOf(round), 1);
                                _oPage.total--;
                            });
                        }
                    });
                }
            };
        }];
    }).provider('srvOpMission', function() {
        var _siteId, _missionId, _accessId, _oMission, _getMissionDeferred;
        this.config = function(siteId, missionId, accessId) {
            _siteId = siteId;
            _missionId = missionId;
            _accessId = accessId;
        };
        this.$get = ['$q', '$uibModal', 'http2', 'noticebox', function($q, $uibModal, http2, noticebox) {
            var _self = {
                get: function() {
                    var url;
                    if (_getMissionDeferred) {
                        return _getMissionDeferred.promise;
                    }
                    _getMissionDeferred = $q.defer();
                    url = '/rest/site/op/matter/mission/get?site=' + _siteId + '&mission=' + _missionId + '&accessToken=' + _accessId;
                    http2.get(url).then(function(rsp) {
                        var userApp;
                        _oMission = rsp.data.mission;
                        _oMission.extattrs = (_oMission.extattrs && _oMission.extattrs.length) ? JSON.parse(_oMission.extattrs) : {};
                        if (userApp = _oMission.userApp) {
                            if (userApp.data_schemas && angular.isString(userApp.data_schemas)) {
                                userApp.data_schemas = JSON.parse(userApp.data_schemas);
                            }
                        }
                        _getMissionDeferred.resolve(rsp.data);
                    });

                    return _getMissionDeferred.promise;
                },
                chooseApps: function(oMission, includeApps) {
                    return $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/mission/component/chooseApps.html?_=1',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            var oCriteria, oReportConfig, oIncludeApps = {};
                            $scope2.criteria = oCriteria = {};
                            if (includeApps) {
                                includeApps.forEach(function(oApp) {
                                    oIncludeApps[oApp.type + oApp.id] = true;
                                });
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
                                _self.matterList(oCriteria).then(function(matters) {
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
                            $scope2.doSearch();
                        }],
                        backdrop: 'static'
                    }).result;
                },
                matterList: function() {
                    var deferred = $q.defer(),
                        url;

                    url = '/rest/site/op/matter/mission/matterList?site=' + _siteId + '&mission=' + _missionId + '&accessToken=' + _accessId;
                    http2.get(url).then(function(rsp) {
                        deferred.resolve(rsp.data);
                    });
                    return deferred.promise;
                },
                recordByUser: function(oUser, oApp) {
                    var url, deferred = $q.defer();
                    if (!oUser.userid) {
                        alert('无法获得有效用户信息');
                    }
                    url = '/rest/site/op/matter/mission/report/recordByUser';
                    url += '?site=' + _siteId + '&mission=' + _missionId + '&accessToken=' + _accessId;
                    url += '&user=' + oUser.userid;
                    oApp && (url += '&app=' + oApp.type + ',' + oApp.id);
                    http2.get(url).then(function(rsp) {
                        deferred.resolve(rsp.data);
                    });
                    return deferred.promise;
                },
            }
            return _self;
        }];
    });
});