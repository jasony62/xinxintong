angular.module('service.group', ['ui.bootstrap', 'ui.xxt']).
service('tkGroupTeam', ['$q', 'http2', function ($q, http2) {
    this.list = function (oApp, teamType) {
        var defer = $q.defer(),
            url;

        url = '/rest/pl/fe/matter/group/team/list?app=' + oApp.id;
        if (!teamType) {
            url += '&teamType=';
        } else if (/T|R/.test(teamType)) {
            url += '&teamType=' + teamType;
        }
        http2.get(url).then(function (rsp) {
            var teams = rsp.data;
            teams.forEach(function (oTeam) {
                oTeam.extattrs = (oTeam.extattrs && oTeam.extattrs.length) ? JSON.parse(oTeam.extattrs) : {};
                oTeam.targets = (!oTeam.targets || oTeam.targets.length === 0) ? [] : JSON.parse(oTeam.targets);
            });
            defer.resolve(teams);
        });

        return defer.promise;
    };
}]).provider('srvGroupApp', function () {
    var _siteId, _appId, _oApp;
    this.config = function (siteId, appId) {
        _siteId = siteId;
        _appId = appId;
    };
    this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'srvSite', 'tkGroupTeam', function ($q, $uibModal, http2, noticebox, srvSite, tkGroupTeam) {
        return {
            cached: function () {
                return _oApp;
            },
            get: function () {
                var defer = $q.defer(),
                    url;

                if (_oApp) {
                    defer.resolve(_oApp);
                } else {
                    url = '/rest/pl/fe/matter/group/get?site=' + _siteId + '&app=' + _appId;
                    http2.get(url).then(function (rsp) {
                        var schemasById = {};

                        _oApp = rsp.data;
                        _oApp.tags = (!_oApp.tags || _oApp.tags.length === 0) ? [] : _oApp.tags.split(',');
                        try {
                            _oApp.group_rule = _oApp.group_rule && _oApp.group_rule.length ? JSON.parse(_oApp.group_rule) : {};
                            _oApp.dataSchemas.forEach(function (oSchema) {
                                if (oSchema.type !== 'html') {
                                    schemasById[oSchema.id] = oSchema;
                                }
                            });
                            _oApp._schemasById = schemasById;
                        } catch (e) {
                            console.error('error', e);
                        }
                        tkGroupTeam.list(_oApp).then(function (teams) {
                            var teamsById = {};
                            teams.forEach(function (round) {
                                teamsById[round.team_id] = round;
                            });
                            _oApp._teamsById = teamsById;
                            defer.resolve(_oApp);
                        });
                    });
                }

                return defer.promise;
            },
            update: function (names) {
                var defer = $q.defer(),
                    modifiedData = {};

                angular.isString(names) && (names = [names]);
                names.forEach(function (name) {
                    if (name === 'tags') {
                        modifiedData.tags = _oApp.tags.join(',');
                    } else {
                        modifiedData[name] = _oApp[name];
                    }
                });

                http2.post('/rest/pl/fe/matter/group/update?app=' + _appId, modifiedData).then(function (rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            assocWithApp: function (sourceTypes, oMission, notSync) {
                var defer = $q.defer();
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/group/component/sourceApp.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        $scope2.data = {
                            app: '',
                            appType: 'registration',
                            onlySpeaker: 'N'
                        };
                        if (!oMission && _oApp && _oApp.mission) {
                            oMission = _oApp.mission;
                        };
                        if (oMission) {
                            $scope2.data.sameMission = 'Y'
                        };
                        $scope2.sourceTypes = sourceTypes;
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.ok = function () {
                            $mi.close($scope2.data);
                        };
                        $scope2.$watch('data.appType', function (appType) {
                            var url;
                            if (!appType) return;
                            if (appType === 'mschema') {
                                srvSite.memberSchemaList(oMission, !!oMission).then(function (aMschemas) {
                                    $scope2.apps = aMschemas;
                                });
                                delete $scope2.data.includeEnroll;
                            } else {
                                if (appType === 'registration') {
                                    url = '/rest/pl/fe/matter/enroll/list?site=' + _siteId + '&size=999';
                                    url += '&scenario=registration';
                                    delete $scope2.data.includeEnroll;
                                } else if (appType === 'signin') {
                                    url = '/rest/pl/fe/matter/signin/list?site=' + _siteId + '&size=999';
                                    $scope2.data.includeEnroll = 'Y';
                                }
                                oMission && (url += '&mission=' + oMission.id);
                                http2.get(url).then(function (rsp) {
                                    $scope2.apps = rsp.data.apps;
                                });
                            }
                        });
                    }],
                    backdrop: 'static'
                }).result.then(function (data) {
                    var params;
                    if (data.app) {
                        params = {
                            app: data.app.id,
                            appType: data.appType
                        };
                        data.appType === 'signin' && (params.includeEnroll = data.includeEnroll);
                        if (notSync) {
                            params.appTitle = data.app.title;
                            defer.resolve(params);
                        } else {
                            http2.post('/rest/pl/fe/matter/group/record/assocWithApp?app=' + _appId, params).then(function (rsp) {
                                var schemasById = {}
                                _oApp.sourceApp = data.app;
                                _oApp.dataSchemas = rsp.data.dataSchemas;
                                _oApp.dataSchemas.forEach(function (schema) {
                                    if (schema.type !== 'html') {
                                        schemasById[schema.id] = schema;
                                    }
                                });
                                _oApp._schemasById = schemasById;
                                defer.resolve(_oApp);
                            });
                        }
                    }
                });
                return defer.promise;
            },
            syncByApp: function () {
                var defer = $q.defer();
                if (_oApp.sourceApp) {
                    var url = '/rest/pl/fe/matter/group/record/syncByApp?app=' + _appId
                    http2.get(url).then(function (rsp) {
                        noticebox.success('同步' + rsp.data + '个用户');
                        defer.resolve(rsp.data);
                    });
                }
                return defer.promise;
            },
            cancelSourceApp: function () {
                _oApp.sourceApp = null;
                _oApp.dataSchemas = null;
                _oApp.assigned_nickname = '';
                delete _oApp.sourceApp;
                return this.update(['source_app', 'data_schemas', 'assigned_nickname']);
            },
            export: function () {
                var url = '/rest/pl/fe/matter/group/record/export?app=' + _appId;
                window.open(url);
            },
            dealData: function (oUser) {
                var role_team_titles = [];
                oUser.role_teams.forEach(function (teamId) {
                    if (_oApp._teamsById[teamId]) {
                        role_team_titles.push(_oApp._teamsById[teamId].title);
                    }
                });
                oUser.role_team_titles = role_team_titles;
            }
        };
    }];
}).provider('srvGroupTeam', function () {
    var _teams, _siteId, _appId;
    this.config = function (siteId, appId) {
        _siteId = siteId;
        _appId = appId;
    };
    this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'srvGroupApp', 'tkGroupTeam', function ($q, $uibModal, http2, noticebox, srvGroupApp, tkGroupTeam) {
        return {
            cached: function () {
                return _teams;
            },
            list: function () {
                var defer = $q.defer(),
                    url;

                if (_teams) {
                    defer.resolve(_teams);
                } else {
                    _teams = [];
                    tkGroupTeam.list({
                        id: _appId
                    }).then(function (teams) {
                        _teams = teams;
                        defer.resolve(_teams);
                    });
                }
                return defer.promise;
            },
            config: function () {
                var defer = $q.defer();
                srvGroupApp.get().then(function (oApp) {
                    $uibModal.open({
                        templateUrl: 'configRule.html',
                        controller: ['$uibModalInstance', '$scope', 'http2', function ($mi, $scope, http2) {
                            var groupRule, rule, schemas;

                            groupRule = oApp.groupRule;
                            rule = {
                                count: groupRule.count,
                                times: groupRule.times,
                                schemas: []
                            };
                            schemas = angular.copy(oApp.dataSchemas);
                            $scope.schemas = [];
                            http2.get('/rest/pl/fe/matter/group/record/count?app=' + _appId).then(function (rsp) {
                                $scope.countOfPlayers = rsp.data;
                                $scope.$watch('rule.count', function (countOfGroups) {
                                    if (countOfGroups) {
                                        rule.times = Math.ceil($scope.countOfPlayers / countOfGroups);
                                    }
                                });
                            });
                            schemas.forEach(function (oSchema) {
                                if (oSchema.type === 'single') {
                                    if (groupRule.schemas && groupRule.schemas.indexOf(oSchema.id) !== -1) {
                                        oSchema._selected = true;
                                    }
                                    $scope.schemas.push(oSchema);
                                }
                            });
                            $scope.rule = rule;
                            $scope.cancel = function () {
                                $mi.dismiss();
                            };
                            $scope.ok = function () {
                                if ($scope.schemas.length) {
                                    $scope.rule.schemas = [];
                                    $scope.schemas.forEach(function (oSchema) {
                                        if (oSchema._selected) {
                                            $scope.rule.schemas.push(oSchema.id);
                                        }
                                    });
                                }
                                $mi.close($scope.rule);
                            };
                        }],
                        backdrop: 'static',
                    }).result.then(function (oRule) {
                        var url = '/rest/pl/fe/matter/group/configRule?app=' + _appId;
                        _teams.splice(0, _teams.length);
                        http2.post(url, oRule).then(function (rsp) {
                            rsp.data.forEach(function (oTeam) {
                                _teams.push(oTeam);
                            });
                            defer.resolve(_teams);
                        });
                    });
                });
                return defer.promise;
            },
            empty: function () {
                var defer = $q.defer();
                if (window.confirm('本操作将清除已有分组数据，确定执行?')) {
                    var url = '/rest/pl/fe/matter/group/configRule?app=' + _appId;
                    http2.post(url, {}).then(function (rsp) {
                        _teams.splice(0, _teams.length);
                        defer.resolve(rsp.data);
                    });
                }
                return defer.promise;
            },
            add: function () {
                var defer = $q.defer(),
                    oProto = {
                        title: '分组' + (_teams.length + 1)
                    };
                http2.post('/rest/pl/fe/matter/group/team/add?app=' + _appId, oProto).then(function (rsp) {
                    var oNewTeam = rsp.data;
                    oNewTeam._before = angular.copy(oNewTeam);
                    _teams.push(oNewTeam);
                    defer.resolve(oNewTeam);
                });
                return defer.promise;
            },
            update: function (oTeam, name) {
                var defer = $q.defer(),
                    oUpdated = {};

                oUpdated[name] = oTeam[name];
                http2.post('/rest/pl/fe/matter/group/team/update?tid=' + oTeam.team_id, oUpdated).then(function (rsp) {
                    if (rsp.err_code === 0) {
                        oTeam._before = angular.copy(oTeam);
                        defer.resolve(oTeam);
                        noticebox.success('完成保存');
                    } else {
                        oTeam[name] = oTeam._before[name];
                    }
                }, {
                    autoBreak: false
                });
                return defer.promise;
            },
            remove: function (oTeam) {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/matter/group/team/remove?tid=' + oTeam.team_id).then(function (rsp) {
                    _teams.splice(_teams.indexOf(oTeam), 1);
                    defer.resolve();
                });
                return defer.promise;
            },
        }
    }];
}).provider('srvGroupRec', function () {
    var _oApp, _siteId, _appId, _aPlayers;
    this.$get = ['$q', '$uibModal', 'noticebox', 'http2', 'cstApp', 'pushnotify', 'tmsSchema', 'srvGroupApp', function ($q, $uibModal, noticebox, http2, cstApp, pushnotify, tmsSchema, srvGroupApp) {
        return {
            init: function (aCachedUsers) {
                var defer = $q.defer();
                srvGroupApp.get().then(function (oApp) {
                    _oApp = oApp;
                    _siteId = oApp.siteid;
                    _appId = oApp.id;
                    _aGrpRecords = aCachedUsers;
                    defer.resolve();
                });
                return defer.promise;
            },
            execute: function () {
                var _self = this;
                if (window.confirm('本操作将清除已有分组数据，确定执行?')) {
                    http2.get('/rest/pl/fe/matter/group/execute?site=' + _siteId + '&app=' + _appId).then(function (rsp) {
                        _self.list();
                    });
                }
            },
            list: function (oTeam, arg, filterByKeyword) {
                function fnTeam(obj) {
                    if (oTeam === null) {
                        return obj.all(filterByKeyword || {});
                    } else if (oTeam === false) {
                        return obj.pendings('T');
                    } else {
                        return obj.winners(oTeam, 'T');
                    }
                }

                function fnRoleTeam(obj) {
                    if (oTeam === null) {
                        return obj.all(filterByKeyword || {});
                    } else if (oTeam === false) {
                        return obj.pendings('R');
                    } else {
                        return obj.winners(oTeam, 'R');
                    }
                }
                arg === 'team' ? fnTeam(this) : fnRoleTeam(this);
            },
            all: function (oFilter) {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/record/list?app=' + _appId;

                _aGrpRecords.splice(0, _aGrpRecords.length);
                http2.post(url, oFilter).then(function (rsp) {
                    if (rsp.data.total) {
                        rsp.data.records.forEach(function (oRec) {
                            tmsSchema.forTable(oRec, _oApp._schemasById);
                            srvGroupApp.dealData(oRec);
                            _aGrpRecords.push(oRec);
                        });
                    }
                    defer.resolve(rsp.data.records);
                });
                return defer.promise;
            },
            winners: function (oTeam, teamType) {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/record/byTeam?app=' + _appId + '&tid=' + oTeam.team_id + '&teamType=' + teamType;

                _aGrpRecords.splice(0, _aGrpRecords.length);
                http2.get(url).then(function (rsp) {
                    rsp.data.forEach(function (oRec) {
                        tmsSchema.forTable(oRec, _oApp._schemasById);
                        srvGroupApp.dealData(oRec);
                        _aGrpRecords.push(oRec);
                    });
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            pendings: function (teamType) {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/record/pendingsGet?app=' + _appId + '&teamType=' + teamType;

                _aGrpRecords.splice(0, _aGrpRecords.length);
                http2.get(url).then(function (rsp) {
                    rsp.data.forEach(function (oRec) {
                        tmsSchema.forTable(oRec, _oApp._schemasById);
                        srvGroupApp.dealData(oRec);
                        _aGrpRecords.push(oRec);
                    });
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            quitGroup: function (users) {
                var defer = $q.defer(),
                    url, eks = [];

                url = '/rest/pl/fe/matter/group/record/quitGroup?app=' + _appId;

                users.forEach(function ($oRec) {
                    eks.push($oRec.enroll_key);
                });

                http2.post(url, eks).then(function (rsp) {
                    var oResult = rsp.data;
                    users.forEach(function (oRec) {
                        if (oResult[oRec.enroll_key] !== false) {
                            oRec.team_id = '';
                            oRec.team_title = '';
                            tmsSchema.forTable(oRec, _oApp._schemasById);
                        }
                    });
                    defer.resolve();
                });
                return defer.promise;
            },
            joinGroup: function (oTeam, users) {
                var defer = $q.defer(),
                    url, eks = [];

                url = '/rest/pl/fe/matter/group/record/joinGroup?app=' + _appId;
                url += '&team=' + oTeam.team_id;

                users.forEach(function (oRec) {
                    eks.push(oRec.enroll_key);
                });

                http2.post(url, eks).then(function (rsp) {
                    var oResult = rsp.data;
                    users.forEach(function (oRec) {
                        if (oResult[oRec.enroll_key] !== false) {
                            switch (oTeam.team_type) {
                                case 'T':
                                    oRec.team_id = oTeam.team_id;
                                    oRec.team_title = oTeam.title;
                                    break;
                                case 'R':
                                    oRec.role_teams === undefined && (oRec.role_teams = []);
                                    oRec.role_teams.push(oTeam.team_id);
                                    srvGroupApp.dealData(oRec);
                                    break;
                            }
                            tmsSchema.forTable(oRec, _oApp._schemasById);
                        }
                    });
                    defer.resolve();
                });
                return defer.promise;
            },
            add: function (oRec) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/group/record/add?app=' + _appId;
                http2.post(url, oRec).then(function (rsp) {
                    tmsSchema.forTable(rsp.data, _oApp._schemasById);
                    srvGroupApp.dealData(rsp.data);
                    _aGrpRecords.splice(0, 0, rsp.data);
                    defer.resolve();
                });
                return defer.promise;
            },
            update: function (oBeforeRec, oNewRec) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/group/record/update?app=' + _appId;
                url += '&ek=' + oBeforeRec.enroll_key;
                http2.post(url, oNewRec).then(function (rsp) {
                    http2.merge(oBeforeRec, rsp.data);
                    tmsSchema.forTable(oBeforeRec, _oApp._schemasById);
                    srvGroupApp.dealData(oBeforeRec);
                    defer.resolve();
                });
                return defer.promise;
            },
            remove: function (oBeforeRec) {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/matter/group/record/remove?app=' + _appId + '&ek=' + oBeforeRec.enroll_key).then(function (rsp) {
                    _aGrpRecords.splice(_aGrpRecords.indexOf(oBeforeRec), 1);
                    defer.resolve();
                });
                return defer.promise;
            },
            empty: function () {
                var defer = $q.defer(),
                    vcode;

                vcode = prompt('是否要从【' + _oApp.title + '】删除所有用户？，若是，请输入活动名称。');
                if (vcode === _oApp.title) {
                    http2.get('/rest/pl/fe/matter/group/record/empty?app=' + _appId).then(function (rsp) {
                        _aGrpRecords.splice(0, _aGrpRecords.length);
                        defer.resolve();
                    });
                } else {
                    defer.resolve();
                }
                return defer.promise;
            },
            edit: function (oRecord) {
                return $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/group/component/recordEditor.html',
                    controller: 'ctrlGrpRecEditor',
                    windowClass: 'auto-height',
                    resolve: {
                        record: function () {
                            return angular.copy(oRecord);
                        }
                    }
                }).result;
            },
            notify: function (rows) {
                var options = {
                    matterTypes: cstApp.notifyMatter,
                    sender: 'group:' + _appId
                };
                _oApp.mission && (options.missionId = _oApp.mission.id);
                pushnotify.open(_siteId, function (notify) {
                    var url, targetAndMsg = {};
                    if (notify.matters.length) {
                        if (rows) {
                            targetAndMsg.users = [];
                            Object.keys(rows.selected).forEach(function (key) {
                                if (rows.selected[key] === true) {
                                    var rec = _aGrpRecords[key];
                                    targetAndMsg.users.push({
                                        userid: rec.userid,
                                        enroll_key: rec.enroll_key
                                    });
                                }
                            });
                        }
                        targetAndMsg.message = notify.message;

                        url = '/rest/pl/fe/matter/group/notice/send';
                        url += '?site=' + _siteId;
                        targetAndMsg.app = _appId;
                        targetAndMsg.tmplmsg = notify.tmplmsg.id;

                        http2.post(url, targetAndMsg).then(function (data) {
                            noticebox.success('发送完成');
                        });
                    }
                }, options);
            },
        }
    }];
}).provider('srvGroupNotice', function () {
    this.$get = ['$q', 'http2', 'srvGroupApp', function ($q, http2, srvGroupApp) {
        return {
            detail: function (batch) {
                var defer = $q.defer(),
                    url;
                srvGroupApp.get().then(function (oApp) {
                    url = '/rest/pl/fe/matter/group/notice/logList?batch=' + batch.id + '&app=' + oApp.id;
                    http2.get(url).then(function (rsp) {
                        defer.resolve(rsp.data);
                    });
                });

                return defer.promise;
            }
        }
    }]
}).controller('ctrlGrpRecEditor', ['$scope', '$uibModalInstance', '$sce', 'record', 'tmsSchema', 'srvGroupApp', 'tkGroupTeam', function ($scope, $mi, $sce, oRecord, tmsSchema, srvGroupApp, tkGroupTeam) {
    srvGroupApp.get().then(function (oApp) {
        $scope.app = oApp;
        tkGroupTeam.list(oApp).then(function (teams) {
            $scope.teamRounds = [];
            $scope.roleTeams = [];
            teams.forEach(function (oTeam) {
                switch (oTeam.team_type) {
                    case 'T':
                        $scope.teamRounds.push(oTeam);
                        break;
                    case 'R':
                        $scope.roleTeams.push(oTeam);
                        break;
                }
            });
            var obj = {};
            if (oRecord.role_teams && oRecord.role_teams.length) {
                oRecord.role_teams.forEach(function (teamId) {
                    obj[teamId] = true;
                });
                oRecord._role_teams = obj;
            }
        });
        if (oRecord.data) {
            oApp.dataSchemas.forEach(function (schema) {
                if (oRecord.data[schema.id]) {
                    tmsSchema.forEdit(schema, oRecord.data);
                }
            });
        }
        $scope.aTags = oApp.tags;
        oRecord.aTags = (!oRecord.tags || oRecord.tags.length === 0) ? [] : oRecord.tags.split(',');
        $scope.record = oRecord;
    });
    $scope.scoreRangeArray = function (schema) {
        var arr = [];
        if (schema.range && schema.range.length === 2) {
            for (var i = schema.range[0]; i <= schema.range[1]; i++) {
                arr.push('' + i);
            }
        }
        return arr;
    };
    $scope.ok = function () {
        var oNewRecord, oScopeRecord;
        if ($scope.record._role_teams) {
            for (var i in $scope.record._role_teams) {
                if ($scope.record._role_teams[i] && $scope.record.role_teams.indexOf(i) === -1) {
                    $scope.record.role_teams.push(i);
                }
                if (!$scope.record._role_teams[i] && $scope.record.role_teams.indexOf(i) !== -1) {
                    $scope.record.role_teams.splice(i, 1);
                }
            }
        }
        oScopeRecord = $scope.record;
        oNewRecord = {
            data: {},
            is_leader: oScopeRecord.is_leader,
            comment: oScopeRecord.comment,
            tags: oScopeRecord.aTags.join(','),
            team_id: oScopeRecord.team_id,
            role_teams: oScopeRecord.role_teams
        };
        if (oScopeRecord.data) {
            $scope.app.dataSchemas.forEach(function (oSchema) {
                if (oScopeRecord.data[oSchema.id]) {
                    oNewRecord.data[oSchema.id] = oScopeRecord.data[oSchema.id];
                }
            });
            if (oScopeRecord.data.member) {
                oNewRecord.data.member = oScopeRecord.data.member;
            }
        }
        $mi.close({
            record: oNewRecord,
            tags: $scope.aTags
        });
    };
    $scope.cancel = function () {
        $mi.dismiss();
    };
    $scope.$on('tag.xxt.combox.done', function (event, aSelected) {
        var aNewTags = [];
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.record.aTags) {
                if (aSelected[i] === $scope.record.aTags[j]) {
                    existing = true;
                    break;
                }
            }!existing && aNewTags.push(aSelected[i]);
        }
        $scope.record.aTags = $scope.record.aTags.concat(aNewTags);
    });
    $scope.$on('tag.xxt.combox.add', function (event, newTag) {
        $scope.record.aTags.push(newTag);
        $scope.aTags.indexOf(newTag) === -1 && $scope.aTags.push(newTag);
    });
    $scope.$on('tag.xxt.combox.del', function (event, removed) {
        $scope.record.aTags.splice($scope.record.aTags.indexOf(removed), 1);
    });
}]);