define(['require', 'schema', 'page'], function(require, schemaLib, pageLib) {
    var BaseSrvEnrollRecord = function($q, $http, srvRecordConverter, noticebox, $uibModal) {
        this._oApp = null;
        this._oPage = null;
        this._oCriteria = null;
        this._aRecords = null;
        this.init = function(oApp, oPage, oCriteria, oRecords) {
            this._oApp = oApp;
            // schemas
            if (this._oApp._schemasById === undefined) {
                var schemasById = {};
                this._oApp.data_schemas.forEach(function(schema) {
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
                byRound: '',
                joinParams: function() {
                    var p;
                    p = '&page=' + this.at + '&size=' + this.size;
                    p += '&orderby=' + this.orderBy;
                    p += '&rid=' + (this.byRound ? this.byRound : 'ALL');
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
                data: {}
            });
            // records
            this._aRecords = oRecords;
        };
        this._bSearch = function(url) {
            var that = this,
                defer = $q.defer();
            $http.post(url, that._oCriteria, function(rsp) {
                var records;
                if (rsp.data) {
                    records = rsp.data.records ? rsp.data.records : [];
                    rsp.data.total && (that._oPage.total = rsp.data.total);
                    that._oPage.setTotal(rsp.data.total);
                } else {
                    records = [];
                }
                records.forEach(function(record) {
                    srvRecordConverter.forTable(record, that._oApp._schemasById);
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
                $http.post(url, {
                    eks: eks
                }, function(rsp) {
                    selectedRecords.forEach(function(record) {
                        record.verified = 'Y';
                    });
                    noticebox.success('完成操作');
                });
            }
        };
        this._bGet = function(data, method) {
            data.tags = (!data.tags || data.tags.length === 0) ? [] : data.tags.split(',');
            data.entry_rule === null && (data.entry_rule = {});
            data.entry_rule.scope === undefined && (data.entry_rule.scope = 'none');
            try {
                data.data_schemas = data.data_schemas && data.data_schemas.length ? JSON.parse(data.data_schemas) : [];
            } catch (e) {
                console.log('data invalid', e, data.data_schemas);
                data.data_schemas = [];
            }
            if (data.enrollApp && data.enrollApp.data_schemas) {
                try {
                    data.enrollApp.data_schemas = data.enrollApp.data_schemas && data.enrollApp.data_schemas.length ? JSON.parse(data.enrollApp.data_schemas) : [];
                } catch (e) {
                    console.log('data invalid', e, data.enrollApp.data_schemas);
                    data.enrollApp.data_schemas = [];
                }
            }
            if (data.groupApp && data.groupApp.data_schemas) {
                var groupAppDS = data.groupApp.data_schemas;
                try {
                    data.groupApp.data_schemas = groupAppDS && groupAppDS.length ? JSON.parse(groupAppDS) : [];
                } catch (e) {
                    console.log('data invalid', e, groupAppDS);
                    data.groupApp.data_schemas = [];
                }
                if (data.groupApp.rounds && data.groupApp.rounds.length) {
                    var roundDS = {
                            id: '_round_id',
                            type: 'single',
                            title: '分组名称',
                        },
                        ops = [];
                    data.groupApp.rounds.forEach(function(round) {
                        ops.push({
                            v: round.round_id,
                            l: round.title
                        });
                    });
                    roundDS.ops = ops;
                    data.groupApp.data_schemas.splice(0, 0, roundDS);
                }
            }
            method(data);
            data.data_schemas.forEach(function(schema) {
                schemaLib._upgrade(schema);
            });
            data.pages.forEach(function(page) {
                pageLib.enhance(page, data._schemasById);
            });
        };
        this._bFilter = function (){
            var defer = $q.defer(), that = this;
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/recordFilter.html?_=3',
                controller: 'ctrlEnrollFilter',
                windowClass: 'auto-height',
                backdrop: 'static',
                resolve: {
                    dataSchemas: function() {
                        return that._oApp.data_schemas;
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
        }
    };
    angular.module('service.enroll', ['ui.bootstrap', 'ui.xxt', 'service.matter']).
    provider('srvEnrollApp', function() {
        function _mapSchemas(app) {
            var mapOfSchemaByType = {},
                mapOfSchemaById = {},
                enrollDataSchemas = [],
                groupDataSchemas = [],
                canFilteredSchemas = [];

            app.data_schemas.forEach(function(schema) {
                mapOfSchemaByType[schema.type] === undefined && (mapOfSchemaByType[schema.type] = []);
                mapOfSchemaByType[schema.type].push(schema.id);
                mapOfSchemaById[schema.id] = schema;
                if (false === /image|file/.test(schema.type)) {
                    canFilteredSchemas.push(schema);
                }
            });
            // 关联的报名登记项
            if (app.enrollApp && app.enrollApp.data_schemas) {
                app.enrollApp.data_schemas.forEach(function(item) {
                    if (mapOfSchemaById[item.id] === undefined) {
                        mapOfSchemaById[item.id] = item;
                        enrollDataSchemas.push(item);
                    }
                });
            }
            // 关联的分组活动的登记项
            if (app.groupApp && app.groupApp.data_schemas) {
                app.groupApp.data_schemas.forEach(function(item) {
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
        }

        var _siteId, _appId, _accessId, _oApp, _getAppDeferred = false;
        this.config = function(siteId, appId, accessId) {
            _siteId = siteId;
            _appId = appId;
            _accessId = accessId;
        };
        this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'mattersgallery', function($q, $uibModal, http2, noticebox, mattersgallery) {
            var _ins = new BaseSrvEnrollRecord();
            var _self = {
                get: function() {
                    var url;

                    if (_getAppDeferred) {
                        return _getAppDeferred.promise;
                    }
                    _getAppDeferred = $q.defer();
                    url = '/rest/pl/fe/matter/enroll/get?site=' + _siteId + '&id=' + _appId;
                    http2.get(url, function(rsp) {
                        _oApp = rsp.data;
                        _ins._bGet(_oApp, _mapSchemas);
                        _getAppDeferred.resolve(_oApp);
                    });

                    return _getAppDeferred.promise;
                },
                opGet: function() {
                    var url, _getAppDeferred = false;

                    if (_getAppDeferred) {
                        return _getAppDeferred.promise;
                    }
                    _getAppDeferred = $q.defer();
                    url = '/rest/site/op/matter/enroll/get?site=' + _siteId + '&app=' + _appId + '&accessToken=' + _accessId;
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
                        if (name === 'tags') {
                            modifiedData.tags = _oApp.tags.join(',');
                        } else {
                            modifiedData[name] = _oApp[name];
                        }
                    });

                    url = '/rest/pl/fe/matter/enroll/update?site=' + _siteId + '&app=' + _appId;
                    http2.post(url, modifiedData, function(rsp) {
                        //noticebox.success('完成保存');
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
                jumpPages: function() {
                    var defaultInput, pages = _oApp.pages,
                        pages4NonMember = [{
                            name: '$memberschema',
                            title: '填写自定义用户信息'
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
                        pages4NonMember.push(newPage);
                        pages4Nonfan.push(newPage);
                        page.type === 'I' && (defaultInput = newPage);
                    });

                    return {
                        nonMember: pages4NonMember,
                        nonfan: pages4Nonfan,
                        defaultInput: defaultInput
                    }
                },
                resetEntryRule: function() {
                    http2.get('/rest/pl/fe/matter/enroll/entryRuleReset?site=' + _siteId + '&app=' + _appId, function(rsp) {
                        _oApp.entry_rule = rsp.data;
                    });
                },
                changeUserScope: function(ruleScope, sns, memberSchemas, defaultInputPage) {
                    var entryRule = _oApp.entry_rule;
                    entryRule.scope = ruleScope;
                    switch (ruleScope) {
                        case 'member':
                            entryRule.member === undefined && (entryRule.member = {});
                            entryRule.other === undefined && (entryRule.other = {});
                            entryRule.other.entry = '$memberschema';
                            memberSchemas.forEach(function(ms) {
                                entryRule.member[ms.id] = {
                                    entry: defaultInputPage ? defaultInputPage.name : ''
                                };
                            });
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
                    var defer = $q.defer();
                    mattersgallery.open(_siteId, function(missions) {
                        var matter;
                        if (missions.length === 1) {
                            matter = {
                                id: _appId,
                                type: 'enroll'
                            };
                            http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + _siteId + '&id=' + missions[0].id, matter, function(rsp) {
                                var mission = rsp.data,
                                    updatedFields = ['mission_id'];

                                _oApp.mission = mission;
                                _oApp.mission_id = mission.id;
                                if (!_oApp.pic || _oApp.pic.length === 0) {
                                    _oApp.pic = mission.pic;
                                    updatedFields.push('pic');
                                }
                                if (!_oApp.summary || _oApp.summary.length === 0) {
                                    _oApp.summary = mission.summary;
                                    updatedFields.push('summary');
                                }
                                _self.update(updatedFields).then(function() {
                                    defer.resolve(mission);
                                });
                            });
                        }
                    }, {
                        matterTypes: [{
                            value: 'mission',
                            title: '项目',
                            url: '/rest/pl/fe/matter'
                        }],
                        singleMatter: true
                    });
                    return defer.promise;
                },
                quitMission: function() {
                    var matter = {
                            id: _oApp.id,
                            type: 'enroll',
                            title: _oApp.title
                        },
                        defer = $q.defer();
                    http2.post('/rest/pl/fe/matter/mission/matter/remove?site=' + _siteId + '&id=' + _oApp.mission_id, matter, function(rsp) {
                        delete _oApp.mission;
                        _oApp.mission_id = null;
                        _self.update(['mission_id']).then(function() {
                            defer.resolve();
                        });
                    });
                    return defer.promise;
                },
                choosePhase: function() {
                    var phaseId = _oApp.mission_phase_id,
                        newPhase, updatedFields = ['mission_phase_id'];

                    // 去掉活动标题中现有的阶段后缀
                    _oApp.mission.phases.forEach(function(phase) {
                        _oApp.title = _oApp.title.replace('-' + phase.title, '');
                        if (phase.phase_id === phaseId) {
                            newPhase = phase;
                        }
                    });
                    if (newPhase) {
                        // 给活动标题加上阶段后缀
                        _oApp.title += '-' + newPhase.title;
                        updatedFields.push('title');
                        // 设置活动开始时间
                        if (_oApp.start_at == 0) {
                            _oApp.start_at = newPhase.start_at;
                            updatedFields.push('start_at');
                        }
                        // 设置活动结束时间
                        if (_oApp.end_at == 0) {
                            _oApp.end_at = newPhase.end_at;
                            updatedFields.push('end_at');
                        }
                    } else {
                        updatedFields.push('title');
                    }

                    _self.update(updatedFields);
                },
                importSchemaByOther: function() {
                    var defer = $q.defer();
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/importSchemaByOther.html?_=1',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            var page, data, filter;
                            $scope2.page = page = {
                                at: 1,
                                size: 15,
                                j: function() {
                                    return 'page=' + this.at + '&size=' + this.size;
                                }
                            };
                            $scope2.data = data = {};
                            $scope2.filter = filter = {};
                            $scope2.selectApp = function() {
                                if (angular.isString(data.fromApp.data_schemas) && data.fromApp.data_schemas) {
                                    data.fromApp.dataSchemas = JSON.parse(data.fromApp.data_schemas);
                                }
                                data.schemas = [];
                            };
                            $scope2.selectSchema = function(schema) {
                                if (schema._selected) {
                                    data.schemas.push(schema);
                                } else {
                                    data.schemas.splice(data.schemas.indexOf(schema), 1);
                                }
                            };
                            $scope2.ok = function() {
                                $mi.close(data);
                            };
                            $scope2.cancel = function() {
                                $mi.dismiss('cancel');
                            };
                            $scope2.doFilter = function() {
                                page.at = 1;
                                $scope2.doSearch();
                            };
                            $scope2.doSearch = function() {
                                var url = '/rest/pl/fe/matter/enroll/list?site=' + _siteId + '&' + page.j();
                                http2.post(url, {
                                    byTitle: filter.byTitle
                                }, function(rsp) {
                                    $scope2.apps = rsp.data.apps;
                                    if ($scope2.apps.length) {
                                        data.fromApp = $scope2.apps[0];
                                        $scope2.selectApp();
                                    }
                                    page.total = rsp.data.total;
                                });
                            };
                            $scope2.doSearch();
                        }],
                        backdrop: 'static',
                    }).result.then(function(data) {
                        defer.resolve(data.schemas);
                    });
                    return defer.promise;
                },
                summary: function() {
                    var deferred = $q.defer(),
                        url = '/rest/pl/fe/matter/enroll/summary';
                    url += '?site=' + _siteId;
                    url += '&app=' + _appId;
                    http2.get(url, function(rsp) {
                        deferred.resolve(rsp.data);
                    });
                    return deferred.promise;
                },
                assignEnrollApp: function() {
                    $uibModal.open({
                        templateUrl: 'assignEnrollApp.html',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            $scope2.app = _oApp;
                            $scope2.data = {
                                filter: {},
                                source: ''
                            };
                            _oApp.mission && ($scope2.data.sameMission = 'Y');
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope2.ok = function() {
                                $mi.close($scope2.data);
                            };
                            var url = '/rest/pl/fe/matter/enroll/list?site=' + _siteId + '&size=999';
                            _oApp.mission && (url += '&mission=' + _oApp.mission.id);
                            http2.get(url, function(rsp) {
                                $scope2.apps = rsp.data.apps;
                            });
                        }],
                        backdrop: 'static'
                    }).result.then(function(data) {
                        _oApp.enroll_app_id = data.source;
                        _self.update('enroll_app_id').then(function(rsp) {
                            var url = '/rest/pl/fe/matter/enroll/get?site=' + _siteId + '&id=' + _oApp.enroll_app_id;
                            http2.get(url, function(rsp) {
                                rsp.data.data_schemas = JSON.parse(rsp.data.data_schemas);
                                _oApp.enrollApp = rsp.data;
                            });
                        });
                    });
                },
                assignGroupApp: function() {
                    $uibModal.open({
                        templateUrl: 'assignGroupApp.html',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            $scope2.app = _oApp;
                            $scope2.data = {
                                filter: {},
                                source: ''
                            };
                            _oApp.mission && ($scope2.data.sameMission = 'Y');
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope2.ok = function() {
                                $mi.close($scope2.data);
                            };
                            var url = '/rest/pl/fe/matter/group/list?site=' + _siteId + '&size=999';
                            _oApp.mission && (url += '&mission=' + _oApp.mission.id);
                            http2.get(url, function(rsp) {
                                $scope2.apps = rsp.data.apps;
                            });
                        }],
                        backdrop: 'static'
                    }).result.then(function(data) {
                        _oApp.group_app_id = data.source;
                        _self.update('group_app_id').then(function(rsp) {
                            var url = '/rest/pl/fe/matter/group/get?site=' + _siteId + '&app=' + _oApp.group_app_id;
                            http2.get(url, function(rsp) {
                                var groupApp = rsp.data,
                                    roundDS = {
                                        id: '_round_id',
                                        type: 'single',
                                        title: '分组名称',
                                    },
                                    ops = [];
                                //分组活动删除导入来源，groupApp.data_schemas为空字符串 , JSON.parse(''),splice()报错
                                groupApp.data_schemas = groupApp.data_schemas ? JSON.parse(groupApp.data_schemas) : [];
                                groupApp.rounds.forEach(function(round) {
                                    ops.push({
                                        v: round.round_id,
                                        l: round.title
                                    });
                                });
                                roundDS.ops = ops;
                                groupApp.data_schemas.splice(0, 0, roundDS);
                                _oApp.groupApp = groupApp;
                            });
                        });
                    });
                },
            };
            return _self;
        }];
    }).provider('srvEnrollSchema', function() {
        var _siteId, _appId;

        this.config = function(siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$uibModal', '$q', function($uibModal, $q) {
            var _self = {
                add: function(newSchema, afterIndex) {},
                remove: function(removedSchema) {},
                makePagelet: function(schema) {
                    var deferred = $q.defer();
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/pagelet.html',
                        controller: ['$scope', '$uibModalInstance', 'mediagallery', function($scope2, $mi, mediagallery) {
                            var tinymceEditor;
                            $scope2.reset = function() {
                                tinymceEditor.setContent('');
                            };
                            $scope2.ok = function() {
                                var html = tinymceEditor.getContent();
                                tinymceEditor.remove();
                                $mi.close({
                                    html: html
                                });
                            };
                            $scope2.cancel = function() {
                                tinymceEditor.remove();
                                $mi.dismiss();
                            };
                            $scope2.$on('tinymce.multipleimage.open', function(event, callback) {
                                var options = {
                                    callback: callback,
                                    multiple: true,
                                    setshowname: true
                                };
                                mediagallery.open($scope.app.siteid, options);
                            });
                            $scope2.$on('tinymce.instance.init', function(event, editor) {
                                var page;
                                tinymceEditor = editor;
                                editor.setContent(schema.content);
                            });
                        }],
                        size: 'lg',
                        backdrop: 'static'
                    }).result.then(function(result) {
                        deferred.resolve(result);
                    });
                    return deferred.promise;
                },
            };
            return _self;
        }];
    }).provider('srvEnrollRound', function() {
        var _siteId, _appId, _rounds,
            _RestURL = '/rest/pl/fe/matter/enroll/round/',
            RoundState = ['新建', '启用', '停止'];

        this.config = function(siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$q', 'http2', '$uibModal', 'srvEnrollApp', function($q, http2, $uibModal, srvEnrollApp) {
            return {
                RoundState: RoundState,
                list: function() {
                    var defer = $q.defer();
                    if (_rounds) {
                        defer.resolve(_rounds);
                    } else {
                        srvEnrollApp.get().then(function(oApp) {
                            _rounds = oApp.rounds;
                            defer.resolve(_rounds);
                        });
                    }
                    return defer.promise;
                },
                add: function() {
                    this.list().then(function() {
                        $uibModal.open({
                            templateUrl: 'roundEditor.html',
                            backdrop: 'static',
                            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                                $scope.round = {
                                    state: 0
                                };
                                $scope.roundState = RoundState;
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
                            http2.post(_RestURL + 'add?site=' + _siteId + '&app=' + _appId, newRound, function(rsp) {
                                if (_rounds.length > 0 && rsp.data.state == 1) {
                                    _rounds[0].state = 2;
                                }
                                _rounds.splice(0, 0, rsp.data);
                            });
                        });
                    });
                },
                edit: function(round) {
                    this.list().then(function() {
                        $uibModal.open({
                            templateUrl: 'roundEditor.html',
                            backdrop: 'static',
                            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                                $scope.round = angular.copy(round);
                                $scope.roundState = RoundState;
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
                                    $scope.round.state = 2;
                                    $mi.close({
                                        action: 'update',
                                        data: $scope.round
                                    });
                                };
                                $scope.start = function() {
                                    $scope.round.state = 1;
                                    $mi.close({
                                        action: 'update',
                                        data: $scope.round
                                    });
                                };
                            }]
                        }).result.then(function(rst) {
                            var url = _RestURL;
                            if (rst.action === 'update') {
                                url += 'update?site=' + _siteId + '&app=' + _appId + '&rid=' + round.rid;
                                http2.post(url, rst.data, function(rsp) {
                                    if (_rounds.length > 1 && rst.data.state == 1) {
                                        _rounds[1].state = 2;
                                    }
                                    angular.extend(round, rst.data);
                                });
                            } else if (rst.action === 'remove') {
                                url += 'remove?site=' + _siteId + '&app=' + _appId + '&rid=' + round.rid;
                                http2.get(url, function(rsp) {
                                    _rounds.splice(_rounds.indexOf(round), 1);
                                });
                            }
                        });
                    });
                }
            };
        }];
    }).provider('srvEnrollPage', function() {
        var _siteId, _appId;
        this.config = function(siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$uibModal', '$q', 'http2', 'noticebox', 'srvEnrollApp', function($uibModal, $q, http2, noticebox, srvEnrollApp) {
            var _self;
            _self = {
                create: function() {
                    var deferred = $q.defer();
                    srvEnrollApp.get().then(function(app) {
                        $uibModal.open({
                            templateUrl: '/views/default/pl/fe/matter/enroll/component/createPage.html?_=3',
                            backdrop: 'static',
                            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                                $scope.options = {};
                                $scope.ok = function() {
                                    $mi.close($scope.options);
                                };
                                $scope.cancel = function() {
                                    $mi.dismiss();
                                };
                            }],
                        }).result.then(function(options) {
                            http2.post('/rest/pl/fe/matter/enroll/page/add?site=' + _siteId + '&app=' + _appId, options, function(rsp) {
                                var page = rsp.data;
                                pageLib.enhance(page);
                                app.pages.push(page);
                                deferred.resolve(page);
                            });
                        });
                    });
                    return deferred.promise;
                },
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
                    url = '/rest/pl/fe/matter/enroll/page/update';
                    url += '?site=' + _siteId;
                    url += '&app=' + _appId;
                    url += '&page=' + page.id;
                    url += '&cname=' + page.code_name;
                    http2.post(url, updated, function(rsp) {
                        page.$$modified = false;
                        defer.resolve();
                        noticebox.success('完成保存');
                    });

                    return defer.promise;
                },
                clean: function(page) {
                    page.html = '';
                    page.data_schemas = [];
                    page.act_schemas = [];
                    page.user_schemas = [];
                    return _self.update(page, ['data_schemas', 'act_schemas', 'user_schemas', 'html']);
                },
                remove: function(page) {
                    var defer = $q.defer();
                    srvEnrollApp.get().then(function(app) {
                        var url = '/rest/pl/fe/matter/enroll/page/remove';
                        url += '?site=' + _siteId;
                        url += '&app=' + _appId;
                        url += '&pid=' + page.id;
                        url += '&cname=' + page.code_name;
                        http2.get(url, function(rsp) {
                            app.pages.splice(app.pages.indexOf(page), 1);
                            defer.resolve(app.pages);
                            noticebox.success('完成删除');
                        });
                    });
                    return defer.promise;
                }
            };
            return _self;
        }];
    }).provider('srvEnrollRecord', function() {
        var _siteId, _appId;
        this.config = function(siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$q', '$sce', 'http2', 'noticebox', '$uibModal', 'pushnotify', 'cstApp', 'srvRecordConverter', function($q, $sce, http2, noticebox, $uibModal, pushnotify, cstApp, srvRecordConverter) {
            var _ins = new BaseSrvEnrollRecord($q, http2, srvRecordConverter, noticebox, $uibModal);
            _ins.search = function(pageNumber) {
                var url;

                this._aRecords.splice(0, this._aRecords.length);
                pageNumber && (this._oPage.at = pageNumber);
                url = '/rest/pl/fe/matter/enroll/record/list';
                url += '?site=' + this._oApp.siteid;
                url += '&app=' + this._oApp.id;
                url += this._oPage.joinParams();

                return _ins._bSearch(url);
            };
            _ins.searchRecycle = function(pageNumber) {
                var defer = $q.defer(),
                    url;

                this._aRecords.splice(0, this._aRecords.length);
                pageNumber && (this._oPage.at = pageNumber);
                url = '/rest/pl/fe/matter/enroll/record/recycle';
                url += '?site=' + _siteId;
                url += '&app=' + _appId;
                url += this._oPage.joinParams();
                http2.get(url, function(rsp) {
                    var records;
                    if (rsp.data) {
                        records = rsp.data.records ? rsp.data.records : [];
                        rsp.data.total && (_ins._oPage.total = rsp.data.total);
                    } else {
                        records = [];
                    }
                    records.forEach(function(record) {
                        srvRecordConverter.forTable(record, _ins._oApp._schemasById);
                        _ins._aRecords.push(record);
                    });
                    defer.resolve(records);
                });

                return defer.promise;
            };
            _ins.filter = function() {
                return _ins._bFilter();
            };
            _ins.add = function(newRecord) {
                http2.post('/rest/pl/fe/matter/enroll/record/add?site=' + _siteId + '&app=' + _appId, newRecord, function(rsp) {
                    var record = rsp.data;
                    srvRecordConverter.forTable(record, _ins._oApp._schemasById);
                    _ins._aRecords.splice(0, 0, record);
                });
            };
            _ins.update = function(record, updated) {
                http2.post('/rest/pl/fe/matter/enroll/record/update?site=' + _siteId + '&app=' + _appId + '&ek=' + record.enroll_key, updated, function(rsp) {
                    angular.extend(record, rsp.data);
                    srvRecordConverter.forTable(record, _ins._oApp._schemasById);
                });
            };
            _ins.edit = function(record) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/recordEditor.html?_=7',
                    controller: 'ctrlEnrollEdit',
                    backdrop: 'static',
                    resolve: {
                        record: function() {
                            if (record === undefined) {
                                return {
                                    aid: _appId,
                                    tags: '',
                                    data: {}
                                };
                            } else {
                                record.aid = _appId;
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
                    backdrop: 'static',
                }).result.then(function(result) {
                    var record, selectedRecords = [],
                        eks = [],
                        posted = {};

                    for (var p in rows.selected) {
                        if (rows.selected[p] === true) {
                            record = _ins._aRecords[p];
                            eks.push(record.enroll_key);
                            selectedRecords.push(record);
                        }
                    }

                    if (eks.length) {
                        posted = {
                            eks: eks,
                            tags: result.tags,
                            appTags: result.appTags
                        };
                        http2.post('/rest/pl/fe/matter/enroll/record/batchTag?site=' + _siteId + '&app=' + _appId, posted, function(rsp) {
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
                    http2.get('/rest/pl/fe/matter/enroll/record/remove?site=' + _siteId + '&app=' + _appId + '&key=' + record.enroll_key, function(rsp) {
                        var i = _ins._aRecords.indexOf(record);
                        _ins._aRecords.splice(i, 1);
                        _ins._oPage.total = _ins._oPage.total - 1;
                    });
                }
            };
            _ins.restore = function(record) {
                if (window.confirm('确认恢复？')) {
                    http2.get('/rest/pl/fe/matter/enroll/record/restore?site=' + _siteId + '&app=' + _appId + '&key=' + record.enroll_key, function(rsp) {
                        var i = _ins._aRecords.indexOf(record);
                        _ins._aRecords.splice(i, 1);
                        _ins._oPage.total = _oPage.total - 1;
                    });
                }
            };
            _ins.empty = function() {
                var _this = this,
                    vcode;
                vcode = prompt('是否要删除所有登记信息？，若是，请输入活动名称。');
                if (vcode === _ins._oApp.title) {
                    http2.get('/rest/pl/fe/matter/enroll/record/empty?site=' + _siteId + '&app=' + _appId, function(rsp) {
                        _ins._aRecords.splice(0, _ins._aRecords.length);
                        _ins._oPage.total = 0;
                        _ins._oPage.at = 1;
                    });
                }
            };
            _ins.verifyAll = function() {
                if (window.confirm('确定审核通过所有记录（共' + _oPage.total + '条）？')) {
                    http2.get('/rest/pl/fe/matter/enroll/record/verifyAll?site=' + _siteId + '&app=' + _appId, function(rsp) {
                        _ins._aRecords.forEach(function(record) {
                            record.verified = 'Y';
                        });
                        noticebox.success('完成操作');
                    });
                }
            };
            _ins.batchVerify = function(rows) {
                var url;

                url = '/rest/pl/fe/matter/enroll/record/batchVerify';
                url += '?site=' + _siteId;
                url += '&app=' + _appId;

                return _ins._bBatchVerify(rows, url);
            };
            _ins.notify = function(rows) {
                var options = {
                    matterTypes: cstApp.notifyMatter
                };
                _ins._oApp.mission && (options.missionId = _ins._oApp.mission.id);
                pushnotify.open(_siteId, function(notify) {
                    var url, targetAndMsg = {};
                    if (notify.matters.length) {
                        if (rows) {
                            targetAndMsg.users = [];
                            Object.keys(rows.selected).forEach(function(key) {
                                if (rows.selected[key] === true) {
                                    targetAndMsg.users.push(_ins._aRecords[key].userid);
                                }
                            });
                        } else {
                            targetAndMsg.criteria = _oCriteria;
                        }
                        targetAndMsg.message = notify.message;

                        url = '/rest/pl/fe/matter/enroll/record/notify';
                        url += '?site=' + _siteId;
                        url += '&app=' + _appId;
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

                url = '/rest/pl/fe/matter/enroll/record/export';
                url += '?site=' + _siteId + '&app=' + _appId;
                window.open(url);
            };
            _ins.exportImage = function() {
                var url, params = {
                    criteria: _ins._oCriteria
                };

                url = '/rest/pl/fe/matter/enroll/record/exportImage';
                url += '?site=' + _siteId + '&app=' + _appId;
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
                    var url;

                    url = '/rest/pl/fe/matter/enroll/record/matchEnroll';
                    url += '?site=' + _siteId;
                    url += '&app=' + _appId;

                    http2.post(url, record.data, function(rsp) {
                        var matched;
                        if (rsp.data && rsp.data.length === 1) {
                            matched = rsp.data[0];
                            angular.extend(record.data, matched);
                        } else {
                            alert('没有找到匹配的记录，请检查数据是否一致');
                        }
                    });
                },
                _ins.syncByGroup = function(record) {
                    var url;

                    url = '/rest/pl/fe/matter/enroll/record/matchGroup';
                    url += '?site=' + _siteId;
                    url += '&app=' + _appId;

                    http2.post(url, record.data, function(rsp) {
                        var matched;
                        if (rsp.data && rsp.data.length === 1) {
                            matched = rsp.data[0];
                            angular.extend(record.data, matched);
                        } else {
                            alert('没有找到匹配的记录，请检查数据是否一致');
                        }
                    });
                };
            _ins.importByOther = function() {
                var defer = $q.defer();
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/importByOther.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                        var page, data, filter;
                        $scope2.page = page = {
                            at: 1,
                            size: 10,
                            j: function() {
                                return 'page=' + this.at + '&size=' + this.size;
                            }
                        };
                        $scope2.data = data = {};
                        $scope2.filter = filter = {};
                        $scope2.ok = function() {
                            $mi.close(data);
                        };
                        $scope2.cancel = function() {
                            $mi.dismiss('cancel');
                        };
                        $scope2.doFilter = function() {
                            page.at = 1;
                            $scope2.doSearch();
                        };
                        $scope2.doSearch = function() {
                            var url = '/rest/pl/fe/matter/enroll/list?site=' + _siteId + '&' + page.j();
                            http2.post(url, {
                                byTitle: filter.byTitle
                            }, function(rsp) {
                                $scope2.apps = rsp.data.apps;
                                if ($scope2.apps.length) {
                                    data.fromApp = $scope2.apps[0].id;
                                }
                                page.total = rsp.data.total;
                            });
                        };
                        $scope2.doSearch();
                    }],
                    backdrop: 'static',
                }).result.then(function(data) {
                    var url = '/rest/pl/fe/matter/enroll/record/importByOther?site=' + _siteId + '&app=' + _appId;
                    url += '&fromApp=' + data.fromApp;
                    http2.post(url, {}, function(rsp) {
                        noticebox.info('导入（' + rsp.data + '）条数据');
                        _ins.search(1).then(function() {
                            defer.resolve();
                        });
                    });
                });
                return defer.promise;
            };
            _ins.createAppByRecords = function(rows) {
                var defer = $q.defer();
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/createAppByRecords.html?_=5',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                        var canUseSchemas = {},
                            config;
                        _ins._oApp.data_schemas.forEach(function(schema) {
                            if (/shorttext|longtext/.test(schema.type)) {
                                canUseSchemas[schema.id] = schema;
                            }
                        });
                        $scope2.schemas = canUseSchemas;
                        $scope2.config = config = { protoSchema: { type: 'score', range: [1, 5] } };
                        $scope2.ok = function() {
                            var schemas = [];
                            for (var id in config.schemas) {
                                if (config.schemas[id]) {
                                    schemas.push(canUseSchemas[id]);
                                }
                            }
                            $mi.close({ schemas: schemas, protoSchema: config.protoSchema });
                        };
                        $scope2.cancel = function() {
                            $mi.dismiss('cancel');
                        };
                        $scope2.changeSchemaType = function() {
                            switch (config.protoSchema.type) {
                                case 'score':
                                    config.protoSchema = { type: 'score', range: [1, 5] };
                                    break;
                                case 'single':
                                    config.protoSchema = { type: 'single' };
                                    break;
                                case 'multiple':
                                    config.protoSchema = { type: 'multiple' };
                                    break;
                            }
                        };
                    }],
                    backdrop: 'static',
                }).result.then(function(config) {
                    var eks = [];
                    if (config.schemas.length) {
                        for (var p in rows.selected) {
                            if (rows.selected[p] === true) {
                                eks.push(_aRecords[p].enroll_key);
                            }
                        }
                        if (eks.length) {
                            var url = '/rest/pl/fe/matter/enroll/createByRecords?site=' + _siteId + '&app=' + _appId;
                            if (_ins._oApp.mission_id) {
                                url += '&mission=' + _ins._oApp.mission_id;
                            }
                            http2.post(url, {
                                proto: {
                                    scenario: 'voting',
                                    schema: {
                                        type: config.protoSchema.type,
                                        range: config.protoSchema.range,
                                        unique: 'N',
                                        _ver: 1
                                    }
                                },
                                record: {
                                    schemas: config.schemas,
                                    eks: eks
                                }
                            }, function(rsp) {
                                defer.resolve(rsp.data);
                            });
                        }
                    }
                });
                return defer.promise;
            };

            return _ins;
        }];
    }).provider('srvOpEnrollRecord', function() {
        var _siteId, _appId, _accessId;
        this.config = function(siteId, appId, accessId) {
            _siteId = siteId;
            _appId = appId;
            _accessId = accessId;
        };
        this.$get = ['$q', 'http2', 'noticebox', '$uibModal', 'srvRecordConverter', function($q, http2, noticebox, $uibModal, srvRecordConverter) {
            var _ins = new BaseSrvEnrollRecord($q, http2, srvRecordConverter, noticebox, $uibModal);
            _ins.search = function(pageNumber) {
                var url;

                this._aRecords.splice(0, this._aRecords.length);
                pageNumber && (this._oPage.at = pageNumber);
                url = '/rest/site/op/matter/enroll/record/list';
                url += '?site=' + _siteId;
                url += '&app=' + _appId;
                url += '&accessToken=' + _accessId;
                url += this._oPage.joinParams();

                return _ins._bSearch(url);
            };
            _ins.batchVerify = function(rows) {
                var url;

                url = '/rest/site/op/matter/enroll/record/batchVerify';
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
                    http2.get('/rest/site/op/matter/enroll/record/remove?site=' + _siteId + '&app=' + _appId + '&accessToken=' + _accessId + '&ek=' + record.enroll_key, function(rsp) {
                        var i = _ins._aRecords.indexOf(record);
                        _ins._aRecords.splice(i, 1);
                        _ins._oPage.total = _ins._oPage.total - 1;
                    });
                }
            };

            return _ins;
        }];
    }).provider('srvEnrollLog', function() {
        this.$get = ['$q', 'http2', function($q, http2) {
            return {
                list: function(_appId, page) {
                    var defer = $q.defer(),
                        url;
                    if (!page || !page._j) {
                        angular.extend(page, {
                            at: 1,
                            size: 30,
                            orderBy: 'time',
                            _j: function() {
                                var p;
                                p = '&page=' + this.at + '&size=' + this.size;
                                p += '&orderby=' + this.orderBy;
                                return p;
                            }
                        });
                    }
                    url = '/rest/pl/fe/matter/enroll/log/list?app=' + _appId + page._j();
                    http2.get(url, function(rsp) {
                        rsp.data.total && (page.total = rsp.data.total);
                        defer.resolve(rsp.data.logs);
                    });

                    return defer.promise;
                }
            };
        }];
    }).controller('ctrlEnrollEdit', ['$scope', '$uibModalInstance', 'record', 'srvEnrollApp', 'srvEnrollRecord', 'srvRecordConverter', function($scope, $uibModalInstance, record, srvEnrollApp, srvEnrollRecord, srvRecordConverter) {
        srvEnrollApp.get().then(function(app) {
            if (record.data) {
                app.data_schemas.forEach(function(col) {
                    if (record.data[col.id]) {
                        srvRecordConverter.forEdit(col, record.data);
                    }
                });
                app._schemasFromEnrollApp.forEach(function(col) {
                    if (record.data[col.id]) {
                        srvRecordConverter.forEdit(col, record.data);
                    }
                });
                app._schemasFromGroupApp.forEach(function(col) {
                    if (record.data[col.id]) {
                        srvRecordConverter.forEdit(col, record.data);
                    }
                });
            }
            $scope.app = app;
            $scope.enrollDataSchemas = app._schemasByEnrollApp;
            $scope.groupDataSchemas = app._schemasByGroupApp;
            $scope.record = record;
            $scope.record.aTags = (!record.tags || record.tags.length === 0) ? [] : record.tags.split(',');
            $scope.aTags = app.tags;
        });
        $scope.ok = function() {
            var record = $scope.record,
                p = {
                    tags: record.aTags.join(','),
                    data: {}
                };

            record.tags = p.tags;
            record.comment && (p.comment = record.comment);
            p.verified = record.verified;
            p.data = $scope.record.data;
            $uibModalInstance.close([p, $scope.aTags]);
        };
        $scope.cancel = function() {
            $uibModalInstance.dismiss('cancel');
        };
        $scope.scoreRangeArray = function(schema) {
            var arr = [];
            if (schema.range && schema.range.length === 2) {
                for (var i = schema.range[0]; i <= schema.range[1]; i++) {
                    arr.push('' + i);
                }
            }
            return arr;
        };
        $scope.chooseImage = function(fieldName) {
            var data = $scope.record.data;
            srvEnrollRecord.chooseImage(fieldName).then(function(img) {
                !data[fieldName] && (data[fieldName] = []);
                data[fieldName].push(img);
            });
        };
        $scope.removeImage = function(field, index) {
            field.splice(index, 1);
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
        $scope.syncByEnroll = function() {
            srvEnrollRecord.syncByEnroll($scope.record);
        };
        $scope.syncByGroup = function() {
            srvEnrollRecord.syncByGroup($scope.record);
        };
    }]).controller('ctrlEnrollFilter', ['$scope', '$uibModalInstance', 'dataSchemas', 'criteria', function($scope, $mi, dataSchemas, lastCriteria) {
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
