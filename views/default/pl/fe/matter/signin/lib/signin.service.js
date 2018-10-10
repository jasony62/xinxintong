define(['require', 'schema', 'page'], function(require, schemaLib, pageLib) {
    /**
     * BasesrvSigninRecord
     * srvSigninApp
     * srvSigninRound
     * srvSigninRecord
     */
    var BasesrvSigninRecord = function($q, http2, tmsSchema, noticebox, $uibModal) {
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
            http2.post(url, that._oCriteria).then(function(rsp) {
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
                }).then(function(rsp) {
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

            tmsSchema.forTable(record, that._oApp._assocSchemasById);
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
        function _fnMapAssocEnrollApp(oApp) {
            var enrollDataSchemas = [];
            if (oApp.enrollApp && oApp.enrollApp.dataSchemas) {
                oApp.enrollApp.dataSchemas.forEach(function(item) {
                    if (oApp._assocSchemasById[item.id] === undefined) {
                        item.assocState = '';
                        oApp._assocSchemasById[item.id] = item;
                        enrollDataSchemas.push(item);
                    } else if (oApp._assocSchemasById[item.id].fromApp === oApp.enrollApp.id) {
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
                    if (oApp._assocSchemasById[item.id] === undefined) {
                        item.assocState = '';
                        oApp._assocSchemasById[item.id] = item;
                        groupDataSchemas.push(item);
                    } else if (oApp._assocSchemasById[item.id].fromApp === oApp.groupApp.id) {
                        item.assocState = 'yes';
                    } else {
                        item.assocState = 'no';
                    }
                });
            }
            oApp._schemasFromGroupApp = groupDataSchemas;
        }

        function _fnMapSchemas(oApp) {
            var mapOfAppSchemaById = {},
                mapOfSchemaByType = {},
                mapOfSchemaById = {},
                canFilteredSchemas = [];

            oApp.dataSchemas.forEach(function(schema) {
                mapOfSchemaByType[schema.type] === undefined && (mapOfSchemaByType[schema.type] = []);
                mapOfSchemaByType[schema.type].push(schema.id);
                mapOfAppSchemaById[schema.id] = schema;
                mapOfSchemaById[schema.id] = schema;
                if (false === /image|file/.test(schema.type)) {
                    canFilteredSchemas.push(schema);
                }
            });

            oApp._schemasByType = mapOfSchemaByType;
            oApp._schemasById = mapOfAppSchemaById;
            oApp._assocSchemasById = mapOfSchemaById;
            oApp._schemasCanFilter = canFilteredSchemas;

            _fnMapAssocEnrollApp(oApp);
            _fnMapAssocGroupApp(oApp);
        }

        var siteId, appId, app, _oApp, defaultInputPage,
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
                    http2.get(url).then(function(rsp) {
                        _oApp = app = rsp.data;
                        _ins._bGet(app, _fnMapSchemas);
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
                    http2.get(url).then(function(rsp) {
                        _opApps = rsp.data, _opApp = rsp.data.app, _opPage = rsp.data.page;
                        _ins._bGet(_opApp, _fnMapSchemas);
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
                        if (name === 'data_schemas' || name === 'dataSchemas') {
                            modifiedData.data_schemas = _oApp.dataSchemas;
                        } else if (name === 'recycle_schemas' || name === 'recycleSchemas') {
                            modifiedData.recycle_schemas = _oApp.recycleSchemas;
                        } else if (name === 'tags') {
                            modifiedData.tags = _oApp.tags.join(',');
                        } else {
                            modifiedData[name] = _oApp[name];
                        }
                    });
                    url = '/rest/pl/fe/matter/signin/update?site=' + siteId + '&app=' + appId;
                    http2.post(url, modifiedData).then(function(rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                changeUserScope: function(ruleScope, sns) {
                    this.update('entryRule');
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
                            http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + siteId + '&id=' + missions.matters[0].id, matter).then(function(rsp) {
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
                            title: _oApp.title
                        };
                    http2.post('/rest/pl/fe/matter/mission/matter/remove?site=' + siteId + '&id=' + _oApp.mission_id, matter).then(function(rsp) {
                        delete _oApp.mission;
                        _oApp.mission_id = null;
                        _this.update(['mission_id']);
                    });
                },
                remove: function() {
                    var defer = $q.defer(),
                        url;

                    url = '/rest/pl/fe/matter/signin/remove?site=' + siteId + '&app=' + appId;
                    http2.get(url).then(function(rsp) {
                        defer.resolve();
                    });

                    return defer.promise;
                },
                jumpPages: function() {
                    var defaultInput, inapp = [],
                        pages = _oApp.pages,
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
                    var _this = this,
                        defer = $q.defer();
                    $uibModal.open({
                        templateUrl: 'assignEnrollApp.html',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            function listEnrollApp() {
                                var url = '/rest/pl/fe/matter/enroll/list?site=' + siteId + '&scenario=registration&size=999';
                                $scope2.data.sameMission === 'Y' && (url += '&mission=' + _oApp.mission.id);
                                http2.get(url).then(function(rsp) {
                                    $scope2.apps = rsp.data.apps;
                                });
                            }
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
                            $scope2.$watch('data.sameMission', listEnrollApp);
                        }],
                        backdrop: 'static'
                    }).result.then(function(data) {
                        _oApp.enroll_app_id = data.source;
                        _this.update('enroll_app_id').then(function(rsp) {
                            var url = '/rest/pl/fe/matter/enroll/get?site=' + siteId + '&app=' + _oApp.enroll_app_id;
                            http2.get(url).then(function(rsp) {
                                _oApp.enrollApp = rsp.data;
                                _fnMapAssocEnrollApp(_oApp);
                                defer.resolve(_oApp.enrollApp);
                            });
                        });
                    });
                    return defer.promise;
                },
                assignGroupApp: function() {
                    var _this = this,
                        defer = $q.defer();;
                    $uibModal.open({
                        templateUrl: 'assignGroupApp.html',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            function listEnrollApp() {
                                var url = '/rest/pl/fe/matter/group/list?site=' + siteId;
                                $scope2.data.sameMission === 'Y' && (url += '&mission=' + _oApp.mission.id);
                                http2.get(url).then(function(rsp) {
                                    $scope2.apps = rsp.data.apps;
                                });
                            }
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
                            $scope2.$watch('data.sameMission', listEnrollApp);
                        }],
                        backdrop: 'static'
                    }).result.then(function(data) {
                        _oApp.group_app_id = data.source;
                        _this.update('group_app_id').then(function(rsp) {
                            var url = '/rest/pl/fe/matter/group/get?site=' + siteId + '&app=' + _oApp.group_app_id;
                            http2.get(url).then(function(rsp) {
                                _oApp.groupApp = rsp.data;
                                _fnMapAssocGroupApp(_oApp);
                                defer.resolve(_oApp.groupApp);
                            });
                        });
                    });

                    return defer.promise;
                },
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
                            /*设置阶段的缺省起止时间*/
                            (function() {
                                var nextDay = new Date();
                                nextDay.setTime(nextDay.getTime() + 86400000);
                                params.start_at = nextDay.setHours(0, 0, 0, 0) / 1000;
                                params.end_at = nextDay.setHours(23, 59, 59, 0) / 1000;
                            })();
                            $scope2.params = params;
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope2.ok = function() {
                                $mi.close($scope2.params);
                            };
                        }]
                    }).result.then(function(params) {
                        http2.post('/rest/pl/fe/matter/signin/round/batch?site=' + siteId + '&app=' + appId, params).then(function(rsp) {
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
                    http2.post('/rest/pl/fe/matter/signin/round/add?site=' + siteId + '&app=' + appId, newRound).then(function(rsp) {
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
                    http2.post(url, posted).then(function(rsp) {
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
                        http2.get(url).then(function(rsp) {
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
                            };

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
                                //url += '&expire=864000';

                                http2.post(url, {
                                    params: params
                                }).then(function(rsp) {
                                    $scope2.qrcode = rsp.data;
                                });
                            };
                            $scope2.downloadWxQrcode = function() {
                                $('<a href="' + $scope2.qrcode.pic + '" download="' + app.title + '_' + round.title + '_签到二维码.jpeg"></a>')[0].click();
                            };
                            if (app.entry_rule.scope === 'sns' && sns.wx) {
                                if (sns.wx.can_qrcode === 'Y') {
                                    http2.get('/rest/pl/fe/matter/signin/wxQrcode?site=' + siteId + '&app=' + appId + '&round=' + round.rid).then(function(rsp) {
                                        var qrcodes = rsp.data;
                                        $scope2.qrcode = qrcodes.length ? qrcodes[0] : false;
                                    });
                                }
                            }
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
    }).provider('srvSigninRecord', function() {
        var siteId, appId;
        this.config = function(site, app) {
            siteId = site;
            appId = app;
        }
        this.$get = ['$q', '$uibModal', '$sce', 'http2', 'noticebox', 'pushnotify', 'cstApp', 'tmsSchema', function($q, $uibModal, $sce, http2, noticebox, pushnotify, cstApp, tmsSchema) {
            var _ins = new BasesrvSigninRecord($q, http2, tmsSchema, noticebox, $uibModal);

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
                http2.get('/rest/pl/fe/matter/signin/record/get?ek=' + ek).then(function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.add = function(newRecord) {
                http2.post('/rest/pl/fe/matter/signin/record/add?site=' + siteId + '&app=' + appId, newRecord).then(function(rsp) {
                    var record = rsp.data;
                    _ins._bConvertRecord4Table(record);
                    _ins._aRecords.splice(0, 0, record);
                });
            };
            _ins.update = function(record, updated) {
                http2.post('/rest/pl/fe/matter/signin/record/update?site=' + siteId + '&app=' + appId + '&ek=' + record.enroll_key, updated).then(function(rsp) {
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
                        http2.post('/rest/pl/fe/matter/signin/record/batchTag?site=' + siteId + '&app=' + appId, posted).then(function(rsp) {
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
                    http2.get('/rest/pl/fe/matter/signin/record/remove?site=' + siteId + '&app=' + appId + '&key=' + record.enroll_key).then(function(rsp) {
                        var i = _ins._aRecords.indexOf(record);
                        _ins._aRecords.splice(i, 1);
                        _ins._oPage.total = _ins._oPage.total - 1;
                    });
                }
            };
            _ins.empty = function() {
                var vcode;
                vcode = prompt('是否要删除所有登记信息？，若是，请输入活动名称。');
                if (vcode === _ins._oApp.title) {
                    http2.get('/rest/pl/fe/matter/signin/record/empty?site=' + siteId + '&app=' + appId).then(function(rsp) {
                        _ins._aRecords.splice(0, _ins._aRecords.length);
                        _ins._oPage.total = 0;
                        _ins._oPage.at = 1;
                    });
                }
            };
            _ins.verifyAll = function() {
                if (window.confirm('确定审核通过所有记录（共' + _oPage.total + '条）？')) {
                    http2.get('/rest/pl/fe/matter/signin/record/verifyAll?site=' + siteId + '&app=' + appId).then(function(rsp) {
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

                        http2.post(url, targetAndMsg).then(function(data) {
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

                http2.post(url, record.data).then(function(rsp) {
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
                tmsSchema.forEdit(col, data);
                return data;
            };
            _ins.importByEnrollApp = function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/signin/record/importByEnrollApp';
                url += '?site=' + siteId + '&app=' + appId;

                http2.get(url).then(function(rsp) {
                    noticebox.info('更新了（' + rsp.data + '）条数据');
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            };
            _ins.absent = function(rid) {
                var defer = $q.defer(),
                    url;
                url = '/rest/pl/fe/matter/signin/record/absent?site=' + siteId + '&app=' + appId;
                if (rid) url += '&rid=' + rid;
                http2.get(url).then(function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            };
            _ins.editCause = function(user) {
                var defer = $q.defer();
                $uibModal.open({
                    templateUrl: 'editCause.html',
                    controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                        $scope2.cause = '';
                        $scope2.cancel = function() {
                            $mi.dismiss();
                        };
                        $scope2.ok = function() {
                            var url, params = {};
                            params[user.userid] = $scope2.cause;
                            url = '/rest/pl/fe/matter/signin/update?site=' + siteId + '&app=' + appId;
                            http2.post(url, { 'absent_cause': params }).then(function(rsp) {
                                $mi.close();
                                defer.resolve($scope2.cause);
                            });
                        };
                    }],
                    backdrop: 'static'
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
        this.$get = ['$q', 'http2', 'noticebox', '$uibModal', 'tmsSchema', function($q, http2, noticebox, $uibModal, tmsSchema) {
            var _ins = new BasesrvSigninRecord($q, http2, tmsSchema, noticebox, $uibModal);
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
                    http2.get('/rest/site/op/matter/signin/record/remove?site=' + _siteId + '&app=' + _appId + '&accessToken=' + _accessId + '&ek=' + record.enroll_key).then(function(rsp) {
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
                    http2.get(url).then(function(rsp) {
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