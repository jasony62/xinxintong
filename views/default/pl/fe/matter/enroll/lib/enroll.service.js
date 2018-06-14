define(['require', 'schema', 'page'], function(require, schemaLib, pageLib) {
    'use strict';
    var BaseSrvEnrollRecord = function($q, $http, noticebox, $uibModal, tmsSchema) {
        this._oApp = null;
        this._oPage = null;
        this._oCriteria = null;
        this._aRecords = null;
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
            var proto = angular.extend({
                at: 1,
                size: 30,
                orderBy: 'time',
                joinParams: function() {
                    var p;
                    p = '&page=' + this.at + '&size=' + this.size;
                    p += '&orderby=' + this.orderBy;
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
            }, oPage);

            angular.extend(this._oPage, proto);
            // criteria
            this._oCriteria = oCriteria;
            angular.extend(this._oCriteria, {
                record: {
                    rid: '',
                    verified: ''
                },
                order: {
                    orderby: '',
                    schemaId: ''
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
            $http.post(url, that._oCriteria, function(rsp) {
                var records;
                if (rsp.data) {
                    records = rsp.data.records ? rsp.data.records : [];
                    rsp.data.total && (that._oPage.total = rsp.data.total);
                    that._oPage.setTotal(rsp.data.total);
                } else {
                    records = [];
                }
                records.forEach(function(oRecord) {
                    tmsSchema.forTable(oRecord, that._oApp._unionSchemasById);
                    that._aRecords.push(oRecord);
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
        this._bGetAfter = function(oEnrollApp, fnCallback) {
            oEnrollApp.tags = (!oEnrollApp.tags || oEnrollApp.tags.length === 0) ? [] : oEnrollApp.tags.split(',');
            if (oEnrollApp.groupApp && oEnrollApp.groupApp.dataSchemas) {
                if (oEnrollApp.groupApp.rounds && oEnrollApp.groupApp.rounds.length) {
                    var roundDS = {
                            id: '_round_id',
                            type: 'single',
                            title: '分组名称',
                        },
                        ops = [];
                    oEnrollApp.groupApp.rounds.forEach(function(round) {
                        ops.push({
                            v: round.round_id,
                            l: round.title
                        });
                    });
                    roundDS.ops = ops;
                    oEnrollApp.groupApp.dataSchemas.splice(0, 0, roundDS);
                }
            }
            fnCallback(oEnrollApp);
            if (oEnrollApp.dataSchemas) {
                oEnrollApp.dataSchemas.forEach(function(oSchema) {
                    schemaLib._upgrade(oSchema, oEnrollApp);
                });
            }
            if (oEnrollApp.pages) {
                oEnrollApp.pages.forEach(function(oPage) {
                    pageLib.enhance(oPage, oEnrollApp._schemasById);
                });
            }
        };
        this._bFilter = function(srvEnlRnd) {
            var defer = $q.defer(),
                that = this;
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/recordFilter.html?_=6',
                controller: 'ctrlEnrollFilter',
                windowClass: 'auto-height',
                backdrop: 'static',
                resolve: {
                    app: function() {
                        return that._oApp;
                    },
                    dataSchemas: function() {
                        return that._oApp.dataSchemas;
                    },
                    criteria: function() {
                        return angular.copy(that._oCriteria);
                    },
                    srvEnlRnd: function() {
                        return srvEnlRnd;
                    }
                }
            }).result.then(function(oCriteria) {
                defer.resolve();
                angular.extend(that._oCriteria, oCriteria);
                that.search(1).then(function() {
                    defer.resolve();
                });
            });
            return defer.promise;
        }
    };
    var ngModule = angular.module('service.enroll', ['ui.bootstrap', 'ui.xxt', 'service.matter']);
    /**
     * app
     */
    ngModule.provider('srvEnrollApp', function() {
        function _fnMapAssocEnrollApp(oApp) {
            var enrollDataSchemas = [];
            if (oApp.enrollApp && oApp.enrollApp.dataSchemas) {
                oApp.enrollApp.dataSchemas.forEach(function(item) {
                    if (oApp._unionSchemasById[item.id] === undefined) {
                        item.assocState = '';
                        oApp._unionSchemasById[item.id] = item;
                        enrollDataSchemas.push(item);
                    } else if (oApp._unionSchemasById[item.id].fromApp === oApp.enrollApp.id) {
                        item.assocState = 'yes';
                    } else {
                        item.assocState = 'no';
                    }
                });
            }
            oApp._schemasFromEnrollApp = enrollDataSchemas;
        }

        function _fnMapAssocGroupApp(oApp) {
            var groupDataSchemas = [];
            if (oApp.groupApp && oApp.groupApp.dataSchemas) {
                oApp.groupApp.dataSchemas.forEach(function(item) {
                    if (oApp._unionSchemasById[item.id] === undefined) {
                        item.assocState = '';
                        oApp._unionSchemasById[item.id] = item;
                        groupDataSchemas.push(item);
                    } else if (oApp._unionSchemasById[item.id].fromApp === oApp.groupApp.id) {
                        item.assocState = 'yes';
                    } else {
                        item.assocState = 'no';
                    }
                });
            }
            oApp._schemasFromGroupApp = groupDataSchemas;
        }

        function _fnMapSchemas(oApp) {
            var mapOfSchemaByType = {},
                mapOfSchemaById = {},
                mapOfUnionSchemaById = {},
                inputSchemas = [],
                canFilteredSchemas = [];

            if (oApp.dataSchemas) {
                oApp.dataSchemas.forEach(function(schema) {
                    mapOfSchemaByType[schema.type] === undefined && (mapOfSchemaByType[schema.type] = []);
                    mapOfSchemaByType[schema.type].push(schema.id);
                    mapOfSchemaById[schema.id] = schema;
                    mapOfUnionSchemaById[schema.id] = schema;
                    if (schema.type !== 'html') {
                        inputSchemas.push(schema);
                    }
                    if (false === /image|file|html/.test(schema.type)) {
                        canFilteredSchemas.push(schema);
                    }
                });
            }
            oApp._schemasByType = mapOfSchemaByType;
            oApp._schemasById = mapOfSchemaById;
            oApp._schemasForInput = inputSchemas;
            oApp._unionSchemasById = mapOfUnionSchemaById;
            oApp._schemasCanFilter = canFilteredSchemas;

            _fnMapAssocEnrollApp(oApp);
            _fnMapAssocGroupApp(oApp);
        }


        var _siteId, _appId, _accessId, _oApp, _getAppDeferred = false;
        this.config = function(siteId, appId, accessId) {
            _siteId = siteId;
            _appId = appId;
            _accessId = accessId;
        };
        this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'srvSite', function($q, $uibModal, http2, noticebox, srvSite) {
            function _fnMakeApiUrl(action) {
                var url;
                url = '/rest/pl/fe/matter/enroll/' + action + '?site=' + _siteId + '&app=' + _appId;
                return url;
            }

            function _fnGetApp(url) {
                if (_getAppDeferred) {
                    return _getAppDeferred.promise;
                }
                _getAppDeferred = $q.defer();
                http2.get(url, function(rsp) {
                    _oApp = rsp.data;
                    _ins._bGetAfter(_oApp, _fnMapSchemas);
                    _getAppDeferred.resolve(_oApp);
                });

                return _getAppDeferred.promise;
            }

            var _ins, _self;
            _ins = new BaseSrvEnrollRecord();
            _self = {
                get: function() {
                    return _fnGetApp(_fnMakeApiUrl('get'));
                },
                opGet: function() {
                    var url;
                    url = '/rest/site/op/matter/enroll/get?site=' + _siteId + '&app=' + _appId + '&accessToken=' + _accessId;
                    return _fnGetApp(url);
                },
                update: function(names) {
                    var defer = $q.defer(),
                        modifiedData = {};

                    angular.isString(names) && (names = [names]);
                    names.forEach(function(name) {
                        if (/data_schemas|dataSchemas/.test(name)) {
                            modifiedData['data_schemas'] = _oApp.dataSchemas;
                        } else if (/recycle_schemas|recycleSchemas/.test(name)) {
                            modifiedData['recycle_schemas'] = _oApp.recycleSchemas;
                        } else if (name === 'tags') {
                            modifiedData.tags = _oApp.tags.join(',');
                        } else {
                            modifiedData[name] = _oApp[name];
                        }
                    });
                    http2.post(_fnMakeApiUrl('update'), modifiedData, function(rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                remove: function() {
                    var defer = $q.defer();
                    http2.get(_fnMakeApiUrl('remove'), function(rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                jumpPages: function() {
                    var defaultInput, all = [],
                        otherwise = [],
                        exclude = [],
                        pages4NonMember = [{
                            name: '$memberschema',
                            title: '提示填写联系人信息'
                        }],
                        pages4Nonfan = [{
                            name: '$mpfollow',
                            title: '提示关注'
                        }];

                    _oApp.pages.forEach(function(page) {
                        var newPage = {
                            name: page.name,
                            title: page.title
                        };
                        all.push(newPage);
                        exclude.push(newPage);
                        if (page.type !== 'V') {
                            otherwise.push(newPage);
                            pages4NonMember.push(newPage);
                            pages4Nonfan.push(newPage);
                        }
                        page.type === 'I' && (defaultInput = newPage);
                    });
                    all.push({ name: 'action', 'title': '活动动态页' });
                    all.push({ name: 'repos', 'title': '共享数据页' });
                    all.push({ name: 'rank', 'title': '排行榜' });
                    all.push({ name: 'score', 'title': '测验结果' });
                    otherwise.push({ name: 'action', 'title': '活动动态页' });
                    otherwise.push({ name: 'repos', 'title': '共享数据页' });
                    otherwise.push({ name: 'rank', 'title': '排行榜' });
                    exclude.push({ name: 'action', 'title': '活动动态页' });
                    exclude.push({ name: 'repos', 'title': '共享数据页' });
                    exclude.push({ name: 'cowork', 'title': '讨论页' });
                    exclude.push({ name: 'rank', 'title': '排行榜' });
                    exclude.push({ name: 'score', 'title': '测验结果' });

                    return {
                        all: all,
                        otherwise: otherwise,
                        exclude: exclude,
                        nonMember: pages4NonMember,
                        nonfan: pages4Nonfan,
                        defaultInput: defaultInput
                    }
                },
                changeUserScope: function(ruleScope, oSiteSns, oDefaultInputPage) {
                    var oEntryRule = _oApp.entryRule;
                    oEntryRule.scope = ruleScope;
                    return this.update('entryRule');
                },
                assignMission: function() {
                    var defer = $q.defer();
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
                                id: _appId,
                                type: 'enroll'
                            };
                            http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + _siteId + '&id=' + missions.matters[0].id, matter, function(rsp) {
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
                    });
                    return defer.promise;
                },
                quitMission: function() {
                    var defer = $q.defer();
                    http2.get(_fnMakeApiUrl('quitMission'), function(rsp) {
                        delete _oApp.mission;
                        _oApp.mission_id = 0;
                        _oApp.sync_mission_round = 'N';
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                opData: function() {
                    var deferred = $q.defer();
                    http2.get(_fnMakeApiUrl('opData'), function(rsp) {
                        deferred.resolve(rsp.data);
                    });
                    return deferred.promise;
                },
                assignEnrollApp: function() {
                    var defer = $q.defer();
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
                            var url = '/rest/pl/fe/matter/enroll/get?site=' + _siteId + '&app=' + _oApp.enroll_app_id;
                            http2.get(url, function(rsp) {
                                _oApp.enrollApp = rsp.data;
                                _fnMapAssocEnrollApp(_oApp);
                                defer.resolve(_oApp.enrollApp);
                            });
                        });
                    });
                    return defer.promise;
                },
                assignGroupApp: function() {
                    var defer = $q.defer();
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
                                _oApp.groupApp = rsp.data;
                                _fnMapAssocGroupApp(_oApp);
                                defer.resolve(_oApp.groupApp);
                            });
                        });
                    });
                    return defer.promise;
                },
                renewScore: function(record) {
                    var url, defer;

                    url = '/rest/pl/fe/matter/enroll/record/renewScore';
                    url += '?site=' + _siteId;
                    url += '&app=' + _appId;
                    defer = $q.defer();

                    http2.get(url, function(rsp) {
                        defer.resolve();
                    });

                    return defer.promise;
                }
            };
            return _self;
        }];
    });
    /**
     * round
     */
    ngModule.provider('srvEnrollRound', function() {
        var _siteId, _appId, _rounds, _oPage,
            _RestURL = '/rest/pl/fe/matter/enroll/round/',
            RoundState = ['新建', '启用', '结束'];

        this.config = function(siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$q', '$uibModal', 'http2', 'srvEnrollApp', function($q, $uibModal, http2, srvEnrollApp) {
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
                    url = _RestURL + 'list?site=' + _siteId + '&app=' + _appId + '&' + _oPage.j();
                    if (checkRid) {
                        url += '&checked=' + checkRid;
                    }
                    http2.get(url, function(rsp) {
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
                        http2.post(_RestURL + 'add?site=' + _siteId + '&app=' + _appId, newRound, function(rsp) {
                            if (_rounds.length > 0 && rsp.data.state == 1) {
                                _rounds[0].state = 2;
                            }
                            _rounds.splice(0, 0, rsp.data);
                            _oPage.total++;
                        });
                    });
                },
                edit: function(oRound) {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/roundEditor.html?_=2',
                        backdrop: 'static',
                        controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                            $scope.round = { rid: oRound.id, mission_rid: oRound.mission_rid, title: oRound.title, start_at: oRound.start_at, end_at: oRound.end_at, state: oRound.state };
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
                            $scope.downloadQrcode = function(url) {
                                $('<a href="' + url + '" download="登记二维码.png"></a>')[0].click();
                            };
                            srvEnrollApp.get().then(function(oApp) {
                                var rndEntryUrl;
                                rndEntryUrl = oApp.entryUrl + '&rid=' + oRound.rid;
                                $scope.entry = {
                                    url: rndEntryUrl,
                                    qrcode: '/rest/site/fe/matter/enroll/qrcode?site=' + oApp.siteid + '&url=' + encodeURIComponent(rndEntryUrl),
                                }
                                if (oApp.mission) {
                                    http2.get('/rest/pl/fe/matter/mission/round/list?mission=' + oApp.mission.id, function(rsp) {
                                        $scope.missionRounds = rsp.data.rounds;
                                    });
                                }
                            });
                        }]
                    }).result.then(function(rst) {
                        var url = _RestURL;
                        if (rst.action === 'update') {
                            url += 'update?site=' + _siteId + '&app=' + _appId + '&rid=' + oRound.rid;
                            http2.post(url, rst.data, function(rsp) {
                                if (_rounds.length > 1 && rst.data.state === '1') {
                                    _rounds[1].state = '2';
                                }
                                angular.extend(oRound, rsp.data);
                            });
                        } else if (rst.action === 'remove') {
                            url += 'remove?site=' + _siteId + '&app=' + _appId + '&rid=' + oRound.rid;
                            http2.get(url, function(rsp) {
                                _rounds.splice(_rounds.indexOf(oRound), 1);
                                _oPage.total--;
                            });
                        }
                    });
                },
                cron: function() {
                    var defer = $q.defer();
                    srvEnrollApp.get().then(function(oApp) {
                        $uibModal.open({
                            templateUrl: '/views/default/pl/fe/matter/enroll/component/roundCron.html?_=2',
                            size: 'lg',
                            backdrop: 'static',
                            controller: ['$scope', '$uibModalInstance', 'http2', function($scope, $mi, $http2) {
                                var aCronRules, byPeriods, byIntervals;
                                $scope.mdays = [];
                                while ($scope.mdays.length < 28) {
                                    $scope.mdays.push('' + ($scope.mdays.length + 1));
                                }
                                aCronRules = oApp.roundCron ? angular.copy(oApp.roundCron) : [];
                                $scope.byPeriods = byPeriods = [];
                                $scope.byIntervals = byIntervals = [];
                                $scope.example = function(oRule) {
                                    http2.post('/rest/pl/fe/matter/enroll/round/getcron', { roundCron: oRule }, function(rsp) {
                                        oRule.case = rsp.data;
                                    });
                                };
                                aCronRules.forEach(function(oRule) {
                                    switch (oRule.pattern) {
                                        case 'period':
                                            byPeriods.push(oRule);
                                            break;
                                        case 'interval':
                                            byIntervals.push(oRule);
                                            break;
                                    }
                                    $scope.example(oRule);
                                });
                                $scope.changePeriod = function(oRule) {
                                    if (oRule.period !== 'W') {
                                        oRule.wday = '';
                                    }
                                    if (oRule.period !== 'M') {
                                        oRule.mday = '';
                                    }
                                };
                                $scope.addPeriod = function() {
                                    var oNewRule;
                                    oNewRule = {
                                        pattern: 'period',
                                        period: 'D',
                                        hour: 8
                                    };
                                    byPeriods.push(oNewRule);
                                    aCronRules.push(oNewRule);
                                };
                                $scope.removePeriod = function(rule) {
                                    byPeriods.splice(byPeriods.indexOf(rule), 1);
                                    aCronRules.splice(aCronRules.indexOf(rule), 1);
                                };
                                $scope.addInterval = function() {
                                    var oNewRule;
                                    oNewRule = {
                                        pattern: 'interval',
                                        start_at: parseInt(new Date * 1 / 1000),
                                    };
                                    byIntervals.push(oNewRule);
                                    aCronRules.push(oNewRule);
                                };
                                $scope.removeInterval = function(rule) {
                                    byIntervals.splice(byIntervals.indexOf(rule), 1);
                                    aCronRules.splice(aCronRules.indexOf(rule), 1);
                                };
                                $scope.$on('xxt.tms-datepicker.change', function(event, oData) {
                                    oData.obj[oData.state] = oData.value;
                                    $scope.example(oData.obj);
                                });
                                $scope.cancel = function() {
                                    $mi.dismiss();
                                };
                                $scope.ok = function() {
                                    $mi.close(aCronRules);
                                };
                            }]
                        }).result.then(function(aCronRules) {
                            aCronRules.forEach(function(oRule) {
                                delete oRule.case;
                            });
                            oApp.roundCron = aCronRules;
                            srvEnrollApp.update('roundCron').then(function() {
                                defer.resolve(aCronRules);
                            });
                        });
                    });
                    return defer.promise;
                }
            };
        }];
    });
    /**
     * record
     */
    ngModule.provider('srvEnrollRecord', function() {
        var _siteId, _appId;
        this.config = function(siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$q', 'http2', 'noticebox', '$uibModal', 'pushnotify', 'cstApp', 'srvEnrollRound', 'tmsSchema', function($q, http2, noticebox, $uibModal, pushnotify, cstApp, srvEnlRnd, tmsSchema) {
            var _ins = new BaseSrvEnrollRecord($q, http2, noticebox, $uibModal, tmsSchema);
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
                        tmsSchema.forTable(record, _ins._oApp._unionSchemasById);
                        _ins._aRecords.push(record);
                    });
                    defer.resolve(records);
                });

                return defer.promise;
            };
            _ins.filter = function() {
                return _ins._bFilter(srvEnlRnd);
            };
            _ins.get = function(ek) {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/matter/enroll/record/get?ek=' + ek, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.add = function(newRecord) {
                var defer = $q.defer();
                http2.post('/rest/pl/fe/matter/enroll/record/add?site=' + _siteId + '&app=' + _appId, newRecord, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.update = function(record, updated) {
                var defer = $q.defer();
                http2.post('/rest/pl/fe/matter/enroll/record/update?site=' + _siteId + '&app=' + _appId + '&ek=' + record.enroll_key, updated, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
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
                        _ins._oPage.total = _ins._oPage.total - 1;
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
                if (window.confirm('确定审核通过所有记录（共' + _ins._oPage.total + '条）？')) {
                    http2.get('/rest/pl/fe/matter/enroll/record/batchVerify?site=' + _siteId + '&app=' + _appId + '&all=Y', function(rsp) {
                        _ins._aRecords.forEach(function(record) {
                            record.verified = 'Y';
                        });
                        noticebox.success('完成操作');
                    });
                }
            };
            _ins.batchVerify = function(rows) {
                var url;
                if (window.confirm('确定审核通过选中的记录（共' + Object.keys(rows.selected).length + '条）？')) {
                    url = '/rest/pl/fe/matter/enroll/record/batchVerify';
                    url += '?site=' + _siteId;
                    url += '&app=' + _appId;

                    return _ins._bBatchVerify(rows, url);
                }
            };
            _ins.notify = function(rows) {
                var options = {
                    matterTypes: cstApp.notifyMatter,
                    sender: 'enroll:' + _appId
                };
                _ins._oApp.mission && (options.missionId = _ins._oApp.mission.id);
                pushnotify.open(_siteId, function(notify) {
                    var url, targetAndMsg = {};
                    if (notify.matters.length) {
                        if (rows) {
                            targetAndMsg.users = [];
                            Object.keys(rows.selected).forEach(function(key) {
                                if (rows.selected[key] === true) {
                                    var rec = _ins._aRecords[key];
                                    if (Object.keys(rec).indexOf('enroll_key') !== -1) {
                                        targetAndMsg.users.push({ userid: rec.userid, enroll_key: rec.enroll_key });
                                    } else {
                                        targetAndMsg.users.push({ userid: rec.userid });
                                    }
                                }
                            });
                        } else {
                            targetAndMsg.criteria = _ins._oCriteria;
                        }
                        targetAndMsg.message = notify.message;

                        url = '/rest/pl/fe/matter/enroll/notice/send';
                        url += '?site=' + _siteId;
                        url += '&app=' + _appId;
                        url += '&tmplmsg=' + notify.tmplmsg.id;

                        http2.post(url, targetAndMsg, function(data) {
                            noticebox.success('发送完成');
                        });
                    }
                }, options);
            };
            _ins.export = function() {
                var url, oCriteria;
                oCriteria = {};
                if (_ins._oCriteria.keyword) {
                    oCriteria.keyword = _ins._oCriteria.keyword;
                }
                if (_ins._oCriteria.data && Object.keys(_ins._oCriteria.data).length) {
                    var oFilterDat = {};
                    angular.forEach(_ins._oCriteria.data, function(v, k) {
                        v && (oFilterDat[k] = v);
                    });
                    if (Object.keys(oFilterDat).length) {
                        oCriteria.data = oFilterDat;
                    }
                }
                if (_ins._oCriteria.tags && _ins._oCriteria.tags.length) {
                    oCriteria.tags = _ins._oCriteria.tags;
                }
                if (_ins._oCriteria.order) {}
                if (_ins._oCriteria.record) {
                    var oFilterRec = {};
                    if (_ins._oCriteria.record.rid) {
                        oFilterRec.rid = _ins._oCriteria.record.rid;
                    }
                    if (_ins._oCriteria.record.verified) {
                        oFilterRec.verified = _ins._oCriteria.record.verified;
                    }
                    if (Object.keys(oFilterRec).length) {
                        oCriteria.record = oFilterRec;
                    }
                }
                url = '/rest/pl/fe/matter/enroll/record/export';
                url += '?site=' + _siteId + '&app=' + _appId;
                url += '&filter=' + JSON.stringify(oCriteria);
                window.open(url);
            };
            _ins.exportImage = function() {
                var url;
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
            };
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
            _ins.exportToOther = function(oApp, rows) {
                var defer, eks;
                if (rows) {
                    eks = [];
                    Object.keys(rows.selected).forEach(function(key) {
                        if (rows.selected[key] === true) {
                            var oRec = _ins._aRecords[key];
                            if (Object.keys(oRec).indexOf('enroll_key') !== -1) {
                                eks.push(oRec.enroll_key);
                            }
                        }
                    });
                }
                defer = $q.defer();
                if (!eks || eks.length === 0) {
                    defer.reject();
                } else {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/exportToOther.html?_=1',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            var page, data, filter;
                            $scope2.sourceApp = oApp;
                            $scope2.page = page = {
                                at: 1,
                                size: 10,
                                j: function() {
                                    return 'page=' + this.at + '&size=' + this.size;
                                }
                            };
                            $scope2.data = data = { mappings: {} };
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
                                        data.fromApp = $scope2.apps[0];
                                    }
                                    $scope2.apps.forEach(function(oApp) {
                                        oApp.dataSchemas = JSON.parse(oApp.data_schemas);
                                    });
                                    page.total = rsp.data.total;
                                });
                            };
                            $scope2.doSearch();
                        }],
                        backdrop: 'static',
                        size: 'lg'
                    }).result.then(function(data) {
                        var url;
                        if (data.fromApp && data.fromApp.id && data.mappings) {
                            url = '/rest/pl/fe/matter/enroll/record/exportToOther';
                            url += '?app=' + oApp.id;
                            url += '&targetApp=' + data.fromApp.id;
                            http2.post(url, { mappings: data.mappings, eks, eks }, function() {});
                        }
                    });
                }
                return defer.promise;
            };
            _ins.sum4Schema = function() {
                var url,
                    defer = $q.defer();

                url = '/rest/pl/fe/matter/enroll/record/sum4Schema';
                url += '?site=' + _siteId;
                url += '&app=' + _appId;
                if (_ins._oCriteria.record) {
                    if (_ins._oCriteria.record.rid) {
                        url += '&rid=' + _ins._oCriteria.record.rid;
                    }
                    if (_ins._oCriteria.record.group_id) {
                        url += '&gid=' + _ins._oCriteria.record.group_id;
                    }
                    if (_ins._oCriteria.data._round_id) {
                        url += '&gid=' + _ins._oCriteria.data._round_id;
                    }
                }

                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data);
                })
                return defer.promise;
            };
            _ins.score4Schema = function() {
                var url,
                    defer = $q.defer();

                url = '/rest/pl/fe/matter/enroll/record/score4Schema';
                url += '?site=' + _siteId;
                url += '&app=' + _appId;
                if (_ins._oCriteria.record) {
                    if (_ins._oCriteria.record.rid) {
                        url += '&rid=' + _ins._oCriteria.record.rid;
                    }
                    if (_ins._oCriteria.record.group_id) {
                        url += '&gid=' + _ins._oCriteria.record.group_id;
                    }
                    if (_ins._oCriteria.data._round_id) {
                        url += '&gid=' + _ins._oCriteria.data._round_id;
                    }
                }

                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data);
                })
                return defer.promise;
            };
            _ins.listRemark = function(ek, schemaId, itemId) {
                var url, defer = $q.defer();
                url = '/rest/pl/fe/matter/enroll/remark/list';
                url += '?site=' + _siteId;
                url += '&ek=' + ek;
                schemaId && (url += '&schema=' + schemaId);
                itemId && (url += '&id=' + itemId);
                if (itemId == '0') {
                    url += '&id=null';
                }
                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.addRemark = function(ek, schemaId, newRemark, itemId) {
                var url, defer = $q.defer();
                url = '/rest/pl/fe/matter/enroll/remark/add?ek=' + ek;
                schemaId && (url += '&schema=' + schemaId);
                itemId && (url += '&id=' + itemId);
                http2.post(url, newRemark, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.agree = function(ek, schemaId, value, itemId) {
                var url, defer = $q.defer();
                url = '/rest/pl/fe/matter/enroll/data/agree?ek=' + ek;
                url += '&schema=' + schemaId;
                url += '&value=' + value;
                itemId && (url += '&id=' + itemId);
                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.agreeRemark = function(remarkId, value) {
                var url, defer = $q.defer();
                url = '/rest/pl/fe/matter/enroll/remark/agree';
                url += '?value=' + value;
                http2.post(url, { remark: remarkId }, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };

            return _ins;
        }];
    });
    /**
     * op record
     */
    ngModule.provider('srvOpEnrollRecord', function() {
        var _siteId, _appId, _accessId;
        this.config = function(siteId, appId, accessId) {
            _siteId = siteId;
            _appId = appId;
            _accessId = accessId;
        };
        this.$get = ['$q', 'http2', 'noticebox', '$uibModal', 'srvOpEnrollRound', 'tmsSchema', function($q, http2, noticebox, $uibModal, srvEnlRnd, tmsSchema) {
            var _ins = new BaseSrvEnrollRecord($q, http2, noticebox, $uibModal, tmsSchema);
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
                return _ins._bFilter(srvEnlRnd);
            };
            _ins.agree = function(oRecord, schemaId, value) {
                var url, defer = $q.defer();
                url = '/rest/site/op/matter/enroll/data/agree?ek=' + oRecord.enroll_key;
                url += '&schema=' + schemaId;
                url += '&value=' + value;
                url += '&site=' + _siteId;
                url += '&accessToken=' + _accessId
                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
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
            _ins.sum4Schema = function(rid) {
                var url,
                    params = {
                        criteria: _ins._oCriteria
                    },
                    defer = $q.defer();

                url = '/rest/site/op/matter/enroll/record/sum4Schema';
                url += '?site=' + _siteId;
                url += '&app=' + _appId;
                url += '&accessToken=' + _accessId;
                params.criteria.record && (url += '&rid=' + params.criteria.record.rid);

                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data);
                })
                return defer.promise;
            };
            _ins.agreeRemark = function(remarkId, value) {
                var url, defer = $q.defer();
                url = '/rest/site/op/matter/enroll/remark/agree';
                url += '?site=' + _siteId;
                url += '&accessToken=' + _accessId;
                url += '&value=' + value;
                http2.post(url, { remark: remarkId }, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };

            return _ins;
        }];
    });
    /**
     * op round
     */
    ngModule.provider('srvOpEnrollRound', function() {
        var _siteId, _appId, _accessId, _rounds, _oPage, _checked,
            _RestURL = '/rest/site/op/matter/enroll/round/',
            RoundState = ['新建', '启用', '停止'];

        this.config = function(siteId, appId, accessId) {
            _siteId = siteId;
            _appId = appId;
            _accessId = accessId;
        };
        this.$get = ['$q', 'http2', '$uibModal', 'srvEnrollApp', function($q, http2, $uibModal, srvEnrollApp) {
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
                    url = _RestURL + 'list?site=' + _siteId + '&app=' + _appId + '&accessToken=' + _accessId + '&' + _oPage.j();
                    if (checkRid) {
                        url += '&checked=' + checkRid;
                    }
                    http2.get(url, function(rsp) {
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
            };
        }];
    });
    /**
     * log
     */
    ngModule.provider('srvEnrollLog', function() {
        var _siteId, _appId, _plOperations, _siteOperations;

        this.config = function(siteId, appId) {
            _siteId = siteId;
            _appId = appId;
            _plOperations = [{
                value: 'add',
                title: '新增记录'
            }, {
                value: 'updateData',
                title: '修改记录'
            }, {
                value: 'removeData',
                title: '删除记录'
            }, {
                value: 'restoreData',
                title: '恢复记录'
            }, {
                value: 'U',
                title: '修改活动'
            }, {
                value: 'verify.batch',
                title: '审核通过指定记录'
            }, {
                value: 'verify.all',
                title: '审核通过全部记录'
            }];
            _siteOperations = [{
                value: 'read',
                title: '阅读'
            }, {
                value: 'site.matter.enroll.submit',
                title: '提交'
            }, {
                value: 'site.matter.enroll.data.do.like',
                title: '表态其他人的填写内容'
            }, {
                value: 'site.matter.enroll.cowork.do.submit',
                title: '提交协作新内容'
            }, {
                value: 'site.matter.enroll.do.remark',
                title: '评论'
            }, {
                value: 'site.matter.enroll.cowork.do.like',
                title: '表态其他人填写的协作内容'
            }, {
                value: 'site.matter.enroll.remark.do.like',
                title: '表态其他人的评论'
            }, {
                value: 'site.matter.enroll.data.get.agree',
                title: '对记录表态'
            }, {
                value: 'site.matter.enroll.cowork.get.agree',
                title: '对协作记录表态'
            }, {
                value: 'ite.matter.enroll.remark.get.agree',
                title: '对评论表态'
            }, {
                value: 'site.matter.enroll.remove',
                title: '删除记录'
            }, {
                value: 'site.matter.enroll.remark.as.cowork',
                title: '将用户留言设置为协作记录'
            }];
        };
        this.$get = ['$q', 'http2', '$uibModal', function($q, http2, $uibModal) {
            return {
                list: function(page, type, criteria) {
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
                    url = '/rest/pl/fe/matter/enroll/log/list?logType=' + type + '&app=' + _appId + page._j();
                    http2.post(url, criteria, function(rsp) {
                        rsp.data.total && (page.total = rsp.data.total);
                        defer.resolve(rsp.data.logs);
                    });

                    return defer.promise;
                },
                filter: function(type) {
                    var defer = $q.defer();
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/logFilter.html?_=1',
                        controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                            var oCriteria;
                            $scope2.type = type;
                            $scope2.siteOperations = _siteOperations;
                            $scope2.plOperations = _plOperations;
                            $scope2.pageOfRound = {
                                at: 1,
                                size: 5,
                                j: function() {
                                    return '&page=' + this.at + '&size=' + this.size;
                                }
                            };
                            $scope2.criteria = oCriteria = {
                                byUser: '',
                                byRid: '',
                                byOp: 'ALL'
                            };
                            $scope2.doSearchRound = function() {
                                var url = '/rest/pl/fe/matter/enroll/round/list?site=' + _siteId + '&app=' + _appId + $scope2.pageOfRound.j();
                                http2.get(url, function(rsp) {
                                    oCriteria.byRid = rsp.data.active.rid;
                                    $scope2.activeRound = rsp.data.active;
                                    $scope2.rounds = rsp.data.rounds;
                                    $scope2.rounds.total = rsp.data.total;
                                });
                            };
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope2.ok = function() {
                                defer.resolve(oCriteria);
                                $mi.close();
                            };
                            $scope2.doSearchRound();
                        }],
                        backdrop: 'static',
                    });
                    return defer.promise;
                }
            };
        }];
    });
    /**
     * template
     */
    ngModule.provider('srvTempApp', function() {
        function _fnMapSchemas(app) {
            var mapOfSchemaByType = {},
                mapOfSchemaById = {},
                mapOfUnionSchemaById = {},
                enrollDataSchemas = [],
                groupDataSchemas = [],
                canFilteredSchemas = [];

            app.dataSchemas.forEach(function(schema) {
                mapOfSchemaByType[schema.type] === undefined && (mapOfSchemaByType[schema.type] = []);
                mapOfSchemaByType[schema.type].push(schema.id);
                mapOfSchemaById[schema.id] = schema;
                mapOfUnionSchemaById[schema.id] = schema;
                if (false === /image|file/.test(schema.type)) {
                    canFilteredSchemas.push(schema);
                }
            });
            // 关联的报名登记项
            if (app.enrollApp && app.enrollApp.dataSchemas) {
                app.enrollApp.dataSchemas.forEach(function(item) {
                    if (mapOfUnionSchemaById[item.id] === undefined) {
                        mapOfUnionSchemaById[item.id] = item;
                        enrollDataSchemas.push(item);
                    }
                });
            }
            // 关联的分组活动的登记项
            if (app.groupApp && app.groupApp.data_schemas) {
                app.groupApp.data_schemas.forEach(function(item) {
                    if (mapOfUnionSchemaById[item.id] === undefined) {
                        mapOfUnionSchemaById[item.id] = item;
                        groupDataSchemas.push(item);
                    }
                });
            }

            app._schemasByType = mapOfSchemaByType;
            app._schemasById = mapOfSchemaById;
            app._unionSchemasById = mapOfUnionSchemaById;
            app._schemasCanFilter = canFilteredSchemas;
            app._schemasFromEnrollApp = enrollDataSchemas;
            app._schemasFromGroupApp = groupDataSchemas;
        }
        var _siteId, _appId, _oApp, _vId, _getAppDeferred = false;
        this.config = function(siteId, appId, vId) {
            _siteId = siteId;
            _appId = appId;
            _vId = vId;
        };
        this.$get = ['$q', 'http2', 'noticebox', '$uibModal', function($q, http2, noticebox, $uibModal) {
            var _self = {
                tempEnrollGet: function() {
                    var url;

                    if (_getAppDeferred) {
                        return _getAppDeferred.promise;
                    }
                    _getAppDeferred = $q.defer();
                    if (_vId) {
                        url = '/rest/pl/fe/template/get?site=' + _siteId + '&tid=' + _appId + '&vid=' + _vId;
                    } else {
                        url = '/rest/pl/fe/template/get?site=' + _siteId + '&tid=' + _appId;
                    }
                    http2.get(url, function(rsp) {
                        _oApp = rsp.data;

                        function _tGet(data, method) {
                            try {
                                data.data_schemas = data.data_schemas && data.data_schemas.length ? JSON.parse(data.data_schemas) : [];
                            } catch (e) {
                                console.log('data invalid', e, data.data_schemas);
                                data.data_schemas = [];
                            }
                            method(data);
                            data.data_schemas.forEach(function(schema) {
                                schemaLib._upgrade(schema);
                            });
                            data.pages.forEach(function(page) {
                                pageLib.enhance(page, data._unionSchemasById);
                            });
                        }
                        _tGet(_oApp, _fnMapSchemas);
                        _getAppDeferred.resolve(_oApp);
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

                    url = '/rest/pl/fe/template/update?site=' + _siteId;
                    url += '&tid=' + _appId;
                    url += '&vid=' + _oApp.vid;
                    http2.post(url, modifiedData, function(rsp) {
                        //noticebox.success('完成保存');
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                shareAsTemplate: function() {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/site/template/component/templateShare.html',
                        controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                            $scope.data = {};
                            $scope.params = {};
                            $scope.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope.ok = function() {
                                $mi.close($scope.data);
                            };
                        }],
                        backdrop: 'static'
                    }).result.then(function(data) {
                        http2.post('/rest/pl/fe/template/putCreate?site=' + _siteId + '&tid=' + _appId, data, function(rsp) {
                            location.href = '/rest/pl/fe/template/site?site=' + _siteId;
                        });
                    });
                },
                cancelAsTemplate: function() {
                    var url = '/rest/pl/fe/template/unPut?site=' + _siteId + '&tid=' + _appId;
                    http2.get(url, function(rsp) {
                        location.href = '/rest/pl/fe/template/site?site=' + _siteId;
                    });
                },
                applyToHome: function() {
                    var url = '/rest/pl/fe/template/pushHome?site=' + _siteId;
                    url += '&tid=' + _appId;
                    http2.get(url, function(rsp) {
                        noticebox.success('完成申请！');
                    });
                },
                createVersion: function() {
                    var url;
                    url = '/rest/pl/fe/template/createVersion?site=' + _siteId;
                    url += '&tid=' + _appId;
                    url += '&lastVersion=' + _oApp.last_version;
                    url += '&matterType=' + _oApp.matter_type;
                    http2.get(url, function(rsp) {
                        location.href = '/rest/pl/fe/template/' + _oApp.matter_type + '?site=' + _siteId + '&id=' + _appId + '&vid=' + rsp.data.vid;
                    });
                },
                lookView: function(num) {
                    var url, defer = $q.defer();
                    url = '/rest/pl/fe/template/get?site=' + _siteId;
                    url += '&tid=' + _appId;
                    url += '&vid=' + num;
                    http2.get(url, function(rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                lookDetail: function(id) {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/site/template/component/templateDetail.html',
                        backdrop: 'static',
                        controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                            if (id === undefined) return false;
                            http2.get('/rest/pl/fe/template/getVersion?site=' + _siteId + '&tid=' + _appId + '&vid=' + id, function(rsp) {
                                $scope.version = rsp.data;
                            });
                            $scope.cancel = function() {
                                $mi.dismiss();
                            };
                        }]
                    });

                },
                addReceiver: function(shareUser) {
                    var defer = $q.defer(),
                        url;
                    url = '/rest/pl/fe/template/acl/add?label=' + shareUser.label;
                    url += '&site=' + _siteId;
                    url += '&tid=' + _appId;
                    http2.get(url, function(rsp) {
                        if (_oApp.acl === undefined) {
                            _oApp.acl = [];
                        }
                        _oApp.acl.push(rsp.data);
                        defer.resolve(_oApp);
                    });
                    return defer.promise;
                },
                removeReceiver: function(acl) {
                    var defer = $q.defer(),
                        url;
                    url = '/rest/pl/fe/template/acl/remove';
                    url += '?acl=' + acl.id;
                    http2.get(url, function(rsp) {
                        angular.forEach(_oApp.acl, function(item, index) {
                            if (item.id == acl.id) {
                                _oApp.acl.splice(index, 1);
                            }
                        })
                        defer.resolve();
                    });
                    return defer.promise;
                },
                removeAsTemplate: function() {
                    var defer = $q.defer(),
                        url;
                    url = '/rest/pl/fe/template/remove?site=' + _siteId + '&tid=' + _appId;
                    http2.get(url, function(rsp) {
                        defer.resolve();
                    });
                    return defer.promise;
                },
            }
            return _self;
        }];
    });
    ngModule.provider('srvTempPage', function() {
        var _siteId, _appId;
        this.config = function(siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$uibModal', '$q', 'http2', 'noticebox', 'srvEnrollApp', 'srvTempApp', function($uibModal, $q, http2, noticebox, srvEnrollApp, srvTempApp) {
            var _self;
            _self = {
                create: function() {
                    var deferred = $q.defer();
                    srvTempApp.tempEnrollGet().then(function(app) {
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
                            http2.post('/rest/pl/fe/template/enroll/add?site=' + _siteId + '&tid=' + _appId + '&vid=' + app.vid, options, function(rsp) {
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
                    srvTempApp.tempEnrollGet().then(function(app) {
                        url = '/rest/pl/fe/template/enroll/updatePage';
                        url += '?site=' + _siteId;
                        url += '&tid=' + _appId;
                        url += '&vid=' + app.vid;
                        url += '&pageId=' + page.id;
                        url += '&cname=' + page.code_name;
                        http2.post(url, updated, function(rsp) {
                            page.$$modified = false;
                            defer.resolve();
                            noticebox.success('完成保存');
                        });
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
                    srvTempApp.tempEnrollGet().then(function(app) {
                        var url = '/rest/pl/fe/template/enroll/remove';
                        url += '?site=' + _siteId;
                        url += '&tid=' + _appId;
                        url += '&vid=' + app.vid;
                        url += '&pageId=' + page.id;
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
    });
    ngModule.provider('srvTempRecord', function() {
        var _siteId, _appId;
        this.config = function(siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$q', 'http2', function($q, http2) {
            var _self = {
                list: function(article, page) {
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
                                return p;
                            }
                        });
                    }
                    url = '/rest/pl/fe/template/order/listPurchaser?site=' + _siteId + '&tid=' + _appId;
                    url += page._j();
                    http2.get(url, function(rsp) {
                        rsp.data.total && (page.total = rsp.data.total);
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                }
            }
            return _self;
        }];
    });
    /**
     * notice
     */
    ngModule.provider('srvEnrollNotice', function() {
        this.$get = ['$q', 'http2', function($q, http2) {
            return {
                detail: function(batch) {
                    var defer = $q.defer(),
                        url;
                    url = '/rest/pl/fe/matter/enroll/notice/logList?batch=' + batch.id;
                    http2.get(url, function(rsp) {
                        defer.resolve(rsp.data);
                    });

                    return defer.promise;
                }
            };
        }];
    });
    /**
     * filter
     */
    ngModule.controller('ctrlEnrollFilter', ['$scope', '$uibModalInstance', 'dataSchemas', 'criteria', 'srvEnlRnd', 'app', function($scope, $mi, dataSchemas, lastCriteria, srvEnlRnd, oApp) {
        var canFilteredSchemas = [];

        if (!oApp.group_app_id) {
            if (oApp.entryRule && oApp.entryRule.scope && oApp.entryRule.scope.group === 'Y' && oApp.entryRule.group && oApp.entryRule.group.id) {
                $scope.bRequireGroup = true;
                $scope.groups = oApp.groups;
            }
        }
        dataSchemas.forEach(function(schema) {
            if (false === /image|file|score|html/.test(schema.type) && schema.id.indexOf('member') !== 0) {
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
        $scope.checkedRounds = {};
        $scope.toggleCheckedRound = function(rid) {
            if (rid === 'ALL' && $scope.checkedRounds.ALL) {
                $scope.checkedRounds = { ALL: true };
            } else if ($scope.checkedRounds[rid]) {
                $scope.checkedRounds.ALL = false;
            }
        };
        $scope.clean = function() {
            var oCriteria = $scope.criteria;
            if (oCriteria.record) {
                if (oCriteria.record.verified) {
                    oCriteria.record.verified = '';
                }
                if (oCriteria.record.rnd) {
                    oCriteria.record.rnd = [];
                }
            }
            if (oCriteria.data) {
                angular.forEach(oCriteria.data, function(val, key) {
                    oCriteria.data[key] = '';
                });
            }
        };
        $scope.ok = function() {
            var oCriteria = $scope.criteria,
                optionCriteria;
            /* 将单选题/多选题的结果拼成字符串 */
            canFilteredSchemas.forEach(function(schema) {
                var result;
                if (/multiple/.test(schema.type)) {
                    if ((optionCriteria = oCriteria.data[schema.id])) {
                        result = [];
                        Object.keys(optionCriteria).forEach(function(key) {
                            optionCriteria[key] && result.push(key);
                        });
                        oCriteria.data[schema.id] = result.join(',');
                    }
                }
            });
            /* 将选中的轮次拼成数组 */
            if (!oCriteria.record) {
                $oCriteria.record = {};
            }
            oCriteria.record.rid = [];
            if (Object.keys($scope.checkedRounds).length) {
                angular.forEach($scope.checkedRounds, function(v, k) {
                    if (v) {
                        oCriteria.record.rid.push(k);
                    }
                });
            }
            $mi.close(oCriteria);
        };
        $scope.cancel = function() {
            $mi.dismiss('cancel');
        };
        $scope.doSearchRound = function() {
            srvEnlRnd.list().then(function(oResult) {
                var oCriteria = $scope.criteria;
                $scope.activeRound = oResult.active;
                if ($scope.activeRound) {
                    var otherRounds = [];
                    oResult.rounds.forEach(function(oRound) {
                        if (oRound.rid !== $scope.activeRound.rid) {
                            otherRounds.push(oRound);
                        }
                    });
                    $scope.rounds = otherRounds;
                } else {
                    $scope.rounds = oResult.rounds;
                }
                $scope.pageOfRound = oResult.page;
                if (!oCriteria.record) {
                    oCriteria.record = { rid: [] };
                }
                if (oCriteria.record.rid.length) {
                    oCriteria.record.rid.forEach(function(rid) {
                        $scope.checkedRounds[rid] = true;;
                    });
                }
            });
        };
        $scope.doSearchRound();
    }])
});