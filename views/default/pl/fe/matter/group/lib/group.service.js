angular.module('service.group', ['ui.bootstrap', 'ui.xxt']).
provider('srvGroupApp', function() {
    var _siteId, _appId, _oApp;
    this.config = function(siteId, appId) {
        _siteId = siteId;
        _appId = appId;
    };
    this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'srvSite', function($q, $uibModal, http2, noticebox, srvSite) {
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
                    http2.get(url, function(rsp) {
                        var url, schemasById = {},
                            roundsById = {};
                        _oApp = rsp.data;
                        _oApp.tags = (!_oApp.tags || _oApp.tags.length === 0) ? [] : _oApp.tags.split(',');
                        try {
                            _oApp.group_rule = _oApp.group_rule && _oApp.group_rule.length ? JSON.parse(_oApp.group_rule) : {};
                            _oApp.data_schemas = _oApp.data_schemas && _oApp.data_schemas.length ? JSON.parse(_oApp.data_schemas) : [];
                            _oApp.data_schemas.forEach(function(schema) {
                                if (schema.type !== 'html') {
                                    schemasById[schema.id] = schema;
                                }
                            });
                            _oApp.rounds.forEach(function(round) {
                                roundsById[round.round_id] = round;
                            });
                            _oApp._schemasById = schemasById;
                            _oApp._roundsById = roundsById;
                        } catch (e) {
                            console.error('error', e);
                        }
                        _oApp.opUrl = location.protocol + '//' + location.host + '/rest/site/op/matter/group?site=' + _siteId + '&app=' + _appId;
                        if (_oApp.page_code_id == 0 && _oApp.scenario.length) {
                            url = '/rest/pl/fe/matter/group/page/create?site=' + _siteId + '&app=' + _appId + '&scenario=' + _oApp.scenario;
                            http2.get(url, function(rsp) {
                                _oApp.page_code_id = rsp.data;
                                defer.resolve(_oApp);
                            });
                        } else {
                            defer.resolve(_oApp);
                        }
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

                http2.post('/rest/pl/fe/matter/group/update?site=' + _siteId + '&app=' + _appId, modifiedData, function(rsp) {
                    modifiedData = {};
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            remove: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/enroll/remove?site=' + _siteId + '&app=' + _appId;
                http2.get(url, function(rsp) {
                    defer.resolve();
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
                        $scope2.cancel = function() {
                            $mi.dismiss();
                        };
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
                                http2.get(url, function(rsp) {
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
                            http2.post('/rest/pl/fe/matter/group/player/assocWithApp?site=' + _siteId + '&app=' + _appId, params, function(rsp) {
                                var schemasById = {}
                                _oApp.sourceApp = data.app;
                                if (angular.isString(rsp.data.data_schemas)) {
                                    _oApp.data_schemas = rsp.data.data_schemas ? JSON.parse(rsp.data.data_schemas) : '';
                                } else {
                                    _oApp.data_schemas = rsp.data.data_schemas;
                                }
                                _oApp.data_schemas.forEach(function(schema) {
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
                    var url = '/rest/pl/fe/matter/group/player/syncByApp?site=' + _siteId + '&app=' + _appId
                    if (_oApp.sourceApp.type === 'wall') {
                        if (window.confirm('仅同步发言用户用，请按确认！\n同步所有用户，请按取消!')) {
                            url += '&onlySpeaker=Y';
                        } else {
                            url += '&onlySpeaker=N';
                        }
                    }
                    http2.get(url, function(rsp) {
                        noticebox.success('同步' + rsp.data + '个用户');
                        defer.resolve(rsp.data);
                    });
                }
                return defer.promise;
            },
            cancelSourceApp: function() {
                _oApp.source_app = '';
                _oApp.data_schemas = '';
                _oApp.assigned_nickname = '';
                delete _oApp.sourceApp;
                return this.update(['source_app', 'data_schemas', 'assigned_nickname']);
            },
            export: function() {
                var url = '/rest/pl/fe/matter/group/player/export?site=' + _siteId + '&app=' + _appId;
                window.open(url);
            },
            dealData: function(player) {
                var role_round_titles = [];
                player.role_rounds.forEach(function(roundId) {
                    role_round_titles.push(_oApp._roundsById[roundId].title);
                });
                player.role_round_titles = role_round_titles;
            }
        };
    }];
}).provider('srvGroupRound', function() {
    var _rounds, _siteId, _appId;
    this.config = function(siteId, appId) {
        _siteId = siteId;
        _appId = appId;
    };
    this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'srvGroupApp', function($q, $uibModal, http2, noticebox, srvGroupApp) {
        return {
            cached: function() {
                return _rounds;
            },
            list: function() {
                var defer = $q.defer(),
                    url;

                if (_rounds) {
                    defer.resolve(_rounds);
                } else {
                    _rounds = [];
                    url = '/rest/pl/fe/matter/group/round/list?site=' + _siteId + '&app=' + _appId + '&cascade=playerCount';
                    http2.get(url, function(rsp) {
                        var rounds = rsp.data;
                        rounds.forEach(function(oRound) {
                            oRound.extattrs = (oRound.extattrs && oRound.extattrs.length) ? JSON.parse(oRound.extattrs) : {};
                            oRound.targets = (!oRound.targets || oRound.targets.length === 0) ? [] : JSON.parse(oRound.targets);
                            _rounds.push(oRound);
                        });
                        defer.resolve(_rounds);
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
                            schemas = angular.copy(oApp.data_schemas);
                            $scope.schemas = [];
                            http2.get('/rest/pl/fe/matter/group/player/count?site=' + _siteId + '&app=' + _appId, function(rsp) {
                                $scope.countOfPlayers = rsp.data;
                                $scope.$watch('rule.count', function(countOfGroups) {
                                    if (countOfGroups) {
                                        rule.times = Math.ceil($scope.countOfPlayers / countOfGroups);
                                    }
                                });
                            });
                            schemas.forEach(function(schema) {
                                if (schema.type === 'single') {
                                    if (groupRule.schemas && groupRule.schemas.indexOf(schema.id) !== -1) {
                                        schema._selected = true;
                                    }
                                    $scope.schemas.push(schema);
                                }
                            });
                            $scope.rule = rule;
                            $scope.cancel = function() {
                                $mi.dismiss();
                            };
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
                    }).result.then(function(rule) {
                        var url = '/rest/pl/fe/matter/group/configRule?site=' + _siteId + '&app=' + _appId;
                        _rounds.splice(0, _rounds.length);
                        http2.post(url, rule, function(rsp) {
                            rsp.data.forEach(function(round) {
                                _rounds.push(round);
                            });
                            defer.resolve(_rounds);
                        });
                    });
                });
                return defer.promise;
            },
            empty: function() {
                var defer = $q.defer();
                if (window.confirm('本操作将清除已有分组数据，确定执行?')) {
                    var url = '/rest/pl/fe/matter/group/configRule?site=' + _siteId + '&app=' + _appId;
                    http2.post(url, {}, function(rsp) {
                        _rounds.splice(0, _rounds.length);
                        defer.resolve(rsp.data);
                    });
                }
                return defer.promise;
            },
            add: function() {
                var defer = $q.defer(),
                    proto = {
                        title: '分组' + (_rounds.length + 1)
                    };
                http2.post('/rest/pl/fe/matter/group/round/add?site=' + _siteId + '&app=' + _appId, proto, function(rsp) {
                    _rounds.push(rsp.data);
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            update: function(round, name) {
                var defer = $q.defer(),
                    nv = {};

                nv[name] = round[name];
                http2.post('/rest/pl/fe/matter/group/round/update?site=' + _siteId + '&app=' + _appId + '&rid=' + round.round_id, nv, function(rsp) {
                    defer.resolve();
                    noticebox.success('完成保存');
                });
                return defer.promise;
            },
            remove: function(round) {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/matter/group/round/remove?site=' + _siteId + '&app=' + _appId + '&rid=' + round.round_id, function(rsp) {
                    _rounds.splice(_rounds.indexOf(round), 1);
                    defer.resolve();
                });
                return defer.promise;
            },
        }
    }];
}).provider('srvGroupPlayer', function() {
    var _oApp, _siteId, _appId, _aPlayers, _activeRound;
    this.$get = ['$q', '$uibModal', 'noticebox', 'http2', 'cstApp', 'pushnotify', 'tmsSchema', 'srvGroupApp', function($q, $uibModal, noticebox, http2, cstApp, pushnotify, tmsSchema, srvGroupApp) {
        return {
            init: function(aPlayers) {
                var defer = $q.defer();
                srvGroupApp.get().then(function(oApp) {
                    _oApp = oApp;
                    _siteId = oApp.siteid;
                    _appId = oApp.id;
                    _aPlayers = aPlayers;
                    defer.resolve();
                });
                return defer.promise;
            },
            execute: function() {
                var _self = this;
                if (window.confirm('本操作将清除已有分组数据，确定执行?')) {
                    http2.get('/rest/pl/fe/matter/group/execute?site=' + _siteId + '&app=' + _appId, function(rsp) {
                        _self.list(_activeRound);
                    });
                }
            },
            list: function(round, arg) {
                arg == 'round' ? commonRound(this) : roleRound(this);

                function commonRound(obj) {
                    if (round === undefined) {
                        round = _activeRound;
                    }
                    if (round === null) {
                        return obj.all({});
                    } else if (round === false) {
                        return obj.pendings('T');
                    } else {
                        return obj.winners(round, 'T');
                    }
                }

                function roleRound(obj) {
                    if (round === undefined) {
                        round = _activeRound;
                    }
                    if (round === null) {
                        return obj.all({});
                    } else if (round === false) {
                        return obj.pendings('R');
                    } else {
                        return obj.winners(round, 'R');
                    }
                }
            },
            all: function(oFilter) {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/player/list?site=' + _siteId + '&app=' + _appId;

                _activeRound = null;
                _aPlayers.splice(0, _aPlayers.length);
                http2.post(url, oFilter, function(rsp) {
                    if (rsp.data.total) {
                        rsp.data.players.forEach(function(player) {
                            tmsSchema.forTable(player, _oApp._schemasById);
                            srvGroupApp.dealData(player);
                            _aPlayers.push(player);
                        });
                    }
                    defer.resolve(rsp.data.players);
                });
                return defer.promise;
            },
            winners: function(round, roundType) {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/round/winnersGet?app=' + _appId + '&rid=' + round.round_id + '&roundType=' + roundType;

                _activeRound = round;
                _aPlayers.splice(0, _aPlayers.length);
                http2.get(url, function(rsp) {
                    rsp.data.forEach(function(player) {
                        tmsSchema.forTable(player, _oApp._schemasById);
                        srvGroupApp.dealData(player);
                        _aPlayers.push(player);
                    });
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            pendings: function(roundType) {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/player/pendingsGet?app=' + _appId + '&roundType=' + roundType;

                _activeRound = false;
                _aPlayers.splice(0, _aPlayers.length);
                http2.get(url, function(rsp) {
                    rsp.data.forEach(function(player) {
                        tmsSchema.forTable(player, _oApp._schemasById);
                        srvGroupApp.dealData(player);
                        _aPlayers.push(player);
                    });
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            quitGroup: function(users) {
                var defer = $q.defer(),
                    url, eks = [];

                url = '/rest/pl/fe/matter/group/player/quitGroup?app=' + _appId;

                users.forEach(function($oUser) {
                    eks.push($oUser.enroll_key);
                });

                http2.post(url, eks, function(rsp) {
                    var oResult = rsp.data;
                    users.forEach(function(oUser) {
                        if (oResult[oUser.enroll_key] !== false) {
                            oUser.round_id = '';
                            oUser.round_title = '';
                            tmsSchema.forTable(oUser, _oApp._schemasById);
                        }
                    });
                    defer.resolve();
                });
                return defer.promise;
            },
            joinGroup: function(oRound, users) {
                var defer = $q.defer(),
                    url, eks = [];

                url = '/rest/pl/fe/matter/group/player/joinGroup?app=' + _appId;
                url += '&round=' + oRound.round_id;

                users.forEach(function(oUser) {
                    eks.push(oUser.enroll_key);
                });

                http2.post(url, eks, function(rsp) {
                    var oResult = rsp.data;
                    users.forEach(function(oUser) {
                        if (oResult[oUser.enroll_key] !== false) {
                            oUser.round_id = oRound.round_id;
                            oUser.round_title = oRound.title;
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

                url = '/rest/pl/fe/matter/group/player/add?site=' + _siteId + '&app=' + _appId;
                http2.post(url, player, function(rsp) {
                    tmsSchema.forTable(rsp.data, _oApp._schemasById);
                    srvGroupApp.dealData(rsp.data);
                    _aPlayers.splice(0, 0, rsp.data);
                    defer.resolve();
                });
                return defer.promise;
            },
            update: function(player, newPlayer) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/group/player/update?site=' + _siteId + '&app=' + _appId;
                url += '&ek=' + player.enroll_key;
                http2.post(url, newPlayer, function(rsp) {
                    angular.extend(player, rsp.data);
                    tmsSchema.forTable(player, _oApp._schemasById);
                    srvGroupApp.dealData(player);
                    defer.resolve();
                });
                return defer.promise;
            },
            remove: function(player) {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/matter/group/player/remove?site=' + _siteId + '&app=' + _appId + '&ek=' + player.enroll_key, function(rsp) {
                    _aPlayers.splice(_aPlayers.indexOf(player), 1);
                    defer.resolve();
                });
                return defer.promise;
            },
            empty: function() {
                var defer = $q.defer(),
                    vcode;

                vcode = prompt('是否要从【' + _oApp.title + '】删除所有用户？，若是，请输入活动名称。');
                if (vcode === _oApp.title) {
                    http2.get('/rest/pl/fe/matter/group/player/empty?site=' + _siteId + '&app=' + _appId, function(rsp) {
                        _aPlayers.splice(0, _aPlayers.length);
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
                                    var rec = _aPlayers[key];
                                    targetAndMsg.users.push({ userid: rec.userid, enroll_key: rec.enroll_key });
                                }
                            });
                        }
                        targetAndMsg.message = notify.message;

                        url = '/rest/pl/fe/matter/group/notice/send';
                        url += '?site=' + _siteId;
                        targetAndMsg.app = _appId;
                        targetAndMsg.tmplmsg = notify.tmplmsg.id;

                        http2.post(url, targetAndMsg, function(data) {
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
                    http2.get(url, function(rsp) {
                        defer.resolve(rsp.data);
                    });
                });

                return defer.promise;
            }
        }
    }]
}).controller('ctrlGroupEditor', ['$scope', '$uibModalInstance', '$sce', 'player', 'tmsSchema', 'srvGroupApp', 'srvGroupRound', 'srvGroupPlayer', function($scope, $mi, $sce, player, tmsSchema, srvGroupApp, srvGroupRound, srvGroupPlayer) {
    srvGroupApp.get().then(function(oApp) {
        $scope.app = oApp;
        srvGroupRound.list().then(function(rounds) {
            $scope.teamRounds = [];
            $scope.roleRounds = [];
            rounds.forEach(function(oRound) {
                switch (oRound.round_type) {
                    case 'T':
                        $scope.teamRounds.push(oRound);
                        break;
                    case 'R':
                        $scope.roleRounds.push(oRound);
                        break;
                }
            });
            var obj = {};
            if (player.role_rounds && player.role_rounds.length) {
                player.role_rounds.forEach(function(roundId) {
                    obj[roundId] = true;
                });
                player._role_rounds = obj;
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
        if ($scope.player._role_rounds) {
            for (var i in $scope.player._role_rounds) {
                if ($scope.player._role_rounds[i] && $scope.player.role_rounds.indexOf(i) === -1) {
                    $scope.player.role_rounds.push(i);
                }
                if (!$scope.player._role_rounds[i] && $scope.player.role_rounds.indexOf(i) !== -1) {
                    $scope.player.role_rounds.splice(i, 1);
                }
            }
        }
        oScopePlayer = $scope.player;
        oNewPlayer = {
            data: {},
            is_leader: oScopePlayer.is_leader,
            comment: oScopePlayer.comment,
            tags: oScopePlayer.aTags.join(','),
            round_id: oScopePlayer.round_id,
            role_rounds: oScopePlayer.role_rounds
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
    $scope.cancel = function() {
        $mi.dismiss('cancel');
    };
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