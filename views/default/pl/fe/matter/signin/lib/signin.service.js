define(['require', 'schema', 'page'], function(require, schemaLib, pageLib) {
    /**
     * BasesrvSigninRecord
     * srvSigninApp
     * srvSigninRound
     * srvSigninPage
     * srvSigninRecord
     */
    var BasesrvSigninRecord = function($q, http2, srvRecordConverter, noticebox, $uibModal) {
        this._oApp = null;
        this._oPage = null;
        this._oCriteria = null;
        this._aRecords = null;
        this._mapOfRoundsById = {};
        this.init = function(oApp, oPage, oCriteria, oRecords) {
            this._oApp = oApp;
            // schemas
            if (this._oApp._schemasById === undefined) {
                var schemasById = {};
                this._oApp.dataSchemas.forEach(function(schema) {
                    schemasById[schema.id] = schema;
                });
                this._oApp._schemasById = schemasById;
            }
            // pagination
            this._oPage = oPage;
            angular.extend(this._oPage, {
                at: 1,
                size: 30,
                orderBy: 'time',
                joinParams: function() {
                    var p;
                    p = '&page=' + this.at + '&size=' + this.size;
                    p += '&orderby=' + this.orderBy;
                    p += '&rid=' + (this.byRound ? this.byRound : 'all');
                    return p;
                },
                setTotal: function(total) {
                    var lastNumber;
                    this.total = total;
                    this.numbers = [];
                    lastNumber = this.total > 0 ? Math.ceil(this.total / this.size) : 1;
                    for (var i = 1; i <= lastNumber; i++) {
                        this.numbers.push(i);
                    }
                }
            });
            // criteria
            this._oCriteria = oCriteria;
            angular.extend(this._oCriteria, {
                record: {
                    verified: ''
                },
                tags: [],
                data: {},
                keyword: ''
            });
            // records
            this._aRecords = oRecords;
        };
        this._bSearch = function(url) {
            var that = this,
                defer = $q.defer();
            http2.post(url, that._oCriteria, function(rsp) {
                var records;
                if (rsp.data) {
                    records = rsp.data.records ? rsp.data.records : [];
                    rsp.data.total && (that._oPage.total = rsp.data.total);
                    that._oPage.setTotal(rsp.data.total);
                } else {
                    records = [];
                }
                records.forEach(function(record) {
                    that._bConvertRecord4Table(record);
                    record._signinLate = {};
                    if (that._oApp.rounds) {
                        that._oApp.rounds.forEach(function(round) {
                            if (record.signin_log && record.signin_log[round.rid]) {
                                record._signinLate[round.rid] = round.late_at && round.late_at < record.signin_log[round.rid] - 60;
                            }
                        });
                    }
                    that._aRecords.push(record);
                });
                defer.resolve(records);
            });
            return defer.promise;
        }
        this._bBatchVerify = function(rows, url) {
            var eks = [],
                selectedRecords = [],
                that = this;
            for (var p in rows.selected) {
                if (rows.selected[p] === true) {
                    eks.push(that._aRecords[p].enroll_key);
                    selectedRecords.push(that._aRecords[p]);
                }
            }
            if (eks.length) {
                http2.post(url, {
                    eks: eks
                }, function(rsp) {
                    selectedRecords.forEach(function(record) {
                        record.verified = 'Y';
                    });
                    noticebox.success('完成操作');
                });
            }
        };
        this._bGet = function(oSigninApp, method) {
            oSigninApp.tags = (!oSigninApp.tags || oSigninApp.tags.length === 0) ? [] : oSigninApp.tags.split(',');
            if (oSigninApp.groupApp && oSigninApp.groupApp.dataSchemas) {
                if (oSigninApp.groupApp.rounds && oSigninApp.groupApp.rounds.length) {
                    var roundDS = {
                            id: '_round_id',
                            type: 'single',
                            title: '分组名称',
                        },
                        ops = [];
                    oSigninApp.groupApp.rounds.forEach(function(round) {
                        ops.push({
                            v: round.round_id,
                            l: round.title
                        });
                    });
                    roundDS.ops = ops;
                    oSigninApp.groupApp.dataSchemas.splice(0, 0, roundDS);
                }
            }
            method(oSigninApp);
            oSigninApp.dataSchemas.forEach(function(oSchema) {
                schemaLib._upgrade(oSchema, oSigninApp);
            });
            oSigninApp.pages.forEach(function(page) {
                pageLib.enhance(page, oSigninApp._schemasById);
            });
        };
        this._bFilter = function() {
            var defer = $q.defer(),
                that = this;
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/signin/component/recordFilter.html?_=3',
                controller: 'ctrlSigninFilter',
                windowClass: 'auto-height',
                backdrop: 'static',
                resolve: {
                    dataSchemas: function() {
                        return that._oApp.dataSchemas;
                    },
                    criteria: function() {
                        return angular.copy(that._oCriteria);
                    }
                }
            }).result.then(function(criteria) {
                defer.resolve();
                angular.extend(that._oCriteria, criteria);
                that.search(1).then(function() {
                    defer.resolve();
                });
            });
            return defer.promise;
        };
        this._bConvertRecord4Table = function(record) {
            var round, signinAt,
                signinLate = {},
                that = this;

            srvRecordConverter.forTable(record, that._oApp._schemasById);
            // signin log
            for (var roundId in that._mapOfRoundsById) {
                round = that._mapOfRoundsById[roundId];
                if (record.signin_log && round.late_at > 0) {
                    signinAt = parseInt(record.signin_log[roundId]);
                    if (signinAt) {
                        // 忽略秒的影响
                        signinLate[roundId] = (signinAt > parseInt(round.late_at) + 59);
                    }
                }
            }
            record._signinLate = signinLate;

            return record;
        };
    };
    angular.module('service.signin', ['ui.bootstrap', 'ui.xxt', 'service.matter']).
    provider('srvSigninApp', function() {
        function _mapSchemas(app) {
            var mapOfSchemaByType = {},
                mapOfSchemaById = {},
                enrollDataSchemas = [],
                groupDataSchemas = [],
                canFilteredSchemas = [];

            app.dataSchemas.forEach(function(schema) {
                mapOfSchemaByType[schema.type] === undefined && (mapOfSchemaByType[schema.type] = []);
                mapOfSchemaByType[schema.type].push(schema.id);
                mapOfSchemaById[schema.id] = schema;
                if (false === /image|file/.test(schema.type)) {
                    canFilteredSchemas.push(schema);
                }
            });
            // 关联的报名登记项
            if (app.enrollApp && app.enrollApp.dataSchemas) {
                app.enrollApp.dataSchemas.forEach(function(item) {
                    if (mapOfSchemaById[item.id] === undefined) {
                        mapOfSchemaById[item.id] = item;
                        enrollDataSchemas.push(item);
                    }
                });
            }
            // 关联的分组登记项
            if (app.groupApp && app.groupApp.dataSchemas) {
                app.groupApp.dataSchemas.forEach(function(item) {
                    if (mapOfSchemaById[item.id] === undefined) {
                        mapOfSchemaById[item.id] = item;
                        groupDataSchemas.push(item);
                    }
                });
            }

            app._schemasByType = mapOfSchemaByType;
            app._schemasById = mapOfSchemaById;
            app._schemasCanFilter = canFilteredSchemas;
            app._schemasFromEnrollApp = enrollDataSchemas;
            app._schemasFromGroupApp = groupDataSchemas;

            return {
                byType: mapOfSchemaByType,
                byId: mapOfSchemaById,
                enrollData: enrollDataSchemas,
                groupData: groupDataSchemas,
                canFilter: canFilteredSchemas
            }
        }
        var siteId, appId, app, defaultInputPage,
            pages4NonMember = [],
            pages4Nonfan = [],
            _getAppDeferred = false;;

        this.app = function() {
            return app;
        };
        this.config = function(site, app, access) {
            siteId = site;
            appId = app;
            accessId = access;
        }
        this.$get = ['$q', 'http2', 'noticebox', 'srvSite', '$uibModal', function($q, http2, noticebox, srvSite, $uibModal) {
            var _ins = new BasesrvSigninRecord();
            return {
                get: function() {
                    var url;

                    if (_getAppDeferred) {
                        return _getAppDeferred.promise;
                    }
                    _getAppDeferred = $q.defer();
                    url = '/rest/pl/fe/matter/signin/get?site=' + siteId + '&id=' + appId;
                    http2.get(url, function(rsp) {
                        app = rsp.data;
                        _ins._bGet(app, _mapSchemas);
                        _getAppDeferred.resolve(app);
                    });

                    return _getAppDeferred.promise;
                },
                opGet: function() {
                    var url, _getAppDeferred = false;

                    if (_getAppDeferred) {
                        return _getAppDeferred.promise;
                    }
                    _getAppDeferred = $q.defer();
                    url = '/rest/site/op/matter/signin/get?site=' + siteId + '&app=' + appId + '&accessToken=' + accessId;
                    http2.get(url, function(rsp) {
                        _opApps = rsp.data, _opApp = rsp.data.app, _opPage = rsp.data.page;
                        _ins._bGet(_opApp, _mapSchemas);
                        _getAppDeferred.resolve(_opApps);
                    });

                    return _getAppDeferred.promise;
                },
                update: function(names) {
                    var defer = $q.defer(),
                        modifiedData = {},
                        url;

                    angular.isString(names) && (names = [names]);
                    names.forEach(function(name) {
                        if (name === 'data_schemas') {
                            modifiedData.data_schemas = app.dataSchemas;
                        } else if (name === 'tags') {
                            modifiedData.tags = app.tags.join(',');
                        } else {
                            modifiedData[name] = app[name];
                        }
                    });
                    url = '/rest/pl/fe/matter/signin/update?site=' + siteId + '&app=' + appId;
                    http2.post(url, modifiedData, function(rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                resetEntryRule: function() {
                    http2.get('/rest/pl/fe/matter/signin/entryRuleReset?site=' + siteId + '&app=' + appId, function(rsp) {
                        app.entry_rule = rsp.data;
                    });
                },
                changeUserScope: function(ruleScope, sns, memberSchemas, defaultInputPage) {
                    var entryRule = app.entry_rule;
                    entryRule.scope = ruleScope;
                    switch (ruleScope) {
                        case 'member':
                            entryRule.member === undefined && (entryRule.member = {});
                            entryRule.other === undefined && (entryRule.other = {});
                            entryRule.other.entry = '$memberschema';
                            break;
                        case 'sns':
                            entryRule.sns === undefined && (entryRule.sns = {});
                            entryRule.other === undefined && (entryRule.other = {});
                            entryRule.other.entry = '$mpfollow';
                            Object.keys(sns).forEach(function(snsName) {
                                entryRule.sns[snsName] = {
                                    entry: defaultInputPage ? defaultInputPage.name : ''
                                };
                            });
                            break;
                        default:
                    }
                    this.update('entry_rule');
                },
                assignMission: function() {
                    var _this = this;
                    srvSite.openGallery({
                        matterTypes: [{
                            value: 'mission',
                            title: '项目',
                            url: '/rest/pl/fe/matter'
                        }],
                        singleMatter: true
                    }).then(function(missions) {
                        var matter;
                        if (missions.matters.length === 1) {
                            matter = {
                                id: appId,
                                type: 'signin'
                            };
                            http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + siteId + '&id=' + missions.matters[0].id, matter, function(rsp) {
                                var mission = rsp.data,
                                    updatedFields = ['mission_id'];

                                app.mission = mission;
                                app.mission_id = mission.id;
                                if (!app.pic || app.pic.length === 0) {
                                    app.pic = mission.pic;
                                    updatedFields.push('pic');
                                }
                                if (!app.summary || app.summary.length === 0) {
                                    app.summary = mission.summary;
                                    updatedFields.push('summary');
                                }
                                _this.update(updatedFields);
                            });
                        }
                    })
                },
                quitMission: function() {
                    var _this = this,
                        matter = {
                            id: appId,
                            type: 'signin',
                            title: app.title
                        };
                    http2.post('/rest/pl/fe/matter/mission/matter/remove?site=' + siteId + '&id=' + app.mission_id, matter, function(rsp) {
                        delete app.mission;
                        app.mission_id = null;
                        _this.update(['mission_id']);
                    });
                },
                choosePhase: function() {
                    var _this = this,
                        phaseId = app.mission_phase_id,
                        i, phase, newPhase;

                    app.mission.phases.forEach(function(phase) {
                        app.title = app.title.replace('-' + phase.title, '');
                        if (phase.phase_id === phaseId) {
                            newPhase = phase;
                        }
                    });
                    if (newPhase) {
                        app.title += '-' + newPhase.title;
                    }
                    _this.update(['mission_phase_id', 'title']).then(function() {
                        /* 如果活动只有一个轮次，且没有指定过时间，用阶段的时间更新 */
                        if (newPhase && app.rounds.length === 1) {
                            (function() {
                                var round = app.rounds[0],
                                    url;
                                if (round.start_at === '0' && round.end_at === '0') {
                                    url = '/rest/pl/fe/matter/signin/round/update';
                                    url += '?site=' + $scope.siteId;
                                    url += '&app=' + $scope.id;
                                    url += '&rid=' + round.rid;
                                    http2.post(url, {
                                        start_at: newPhase.start_at,
                                        end_at: newPhase.end_at
                                    }, function(rsp) {
                                        round.start_at = newPhase.start_at;
                                        round.end_at = newPhase.end_at;
                                    });
                                }
                            })();
                        }
                    });
                },
                remove: function() {
                    var defer = $q.defer(),
                        url;

                    url = '/rest/pl/fe/matter/signin/remove?site=' + siteId + '&app=' + appId;
                    http2.get(url, function(rsp) {
                        defer.resolve();
                    });

                    return defer.promise;
                },
                jumpPages: function() {
                    var defaultInput, inapp = [],
                        pages = app.pages,
                        pages4NonMember = [{
                            name: '$memberschema',
                            title: '填写联系人信息'
                        }],
                        pages4Nonfan = [{
                            name: '$mpfollow',
                            title: '提示关注'
                        }];

                    pages.forEach(function(page) {
                        var newPage = {
                            name: page.name,
                            title: page.title
                        };
                        inapp.push(newPage);
                        pages4NonMember.push(newPage);
                        pages4Nonfan.push(newPage);
                        page.type === 'I' && (defaultInput = newPage);
                    });

                    return {
                        inapp: inapp,
                        nonMember: pages4NonMember,
                        nonfan: pages4Nonfan,
                        defaultInput: defaultInput
                    }
                },
                assignEnrollApp: function() {
                    var _this = this;
                    $uibModal.open({
                        templateUrl: 'assignEnrollApp.html',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            function listEnrollApp() {
                                var url = '/rest/pl/fe/matter/enroll/list?site=' + siteId + '&scenario=registration&size=999';
                                $scope2.data.sameMission === 'Y' && (url += '&mission=' + app.mission.id);
                                http2.get(url, function(rsp) {
                                    $scope2.apps = rsp.data.apps;
                                });
                            }
                            $scope2.app = app;
                            $scope2.data = {
                                filter: {},
                                source: ''
                            };
                            app.mission && ($scope2.data.sameMission = 'Y');
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope2.ok = function() {
                                $mi.close($scope2.data);
                            };
                            $scope2.$watch('data.sameMission', listEnrollApp);
                        }],
                        backdrop: 'static'
                    }).result.then(function(data) {
                        app.enroll_app_id = data.source;
                        _this.update('enroll_app_id').then(function(rsp) {
                            var url = '/rest/pl/fe/matter/enroll/get?site=' + siteId + '&id=' + app.enroll_app_id;
                            http2.get(url, function(rsp) {
                                rsp.data.data_schemas = rsp.data.dataSchemas;
                                app.enrollApp = rsp.data;
                            });
                            for (var i = app.data_schemas.length - 1; i > 0; i--) {
                                if (app.data_schemas[i].id === 'mobile') {
                                    app.data_schemas[i].requireCheck = 'Y';
                                    break;
                                }
                            }
                            _this.update('data_schemas');
                        });
                    });
                },
                cancelEnrollApp: function() {
                    var _this = this;
                    app.enroll_app_id = '';
                    delete app.enrollApp;
                    this.update('enroll_app_id').then(function() {
                        app.data_schemas.forEach(function(dataSchema) {
                            delete dataSchema.requireCheck;
                        });
                        _this.update('data_schemas');
                    });
                },
                assignGroupApp: function() {
                    var _this = this;
                    $uibModal.open({
                        templateUrl: 'assignGroupApp.html',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            function listEnrollApp() {
                                var url = '/rest/pl/fe/matter/group/list?site=' + siteId;
                                $scope2.data.sameMission === 'Y' && (url += '&mission=' + app.mission.id);
                                http2.get(url, function(rsp) {
                                    $scope2.apps = rsp.data.apps;
                                });
                            }
                            $scope2.app = app;
                            $scope2.data = {
                                filter: {},
                                source: ''
                            };
                            app.mission && ($scope2.data.sameMission = 'Y');
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope2.ok = function() {
                                $mi.close($scope2.data);
                            };
                            $scope2.$watch('data.sameMission', listEnrollApp);
                        }],
                        backdrop: 'static'
                    }).result.then(function(data) {
                        app.group_app_id = data.source;
                        _this.update('group_app_id').then(function(rsp) {
                            var url = '/rest/pl/fe/matter/group/get?site=' + siteId + '&app=' + app.group_app_id;
                            http2.get(url, function(rsp) {
                                var groupApp = rsp.data,
                                    roundDS = {
                                        id: '_round_id',
                                        type: 'single',
                                        title: '分组名称',
                                    },
                                    ops = [];
                                groupApp.rounds.forEach(function(round) {
                                    ops.push({
                                        v: round.round_id,
                                        l: round.title
                                    });
                                });
                                roundDS.ops = ops;
                                groupApp.dataSchemas.splice(0, 0, roundDS);
                                app.groupApp = groupApp;
                            });
                        });
                    });
                },
                cancelGroupApp: function() {
                    var _this = this;
                    app.group_app_id = '';
                    delete app.groupApp;
                    this.update('group_app_id').then(function() {
                        app.data_schemas.forEach(function(dataSchema) {
                            delete dataSchema.requireCheck;
                        });
                        _this.update('data_schemas');
                    });
                }
            };
        }];
    }).provider('srvSigninRound', function() {
        var siteId, appId;
        this.setSiteId = function(id) {
            siteId = id;
        };
        this.setAppId = function(id) {
            appId = id;
        };
        this.$get = ['$q', 'http2', 'noticebox', '$uibModal', function($q, http2, noticebox, $uibModal) {
            return {
                batch: function(app) {
                    var defer = $q.defer();
                    $uibModal.open({
                        templateUrl: 'batchRounds.html',
                        backdrop: 'static',
                        resolve: {
                            app: function() {
                                return app;
                            }
                        },
                        controller: ['$scope', '$uibModalInstance', 'app', function($scope2, $mi, app) {
                            var params = {
                                timesOfDay: 2,
                                overwrite: 'Y'
                            };
                            if (app.mission && app.mission_phase_id) {
                                (function() {
                                    var i, phase;
                                    for (i = app.mission.phases.length - 1; i >= 0; i--) {
                                        phase = app.mission.phases[i];
                                        if (app.mission_phase_id === phase.phase_id) {
                                            params.start_at = phase.start_at;
                                            params.end_at = phase.end_at;
                                            break;
                                        }
                                    }
                                })();
                            } else {
                                /*设置阶段的缺省起止时间*/
                                (function() {
                                    var nextDay = new Date();
                                    nextDay.setTime(nextDay.getTime() + 86400000);
                                    params.start_at = nextDay.setHours(0, 0, 0, 0) / 1000;
                                    params.end_at = nextDay.setHours(23, 59, 59, 0) / 1000;
                                })();
                            }
                            $scope2.params = params;
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope2.ok = function() {
                                $mi.close($scope2.params);
                            };
                        }]
                    }).result.then(function(params) {
                        http2.post('/rest/pl/fe/matter/signin/round/batch?site=' + siteId + '&app=' + appId, params, function(rsp) {
                            if (params.overwrite === 'Y') {
                                app.rounds = rsp.data;
                            } else {
                                app.rounds = rounds.concat(rsp.data);
                            }
                            defer.resolve(app.rounds);
                        });
                    });
                    return defer.promise;
                },
                add: function(rounds) {
                    var newRound = {
                        title: '轮次' + (rounds.length + 1),
                        start_at: Math.round((new Date()).getTime() / 1000),
                        end_at: Math.round((new Date()).getTime() / 1000) + 7200,
                    };
                    http2.post('/rest/pl/fe/matter/signin/round/add?site=' + siteId + '&app=' + appId, newRound, function(rsp) {
                        rounds.push(rsp.data);
                    });
                },
                update: function(round, prop) {
                    var url = '/rest/pl/fe/matter/signin/round/update',
                        posted = {};
                    url += '?site=' + siteId;
                    url += '&app=' + appId;
                    url += '&rid=' + round.rid;
                    posted[prop] = round[prop];
                    http2.post(url, posted, function(rsp) {
                        noticebox.success('完成保存');
                    });
                },
                remove: function(round, rounds) {
                    var url;
                    if (window.confirm('确定删除：' + round.title + '？')) {
                        url = '/rest/pl/fe/matter/signin/round/remove';
                        url += '?site=' + siteId;
                        url += '&app=' + appId;
                        url += '&rid=' + round.rid;
                        http2.get(url, function(rsp) {
                            rounds.splice(rounds.indexOf(round), 1);
                        });
                    }
                },
                qrcode: function(app, sns, round, appUrl) {
                    $uibModal.open({
                        templateUrl: 'roundQrcode.html',
                        backdrop: 'static',
                        controller: ['$scope', '$timeout', '$uibModalInstance', function($scope2, $timeout, $mi) {
                            var popover = {
                                    title: round.title,
                                    url: appUrl + '&round=' + round.rid,
                                },
                                zeroClipboard;

                            popover.qrcode = '/rest/site/fe/matter/signin/qrcode?site=' + siteId + '&url=' + encodeURIComponent(popover.url);
                            $scope2.popover = popover;
                            $scope2.app = app;
                            $scope2.sns = sns;
                            $scope2.downloadQrcode = function(url) {
                                $('<a href="' + url + '" download="' + app.title + '_' + round.title + '_签到二维码.png"></a>')[0].click();
                            };
                            $scope2.createWxQrcode = function() {
                                var url, params = {
                                    round: round.rid
                                };

                                url = '/rest/pl/fe/site/sns/wx/qrcode/create?site=' + siteId;
                                url += '&matter_type=signin&matter_id=' + appId;
                                url += '&expire=864000';

                                http2.post(url, {
                                    params: params
                                }, function(rsp) {
                                    $scope2.qrcode = rsp.data;
                                });
                            };
                            $scope2.downloadWxQrcode = function() {
                                $('<a href="' + $scope2.qrcode.pic + '" download="' + app.title + '_' + round.title + '_签到二维码.jpeg"></a>')[0].click();
                            };
                            if (app.entry_rule.scope === 'sns' && sns.wx) {
                                if (sns.wx.can_qrcode === 'Y') {
                                    http2.get('/rest/pl/fe/matter/signin/wxQrcode?site=' + siteId + '&app=' + appId + '&round=' + round.rid, function(rsp) {
                                        var qrcodes = rsp.data;
                                        $scope2.qrcode = qrcodes.length ? qrcodes[0] : false;
                                    });
                                }
                            }
                            $timeout(function() {
                                new ZeroClipboard(document.querySelector('#copyURL'));
                            });
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope2.ok = function() {
                                $mi.dismiss();
                            };
                        }]
                    });
                }
            };
        }];
    }).provider('srvSigninPage', function() {
        var siteId, appId;
        this.setSiteId = function(id) {
            siteId = id;
        };
        this.setAppId = function(id) {
            appId = id;
        };
        this.$get = ['$q', 'http2', 'noticebox', function($q, http2, noticebox) {
            return {
                update: function(page, names) {
                    var defer = $q.defer(),
                        updated = {},
                        url;

                    angular.isString(names) && (names = [names]);
                    names.forEach(function(name) {
                        if (name === 'html') {
                            updated.html = encodeURIComponent(page.html);
                        } else {
                            updated[name] = page[name];
                        }
                    });
                    url = '/rest/pl/fe/matter/signin/page/update';
                    url += '?site=' + siteId;
                    url += '&app=' + appId;
                    url += '&pid=' + page.id;
                    url += '&cname=' + page.code_name;
                    http2.post(url, updated, function(rsp) {
                        page.$$modified = false;
                        defer.resolve();
                        noticebox.success('完成保存');
                    });

                    return defer.promise;
                },
                remove: function(page) {
                    var defer = $q.defer(),
                        url = '/rest/pl/fe/matter/signin/page/remove';

                    url += '?site=' + siteId;
                    url += '&app=' + appId;
                    url += '&pid=' + page.id;
                    url += '&cname=' + page.code_name;
                    http2.get(url, function(rsp) {
                        defer.resolve();
                        noticebox.success('完成删除');
                    });

                    return defer.promise;
                }
            };
        }];
    }).provider('srvSigninRecord', function() {
        var siteId, appId;
        this.config = function(site, app) {
            siteId = site;
            appId = app;
        }
        this.$get = ['$q', '$uibModal', '$sce', 'http2', 'noticebox', 'pushnotify', 'cstApp', 'srvRecordConverter', function($q, $uibModal, $sce, http2, noticebox, pushnotify, cstApp, srvRecordConverter) {
            var _ins = new BasesrvSigninRecord($q, http2, srvRecordConverter, noticebox, $uibModal);

            _ins.search = function(pageNumber) {
                var url;

                this._aRecords.splice(0, this._aRecords.length);
                pageNumber && (this._oPage.at = pageNumber);
                url = '/rest/pl/fe/matter/signin/record/list';
                url += '?site=' + this._oApp.siteid;
                url += '&app=' + this._oApp.id;
                url += this._oPage.joinParams();

                return _ins._bSearch(url);
            };
            _ins.filter = function() {
                return _ins._bFilter();
            };
            _ins.get = function(ek) {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/matter/signin/record/get?ek=' + ek, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.add = function(newRecord) {
                http2.post('/rest/pl/fe/matter/signin/record/add?site=' + siteId + '&app=' + appId, newRecord, function(rsp) {
                    var record = rsp.data;
                    _ins._bConvertRecord4Table(record);
                    _ins._aRecords.splice(0, 0, record);
                });
            };
            _ins.update = function(record, updated) {
                http2.post('/rest/pl/fe/matter/signin/record/update?site=' + siteId + '&app=' + appId + '&ek=' + record.enroll_key, updated, function(rsp) {
                    angular.extend(record, rsp.data);
                    _ins._bConvertRecord4Table(record);
                });
            };
            _ins.editRecord = function(record) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/signin/component/recordEditor.html?_=4',
                    controller: 'ctrlSigninEdit',
                    backdrop: 'static',
                    windowClass: 'auto-height middle-width',
                    resolve: {
                        record: function() {
                            if (record === undefined) {
                                return {
                                    aid: appId,
                                    tags: '',
                                    data: {}
                                };
                            } else {
                                record.aid = appId;
                                return angular.copy(record);
                            }
                        },
                    }
                }).result.then(function(updated) {
                    if (record) {
                        _ins.update(record, updated[0]);
                    } else {
                        _ins.add(updated[0]);
                    }
                });
            };
            _ins.batchTag = function(rows) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/batchTag.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                        $scope2.appTags = angular.copy(_ins._oApp.tags);
                        $scope2.data = {
                            tags: []
                        };
                        $scope2.ok = function() {
                            $mi.close({
                                tags: $scope2.data.tags,
                                appTags: $scope2.appTags
                            });
                        };
                        $scope2.cancel = function() {
                            $mi.dismiss();
                        };
                        $scope2.$on('tag.xxt.combox.done', function(event, aSelected) {
                            var aNewTags = [];
                            for (var i in aSelected) {
                                var existing = false;
                                for (var j in $scope2.data.tags) {
                                    if (aSelected[i] === $scope2.data.tags[j]) {
                                        existing = true;
                                        break;
                                    }
                                }!existing && aNewTags.push(aSelected[i]);
                            }
                            $scope2.data.tags = $scope2.data.tags.concat(aNewTags);
                        });
                        $scope2.$on('tag.xxt.combox.add', function(event, newTag) {
                            $scope2.data.tags.push(newTag);
                            $scope2.appTags.indexOf(newTag) === -1 && $scope2.appTags.push(newTag);
                        });
                        $scope2.$on('tag.xxt.combox.del', function(event, removed) {
                            $scope2.data.tags.splice($scope2.data.tags.indexOf(removed), 1);
                        });
                    }],
                    backdrop: 'static'
                }).result.then(function(result) {
                    var record, selectedRecords = [],
                        selectedeks = [],
                        posted = {};

                    for (var p in rows.selected) {
                        if (rows.selected[p] === true) {
                            record = _ins._aRecords[p];
                            selectedeks.push(record.enroll_key);
                            selectedRecords.push(record);
                        }
                    }

                    if (selectedeks.length) {
                        posted = {
                            eks: selectedeks,
                            tags: result.tags,
                            appTags: result.appTags
                        };
                        http2.post('/rest/pl/fe/matter/signin/record/batchTag?site=' + siteId + '&app=' + appId, posted, function(rsp) {
                            var m, n, newTag;
                            n = result.tags.length;
                            selectedRecords.forEach(function(record) {
                                if (!record.tags || record.length === 0) {
                                    record.tags = result.tags.join(',');
                                } else {
                                    for (m = 0; m < n; m++) {
                                        newTag = result.tags[m];
                                        (',' + record.tags + ',').indexOf(newTag) === -1 && (record.tags += ',' + newTag);
                                    }
                                }
                            });
                            _ins._oApp.tags = result.appTags;
                        });
                    }
                });
            };
            _ins.remove = function(record) {
                if (window.confirm('确认删除？')) {
                    http2.get('/rest/pl/fe/matter/signin/record/remove?site=' + siteId + '&app=' + appId + '&key=' + record.enroll_key, function(rsp) {
                        var i = _ins._aRecords.indexOf(record);
                        _ins._aRecords.splice(i, 1);
                        _ins._oPage.total = _ins._oPage.total - 1;
                    });
                }
            };
            _ins.empty = function() {
                var vcode;
                vcode = prompt('是否要删除所有登记信息？，若是，请输入活动名称。');
                if (vcode === _oApp.title) {
                    http2.get('/rest/pl/fe/matter/signin/record/empty?site=' + siteId + '&app=' + appId, function(rsp) {
                        _ins._aRecords.splice(0, _ins._aRecords.length);
                        _ins._oPage.total = 0;
                        _ins._oPage.at = 1;
                    });
                }
            };
            _ins.verifyAll = function() {
                if (window.confirm('确定审核通过所有记录（共' + _oPage.total + '条）？')) {
                    http2.get('/rest/pl/fe/matter/signin/record/verifyAll?site=' + siteId + '&app=' + appId, function(rsp) {
                        _ins._aRecords.forEach(function(record) {
                            record.verified = 'Y';
                        });
                        noticebox.success('完成操作');
                    });
                }
            };
            _ins.batchVerify = function(rows) {
                var url;

                url = '/rest/pl/fe/matter/signin/record/batchVerify';
                url += '?site=' + siteId;
                url += '&app=' + appId;

                return _ins._bBatchVerify(rows, url);
            };
            _ins.notify = function(rows) {
                var options = {
                    matterTypes: cstApp.notifyMatter,
                    sender: 'signin:' + appId
                };
                _ins._oApp.mission && (options.missionId = _ins._oApp.mission.id);
                pushnotify.open(siteId, function(notify) {
                    var url, targetAndMsg = {};
                    if (notify.matters.length) {
                        if (rows) {
                            targetAndMsg.users = [];
                            Object.keys(rows.selected).forEach(function(key) {
                                if (rows.selected[key] === true) {
                                    var rec = _ins._aRecords[key];
                                    targetAndMsg.users.push({ userid: rec.userid, enroll_key: rec.enroll_key });
                                }
                            });
                        } else {
                            targetAndMsg.criteria = _oCriteria;
                        }
                        targetAndMsg.message = notify.message;

                        url = '/rest/pl/fe/matter/signin/notice/send';
                        url += '?site=' + siteId;
                        url += '&app=' + appId;
                        url += '&tmplmsg=' + notify.tmplmsg.id;
                        url += _ins._oPage.joinParams();

                        http2.post(url, targetAndMsg, function(data) {
                            noticebox.success('发送成功');
                        });
                    }
                }, options);
            };
            _ins.export = function() {
                var url, params = {
                    criteria: _ins._oCriteria
                };

                url = '/rest/pl/fe/matter/signin/record/export';
                url += '?site=' + siteId + '&app=' + appId;
                window.open(url);
            };
            _ins.exportImage = function() {
                var url, params = {
                    criteria: _ins._oCriteria
                };

                url = '/rest/pl/fe/matter/signin/record/exportImage';
                url += '?site=' + siteId + '&app=' + appId;
                window.open(url);
            };
            _ins.chooseImage = function(imgFieldName) {
                var defer = $q.defer();
                if (imgFieldName !== null) {
                    var ele = document.createElement('input');
                    ele.setAttribute('type', 'file');
                    ele.addEventListener('change', function(evt) {
                        var i, cnt, f, type;
                        cnt = evt.target.files.length;
                        for (i = 0; i < cnt; i++) {
                            f = evt.target.files[i];
                            type = {
                                ".jp": "image/jpeg",
                                ".pn": "image/png",
                                ".gi": "image/gif"
                            }[f.name.match(/\.(\w){2}/g)[0] || ".jp"];
                            f.type2 = f.type || type;
                            var reader = new FileReader();
                            reader.onload = (function(theFile) {
                                return function(e) {
                                    var img = {};
                                    img.imgSrc = e.target.result.replace(/^.+(,)/, "data:" + theFile.type2 + ";base64,");
                                    defer.resolve(img);
                                };
                            })(f);
                            reader.readAsDataURL(f);
                        }
                    }, false);
                    ele.click();
                }
                return defer.promise;
            };
            _ins.syncByEnroll = function(record) {
                var _this = this,
                    defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/signin/record/matchEnroll';
                url += '?site=' + siteId;
                url += '&app=' + appId;

                http2.post(url, record.data, function(rsp) {
                    var matched;
                    if (rsp.data && rsp.data.length === 1) {
                        matched = rsp.data[0];
                        _ins._oApp._schemasFromEnrollApp.forEach(function(col) {
                            if (matched[col.id]) {
                                _this.convertRecord4Edit(col, matched);
                            }
                        });
                        angular.extend(record.data, matched);
                    } else {
                        alert('没有找到匹配的记录，请检查数据是否一致');
                    }
                });
            };
            _ins.convertRecord4Edit = function(col, data) {
                srvRecordConverter.forEdit(col, data);
                return data;
            };
            _ins.importByEnrollApp = function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/signin/record/importByEnrollApp';
                url += '?site=' + siteId + '&app=' + appId;

                http2.get(url, function(rsp) {
                    noticebox.info('更新了（' + rsp.data + '）条数据');
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            };
            return _ins;
        }];
    }).provider('srvOpSigninRecord', function() {
        var _siteId, _appId, _accessId;
        this.config = function(siteId, appId, accessId) {
            _siteId = siteId;
            _appId = appId;
            _accessId = accessId;
        };
        this.$get = ['$q', 'http2', 'noticebox', '$uibModal', 'srvRecordConverter', function($q, http2, noticebox, $uibModal, srvRecordConverter) {
            var _ins = new BasesrvSigninRecord($q, http2, srvRecordConverter, noticebox, $uibModal);
            _ins.search = function(pageNumber) {
                var url;

                this._aRecords.splice(0, this._aRecords.length);
                pageNumber && (this._oPage.at = pageNumber);
                url = '/rest/site/op/matter/signin/record/list';
                url += '?site=' + this._oApp.siteid;
                url += '&app=' + this._oApp.id;
                url += '&accessToken=' + _accessId;
                url += this._oPage.joinParams();

                return _ins._bSearch(url);
            };
            _ins.batchVerify = function(rows) {
                var url;

                url = '/rest/site/op/matter/signin/record/batchVerify';
                url += '?site=' + _siteId;
                url += '&app=' + _appId;
                url += '&accessToken=' + _accessId;

                return _ins._bBatchVerify(rows, url);
            };
            _ins.filter = function() {
                return _ins._bFilter();
            };
            _ins.remove = function(record) {
                if (window.confirm('确认删除？')) {
                    http2.get('/rest/site/op/matter/signin/record/remove?site=' + _siteId + '&app=' + _appId + '&accessToken=' + _accessId + '&ek=' + record.enroll_key, function(rsp) {
                        var i = _ins._aRecords.indexOf(record);
                        _ins._aRecords.splice(i, 1);
                        _ins._oPage.total = _ins._oPage.total - 1;
                    });
                }
            };

            return _ins;
        }];
    }).provider('srvSigninNotice', function() {
        this.$get = ['$q', 'http2', function($q, http2) {
            return {
                detail: function(batch) {
                    var defer = $q.defer(),
                        url;
                    url = '/rest/pl/fe/matter/signin/notice/logList?batch=' + batch.id;
                    http2.get(url, function(rsp) {
                        defer.resolve(rsp.data);
                    });

                    return defer.promise;
                }
            }
        }]
    }).controller('ctrlSigninEdit', ['$scope', '$uibModalInstance', 'record', 'srvSigninApp', 'srvSigninRecord', function($scope, $mi, record, srvSigninApp, srvSigninRecord) {
        srvSigninApp.get().then(function(app) {
            if (record.data) {
                app.dataSchemas.forEach(function(col) {
                    if (record.data[col.id]) {
                        srvSigninRecord.convertRecord4Edit(col, record.data);
                    }
                });
                app._schemasFromEnrollApp.forEach(function(col) {
                    if (record.data[col.id]) {
                        srvSigninRecord.convertRecord4Edit(col, record.data);
                    }
                });
            }
            $scope.app = app;
            $scope.enrollDataSchemas = app._schemasFromEnrollApp;
            $scope.record = record;
            $scope.record.aTags = (!record.tags || record.tags.length === 0) ? [] : record.tags.split(',');
            $scope.aTags = app.tags;
        });

        $scope.ok = function() {
            var record = $scope.record,
                p = {};

            p.data = record.data;
            p.verified = record.verified;
            p.tags = record.tags = record.aTags.join(',');
            p.comment = record.comment;
            p.signin_log = record.signin_log;

            $mi.close([p, $scope.aTags]);
        };
        $scope.cancel = function() {
            $mi.dismiss();
        };
        $scope.chooseImage = function(fieldName) {
            var data = $scope.record.data;
            srvSigninRecord.chooseImage(fieldName).then(function(img) {
                !data[fieldName] && (data[fieldName] = []);
                data[fieldName].push(img);
            });
        };
        $scope.removeImage = function(imgField, index) {
            imgField.splice(index, 1);
        };
        $scope.$on('tag.xxt.combox.done', function(event, aSelected) {
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
        $scope.$on('tag.xxt.combox.add', function(event, newTag) {
            if (-1 === $scope.record.aTags.indexOf(newTag)) {
                $scope.record.aTags.push(newTag);
                if (-1 === $scope.aTags.indexOf(newTag)) {
                    $scope.aTags.push(newTag);
                }
            }
        });
        $scope.$on('tag.xxt.combox.del', function(event, removed) {
            $scope.record.aTags.splice($scope.record.aTags.indexOf(removed), 1);
        });
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            if (data.state === 'signinAt') {
                !record.signin_log && (record.signin_log = {});
                record.signin_log[data.obj.rid] = data.value;
            }
        });
        $scope.syncByEnroll = function() {
            srvSigninRecord.syncByEnroll($scope.record);
        };
    }]).controller('ctrlSigninFilter', ['$scope', '$uibModalInstance', 'dataSchemas', 'criteria', function($scope, $mi, dataSchemas, lastCriteria) {
        var canFilteredSchemas = [];
        dataSchemas.forEach(function(schema) {
            if (false === /image|file/.test(schema.type)) {
                canFilteredSchemas.push(schema);
            }
            if (/multiple/.test(schema.type)) {
                var options = {};
                if (lastCriteria.data[schema.id]) {
                    lastCriteria.data[schema.id].split(',').forEach(function(key) {
                        options[key] = true;
                    })
                }
                lastCriteria.data[schema.id] = options;
            }
            $scope.schemas = canFilteredSchemas;
            $scope.criteria = lastCriteria;
        });
        $scope.clean = function() {
            var criteria = $scope.criteria;
            if (criteria.record) {
                if (criteria.record.verified) {
                    criteria.record.verified = '';
                }
            }
            if (criteria.data) {
                angular.forEach(criteria.data, function(val, key) {
                    criteria.data[key] = '';
                });
            }
        };
        $scope.ok = function() {
            var criteria = $scope.criteria,
                optionCriteria;
            // 将单选题/多选题的结果拼成字符串
            canFilteredSchemas.forEach(function(schema) {
                var result;
                if (/multiple/.test(schema.type)) {
                    if ((optionCriteria = criteria.data[schema.id])) {
                        result = [];
                        Object.keys(optionCriteria).forEach(function(key) {
                            optionCriteria[key] && result.push(key);
                        });
                        criteria.data[schema.id] = result.join(',');
                    }
                }
            });
            $mi.close(criteria);
        };
        $scope.cancel = function() {
            $mi.dismiss('cancel');
        };
    }]);
});
