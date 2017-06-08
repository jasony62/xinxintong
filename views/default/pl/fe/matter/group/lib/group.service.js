angular.module('service.group', ['ui.bootstrap', 'ui.xxt']).
provider('srvGroupApp', function() {
    var _siteId, _appId, _oApp;
    this.config = function(siteId, appId) {
        _siteId = siteId;
        _appId = appId;
    };
    this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'mattersgallery', 'srvSite', function($q, $uibModal, http2, noticebox, mattersgallery, srvSite) {
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
                        var url, schemasById = {};
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
                            _oApp._schemasById = schemasById;
                        } catch (e) {
                            console.error('error', e);
                        }
                        _oApp.opUrl = 'http://' + location.host + '/rest/site/op/matter/group?site=' + _siteId + '&app=' + _appId;
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
            roundList: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/group/round/list?site=' + _siteId + '&app=' + _appId;
                http2.get(url, function(rsp) {
                    var rounds = rsp.data;
                    angular.forEach(rounds, function(round) {
                        round.extattrs = (round.extattrs && round.extattrs.length) ? JSON.parse(round.extattrs) : {};
                    });
                    defer.resolve(rounds);
                });

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
            importByApp: function(importSource) {
                var defer = $q.defer();
                $uibModal.open({
                    templateUrl: 'importByApp.html',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                        $scope2.app = _oApp;
                        $scope2.data = {
                            app: '',
                            appType: 'registration',
                            onlySpeaker: 'N'
                        };
                        _oApp.mission && ($scope2.data.sameMission = 'Y');
                        $scope2.importSource = importSource;
                        $scope2.cancel = function() {
                            $mi.dismiss();
                        };
                        $scope2.ok = function() {
                            $mi.close($scope2.data);
                        };
                        $scope2.$watch('data.appType', function(appType) {
                            if (!appType) return;
                            var url;
                            if (appType === 'mschema') {
                                srvSite.memberSchemaList().then(function(aMschemas) {
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
                                _oApp.mission && (url += '&mission=' + _oApp.mission.id);
                                http2.get(url, function(rsp) {
                                    $scope2.apps = $scope2.data.appType === 'wall' ? rsp.data : rsp.data.apps;
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
                        http2.post('/rest/pl/fe/matter/group/player/importByApp?site=' + _siteId + '&app=' + _appId, params, function(rsp) {
                            _oApp.sourceApp = data.app;
                            if (angular.isString(rsp.data.data_schemas)) {
                                _oApp.data_schemas = rsp.data.data_schemas ? JSON.parse(rsp.data.data_schemas) : '';
                            } else {
                                _oApp.data_schemas = rsp.data.data_schemas;
                            }
                            defer.resolve();
                        });
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
                delete _oApp.sourceApp;
                return this.update(['source_app', 'data_schemas']);
            },
            export: function() {
                var url = '/rest/pl/fe/matter/group/player/export?site=' + _siteId + '&app=' + _appId;
                window.open(url);
            },
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
                        rounds.forEach(function(round) {
                            round.extattrs = (round.extattrs && round.extattrs.length) ? JSON.parse(round.extattrs) : {};
                            _rounds.push(round);
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
                            var rule, schemas;
                            rule = angular.copy(oApp.group_rule);
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
                                    $scope.schemas.push(schema);
                                    if (rule.schema && rule.schema.id === schema.id) {
                                        rule.schema = schema;
                                    }
                                }
                            });
                            $scope.rule = rule;
                            $scope.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope.ok = function() {
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
                            defer.resolve();
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
                    defer.resolve();
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
    this.$get = ['$q', '$uibModal', 'noticebox', 'http2', 'cstApp', 'pushnotify', 'srvRecordConverter', 'srvGroupApp', function($q, $uibModal, noticebox, http2, cstApp, pushnotify, srvRecordConverter, srvGroupApp) {
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
            list: function(round) {
                if (round === undefined) {
                    round = _activeRound;
                }
                if (round === null) {
                    return this.all();
                } else if (round === false) {
                    return this.pendings();
                } else {
                    return this.winners(round);
                }
            },
            all: function() {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/player/list?site=' + _siteId + '&app=' + _appId;

                _activeRound = null;
                _aPlayers.splice(0, _aPlayers.length);
                http2.get(url, function(rsp) {
                    if (rsp.data.total) {
                        rsp.data.players.forEach(function(player) {
                            srvRecordConverter.forTable(player, _oApp._schemasById);
                            _aPlayers.push(player);
                        });
                    }
                    defer.resolve(rsp.data.players);
                });
                return defer.promise;
            },
            winners: function(round) {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/round/winnersGet?app=' + _appId + '&rid=' + round.round_id;

                _activeRound = round;
                _aPlayers.splice(0, _aPlayers.length);
                http2.get(url, function(rsp) {
                    rsp.data.forEach(function(player) {
                        srvRecordConverter.forTable(player, _oApp._schemasById);
                        _aPlayers.push(player);
                    });
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            pendings: function() {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/group/player/pendingsGet?app=' + _appId;

                _activeRound = false;
                _aPlayers.splice(0, _aPlayers.length);
                http2.get(url, function(rsp) {
                    rsp.data.forEach(function(player) {
                        srvRecordConverter.forTable(player, _oApp._schemasById);
                        _aPlayers.push(player);
                    });
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            quitGroup: function(round, players) {
                var defer = $q.defer(),
                    url, eks = [];

                url = '/rest/pl/fe/matter/group/player/quitGroup?site=' + _siteId + '&app=' + _appId;
                url += '&round=' + round.round_id;

                players.forEach(function(player) {
                    eks.push(player.enroll_key);
                });

                http2.post(url, eks, function(rsp) {
                    var result = rsp.data;
                    players.forEach(function(player) {
                        if (result[player.enroll_key] !== false) {
                            _aPlayers.splice(_aPlayers.indexOf(player), 1);
                        }
                    });
                    defer.resolve();
                });
                return defer.promise;
            },
            joinGroup: function(round, players) {
                var defer = $q.defer(),
                    url, eks = [];

                url = '/rest/pl/fe/matter/group/player/joinGroup?site=' + _siteId + '&app=' + _appId;
                url += '&round=' + round.round_id;

                players.forEach(function(player) {
                    eks.push(player.enroll_key);
                });

                http2.post(url, eks, function(rsp) {
                    var result = rsp.data;
                    players.forEach(function(player) {
                        if (result[player.enroll_key] !== false) {
                            if (_activeRound === false) {
                                _aPlayers.splice(_aPlayers.indexOf(player), 1);
                            } else if (_activeRound === null) {
                                player.round_id = round.round_id;
                                player.round_title = round.title;
                                srvRecordConverter.forTable(player, _oApp._schemasById);
                            }
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
                    srvRecordConverter.forTable(rsp.data, _oApp._schemasById);
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
                    srvRecordConverter.forTable(player, _oApp._schemasById);
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
                    templateUrl: '/views/default/pl/fe/matter/group/component/playerEditor.html?_=4',
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
                        } else {
                            targetAndMsg.criteria = _ins._oCriteria;
                        }
                        targetAndMsg.message = notify.message;

                        url = '/rest/pl/fe/matter/group/notice/send';
                        url += '?site=' + _siteId;
                        url += '&app=' + _appId;
                        url += '&tmplmsg=' + notify.tmplmsg.id;

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
                    url = '/rest/pl/fe/matter/group/notice/logList?batch=' + batch.id + '&aid=' + oApp.id;
                    http2.get(url, function(rsp) {
                        defer.resolve(rsp.data);
                    });
                });

                return defer.promise;
            }
        }
    }]
}).controller('ctrlGroupEditor', ['$scope', '$uibModalInstance', '$sce', 'player', 'srvRecordConverter', 'srvGroupApp', 'srvGroupRound', 'srvGroupPlayer', function($scope, $mi, $sce, player, srvRecordConverter, srvGroupApp, srvGroupRound, srvGroupPlayer) {
    srvGroupApp.get().then(function(oApp) {
        $scope.app = oApp;
        srvGroupRound.list().then(function(rounds) {
            $scope.rounds = rounds;
        });
        if (player.data) {
            oApp.data_schemas.forEach(function(schema) {
                if (player.data[schema.id]) {
                    srvRecordConverter.forEdit(schema, player.data);
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
        var c, p, col;
        p = {
            data: {},
            comment: $scope.player.comment,
            tags: $scope.player.aTags.join(','),
            round_id: $scope.player.round_id
        };
        $scope.player.tags = p.tags;
        if ($scope.player.data) {
            for (c in $scope.app.data_schemas) {
                col = $scope.app.data_schemas[c];
                p.data[col.id] = $scope.player.data[col.id];
            }
        }
        $mi.close({ player: p, tags: $scope.aTags });
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
