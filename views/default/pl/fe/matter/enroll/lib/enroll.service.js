define(['require', 'frame/templates', 'schema', 'page'], function (require, FrameTemplates, schemaLib, pageLib) {
    'use strict';
    var BaseSrvEnrollRecord = function ($q, http2, noticebox, $uibModal, tmsSchema) {
        this._oApp = null;
        this._oPage = null;
        this._oCriteria = null;
        this._aRecords = null;
        this.init = function (oApp, oPage, oCriteria, oRecords) {
            this._oApp = oApp;
            // schemas
            if (this._oApp._schemasById === undefined) {
                var schemasById = {};
                this._oApp.dataSchemas.forEach(function (schema) {
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
                joinParams: function () {
                    var p;
                    p = '&page=' + this.at + '&size=' + this.size;
                    p += '&orderby=' + this.orderBy;
                    return p;
                },
                setTotal: function (total) {
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
        this._bSearch = function (url) {
            var that = this,
                defer = $q.defer();
            http2.post(url, that._oCriteria).then(function (rsp) {
                var records;
                if (rsp.data) {
                    records = rsp.data.records ? rsp.data.records : [];
                    rsp.data.total && (that._oPage.total = rsp.data.total);
                    that._oPage.setTotal(rsp.data.total);
                } else {
                    records = [];
                }
                records.forEach(function (oRecord) {
                    tmsSchema.forTable(oRecord, that._oApp._unionSchemasById);
                    that._aRecords.push(oRecord);
                });
                defer.resolve(records);
            });
            return defer.promise;
        };
        this._bBatchVerify = function (rows, url) {
            var eks = [],
                selectedRecords = [];
            for (var p in rows.selected) {
                if (rows.selected[p] === true) {
                    eks.push(this._aRecords[p].enroll_key);
                    selectedRecords.push(this._aRecords[p]);
                }
            }
            if (eks.length) {
                http2.post(url, {
                    eks: eks
                }).then(function () {
                    selectedRecords.forEach(function (record) {
                        record.verified = 'Y';
                    });
                    noticebox.success('完成操作');
                });
            }
        };
        this._bGetAfter = function (oEnrollApp, fnCallback) {
            oEnrollApp.tags = (!oEnrollApp.tags || oEnrollApp.tags.length === 0) ? [] : oEnrollApp.tags.split(',');
            fnCallback(oEnrollApp);
            if (oEnrollApp.pages) {
                oEnrollApp.pages.forEach(function (oPage) {
                    pageLib.enhance(oPage, oEnrollApp._schemasById);
                });
            }
        };
        this._bFilter = function (srvEnlRnd) {
            var defer = $q.defer(),
                that = this;
            http2.post('/rest/script/time', {
                html: {
                    'filter': '/views/default/pl/fe/matter/enroll/component/recordFilter'
                }
            }).then(function (rsp) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/recordFilter.html?_' + rsp.data.html.filter.time,
                    controller: 'ctrlEnrollFilter',
                    windowClass: 'auto-height',
                    backdrop: 'static',
                    resolve: {
                        app: function () {
                            return that._oApp;
                        },
                        dataSchemas: function () {
                            return that._oApp.dataSchemas;
                        },
                        criteria: function () {
                            return angular.copy(that._oCriteria);
                        },
                        srvEnlRnd: function () {
                            return srvEnlRnd;
                        }
                    }
                }).result.then(function (oCriteria) {
                    defer.resolve();
                    angular.extend(that._oCriteria, oCriteria);
                    that.search(1).then(function () {
                        defer.resolve();
                    });
                });
            });
            return defer.promise;
        }
    };
    var ngModule = angular.module('service.enroll', ['ui.bootstrap', 'ui.xxt', 'service.matter']);
    ngModule.provider('srvEnrollApp', function () {
        function _fnMapAssocEnrollApp(oApp) {
            var enrollDataSchemas = [];
            if (oApp.enrollApp && oApp.enrollApp.dataSchemas) {
                oApp.enrollApp.dataSchemas.forEach(function (item) {
                    if (oApp._unionSchemasById[item.id] === undefined) {
                        item.assocState = '';
                        oApp._unionSchemasById[item.id] = item;
                        enrollDataSchemas.push(item);
                    } else if (oApp._schemasById[item.id].fromApp === oApp.enrollApp.id) {
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
                oApp.groupApp.dataSchemas.forEach(function (item) {
                    if (oApp._unionSchemasById[item.id] === undefined) {
                        item.assocState = '';
                        oApp._unionSchemasById[item.id] = item;
                        groupDataSchemas.push(item);
                    } else if (oApp._schemasById[item.id].fromApp === oApp.groupApp.id) {
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
                oApp.dataSchemas.forEach(function (schema) {
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
        this.config = function (siteId, appId, accessId) {
            _siteId = siteId;
            _appId = appId;
            _accessId = accessId;
        };
        this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'srvSite', function ($q, $uibModal, http2, noticebox, srvSite) {
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
                http2.get(url).then(function (rsp) {
                    _oApp = rsp.data;
                    _ins._bGetAfter(_oApp, _fnMapSchemas);
                    _getAppDeferred.resolve(_oApp);
                });

                return _getAppDeferred.promise;
            }

            var _ins, _self;
            _ins = new BaseSrvEnrollRecord();
            _self = {
                get: function () {
                    return _fnGetApp(_fnMakeApiUrl('get'));
                },
                check: function () {
                    http2.get(_fnMakeApiUrl('check')).then(function () {});
                },
                renew: function (props) {
                    if (_oApp) {
                        http2.get(_fnMakeApiUrl('get')).then(function (rsp) {
                            var oNewApp = rsp.data;
                            if (props && props.length) {
                                props.forEach(function (prop) {
                                    _oApp[prop] = oNewApp[prop];
                                });
                            } else {
                                http2.merge(_oApp, oNewApp);
                            }
                            _ins._bGetAfter(_oApp, _fnMapSchemas);
                        });
                    }
                },
                update: function (names) {
                    var defer = $q.defer(),
                        modifiedData = {};

                    angular.isString(names) && (names = [names]);
                    names.forEach(function (name) {
                        if (/data_schemas|dataSchemas/.test(name)) {
                            modifiedData['dataSchemas'] = _oApp.dataSchemas;
                        } else if (/recycle_schemas|recycleSchemas/.test(name)) {
                            modifiedData['recycle_schemas'] = _oApp.recycleSchemas;
                        } else if (name === 'tags') {
                            modifiedData.tags = _oApp.tags.join(',');
                        } else {
                            modifiedData[name] = _oApp[name];
                        }
                    });
                    http2.post(_fnMakeApiUrl('update'), modifiedData).then(function (rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                remove: function () {
                    var defer = $q.defer();
                    http2.get(_fnMakeApiUrl('remove')).then(function (rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                jumpPages: function () {
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

                    _oApp.pages.forEach(function (page) {
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
                    all.push({
                        name: 'kanban',
                        'title': '活动看板页'
                    });
                    all.push({
                        name: 'repos',
                        'title': '共享数据页'
                    });
                    all.push({
                        name: 'rank',
                        'title': '排行榜'
                    });
                    all.push({
                        name: 'votes',
                        'title': '投票榜'
                    });
                    all.push({
                        name: 'marks',
                        'title': '打分榜'
                    });
                    all.push({
                        name: 'score',
                        'title': '测验结果'
                    });
                    all.push({
                        name: 'stat',
                        'title': '统计页'
                    });
                    otherwise.push({
                        name: 'kanban',
                        'title': '活动看板页'
                    });
                    otherwise.push({
                        name: 'repos',
                        'title': '共享数据页'
                    });
                    otherwise.push({
                        name: 'rank',
                        'title': '排行榜'
                    });
                    otherwise.push({
                        name: 'votes',
                        'title': '投票榜'
                    });
                    otherwise.push({
                        name: 'marks',
                        'title': '打分榜'
                    });
                    otherwise.push({
                        name: 'stat',
                        'title': '统计页'
                    });
                    exclude.push({
                        name: 'kanban',
                        'title': '活动看板页'
                    });
                    exclude.push({
                        name: 'repos',
                        'title': '共享数据页'
                    });
                    exclude.push({
                        name: 'cowork',
                        'title': '讨论页'
                    });
                    exclude.push({
                        name: 'rank',
                        'title': '排行榜'
                    });
                    exclude.push({
                        name: 'votes',
                        'title': '投票榜'
                    });
                    exclude.push({
                        name: 'marks',
                        'title': '打分榜'
                    });
                    exclude.push({
                        name: 'score',
                        'title': '测验结果'
                    });
                    exclude.push({
                        name: 'stat',
                        'title': '统计页'
                    });

                    return {
                        all: all,
                        otherwise: otherwise,
                        exclude: exclude,
                        nonMember: pages4NonMember,
                        nonfan: pages4Nonfan,
                        defaultInput: defaultInput
                    }
                },
                assignMission: function () {
                    var defer = $q.defer();
                    srvSite.openGallery({
                        matterTypes: [{
                            value: 'mission',
                            title: '项目',
                            url: '/rest/pl/fe/matter'
                        }],
                        singleMatter: true
                    }).then(function (missions) {
                        var matter;
                        if (missions.matters.length === 1) {
                            matter = {
                                id: _appId,
                                type: 'enroll'
                            };
                            http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + _siteId + '&id=' + missions.matters[0].id, matter).then(function (rsp) {
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
                                _self.update(updatedFields).then(function () {
                                    defer.resolve(mission);
                                });
                            });
                        }
                    });
                    return defer.promise;
                },
                quitMission: function () {
                    var defer = $q.defer();
                    http2.get(_fnMakeApiUrl('quitMission')).then(function (rsp) {
                        delete _oApp.mission;
                        _oApp.mission_id = 0;
                        _oApp.sync_mission_round = 'N';
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                opData: function () {
                    var deferred = $q.defer();
                    http2.get(_fnMakeApiUrl('opData')).then(function (rsp) {
                        deferred.resolve(rsp.data);
                    });
                    return deferred.promise;
                },
                renewScoreByRound: function (rid) {
                    var url, defer;

                    url = '/rest/pl/fe/matter/enroll/record/renewScoreByRound';
                    url += '?app=' + _appId;
                    if (rid) url += '&rid=' + rid;
                    defer = $q.defer();

                    http2.get(url).then(function (rsp) {
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
    ngModule.service('tkEnrollRound', ['$q', '$uibModal', 'http2', 'CstApp', function ($q, $uibModal, http2, CstApp) {
        function RoundModal(oApp, oRound) {
            this.templateUrl = FrameTemplates.url('roundEditor');
            this.backdrop = 'static';
            this.controller = ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                $scope2.round = angular.copy(oRound);
                $scope2.roundState = CstApp.options.round.state;
                $scope2.$on('xxt.tms-datepicker.change', function (event, data) {
                    if (data.state === 'start_at') {
                        if (data.obj[data.state] == 0 && data.value > 0) {
                            $scope2.round.state = '1';
                        } else if (data.obj[data.state] > 0 && data.value == 0) {
                            $scope2.round.state = '0';
                        }
                    }
                    data.obj[data.state] = data.value;
                });
                $scope2.close = function () {
                    $mi.dismiss();
                };
                $scope2.ok = function () {
                    $mi.close($scope2.round);
                };
                $scope2.stop = function () {
                    $scope2.round.state = '2';
                    $mi.close($scope2.round);
                };
                $scope2.start = function () {
                    $scope2.round.state = '1';
                    $mi.close($scope2.round);
                };
                if (oRound.rid) {
                    $scope2.downloadQrcode = function (url) {
                        $('<a href="' + url + '" download="记录活动轮次二维码.png"></a>')[0].click();
                    };
                    var rndEntryUrl;
                    rndEntryUrl = oApp.entryUrl + '&rid=' + oRound.rid;
                    $scope2.entry = {
                        url: rndEntryUrl,
                        qrcode: '/rest/site/fe/matter/enroll/qrcode?site=' + oApp.siteid + '&url=' + encodeURIComponent(rndEntryUrl),
                    }
                    if (oApp.mission) {
                        http2.get('/rest/pl/fe/matter/mission/round/list?mission=' + oApp.mission.id).then(function (rsp) {
                            $scope2.missionRounds = rsp.data.rounds;
                        });
                    }
                }
            }];
        }
        this.add = function (oApp) {
            var defer;
            defer = $q.defer();
            $uibModal.open(new RoundModal(oApp, {
                state: '0',
                start_at: '0',
                purpose: 'C',
            })).result.then(function (oNewRound) {
                http2.post('/rest/pl/fe/matter/enroll/round/add?app=' + oApp.id, oNewRound).then(function (rsp) {
                    defer.resolve(rsp.data);
                });
            });

            return defer.promise;
        };
        this.edit = function (oApp, oRound) {
            var defer;
            defer = $q.defer();
            $uibModal.open(new RoundModal(oApp, oRound)).result.then(function (oNewRound) {
                var url;
                url = '/rest/pl/fe/matter/enroll/round/update?app=' + oApp.id + '&rid=' + oRound.rid;
                http2.post(url, oNewRound).then(function (rsp) {
                    defer.resolve(rsp.data);
                });
            });

            return defer.promise;
        };
        this.remove = function (oApp, oRound) {
            var url = '/rest/pl/fe/matter/enroll/round/remove?app=' + oApp.id + '&rid=' + oRound.rid;
            return http2.get(url);
        };
        this.list = function (oApp, oPage) {
            var url, defer;
            defer = $q.defer();
            url = '/rest/pl/fe/matter/enroll/round/list?app=' + oApp.id;
            http2.get(url, {
                page: oPage
            }).then(function (rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
    }]);
    ngModule.provider('srvEnrollRound', function () {
        var _rounds, _oPage;
        this.$get = ['$q', '$uibModal', 'http2', 'srvEnrollApp', 'tkEnrollRound', function ($q, $uibModal, http2, srvEnrollApp, tkEnlRnd) {
            return {
                init: function (rounds, page) {
                    _rounds = rounds;
                    _oPage = page;
                },
                list: function (checkRid) {
                    var defer = $q.defer();
                    srvEnrollApp.get().then(function (oApp) {
                        var url;
                        if (_rounds === undefined) {
                            _rounds = [];
                        }
                        if (_oPage === undefined) {
                            _oPage = {};
                        }
                        url = '/rest/pl/fe/matter/enroll/round/list?app=' + oApp.id;
                        if (checkRid) {
                            url += '&checked=' + checkRid;
                        }
                        http2.get(url, {
                            page: _oPage
                        }).then(function (rsp) {
                            var _oCheckedRnd;
                            _rounds.splice(0, _rounds.length);
                            rsp.data.rounds.forEach(function (rnd) {
                                rsp.data.active && (rnd._isActive = rnd.rid === rsp.data.active.rid);
                                _rounds.push(rnd);
                            });
                            _oCheckedRnd = (rsp.data.checked ? rsp.data.checked : '');
                            defer.resolve({
                                rounds: _rounds,
                                page: _oPage,
                                active: rsp.data.active,
                                checked: _oCheckedRnd
                            });
                        });
                    });

                    return defer.promise;
                },
                add: function () {
                    srvEnrollApp.get().then(function (oApp) {
                        tkEnlRnd.add(oApp).then(function (oNewRound) {
                            if (_rounds.length > 0 && oNewRound.state == 1) {
                                _rounds[0].state = 2;
                            }
                            _rounds.splice(0, 0, oNewRound);
                            _oPage.total++;
                        });
                    });
                },
                edit: function (oRound) {
                    srvEnrollApp.get().then(function (oApp) {
                        tkEnlRnd.edit(oApp, oRound).then(function (oNewRound) {
                            angular.extend(oRound, oNewRound);
                        });
                    });
                },
                remove: function (oRound) {
                    srvEnrollApp.get().then(function (oApp) {
                        tkEnlRnd.remove(oApp, oRound).then(function () {
                            _rounds.splice(_rounds.indexOf(oRound), 1);
                            _oPage.total--;
                        });
                    });
                }
            };
        }];
    });
    /**
     * record
     */
    ngModule.provider('srvEnrollRecord', function () {
        var _siteId, _appId;
        this.config = function (siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$q', 'http2', 'noticebox', '$uibModal', 'pushnotify', 'CstApp', 'srvEnrollRound', 'tmsSchema', function ($q, http2, noticebox, $uibModal, pushnotify, CstApp, srvEnlRnd, tmsSchema) {
            var _ins = new BaseSrvEnrollRecord($q, http2, noticebox, $uibModal, tmsSchema);
            _ins.search = function (pageNumber) {
                var url;

                this._aRecords.splice(0, this._aRecords.length);
                pageNumber && (this._oPage.at = pageNumber);
                url = '/rest/pl/fe/matter/enroll/record/list';
                url += '?site=' + this._oApp.siteid;
                url += '&app=' + this._oApp.id;
                url += this._oPage.joinParams();

                return _ins._bSearch(url);
            };
            _ins.searchRecycle = function (pageNumber) {
                var defer = $q.defer(),
                    url;

                this._aRecords.splice(0, this._aRecords.length);
                pageNumber && (this._oPage.at = pageNumber);
                url = '/rest/pl/fe/matter/enroll/record/recycle';
                url += '?site=' + _siteId;
                url += '&app=' + _appId;
                url += this._oPage.joinParams();
                http2.get(url).then(function (rsp) {
                    var records;
                    if (rsp.data) {
                        records = rsp.data.records ? rsp.data.records : [];
                        rsp.data.total && (_ins._oPage.total = rsp.data.total);
                    } else {
                        records = [];
                    }
                    records.forEach(function (record) {
                        tmsSchema.forTable(record, _ins._oApp._unionSchemasById);
                        _ins._aRecords.push(record);
                    });
                    defer.resolve(records);
                });

                return defer.promise;
            };
            _ins.filter = function () {
                return _ins._bFilter(srvEnlRnd);
            };
            _ins.get = function (ek) {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/matter/enroll/record/get?ek=' + ek).then(function (rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.add = function (newRecord) {
                var defer = $q.defer();
                http2.post('/rest/pl/fe/matter/enroll/record/add?site=' + _siteId + '&app=' + _appId, newRecord).then(function (rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.update = function (oRecord, oUpdated) {
                var defer = $q.defer();
                http2.post('/rest/pl/fe/matter/enroll/record/update?site=' + _siteId + '&app=' + _appId + '&ek=' + oRecord.enroll_key, oUpdated).then(function (rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.batchTag = function (rows) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/batchTag.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        $scope2.appTags = angular.copy(_ins._oApp.tags);
                        $scope2.data = {
                            tags: []
                        };
                        $scope2.ok = function () {
                            $mi.close({
                                tags: $scope2.data.tags,
                                appTags: $scope2.appTags
                            });
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.$on('tag.xxt.combox.done', function (event, aSelected) {
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
                        $scope2.$on('tag.xxt.combox.add', function (event, newTag) {
                            $scope2.data.tags.push(newTag);
                            $scope2.appTags.indexOf(newTag) === -1 && $scope2.appTags.push(newTag);
                        });
                        $scope2.$on('tag.xxt.combox.del', function (event, removed) {
                            $scope2.data.tags.splice($scope2.data.tags.indexOf(removed), 1);
                        });
                    }],
                    backdrop: 'static',
                }).result.then(function (result) {
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
                        http2.post('/rest/pl/fe/matter/enroll/record/batchTag?site=' + _siteId + '&app=' + _appId, posted).then(function (rsp) {
                            var m, n, newTag;
                            n = result.tags.length;
                            selectedRecords.forEach(function (record) {
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
            _ins.remove = function (record) {
                if (window.confirm('确认删除？')) {
                    http2.get('/rest/pl/fe/matter/enroll/record/remove?app=' + _appId + '&ek=' + record.enroll_key).then(function (rsp) {
                        var i = _ins._aRecords.indexOf(record);
                        _ins._aRecords.splice(i, 1);
                        _ins._oPage.total = _ins._oPage.total - 1;
                    });
                }
            };
            _ins.batchRemove = function (oTmsRows) {
                function removeNext(p) {
                    var oRec;
                    if (p < rowIndexes.length) {
                        oRec = _ins._aRecords[rowIndexes[p]];
                        http2.get('/rest/pl/fe/matter/enroll/record/remove?app=' + _appId + '&ek=' + oRec.enroll_key).then(function (rsp) {
                            removedRecords.push(oRec);
                            removeNext(++p);
                        });
                    } else {
                        removedRecords.forEach(function (oRemoved) {
                            _ins._aRecords.splice(_ins._aRecords.indexOf(oRemoved), 1);
                            _ins._oPage.total = _ins._oPage.total - 1;
                        });
                        oTmsRows.reset();
                    }
                }
                var rowIndexes, removedRecords;
                rowIndexes = oTmsRows.indexes();
                if (rowIndexes.length) {
                    removedRecords = [];
                    removeNext(0);
                }
            };
            _ins.restore = function (record) {
                if (window.confirm('确认恢复？')) {
                    http2.get('/rest/pl/fe/matter/enroll/record/recover?app=' + _appId + '&ek=' + record.enroll_key).then(function (rsp) {
                        var i = _ins._aRecords.indexOf(record);
                        _ins._aRecords.splice(i, 1);
                        _ins._oPage.total = _ins._oPage.total - 1;
                    });
                }
            };
            _ins.empty = function () {
                var _this = this,
                    vcode;
                vcode = prompt('是否要删除所有登记信息？，若是，请输入活动名称。');
                if (vcode === _ins._oApp.title) {
                    http2.get('/rest/pl/fe/matter/enroll/record/empty?app=' + _appId).then(function (rsp) {
                        _ins._aRecords.splice(0, _ins._aRecords.length);
                        _ins._oPage.total = 0;
                        _ins._oPage.at = 1;
                    });
                }
            };
            _ins.verifyAll = function () {
                if (window.confirm('确定审核通过所有记录（共' + _ins._oPage.total + '条）？')) {
                    http2.get('/rest/pl/fe/matter/enroll/record/batchVerify?app=' + _appId + '&all=Y').then(function (rsp) {
                        _ins._aRecords.forEach(function (record) {
                            record.verified = 'Y';
                        });
                        noticebox.success('完成操作');
                    });
                }
            };
            _ins.batchVerify = function (rows) {
                var url;
                if (window.confirm('确定审核通过选中的记录（共' + Object.keys(rows.selected).length + '条）？')) {
                    url = '/rest/pl/fe/matter/enroll/record/batchVerify?app=' + _appId;
                    return _ins._bBatchVerify(rows, url);
                }
            };
            _ins.renewScore = function (oTmsRows) {
                function fnRenewScore(i) {
                    if (i < eks.length) {
                        http2.get(url + '&ek=' + eks[i]).then(function (rsp) {
                            noticebox.success('第【' + (i + 1) + '】条记录更新完成');
                            fnRenewScore(++i);
                        });
                    } else {
                        defer.resolve();
                    }
                }
                var eks, defer, url;

                defer = $q.defer();
                eks = oTmsRows.walk(_ins._aRecords, function (oRec) {
                    return oRec.enroll_key;
                });
                if (eks.length) {
                    url = '/rest/pl/fe/matter/enroll/record/renewScore';
                    url += '?app=' + _appId;
                    fnRenewScore(0);
                } else {
                    defer.reject();
                }

                return defer.promise;
            };
            _ins.notify = function (rows) {
                var options = {
                    matterTypes: CstApp.notifyMatter,
                    sender: 'enroll:' + _appId
                };
                _ins._oApp.mission && (options.missionId = _ins._oApp.mission.id);
                pushnotify.open(_siteId, function (notify) {
                    var url, targetAndMsg = {};
                    if (notify.matters.length) {
                        if (rows) {
                            targetAndMsg.users = [];
                            Object.keys(rows.selected).forEach(function (key) {
                                if (rows.selected[key] === true) {
                                    var rec = _ins._aRecords[key];
                                    if (Object.keys(rec).indexOf('enroll_key') !== -1) {
                                        targetAndMsg.users.push({
                                            userid: rec.userid,
                                            enroll_key: rec.enroll_key
                                        });
                                    } else {
                                        targetAndMsg.users.push({
                                            userid: rec.userid
                                        });
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

                        http2.post(url, targetAndMsg).then(function (data) {
                            noticebox.success('发送完成');
                        });
                    }
                }, options);
            };
            _ins.export = function () {
                var url, oCriteria;
                oCriteria = {};
                if (_ins._oCriteria.keyword) {
                    oCriteria.keyword = _ins._oCriteria.keyword;
                }
                if (_ins._oCriteria.data && Object.keys(_ins._oCriteria.data).length) {
                    var oFilterDat = {};
                    angular.forEach(_ins._oCriteria.data, function (v, k) {
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
            _ins.exportImage = function () {
                var url;
                url = '/rest/pl/fe/matter/enroll/record/exportImage';
                url += '?site=' + _siteId + '&app=' + _appId;
                window.open(url);
            };
            _ins.chooseImage = function (imgFieldName) {
                var defer = $q.defer();
                if (imgFieldName !== null) {
                    var ele = document.createElement('input');
                    ele.setAttribute('type', 'file');
                    ele.addEventListener('change', function (evt) {
                        var i, cnt, f, type;
                        cnt = evt.target.files.length;
                        for (i = 0; i < cnt; i++) {
                            f = evt.target.files[i];
                            type = {
                                ".jp": "image/jpeg",
                                ".pn": "image/png",
                                ".gi": "image/gif"
                            } [f.name.match(/\.(\w){2}/g)[0] || ".jp"];
                            f.type2 = f.type || type;
                            var reader = new FileReader();
                            reader.onload = (function (theFile) {
                                return function (e) {
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
            _ins.syncByEnroll = function (record) {
                var url;

                url = '/rest/pl/fe/matter/enroll/record/matchEnroll';
                url += '?site=' + _siteId;
                url += '&app=' + _appId;

                http2.post(url, record.data).then(function (rsp) {
                    var matched;
                    if (rsp.data && rsp.data.length === 1) {
                        matched = rsp.data[0];
                        angular.extend(record.data, matched);
                    } else {
                        alert('没有找到匹配的记录，请检查数据是否一致');
                    }
                });
            };
            _ins.syncByGroup = function (oRecord) {
                var url;
                url = '/rest/pl/fe/matter/enroll/record/matchGroup?app=' + _appId;
                http2.post(url, oRecord.data).then(function (rsp) {
                    angular.extend(oRecord.data, rsp.data.data);
                    noticebox.success('找到匹配记录');
                });
            };
            /**
             * 从其他活动导入记录
             */
            _ins.importByOther = function () {
                var defer = $q.defer();
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/record/importByOther.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        function doSearchRnd(appId, oDataset) {
                            http2.get('/rest/pl/fe/matter/enroll/round/list?app=' + appId, {
                                page: oDataset.page
                            }).then(function (rsp) {
                                oDataset.data = rsp.data.rounds;
                                _oData.fromRnd = oDataset.data[0];
                            });
                        }
                        var _oData;
                        $scope2.data = _oData = {};
                        $scope2.rounds = {
                            page: {}
                        };
                        $scope2.fromApps = {
                            page: {},
                            filter: {}
                        };
                        $scope2.fromRnds = {
                            page: {}
                        };
                        $scope2.ok = function () {
                            $mi.close(_oData);
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss('cancel');
                        };
                        $scope2.doFilter = function () {
                            $scope2.fromApps.page.at = 1;
                            $scope2.doSearchFromApp();
                        };
                        $scope2.doSearchRnd = function () {
                            doSearchRnd(_appId, $scope2.rounds);
                        };
                        $scope2.doSearchFromApp = function () {
                            var url = '/rest/pl/fe/matter/enroll/list?site=' + _siteId;
                            http2.post(url, {
                                byTitle: $scope2.fromApps.filter.byTitle
                            }, {
                                page: $scope2.fromApps.page
                            }).then(function (rsp) {
                                $scope2.fromApps.data = rsp.data.apps;
                                if ($scope2.fromApps.data.length) {
                                    _oData.fromApp = $scope2.fromApps.data[0];
                                    $scope2.doSearchFromRnd(1);
                                }
                            });
                        };
                        $scope2.doSearchFromRnd = function (at) {
                            if (at) {
                                $scope2.fromRnds.page.at = at;
                            }
                            doSearchRnd(_oData.fromApp.id, $scope2.fromRnds);
                        };
                        $scope2.$watch('data.fromApp', function (oFromApp) {
                            if (oFromApp) {
                                $scope2.doSearchFromRnd(1);
                                http2.get('/rest/pl/fe/matter/enroll/schema/compatible?app1=' + _appId + '&app2=' + oFromApp.id).then(function (rsp) {
                                    _oData.compatibleSchemas = rsp.data;
                                });
                            }
                        });
                        $scope2.$watch('data.fromRnd', function (oFromRnd) {
                            if (oFromRnd) {
                                http2.get('/rest/pl/fe/matter/enroll/record/countByRound?round=' + oFromRnd.rid).then(function (rsp) {
                                    _oData.countOfRecord = rsp.data;
                                });
                            }
                        });
                        $scope2.$watch('data', function (oNewData) {
                            if (oNewData) {
                                $scope2.executable = (!!oNewData.toRnd && !!oNewData.fromApp && !!oNewData.fromRnd && !!oNewData.compatibleSchemas && oNewData.compatibleSchemas.length > 0 && oNewData.countOfRecord > 0);
                            }
                        }, true);
                        $scope2.doSearchRnd();
                        $scope2.doSearchFromApp();
                    }],
                    backdrop: 'static',
                    size: 'lg',
                    windowClass: 'auto-height'
                }).result.then(function (_oData) {
                    var url = '/rest/pl/fe/matter/enroll/record/importByOther?site=' + _siteId + '&app=' + _appId;
                    url += '&fromApp=' + _oData.fromApp.id;
                    if (_oData.toRnd) {
                        url += '&toRnd=' + _oData.toRnd.rid;
                    }
                    if (_oData.fromRnd) {
                        url += '&fromRnd=' + _oData.fromRnd.rid;
                    }
                    http2.post(url, {}).then(function (rsp) {
                        noticebox.info('导入（' + rsp.data + '）条数据');
                        _ins.search(1).then(function () {
                            defer.resolve();
                        });
                    });
                });
                return defer.promise;
            };
            _ins.exportToOther = function (oApp, rows) {
                var defer, eks;
                if (rows) {
                    eks = [];
                    Object.keys(rows.selected).forEach(function (key) {
                        if (rows.selected[key] === true) {
                            eks.push(_ins._aRecords[key].enroll_key);
                        }
                    });
                }
                defer = $q.defer();
                if (!eks || eks.length === 0) {
                    defer.reject();
                } else {
                    http2.post('/rest/script/time', {
                        html: {
                            'export': '/views/default/pl/fe/matter/enroll/component/record/exportToOther'
                        }
                    }).then(function (rsp) {
                        $uibModal.open({
                            templateUrl: '/views/default/pl/fe/matter/enroll/component/record/exportToOther.html?_=' + rsp.data.html.export.time,
                            controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                                var page, data, filter;
                                $scope2.sourceApp = oApp;
                                $scope2.page = page = {};
                                $scope2.data = data = {
                                    mappings: {}
                                };
                                $scope2.filter = filter = {};
                                $scope2.ok = function () {
                                    $mi.close(data);
                                };
                                $scope2.cancel = function () {
                                    $mi.dismiss('cancel');
                                };
                                $scope2.doFilter = function () {
                                    page.at = 1;
                                    $scope2.doSearch();
                                };
                                $scope2.doSearch = function () {
                                    var url = '/rest/pl/fe/matter/enroll/list?site=' + _siteId;
                                    http2.post(url, {
                                        byTitle: filter.byTitle
                                    }, {
                                        page: page
                                    }).then(function (rsp) {
                                        $scope2.apps = rsp.data.apps;
                                        if ($scope2.apps.length) {
                                            data.fromApp = $scope2.apps[0];
                                        }
                                        $scope2.apps.forEach(function (oApp) {
                                            oApp.dataSchemas = JSON.parse(oApp.data_schemas);
                                        });
                                    });
                                };
                                $scope2.doSearch();
                            }],
                            backdrop: 'static',
                            size: 'lg'
                        }).result.then(function (data) {
                            var url;
                            if (data.fromApp && data.fromApp.id && data.mappings) {
                                url = '/rest/pl/fe/matter/enroll/record/exportToOther';
                                url += '?app=' + oApp.id;
                                url += '&targetApp=' + data.fromApp.id;
                                http2.post(url, {
                                    mappings: data.mappings,
                                    eks: eks
                                }).then(function (rsp) {
                                    noticebox.success('导入【' + rsp.data + '】条记录');
                                    defer.resolve(rsp.data);
                                });
                            }
                        });
                    });
                }

                return defer.promise;
            };
            _ins.transferVotes = function (oApp) {
                var defer;
                defer = $q.defer();
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/transferVotes.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        var oPage, oResult, oFilter;
                        $scope2.page = oPage = {};
                        $scope2.result = oResult = {
                            limit: {
                                scope: 'top',
                                num: 3
                            },
                            targetSchema: null,
                            votingSchemas: []
                        };
                        $scope2.votingSchemas = [];
                        oApp.dataSchemas.forEach(function (oSchema) {
                            if (/single|multiple/.test(oSchema.type)) {
                                $scope2.votingSchemas.push(angular.copy(oSchema));
                            }
                        });
                        $scope2.filter = oFilter = {};
                        $scope2.selectApp = function () {
                            if (angular.isString(oResult.fromApp.data_schemas) && oResult.fromApp.data_schemas) {
                                oResult.fromApp.dataSchemas = JSON.parse(oResult.fromApp.data_schemas);
                            }
                            oResult.schemas = [];
                        };
                        $scope2.selectSchema = function (oSchema) {
                            if (oSchema._selected) {
                                oResult.votingSchemas.push(oSchema.id);
                            } else {
                                oResult.votingSchemas.splice(oResult.votingSchemas.indexOf(oSchema.id), 1);
                            }
                        };
                        $scope2.ok = function () {
                            $mi.close(oResult);
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.doFilter = function () {
                            oPage.at = 1;
                            $scope2.doSearch();
                        };
                        $scope2.doSearch = function () {
                            var url = '/rest/pl/fe/matter/enroll/list?site=' + oApp.siteid;
                            http2.post(url, {
                                byTitle: oFilter.byTitle
                            }, {
                                page: oPage
                            }).then(function (rsp) {
                                $scope2.apps = rsp.data.apps;
                                if ($scope2.apps.length) {
                                    oResult.fromApp = $scope2.apps[0];
                                    $scope2.selectApp();
                                }
                            });
                        };
                        $scope2.doSearch();
                    }],
                    backdrop: 'static',
                    size: 'lg'
                }).result.then(function (oResult) {
                    var url;
                    if (oResult.fromApp && oResult.targetSchema && oResult.votingSchemas.length) {
                        url = '/rest/pl/fe/matter/enroll/record/transferVotes';
                        url += '?app=' + oApp.id;
                        url += '&targetApp=' + oResult.fromApp.id;
                        http2.post(url, {
                            targetSchema: oResult.targetSchema,
                            votingSchemas: oResult.votingSchemas,
                            limit: oResult.limit
                        }).then(function (rsp) {
                            noticebox.info('创建（' + rsp.data + '）条记录');
                            defer.resolve(rsp);
                        });
                    }
                });
                return defer.promise;
            };
            _ins.transferSchemaAndVotes = function (oApp) {
                var defer;
                defer = $q.defer();
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/transferSchemaAndVotes.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        var oPage, oResult, oFilter;
                        $scope2.page = oPage = {};
                        $scope2.result = oResult = {
                            votingSchemas: [],
                            limit: {
                                scope: 'top',
                                num: 3
                            }
                        };
                        $scope2.votingSchemas = [];
                        oApp.dynaDataSchemas.forEach(function (oSchema) {
                            if (/single|multiple/.test(oSchema.type)) {
                                $scope2.votingSchemas.push(angular.copy(oSchema));
                            }
                        });
                        $scope2.filter = oFilter = {};
                        $scope2.selectApp = function () {
                            oResult.questionSchemas = [];
                            oResult.answerSchemas = [];
                            if (angular.isString(oResult.fromApp.data_schemas) && oResult.fromApp.data_schemas) {
                                oResult.fromApp.dataSchemas = JSON.parse(oResult.fromApp.data_schemas);
                                oResult.fromApp.dataSchemas.forEach(function (oSchema) {
                                    if (/shorttext|longtext/.test(oSchema.type)) {
                                        oResult.questionSchemas.push(oSchema);
                                    } else if ('multitext' === oSchema.type) {
                                        oResult.answerSchemas.push(oSchema);
                                    }
                                });
                            }
                        };
                        $scope2.selectSchema = function (oSchema) {
                            if (oSchema._selected) {
                                oResult.votingSchemas.push(oSchema.id);
                            } else {
                                oResult.votingSchemas.splice(oResult.votingSchemas.indexOf(oSchema.id), 1);
                            }
                        };
                        $scope2.ok = function () {
                            $mi.close(oResult);
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.doFilter = function () {
                            oPage.at = 1;
                            $scope2.doSearch();
                        };
                        $scope2.doSearch = function () {
                            var url = '/rest/pl/fe/matter/enroll/list?site=' + oApp.siteid;
                            http2.post(url, {
                                byTitle: oFilter.byTitle
                            }, {
                                page: oPage
                            }).then(function (rsp) {
                                $scope2.apps = rsp.data.apps;
                                if ($scope2.apps.length) {
                                    oResult.fromApp = $scope2.apps[0];
                                    $scope2.selectApp();
                                }
                            });
                        };
                        $scope2.disabled = true; // 选择的参数是否完整
                        $scope2.$watch('result', function () {
                            $scope2.disabled = false;
                            if (!oResult.votingSchemas || oResult.votingSchemas.length === 0) $scope2.disabled = true;
                            if (!oResult.fromApp) $scope2.disabled = true;
                            if (!oResult.answerSchema) $scope2.disabled = true;
                            if (!oResult.questionSchema) $scope2.disabled = true;
                        }, true);
                        $scope2.doSearch();
                    }],
                    backdrop: 'static',
                    windowClass: 'auto-height',
                    size: 'lg'
                }).result.then(function (oResult) {
                    var url;
                    if (oResult.fromApp && oResult.questionSchema && oResult.answerSchema && oResult.votingSchemas.length) {
                        url = '/rest/pl/fe/matter/enroll/record/transferSchemaAndVotes';
                        url += '?app=' + oApp.id;
                        url += '&targetApp=' + oResult.fromApp.id;
                        http2.post(url, {
                            questionSchema: oResult.questionSchema,
                            answerSchema: oResult.answerSchema,
                            votingSchemas: oResult.votingSchemas,
                            limit: oResult.limit
                        }).then(function (rsp) {
                            noticebox.info('创建（' + rsp.data + '）条记录');
                            defer.resolve(rsp);
                        });
                    }
                });
                return defer.promise;
            };
            _ins.transferGroupAndMarks = function (oApp) {
                var defer;
                defer = $q.defer();
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/transferGroupAndMarks.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        var oPage, oResult, oFilter;
                        $scope2.page = oPage = {};
                        $scope2.result = oResult = {
                            limit: {
                                num: 1
                            }
                        };
                        $scope2.filter = oFilter = {};
                        $scope2.selectApp = function () {
                            oResult.questionSchemas = [];
                            oResult.answerSchemas = [];
                            if (angular.isString(oResult.fromApp.data_schemas) && oResult.fromApp.data_schemas) {
                                oResult.fromApp.dataSchemas = JSON.parse(oResult.fromApp.data_schemas);
                                oResult.fromApp.dataSchemas.forEach(function (oSchema) {
                                    if (/shorttext|longtext/.test(oSchema.type)) {
                                        oResult.questionSchemas.push(oSchema);
                                    } else if ('multitext' === oSchema.type) {
                                        oResult.answerSchemas.push(oSchema);
                                    }
                                });
                            }
                        };
                        $scope2.ok = function () {
                            $mi.close(oResult);
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.doFilter = function () {
                            oPage.at = 1;
                            $scope2.doSearch();
                        };
                        $scope2.doSearch = function () {
                            var url = '/rest/pl/fe/matter/enroll/list?site=' + oApp.siteid;
                            http2.post(url, {
                                byTitle: oFilter.byTitle
                            }, {
                                page: oPage
                            }).then(function (rsp) {
                                $scope2.apps = rsp.data.apps;
                                if ($scope2.apps.length) {
                                    oResult.fromApp = $scope2.apps[0];
                                    $scope2.selectApp();
                                }
                            });
                        };
                        $scope2.disabled = true; // 选择的参数是否完整
                        $scope2.$watch('result', function () {
                            $scope2.disabled = false;
                            if (!oResult.fromApp) $scope2.disabled = true;
                            if (!oResult.answerSchema) $scope2.disabled = true;
                            if (!oResult.questionSchema) $scope2.disabled = true;
                        }, true);
                        $scope2.doSearch();
                    }],
                    backdrop: 'static',
                    windowClass: 'auto-height',
                    size: 'lg'
                }).result.then(function (oResult) {
                    var url;
                    if (oResult.fromApp && oResult.questionSchema && oResult.answerSchema) {
                        url = '/rest/pl/fe/matter/enroll/record/transferGroupAndMarks';
                        url += '?app=' + oApp.id;
                        url += '&targetApp=' + oResult.fromApp.id;
                        http2.post(url, {
                            questionSchema: oResult.questionSchema,
                            answerSchema: oResult.answerSchema,
                            limit: oResult.limit
                        }).then(function (rsp) {
                            noticebox.info('创建（' + rsp.data + '）条记录');
                            defer.resolve(rsp);
                        });
                    }
                });
                return defer.promise;
            };
            _ins.fillByOther = function (oApp) {
                var defer;
                defer = $q.defer();
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/fillByOther.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        var oPage, oResult, oFilter, dataSchemas;
                        $scope2.dataSchemas = dataSchemas = [];
                        oApp.dataSchemas.forEach(function (oSchema) {
                            if (oSchema.type === 'shorttext') {
                                dataSchemas.push(oSchema);
                            }
                        });
                        $scope2.page = oPage = {};
                        $scope2.data = oResult = {
                            matterType: 'mschema',
                            intersected: {},
                            filled: {}
                        };
                        $scope2.filter = oFilter = {};
                        $scope2.ok = function () {
                            $mi.close(oResult);
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.doFilter = function () {
                            oPage.at = 1;
                            $scope2.doSearch();
                        };
                        $scope2.doSearch = function () {
                            var url;
                            switch (oResult.matterType) {
                                case 'mschema':
                                    url = '/rest/pl/fe/site/member/schema/list?valid=Y&site=' + oApp.siteid + '&matter=' + oApp.id + ',enroll';
                                    http2.post(url, {
                                        byTitle: oFilter.byTitle
                                    }).then(function (rsp) {
                                        $scope2.apps = rsp.data;
                                        if ($scope2.apps.length) {
                                            oResult.fromApp = $scope2.apps[0];
                                        }
                                    });
                                    break;
                                case 'enroll':
                                    url = '/rest/pl/fe/matter/enroll/list?site=' + oApp.siteid;
                                    http2.post(url, {
                                        byTitle: oFilter.byTitle
                                    }, {
                                        page: oPage
                                    }).then(function (rsp) {
                                        $scope2.apps = rsp.data.apps;
                                        if ($scope2.apps.length) {
                                            oResult.fromApp = $scope2.apps[0];
                                            $scope2.selectApp();
                                        }
                                    });
                                    break;
                            }
                        };
                        $scope2.doSearch();
                    }],
                    backdrop: 'static',
                    size: 'lg'
                }).result.then(function (data) {
                    var url, intersectedSchemas, filledSchemas;
                    if (data.fromApp && data.fromApp.id && data.intersected) {
                        intersectedSchemas = [];
                        angular.forEach(data.intersected, function (source, target) {
                            if (source) {
                                intersectedSchemas.push([source, target]);
                            }
                        });
                        filledSchemas = [];
                        angular.forEach(data.filled, function (source, target) {
                            if (source) {
                                filledSchemas.push([source, target]);
                            }
                        });
                        if (intersectedSchemas.length && filledSchemas) {
                            url = '/rest/pl/fe/matter/enroll/record/fillByOther';
                            url += '?app=' + oApp.id;
                            url += '&targetApp=' + data.matterType + ',' + data.fromApp.id;
                            url += '&preview=N';
                            http2.post(url, {
                                intersectedSchemas: intersectedSchemas,
                                filledSchemas: filledSchemas
                            }).then(function (rsp) {
                                defer.resolve(rsp.data);
                            });
                        }
                    }
                });
                return defer.promise;
            };
            _ins.sum4Schema = function () {
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

                http2.get(url).then(function (rsp) {
                    defer.resolve(rsp.data);
                })
                return defer.promise;
            };
            _ins.score4Schema = function () {
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

                http2.get(url).then(function (rsp) {
                    defer.resolve(rsp.data);
                })
                return defer.promise;
            };
            _ins.listRemark = function (ek, schemaId, itemId) {
                var url, defer = $q.defer();
                url = '/rest/pl/fe/matter/enroll/remark/list';
                url += '?site=' + _siteId;
                url += '&ek=' + ek;
                schemaId && (url += '&schema=' + schemaId);
                itemId && (url += '&id=' + itemId);
                if (itemId == '0') {
                    url += '&id=null';
                }
                http2.get(url).then(function (rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.agree = function (ek, schemaId, value, itemId) {
                var url, defer = $q.defer();
                url = '/rest/pl/fe/matter/enroll/data/agree?ek=' + ek;
                url += '&schema=' + schemaId;
                url += '&value=' + value;
                itemId && (url += '&id=' + itemId);
                http2.get(url).then(function (rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };

            return _ins;
        }];
    });
    /**
     * log
     */
    ngModule.provider('srvEnrollLog', function () {
        var _siteId, _appId, _plOperations, _siteOperations;

        this.config = function (siteId, appId) {
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
            }, {
                value: 'site.matter.enroll.schema.get.vote',
                title: '投票'
            }];
        };
        this.$get = ['$q', 'http2', '$uibModal', function ($q, http2, $uibModal) {
            return {
                list: function (page, type, criteria) {
                    var defer = $q.defer(),
                        url;
                    if (!page || !page._j) {
                        angular.extend(page, {
                            at: 1,
                            size: 30,
                            orderBy: 'time',
                            _j: function () {
                                var p;
                                p = '&page=' + this.at + '&size=' + this.size;
                                p += '&orderby=' + this.orderBy;
                                return p;
                            }
                        });
                    }
                    url = '/rest/pl/fe/matter/enroll/log/list?logType=' + type + '&app=' + _appId + page._j();
                    http2.post(url, criteria).then(function (rsp) {
                        rsp.data.total && (page.total = rsp.data.total);
                        defer.resolve(rsp.data.logs);
                    });

                    return defer.promise;
                },
                filter: function (type, criteria) {
                    var defer = $q.defer();
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/logFilter.html?_=1',
                        controller: ['$scope', '$uibModalInstance', 'http2', function ($scope2, $mi, http2) {
                            var oCriteria;
                            $scope2.type = type;
                            $scope2.criteria = oCriteria = criteria;
                            $scope2.siteOperations = _siteOperations;
                            $scope2.plOperations = _plOperations;
                            $scope2.pageOfRound = {
                                at: 1,
                                size: 30,
                                j: function () {
                                    return '&page=' + this.at + '&size=' + this.size;
                                }
                            };
                            $scope2.doSearchRound = function () {
                                var url = '/rest/pl/fe/matter/enroll/round/list?site=' + _siteId + '&app=' + _appId + $scope2.pageOfRound.j();
                                http2.get(url).then(function (rsp) {
                                    oCriteria.byRid = rsp.data.active.rid;
                                    $scope2.activeRound = rsp.data.active;
                                    $scope2.rounds = rsp.data.rounds;
                                    $scope2.rounds.total = rsp.data.total;
                                });
                            };
                            $scope2.cancel = function () {
                                $mi.dismiss();
                            };
                            $scope2.ok = function () {
                                defer.resolve(oCriteria);
                                $mi.close();
                            };
                            if (type === 'pl' || type === 'site') {
                                $scope2.doSearchRound();
                            }
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
    ngModule.provider('srvTempApp', function () {
        function _fnMapSchemas(app) {
            var mapOfSchemaByType = {},
                mapOfSchemaById = {},
                mapOfUnionSchemaById = {},
                enrollDataSchemas = [],
                groupDataSchemas = [],
                canFilteredSchemas = [];

            app.dataSchemas.forEach(function (schema) {
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
                app.enrollApp.dataSchemas.forEach(function (item) {
                    if (mapOfUnionSchemaById[item.id] === undefined) {
                        mapOfUnionSchemaById[item.id] = item;
                        enrollDataSchemas.push(item);
                    }
                });
            }
            // 关联的分组活动的登记项
            if (app.groupApp && app.groupApp.data_schemas) {
                app.groupApp.data_schemas.forEach(function (item) {
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
        this.config = function (siteId, appId, vId) {
            _siteId = siteId;
            _appId = appId;
            _vId = vId;
        };
        this.$get = ['$q', 'http2', 'noticebox', '$uibModal', function ($q, http2, noticebox, $uibModal) {
            var _self = {
                tempEnrollGet: function () {
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
                    http2.get(url).then(function (rsp) {
                        _oApp = rsp.data;

                        function _tGet(data, method) {
                            try {
                                data.data_schemas = data.data_schemas && data.data_schemas.length ? JSON.parse(data.data_schemas) : [];
                            } catch (e) {
                                console.log('data invalid', e, data.data_schemas);
                                data.data_schemas = [];
                            }
                            method(data);
                            data.data_schemas.forEach(function (schema) {
                                schemaLib._upgrade(schema);
                            });
                            data.pages.forEach(function (page) {
                                pageLib.enhance(page, data._unionSchemasById);
                            });
                        }
                        _tGet(_oApp, _fnMapSchemas);
                        _getAppDeferred.resolve(_oApp);
                    });

                    return _getAppDeferred.promise;
                },
                update: function (names) {
                    var defer = $q.defer(),
                        modifiedData = {},
                        url;

                    angular.isString(names) && (names = [names]);
                    names.forEach(function (name) {
                        if (name === 'tags') {
                            modifiedData.tags = _oApp.tags.join(',');
                        } else {
                            modifiedData[name] = _oApp[name];
                        }
                    });

                    url = '/rest/pl/fe/template/update?site=' + _siteId;
                    url += '&tid=' + _appId;
                    url += '&vid=' + _oApp.vid;
                    http2.post(url, modifiedData).then(function (rsp) {
                        //noticebox.success('完成保存');
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                shareAsTemplate: function () {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/site/template/component/templateShare.html',
                        controller: ['$scope', '$uibModalInstance', function ($scope, $mi) {
                            $scope.data = {};
                            $scope.params = {};
                            $scope.cancel = function () {
                                $mi.dismiss();
                            };
                            $scope.ok = function () {
                                $mi.close($scope.data);
                            };
                        }],
                        backdrop: 'static'
                    }).result.then(function (data) {
                        http2.post('/rest/pl/fe/template/putCreate?site=' + _siteId + '&tid=' + _appId, data).then(function (rsp) {
                            location.href = '/rest/pl/fe/template/site?site=' + _siteId;
                        });
                    });
                },
                cancelAsTemplate: function () {
                    var url = '/rest/pl/fe/template/unPut?site=' + _siteId + '&tid=' + _appId;
                    http2.get(url).then(function (rsp) {
                        location.href = '/rest/pl/fe/template/site?site=' + _siteId;
                    });
                },
                applyToHome: function () {
                    var url = '/rest/pl/fe/template/pushHome?site=' + _siteId;
                    url += '&tid=' + _appId;
                    http2.get(url).then(function (rsp) {
                        noticebox.success('完成申请！');
                    });
                },
                createVersion: function () {
                    var url;
                    url = '/rest/pl/fe/template/createVersion?site=' + _siteId;
                    url += '&tid=' + _appId;
                    url += '&lastVersion=' + _oApp.last_version;
                    url += '&matterType=' + _oApp.matter_type;
                    http2.get(url).then(function (rsp) {
                        location.href = '/rest/pl/fe/template/' + _oApp.matter_type + '?site=' + _siteId + '&id=' + _appId + '&vid=' + rsp.data.vid;
                    });
                },
                lookView: function (num) {
                    var url, defer = $q.defer();
                    url = '/rest/pl/fe/template/get?site=' + _siteId;
                    url += '&tid=' + _appId;
                    url += '&vid=' + num;
                    http2.get(url).then(function (rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                lookDetail: function (id) {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/site/template/component/templateDetail.html',
                        backdrop: 'static',
                        controller: ['$scope', '$uibModalInstance', function ($scope, $mi) {
                            if (id === undefined) return false;
                            http2.get('/rest/pl/fe/template/getVersion?site=' + _siteId + '&tid=' + _appId + '&vid=' + id).then(function (rsp) {
                                $scope.version = rsp.data;
                            });
                            $scope.cancel = function () {
                                $mi.dismiss();
                            };
                        }]
                    });

                },
                addReceiver: function (shareUser) {
                    var defer = $q.defer(),
                        url;
                    url = '/rest/pl/fe/template/acl/add?label=' + shareUser.label;
                    url += '&site=' + _siteId;
                    url += '&tid=' + _appId;
                    http2.get(url).then(function (rsp) {
                        if (_oApp.acl === undefined) {
                            _oApp.acl = [];
                        }
                        _oApp.acl.push(rsp.data);
                        defer.resolve(_oApp);
                    });
                    return defer.promise;
                },
                removeReceiver: function (acl) {
                    var defer = $q.defer(),
                        url;
                    url = '/rest/pl/fe/template/acl/remove';
                    url += '?acl=' + acl.id;
                    http2.get(url).then(function (rsp) {
                        angular.forEach(_oApp.acl, function (item, index) {
                            if (item.id == acl.id) {
                                _oApp.acl.splice(index, 1);
                            }
                        })
                        defer.resolve();
                    });
                    return defer.promise;
                },
                removeAsTemplate: function () {
                    var defer = $q.defer(),
                        url;
                    url = '/rest/pl/fe/template/remove?site=' + _siteId + '&tid=' + _appId;
                    http2.get(url).then(function (rsp) {
                        defer.resolve();
                    });
                    return defer.promise;
                },
            }
            return _self;
        }];
    });
    ngModule.provider('srvTempPage', function () {
        var _siteId, _appId;
        this.config = function (siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$uibModal', '$q', 'http2', 'noticebox', 'srvEnrollApp', 'srvTempApp', function ($uibModal, $q, http2, noticebox, srvEnrollApp, srvTempApp) {
            var _self;
            _self = {
                create: function () {
                    var deferred = $q.defer();
                    srvTempApp.tempEnrollGet().then(function (app) {
                        $uibModal.open({
                            templateUrl: '/views/default/pl/fe/matter/enroll/component/createPage.html?_=3',
                            backdrop: 'static',
                            controller: ['$scope', '$uibModalInstance', function ($scope, $mi) {
                                $scope.options = {};
                                $scope.ok = function () {
                                    $mi.close($scope.options);
                                };
                                $scope.cancel = function () {
                                    $mi.dismiss();
                                };
                            }],
                        }).result.then(function (options) {
                            http2.post('/rest/pl/fe/template/enroll/add?site=' + _siteId + '&tid=' + _appId + '&vid=' + app.vid, options).then(function (rsp) {
                                var page = rsp.data;
                                pageLib.enhance(page);
                                app.pages.push(page);
                                deferred.resolve(page);
                            });
                        });
                    });
                    return deferred.promise;
                },
                update: function (page, names) {
                    var defer = $q.defer(),
                        updated = {},
                        url;

                    angular.isString(names) && (names = [names]);
                    names.forEach(function (name) {
                        if (name === 'html') {
                            updated.html = encodeURIComponent(page.html);
                        } else {
                            updated[name] = page[name];
                        }
                    });
                    srvTempApp.tempEnrollGet().then(function (app) {
                        url = '/rest/pl/fe/template/enroll/updatePage';
                        url += '?site=' + _siteId;
                        url += '&tid=' + _appId;
                        url += '&vid=' + app.vid;
                        url += '&pageId=' + page.id;
                        url += '&cname=' + page.code_name;
                        http2.post(url, updated).then(function (rsp) {
                            page.$$modified = false;
                            defer.resolve();
                            noticebox.success('完成保存');
                        });
                    });
                    return defer.promise;
                },
                clean: function (oPage) {
                    oPage.html = '';
                    oPage.dataSchemas = [];
                    oPage.actSchemas = [];
                    return _self.update(oPage, ['dataSchemas', 'actSchemas', 'html']);
                },
                remove: function (oPage) {
                    var defer = $q.defer();
                    srvTempApp.tempEnrollGet().then(function (app) {
                        var url = '/rest/pl/fe/template/enroll/remove';
                        url += '?site=' + _siteId;
                        url += '&tid=' + _appId;
                        url += '&vid=' + app.vid;
                        url += '&pageId=' + oPage.id;
                        url += '&cname=' + oPage.code_name;
                        http2.get(url).then(function (rsp) {
                            app.pages.splice(app.pages.indexOf(oPage), 1);
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
    ngModule.provider('srvTempRecord', function () {
        var _siteId, _appId;
        this.config = function (siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$q', 'http2', function ($q, http2) {
            var _self = {
                list: function (article, page) {
                    var defer = $q.defer(),
                        url;
                    if (!page || !page._j) {
                        angular.extend(page, {
                            at: 1,
                            size: 30,
                            orderBy: 'time',
                            _j: function () {
                                var p;
                                p = '&page=' + this.at + '&size=' + this.size;
                                return p;
                            }
                        });
                    }
                    url = '/rest/pl/fe/template/order/listPurchaser?site=' + _siteId + '&tid=' + _appId;
                    url += page._j();
                    http2.get(url).then(function (rsp) {
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
    ngModule.provider('srvEnrollNotice', function () {
        this.$get = ['$q', 'http2', function ($q, http2) {
            return {
                detail: function (batch) {
                    var defer = $q.defer(),
                        url;
                    url = '/rest/pl/fe/matter/enroll/notice/logList?batch=' + batch.id;
                    http2.get(url).then(function (rsp) {
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
    ngModule.controller('ctrlEnrollFilter', ['$scope', '$uibModalInstance', 'dataSchemas', 'criteria', 'srvEnlRnd', 'app', function ($scope, $mi, dataSchemas, lastCriteria, srvEnlRnd, oApp) {
        var canFilteredSchemas = [];

        if (oApp.entryRule && oApp.entryRule.scope && oApp.entryRule.scope.group === 'Y' && oApp.entryRule.group && oApp.entryRule.group.id) {
            $scope.bRequireGroup = true;
            $scope.groups = oApp.groupApp.teams;
        }
        dataSchemas.forEach(function (schema) {
            if (false === /image|file|score|html/.test(schema.type) && schema.id.indexOf('member') !== 0) {
                canFilteredSchemas.push(schema);
            }
            if (/multiple/.test(schema.type)) {
                var options = {};
                if (lastCriteria.data[schema.id]) {
                    lastCriteria.data[schema.id].split(',').forEach(function (key) {
                        options[key] = true;
                    })
                }
                lastCriteria.data[schema.id] = options;
            }
            $scope.schemas = canFilteredSchemas;
            $scope.criteria = lastCriteria;
        });
        $scope.checkedRounds = {};
        $scope.toggleCheckedRound = function (rid) {
            if (rid === 'ALL' && $scope.checkedRounds.ALL) {
                $scope.checkedRounds = {
                    ALL: true
                };
            } else if ($scope.checkedRounds[rid]) {
                $scope.checkedRounds.ALL = false;
            }
        };
        $scope.clean = function () {
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
                angular.forEach(oCriteria.data, function (val, key) {
                    oCriteria.data[key] = '';
                });
            }
        };
        $scope.ok = function () {
            var oCriteria = $scope.criteria,
                optionCriteria;
            /* 将单选题/多选题的结果拼成字符串 */
            canFilteredSchemas.forEach(function (schema) {
                var result;
                if (/multiple/.test(schema.type)) {
                    if ((optionCriteria = oCriteria.data[schema.id])) {
                        result = [];
                        Object.keys(optionCriteria).forEach(function (key) {
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
                angular.forEach($scope.checkedRounds, function (v, k) {
                    if (v) {
                        oCriteria.record.rid.push(k);
                    }
                });
            }
            $mi.close(oCriteria);
        };
        $scope.cancel = function () {
            $mi.dismiss('cancel');
        };
        $scope.doSearchRound = function () {
            srvEnlRnd.list().then(function (oResult) {
                var oCriteria = $scope.criteria;
                $scope.activeRound = oResult.active;
                if ($scope.activeRound) {
                    var otherRounds = [];
                    oResult.rounds.forEach(function (oRound) {
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
                    oCriteria.record = {
                        rid: []
                    };
                }
                if (oCriteria.record.rid.length) {
                    oCriteria.record.rid.forEach(function (rid) {
                        $scope.checkedRounds[rid] = true;;
                    });
                }
            });
        };
        $scope.doSearchRound();
    }])
});