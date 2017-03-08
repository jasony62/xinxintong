define(['require'], function(require) {
    angular.module('service.mission', ['ui.tms', 'ui.xxt', 'service.matter']).
    provider('srvMission', function() {
        var _siteId, _missionId, _oMission, _getMissionDeferred;
        this.config = function(siteId, missionId) {
            _siteId = siteId;
            _missionId = missionId;
        };
        this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'srvRecordConverter', function($q, $uibModal, http2, noticebox, srvRecordConverter) {
            var _self = {
                get: function() {
                    var url;
                    if (_getMissionDeferred) {
                        return _getMissionDeferred.promise;
                    }
                    _getMissionDeferred = $q.defer();
                    url = '/rest/pl/fe/matter/mission/get?id=' + _missionId;
                    http2.get(url, function(rsp) {
                        var userApp;
                        _oMission = rsp.data;
                        _oMission.extattrs = (_oMission.extattrs && _oMission.extattrs.length) ? JSON.parse(_oMission.extattrs) : {};
                        _oMission.opUrl = 'http://' + location.host + '/rest/site/op/matter/mission?site=' + _oMission.siteid + '&mission=' + _oMission.id;
                        if (userApp = _oMission.userApp) {
                            if (userApp.data_schemas && angular.isString(userApp.data_schemas)) {
                                userApp.data_schemas = JSON.parse(userApp.data_schemas);
                            }
                        }
                        _getMissionDeferred.resolve(_oMission);
                    });

                    return _getMissionDeferred.promise;
                },
                matterCount: function() {
                    var deferred = $q.defer();
                    http2.get('/rest/pl/fe/matter/mission/matter/count?id=' + _missionId, function(rsp) {
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
                        srvRecordConverter.config(mission.userApp.data_schemas);
                    });

                    url = '/rest/pl/fe/matter/mission/user/list?mission=' + _missionId;
                    url += '&' + oResultSet.page.j();
                    http2.post(url, oResultSet.criteria, function(rsp) {
                        var records = rsp.data.records;
                        oResultSet.users.splice(0, oResultSet.users.length);
                        if (records && records.length) {
                            records.forEach(function(record) {
                                srvRecordConverter.forTable(record);
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
                        http2.get('/rest/pl/fe/matter/mission/user/recordByUser?mission=' + _missionId + '&user=' + user.userid, function(rsp) {
                            deferred.resolve(rsp.data);
                        });
                    } else {
                        alert('无法获得有效用户信息');
                    }
                    return deferred.promise;
                },
                submit: function(modifiedData) {
                    var defer = $q.defer();
                    http2.post('/rest/pl/fe/matter/mission/setting/update?id=' + _missionId, modifiedData, function(rsp) {
                        noticebox.success('完成保存');
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                }
            }
            return _self;
        }];
    }).
    provider('srvOpMission', function() {
        var _siteId, _missionId, _oMission, _getMissionDeferred;
        this.config = function(siteId, missionId) {
            _siteId = siteId;
            _missionId = missionId;
        };
        this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'srvRecordConverter', function($q, $uibModal, http2, noticebox, srvRecordConverter) {
            var _self = {
                get: function() {
                    var url;
                    if (_getMissionDeferred) {
                        return _getMissionDeferred.promise;
                    }
                    _getMissionDeferred = $q.defer();
                    url = '/rest/site/op/matter/mission/get?site=' + _siteId + '&mission=' + _missionId;
                    http2.get(url, function(rsp) {
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
                matterList: function() {
                    var deferred = $q.defer(),
                        url;

                    url = '/rest/site/op/matter/mission/matterList?site=' + _siteId + '&mission=' + _missionId;
                    http2.get(url, function(rsp) {
                        deferred.resolve(rsp.data);
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

                    _self.get().then(function(result) {
                        var mission = result.mission;
                        if (mission && mission.userApp) {
                            srvRecordConverter.config(mission.userApp.data_schemas);
                        }
                    });

                    url = '/rest/site/op/matter/mission/user/list?site=' + _siteId + '&mission=' + _missionId;
                    url += '&' + oResultSet.page.j();
                    http2.post(url, oResultSet.criteria, function(rsp) {
                        var records = rsp.data.records;
                        oResultSet.users.splice(0, oResultSet.users.length);
                        if (records && records.length) {
                            records.forEach(function(record) {
                                srvRecordConverter.forTable(record);
                                oResultSet.users.push(record);
                            });
                        }
                        oResultSet.page.total = rsp.data.total;
                        deferred.resolve(rsp.data);
                    });
                    return deferred.promise;
                },
            }
            return _self;
        }];
    });
});
