angular.module('service.group', ['ui.bootstrap', 'ui.xxt']).
service('tkGroupTeam', ['$q', 'http2', function($q, http2) {
    this.list = function(oApp, teamType) {
        var defer = $q.defer(),
            url;

        url = '/rest/pl/fe/matter/group/team/list?app=' + oApp.id;
        if (!teamType) {
            url += '&teamType=';
        } else if (/T|R/.test(teamType)) {
            url += '&teamType=' + teamType;
        }
        http2.get(url).then(function(rsp) {
            var teams = rsp.data;
            teams.forEach(function(oTeam) {
                oTeam.extattrs = (oTeam.extattrs && oTeam.extattrs.length) ? JSON.parse(oTeam.extattrs) : {};
                oTeam.targets = (!oTeam.targets || oTeam.targets.length === 0) ? [] : JSON.parse(oTeam.targets);
            });
            defer.resolve(teams);
        });

        return defer.promise;
    };
}]).provider('srvGroupApp', function() {
    var _siteId, _appId, _oApp;
    this.config = function(siteId, appId) {
        _siteId = siteId;
        _appId = appId;
    };
    this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'srvSite', 'tkGroupTeam', function($q, $uibModal, http2, noticebox, srvSite, tkGroupTeam) {
        return {
            cached: function() {
                return _oApp;
            },
            get: function() {
                var defer = $q.defer(),
                    url;

                if (_oApp) {
                    defer.resolve(_oApp);
                } else {
                    url = '/rest/pl/fe/matter/group/get?site=' + _siteId + '&app=' + _appId;
                    http2.get(url).then(function(rsp) {
                        var schemasById = {};

                        _oApp = rsp.data;
                        _oApp.tags = (!_oApp.tags || _oApp.tags.length === 0) ? [] : _oApp.tags.split(',');
                        try {
                            _oApp.group_rule = _oApp.group_rule && _oApp.group_rule.length ? JSON.parse(_oApp.group_rule) : {};
                            _oApp.dataSchemas.forEach(function(oSchema) {
                                if (oSchema.type !== 'html') {
                                    schemasById[oSchema.id] = oSchema;
                                }
                            });
                            _oApp._schemasById = schemasById;
                        } catch (e) {
                            console.error('error', e);
                        }
                        tkGroupTeam.list(_oApp).then(function(teams) {
                            var teamsById = {};
                            teams.forEach(function(round) {
                                teamsById[round.team_id] = round;
                            });
                            _oApp._roundsById = teamsById;
                            defer.resolve(_oApp);
                        });
                    });
                }

                return defer.promise;
            },
            update: function(names) {
                var defer = $q.defer(),
                    modifiedData = {};

                angular.isString(names) && (names = [names]);
                names.forEach(function(name) {
                    if (name === 'tags') {
                        modifiedData.tags = _oApp.tags.join(',');
                    } else {
                        modifiedData[name] = _oApp[name];
                    }
                });

                http2.post('/rest/pl/fe/matter/group/update?app=' + _appId, modifiedData).then(function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            assocWithApp: function(sourceTypes, oMission, notSync) {
                var defer = $q.defer();
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/group/component/sourceApp.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
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
                        $scope2.cancel = function() { $mi.dismiss(); };
                        $scope2.ok = function() {
                            $mi.close($scope2.data);
                        };
                        $scope2.$watch('data.appType', function(appType) {
                            var url;
                            if (!appType) return;
                            if (appType === 'mschema') {
                                srvSite.memberSchemaList(oMission, !!oMission).then(function(aMschemas) {
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
                                } else {
                                    url = '/rest/pl/fe/matter/wall/list?site=' + _siteId + '&size=999';
                                }
                                oMission && (url += '&mission=' + oMission.id);
                                http2.get(url).then(function(rsp) {
                                    $scope2.apps = rsp.data.apps;
                                });
                            }
                        });
                    }],
                    backdrop: 'static'
                }).result.then(function(data) {
                    var params;
                    if (data.app) {
                        params = {
                            app: data.app.id,
                            appType: data.appType
                        };
                        data.appType === 'signin' && (params.includeEnroll = data.includeEnroll);
                        data.appType === 'wall' && (params.onlySpeaker = data.onlySpeaker);
                        if (notSync) {
                            params.appTitle = data.app.title;
                            defer.resolve(params);
                        } else {
                            http2.post('/rest/pl/fe/matter/group/user/assocWithApp?app=' + _appId, params).then(function(rsp) {
                                var schemasById = {}
                                _oApp.sourceApp = data.app;
                                _oApp.dataSchemas = rsp.data.dataSchemas;
                                _oApp.dataSchemas.forEach(function(schema) {
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
            syncByApp: function() {
                var defer = $q.defer();
                if (_oApp.sourceApp) {
                    var url = '/rest/pl/fe/matter/group/user/syncByApp?app=' + _appId
                    if (_oApp.sourceApp.type === 'wall') {
                        if (window.confirm('仅同步发言用户用，请按确认！\n同步所有用户，请按取消!')) {
                            url += '&onlySpeaker=Y';
                        } else {
                            url += '&onlySpeaker=N';
                        }
                    }
                    http2.get(url).then(function(rsp) {
                        noticebox.success('同步' + rsp.data + '个用户');
                        defer.resolve(rsp.data);
                    });
                }
                return defer.promise;
            },
            cancelSourceApp: function() {
                _oApp.sourceApp = null;
                _oApp.dataSchemas = null;
                _oApp.assigned_nickname = '';
                delete _oApp.sourceApp;
                return this.update(['source_app', 'data_schemas', 'assigned_nickname']);
            },
            export: function() {
                var url = '/rest/pl/fe/matter/group/user/export?app=' + _appId;
                window.open(url);
            },
            dealData: function(oUser) {
                var role_team_titles = [];
                oUser.role_teams.forEach(function(teamId) {
                    if (_oApp._roundsById[teamId]) {
                        role_team_titles.push(_oApp._roundsById[teamId].title);
                    }
                });
                oUser.role_team_titles = role_team_titles;
            }
        };
    }];
}).provider('srvGroupTeam', function() {
    var _teams, _siteId, _appId;
    this.config = function(siteId, appId) {
        _siteId = siteId;
        _appId = appId;
    };
    this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'srvGroupApp', 'tkGroupTeam', function($q, $uibModal, http2, noticebox, srvGroupApp, tkGroupTeam) {
        return {
            cached: function() {
                return _teams;
            },
            list: function() {
                var defer = $q.defer(),
                    url;

                if (_teams) {
                    defer.resolve(_teams);
                } else {
                    _teams = [];
                    tkGroupTeam.list({ id: _appId }).then(function(teams) {
                        _teams = teams;
                        defer.resolve(_teams);
                    });
                }
                return defer.promise;
            },
            config: function() {
                var defer = $q.defer();
                srvGroupApp.get().then(function(oApp) {
                    $uibModal.open({
                        templateUrl: 'configRule.html',
                        controller: ['$uibModalInstance', '$scope', 'http2', function($mi, $scope, http2) {
                            var groupRule, rule, schemas;

                            groupRule = oApp.groupRule;
                            rule = {
                                count: groupRule.count,
                                times: groupRule.times,
                                schemas: []
                            };
                            schemas = angular.copy(oApp.dataSchemas);
                            $scope.schemas = [];
                            http2.get('/rest/pl/fe/matter/group/user/count?app=' + _appId).then(function(rsp) {
                                $scope.countOfPlayers = rsp.data;
                                $scope.$watch('rule.count', function(countOfGroups) {
                                    if (countOfGroups) {
                                        rule.times = Math.ceil($scope.countOfPlayers / countOfGroups);
                                    }
                                });
                            });
                            schemas.forEach(function(oSchema) {
                                if (oSchema.type === 'single') {
                                    if (groupRule.schemas && groupRule.schemas.indexOf(oSchema.id) !== -1) {
                                        oSchema._selected = true;
                                    }
                                    $scope.schemas.push(oSchema);
                                }
                            });
                            $scope.rule = rule;
                            $scope.cancel = function() { $mi.dismiss(); };
                            $scope.ok = function() {
                                if ($scope.schemas.length) {
                                    $scope.rule.schemas = [];
                                    $scope.schemas.forEach(function(oSchema) {
                                        if (oSchema._selected) {
                                            $scope.rule.schemas.push(oSchema.id);
                                        }
                                    });
                                }
                                $mi.close($scope.rule);
                            };
                        }],
                        backdrop: 'static',
                    }).result.then(function(oRule) {
                        var url = '/rest/pl/fe/matter/group/configRule?app=' + _appId;
                        _teams.splice(0, _teams.length);
                        http2.post(url, oRule).then(function(rsp) {
                            rsp.data.forEach(function(oTeam) {
                                _teams.push(oTeam);
                            });
                            defer.resolve(_teams);
                        });
                    });
                });
                return defer.promise;
            },
            empty: function() {
                var defer = $q.defer();
                if (window.confirm('本操作将清除已有分组数据，确定执行?')) {
                    var url = '/rest/pl/fe/matter/group/configRule?app=' + _appId;
                    http2.post(url, {}).then(function(rsp) {
                        _teams.splice(0, _teams.length);
                        defer.resolve(rsp.data);
                    });
                }
                return defer.promise;
            },
            add: function() {
                var defer = $q.defer(),
                    oProto = {
                        title: '分组' + (_teams.length + 1)
                    };
                http2.post('/rest/pl/fe/matter/group/team/add?app=' + _appId, oProto).then(function(rsp) {
                    var oNewTeam = rsp.data;
                    oNewTeam._before = angular.copy(oNewTeam);
                    _teams.push(oNewTeam);
                    defer.resolve(oNewTeam);
                });
                return defer.promise;
            },
            update: function(oTeam, name) {
                var defer = $q.defer(),
                    oUpdated = {};

                oUpdated[name] = oTeam[name];
                http2.post('/rest/pl/fe/matter/group/team/update?tid=' + oTeam.team_id, oUpdated).then(function(rsp) {
                    if (rsp.err_code === 0) {
                        oTeam._before = angular.copy(oTeam);
                        defer.resolve(oTeam);
                        noticebox.success('完成保存');
                    } else {
                        oTeam[name] = oTeam._before[name];
                    }
                }, { autoBreak: false });
                return defer.promise;
            },
            remove: function(oTeam) {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/matter/group/team/remove?tid=' + oTeam.team_id).then(function(rsp) {
                    _teams.splice(_teams.indexOf(oTeam), 1);
                    defer.resolve();
                });
                return defer.promise;
            },
        }
    }];
}).provider('srvGroupUser', function() {
    var _oApp, _siteId, _appId, _aPlayers;
    this.$get = ['$q', '$uibModal', 'noticebox', 'http2', 'cstApp', 'pushnotify', 'tmsSchema', 'srvGroupApp', function($q, $uibModal, noticebox, http2, cstApp, pushnotify, tmsSchema, srvGroupApp) {
        return {
            init: function(aCachedUsers) {
                var defer = $q.defer();
                srvGroupApp.get().then(function(oApp) {
                    _oApp = oApp;
                    _siteId = oApp.siteid;
                    _appId = oApp.id;
                    _aGrpUsers = aCachedUsers;
                    defer.resolve();
                });
                return defer.promise;
            },
            execute: function() {
                var _self = this;
                if (window.confirm('本操作将清除已有分组数据，确定执行?')) {
                    http2.get('/rest/pl/fe/matter/group/execute?site=' + _siteId + '&app=' + _appId).then(function(rsp) {
                        _self.list();
                    });
                }
            },
            list: function(oTeam, arg, filterByKeyword) {
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
                arg === 'team' ? fnTeam(this) : roleTeam(this);
            },
            all: function(oFilter) {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/user/list?app=' + _appId;

                _aGrpUsers.splice(0, _aGrpUsers.length);
                http2.post(url, oFilter).then(function(rsp) {
                    if (rsp.data.total) {
                        rsp.data.users.forEach(function(oUser) {
                            tmsSchema.forTable(oUser, _oApp._schemasById);
                            srvGroupApp.dealData(oUser);
                            _aGrpUsers.push(oUser);
                        });
                    }
                    defer.resolve(rsp.data.users);
                });
                return defer.promise;
            },
            winners: function(oTeam, teamType) {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/team/winnersGet?app=' + _appId + '&rid=' + oTeam.team_id + '&teamType=' + teamType;

                _aGrpUsers.splice(0, _aGrpUsers.length);
                http2.get(url).then(function(rsp) {
                    rsp.data.forEach(function(player) {
                        tmsSchema.forTable(player, _oApp._schemasById);
                        srvGroupApp.dealData(player);
                        _aGrpUsers.push(player);
                    });
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            pendings: function(teamType) {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/user/pendingsGet?app=' + _appId + '&teamType=' + teamType;

                _aGrpUsers.splice(0, _aGrpUsers.length);
                http2.get(url).then(function(rsp) {
                    rsp.data.forEach(function(player) {
                        tmsSchema.forTable(player, _oApp._schemasById);
                        srvGroupApp.dealData(player);
                        _aGrpUsers.push(player);
                    });
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            quitGroup: function(users) {
                var defer = $q.defer(),
                    url, eks = [];

                url = '/rest/pl/fe/matter/group/user/quitGroup?app=' + _appId;

                users.forEach(function($oUser) {
                    eks.push($oUser.enroll_key);
                });

                http2.post(url, eks).then(function(rsp) {
                    var oResult = rsp.data;
                    users.forEach(function(oUser) {
                        if (oResult[oUser.enroll_key] !== false) {
                            oUser.team_id = '';
                            oUser.team_title = '';
                            tmsSchema.forTable(oUser, _oApp._schemasById);
                        }
                    });
                    defer.resolve();
                });
                return defer.promise;
            },
            joinGroup: function(oTeam, users) {
                var defer = $q.defer(),
                    url, eks = [];

                url = '/rest/pl/fe/matter/group/user/joinGroup?app=' + _appId;
                url += '&team=' + oTeam.team_id;

                users.forEach(function(oUser) {
                    eks.push(oUser.enroll_key);
                });

                http2.post(url, eks).then(function(rsp) {
                    var oResult = rsp.data;
                    users.forEach(function(oUser) {
                        if (oResult[oUser.enroll_key] !== false) {
                            switch (oTeam.team_type) {
                                case 'T':
                                    oUser.team_id = oTeam.team_id;
                                    oUser.team_title = oTeam.title;
                                    break;
                                case 'R':
                                    oUser.role_teams === undefined && (oUser.role_teams = []);
                                    oUser.role_teams.push(oTeam.team_id);
                                    srvGroupApp.dealData(oUser);
                                    break;
                            }
                            tmsSchema.forTable(oUser, _oApp._schemasById);
                        }
                    });
                    defer.resolve();
                });
                return defer.promise;
            },
            add: function(player) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/group/user/add?site=' + _siteId + '&app=' + _appId;
                http2.post(url, player).then(function(rsp) {
                    tmsSchema.forTable(rsp.data, _oApp._schemasById);
                    srvGroupApp.dealData(rsp.data);
                    _aGrpUsers.splice(0, 0, rsp.data);
                    defer.resolve();
                });
                return defer.promise;
            },
            update: function(oGrpUser, oNewGrpUser) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/group/user/update?site=' + _siteId + '&app=' + _appId;
                url += '&ek=' + oGrpUser.enroll_key;
                http2.post(url, oNewGrpUser).then(function(rsp) {
                    angular.extend(oGrpUser, rsp.data);
                    tmsSchema.forTable(oGrpUser, _oApp._schemasById);
                    srvGroupApp.dealData(oGrpUser);
                    defer.resolve();
                });
                return defer.promise;
            },
            remove: function(oGrpUser) {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/matter/group/user/remove?app=' + _appId + '&ek=' + oGrpUser.enroll_key).then(function(rsp) {
                    _aGrpUsers.splice(_aGrpUsers.indexOf(oGrpUser), 1);
                    defer.resolve();
                });
                return defer.promise;
            },
            empty: function() {
                var defer = $q.defer(),
                    vcode;

                vcode = prompt('是否要从【' + _oApp.title + '】删除所有用户？，若是，请输入活动名称。');
                if (vcode === _oApp.title) {
                    http2.get('/rest/pl/fe/matter/group/user/empty?app=' + _appId).then(function(rsp) {
                        _aGrpUsers.splice(0, _aGrpUsers.length);
                        defer.resolve();
                    });
                } else {
                    defer.resolve();
                }
                return defer.promise;
            },
            edit: function(player) {
                return $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/group/component/userEditor.html?_=1',
                    controller: 'ctrlGroupEditor',
                    windowClass: 'auto-height',
                    resolve: {
                        player: function() {
                            return angular.copy(player);
                        }
                    }
                }).result;
            },
            notify: function(rows) {
                var options = {
                    matterTypes: cstApp.notifyMatter,
                    sender: 'group:' + _appId
                };
                _oApp.mission && (options.missionId = _oApp.mission.id);
                pushnotify.open(_siteId, function(notify) {
                    var url, targetAndMsg = {};
                    if (notify.matters.length) {
                        if (rows) {
                            targetAndMsg.users = [];
                            Object.keys(rows.selected).forEach(function(key) {
                                if (rows.selected[key] === true) {
                                    var rec = _aGrpUsers[key];
                                    targetAndMsg.users.push({ userid: rec.userid, enroll_key: rec.enroll_key });
                                }
                            });
                        }
                        targetAndMsg.message = notify.message;

                        url = '/rest/pl/fe/matter/group/notice/send';
                        url += '?site=' + _siteId;
                        targetAndMsg.app = _appId;
                        targetAndMsg.tmplmsg = notify.tmplmsg.id;

                        http2.post(url, targetAndMsg).then(function(data) {
                            noticebox.success('发送完成');
                        });
                    }
                }, options);
            },
        }
    }];
}).provider('srvGroupNotice', function() {
    this.$get = ['$q', 'http2', 'srvGroupApp', function($q, http2, srvGroupApp) {
        return {
            detail: function(batch) {
                var defer = $q.defer(),
                    url;
                srvGroupApp.get().then(function(oApp) {
                    url = '/rest/pl/fe/matter/group/notice/logList?batch=' + batch.id + '&app=' + oApp.id;
                    http2.get(url).then(function(rsp) {
                        defer.resolve(rsp.data);
                    });
                });

                return defer.promise;
            }
        }
    }]
}).controller('ctrlGroupEditor', ['$scope', '$uibModalInstance', '$sce', 'player', 'tmsSchema', 'srvGroupApp', 'tkGroupTeam', function($scope, $mi, $sce, player, tmsSchema, srvGroupApp, tkGroupTeam) {
    srvGroupApp.get().then(function(oApp) {
        $scope.app = oApp;
        tkGroupTeam.list(oApp).then(function(teams) {
            $scope.teamRounds = [];
            $scope.roleTeams = [];
            teams.forEach(function(oTeam) {
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
            if (player.role_teams && player.role_teams.length) {
                player.role_teams.forEach(function(teamId) {
                    obj[teamId] = true;
                });
                player._role_teams = obj;
            }
        });
        if (player.data) {
            oApp.dataSchemas.forEach(function(schema) {
                if (player.data[schema.id]) {
                    tmsSchema.forEdit(schema, player.data);
                }
            });
        }
        $scope.aTags = oApp.tags;
        player.aTags = (!player.tags || player.tags.length === 0) ? [] : player.tags.split(',');
        $scope.player = player;
    });
    $scope.scoreRangeArray = function(schema) {
        var arr = [];
        if (schema.range && schema.range.length === 2) {
            for (var i = schema.range[0]; i <= schema.range[1]; i++) {
                arr.push('' + i);
            }
        }
        return arr;
    };
    $scope.ok = function() {
        var oNewPlayer, oScopePlayer;
        if ($scope.player._role_teams) {
            for (var i in $scope.player._role_teams) {
                if ($scope.player._role_teams[i] && $scope.player.role_teams.indexOf(i) === -1) {
                    $scope.player.role_teams.push(i);
                }
                if (!$scope.player._role_teams[i] && $scope.player.role_teams.indexOf(i) !== -1) {
                    $scope.player.role_teams.splice(i, 1);
                }
            }
        }
        oScopePlayer = $scope.player;
        oNewPlayer = {
            data: {},
            is_leader: oScopePlayer.is_leader,
            comment: oScopePlayer.comment,
            tags: oScopePlayer.aTags.join(','),
            team_id: oScopePlayer.team_id,
            role_teams: oScopePlayer.role_teams
        };
        if (oScopePlayer.data) {
            $scope.app.dataSchemas.forEach(function(oSchema) {
                if (oScopePlayer.data[oSchema.id]) {
                    oNewPlayer.data[oSchema.id] = oScopePlayer.data[oSchema.id];
                }
            });
            if (oScopePlayer.data.member) {
                oNewPlayer.data.member = oScopePlayer.data.member;
            }
        }
        $mi.close({ player: oNewPlayer, tags: $scope.aTags });
    };
    $scope.cancel = function() { $mi.dismiss(); };
    $scope.$on('tag.xxt.combox.done', function(event, aSelected) {
        var aNewTags = [];
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.player.aTags) {
                if (aSelected[i] === $scope.player.aTags[j]) {
                    existing = true;
                    break;
                }
            }!existing && aNewTags.push(aSelected[i]);
        }
        $scope.player.aTags = $scope.player.aTags.concat(aNewTags);
    });
    $scope.$on('tag.xxt.combox.add', function(event, newTag) {
        $scope.player.aTags.push(newTag);
        $scope.aTags.indexOf(newTag) === -1 && $scope.aTags.push(newTag);
    });
    $scope.$on('tag.xxt.combox.del', function(event, removed) {
        $scope.player.aTags.splice($scope.player.aTags.indexOf(removed), 1);
    });
}]);