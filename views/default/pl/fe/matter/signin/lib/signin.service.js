/**
 * srvApp
 * srvRound
 * srvPage
 * srvRecord
 */
'use strict';
angular.module('service.signin', ['ui.bootstrap', 'ui.xxt']).
provider('srvApp', function() {
    var siteId, appId, app, defaultInputPage,
        pages4NonMember = [],
        pages4Nonfan = [];

    this.app = function() {
        return app;
    };
    this.setSiteId = function(id) {
        siteId = id;
    };
    this.setAppId = function(id) {
        appId = id;
    };
    this.$get = ['$q', 'http2', 'noticebox', 'mattersgallery', '$uibModal', function($q, http2, noticebox, mattersgallery, $uibModal) {
        return {
            get: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/signin/get?site=' + siteId + '&id=' + appId;
                http2.get(url, function(rsp) {
                    app = rsp.data;
                    app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
                    app.type = 'enroll';
                    app.entry_rule === null && (app.entry_rule = {});
                    app.entry_rule.scope === undefined && (app.entry_rule.scope = 'none');
                    try {
                        app.data_schemas = app.data_schemas && app.data_schemas.length ? JSON.parse(app.data_schemas) : [];
                    } catch (e) {
                        console.error('data invalid', e, app.data_schemas);
                        app.data_schemas = [];
                    }
                    if (app.enrollApp && app.enrollApp.data_schemas) {
                        try {
                            app.enrollApp.data_schemas = app.enrollApp.data_schemas && app.enrollApp.data_schemas.length ? JSON.parse(app.enrollApp.data_schemas) : [];
                        } catch (e) {
                            console.error('data invalid', e, app.enrollApp.data_schemas);
                            app.enrollApp.data_schemas = [];
                        }
                    }
                    defer.resolve(app);
                });

                return defer.promise;
            },
            update: function(names) {
                var defer = $q.defer(),
                    modifiedData = {},
                    url;

                angular.isString(names) && (names = [names]);
                names.forEach(function(name) {
                    if (['entry_rule'].indexOf(name) !== -1) {
                        modifiedData[name] = encodeURIComponent(JSON.stringify(app.entry_rule));
                    } else if (name === 'tags') {
                        modifiedData.tags = app.tags.join(',');
                    } else {
                        modifiedData[name] = app[name];
                    }
                });
                url = '/rest/pl/fe/matter/signin/update?site=' + siteId + '&app=' + appId;
                http2.post(url, modifiedData, function(rsp) {
                    //noticebox.success('完成保存');
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
                var _this = this;
                mattersgallery.open(siteId, function(matters, type) {
                    var matter;
                    if (matters.length === 1) {
                        matter = {
                            id: appId,
                            type: 'signin'
                        };
                        http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + siteId + '&id=' + matters[0].mission_id, matter, function(rsp) {
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
                }, {
                    matterTypes: [{
                        value: 'mission',
                        title: '项目',
                        url: '/rest/pl/fe/matter'
                    }],
                    singleMatter: true
                });
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
                var defaultInput, pages = app.pages,
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
            assignEnrollApp: function() {
                var _this = this;
                $uibModal.open({
                    templateUrl: 'assignEnrollApp.html',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
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
                        var url = '/rest/pl/fe/matter/enroll/list?site=' + siteId + '&scenario=registration&size=999';
                        app.mission && (url += '&mission=' + app.mission.id);
                        http2.get(url, function(rsp) {
                            $scope2.apps = rsp.data.apps;
                        });
                    }],
                    backdrop: 'static'
                }).result.then(function(data) {
                    app.enroll_oApp_id = data.source;
                    _this.update('enroll_oApp_id').then(function(rsp) {
                        var url = '/rest/pl/fe/matter/enroll/get?site=' + siteId + '&id=' + app.enroll_oApp_id;
                        http2.get(url, function(rsp) {
                            rsp.data.data_schemas = JSON.parse(rsp.data.data_schemas);
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
                app.enroll_oApp_id = '';
                this.update('enroll_oApp_id').then(function() {
                    app.data_schemas.forEach(function(dataSchema) {
                        delete dataSchema.requireCheck;
                    });
                    _this.update('data_schemas');
                });
            },
            mapSchemas: function(app) {
                var mapOfSchemaByType = {},
                    mapOfSchemaById = {},
                    enrollDataSchemas = [],
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

                app._schemasByType = mapOfSchemaByType;
                app._schemasById = mapOfSchemaById;
                app._schemasCanFilter = canFilteredSchemas;
                app._schemasFromEnrollApp = enrollDataSchemas;

                return {
                    byType: mapOfSchemaByType,
                    byId: mapOfSchemaById,
                    enrollData: enrollDataSchemas,
                    canFilter: canFilteredSchemas
                }
            }
        };
    }];
}).provider('srvRound', function() {
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
                            var url;

                            url = '/rest/pl/fe/site/sns/wx/qrcode/create?site=' + siteId;
                            url += '&matter_type=signin&matter_id=' + appId;
                            url += '&expire=864000';

                            http2.get(url, function(rsp) {
                                $scope2.qrcode = rsp.data;
                            });
                        };
                        $scope2.downloadWxQrcode = function() {
                            $('<a href="' + $scope2.qrcode.pic + '" download="' + app.title + '_' + round.title + '_签到二维码.jpeg"></a>')[0].click();
                        };
                        if (app.entry_rule.scope === 'sns' && sns.wx.can_qrcode === 'Y') {
                            http2.get('/rest/pl/fe/matter/signin/wxQrcode?site=' + siteId + '&app=' + appId, function(rsp) {
                                var qrcodes = rsp.data;
                                $scope2.qrcode = qrcodes.length ? qrcodes[0] : false;
                            });
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
}).provider('srvPage', function() {
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
}).provider('srvRecord', function() {
    var siteId, appId;
    this.setSiteId = function(id) {
        siteId = id;
    };
    this.setAppId = function(id) {
        appId = id;
    };
    this.$get = ['$q', '$uibModal', '$sce', 'http2', 'noticebox', 'pushnotify', function($q, $uibModal, $sce, http2, noticebox, pushnotify) {
        function _memberAttr(val, schema) {
            var keys;
            if (val && val.member) {
                keys = schema.id.split('.');
                if (keys.length === 2) {
                    return val.member[keys[1]];
                } else if (val.member.extattr) {
                    return val.member.extattr[keys[2]];
                } else {
                    return '';
                }
            } else {
                return '';
            }
        };

        function _value2Label(val, schema) {
            var i, j, aVal, aLab = [];
            if (val === undefined) return '';
            if (schema.ops && schema.ops.length) {
                aVal = val.split(',');
                schema.ops.forEach(function(op) {
                    aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                });
                if (aLab.length) return aLab.join(',');
            }
            return val;
        };

        function isSigninLate(record, roundId) {
            var round = _mapOfRoundsById[roundId],
                signinAt;
            if (record && record.signin_log && round && round.late_at > 0) {
                signinAt = parseInt(record.signin_log[roundId]);
                if (signinAt) {
                    // 忽略秒的影响
                    return signinAt > parseInt(round.late_at) + 59;
                }
            }
            return false;
        };

        function _convertRecord4Table(record) {
            var schema, round, signinAt, data = {},
                signinLate = {};
            // enroll data
            for (var schemaId in _oApp._schemasById) {
                schema = _oApp._schemasById[schemaId];
                switch (schema.type) {
                    case 'image':
                        var imgs = record.data[schema.id] ? record.data[schema.id].split(',') : [];
                        data[schema.id] = imgs;
                        break;
                    case 'file':
                        var files = record.data[schema.id] ? JSON.parse(record.data[schema.id]) : {};
                        data[schema.id] = files;
                        break;
                    case 'member':
                        data[schema.id] = _memberAttr(record.data[schema.id], schema);
                        break;
                    default:
                        data[schema.id] = _value2Label(record.data[schema.id], schema);
                }
            };
            record._data = data;
            // signin log
            for (var roundId in _mapOfRoundsById) {
                round = _mapOfRoundsById[roundId];
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
        var _oApp, _oPage, _oCriteria, _aRecords, _mapOfRoundsById = {};

        return {
            init: function(oApp, oPage, oCriteria, oRecords) {
                _oApp = oApp;
                // rounds
                if (oApp.rounds && oApp.rounds.length) {
                    oApp.rounds.forEach(function(round) {
                        _mapOfRoundsById[round.rid] = round;
                    });
                }
                // pagination
                _oPage = oPage;
                angular.extend(_oPage, {
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
                    }
                });
                // criteria
                _oCriteria = oCriteria;
                angular.extend(_oCriteria, {
                    late: '',
                    record: {
                        keyword: '',
                        verified: ''
                    },
                    tags: [],
                    data: {}
                });
                // records
                _aRecords = oRecords;
            },
            add: function(newRecord) {
                http2.post('/rest/pl/fe/matter/signin/record/add?site=' + siteId + '&app=' + appId, newRecord, function(rsp) {
                    var record = rsp.data;
                    _convertRecord4Table(record);
                    _aRecords.splice(0, 0, record);
                });
            },
            update: function(record, updated) {
                http2.post('/rest/pl/fe/matter/signin/record/update?site=' + siteId + '&app=' + appId + '&ek=' + record.enroll_key, updated, function(rsp) {
                    angular.extend(record, rsp.data);
                    _convertRecord4Table(record);
                });
            },
            search: function(pageNumber) {
                var _this = this,
                    defer = $q.defer(),
                    url;

                _aRecords.splice(0, _aRecords.length);
                pageNumber && (_oPage.at = pageNumber);
                url = '/rest/pl/fe/matter/signin/record/list';
                url += '?site=' + siteId;
                url += '&app=' + appId;
                url += _oPage.joinParams();
                http2.post(url, _oCriteria, function(rsp) {
                    var records;
                    if (rsp.data) {
                        records = rsp.data.records ? rsp.data.records : [];
                        rsp.data.total && (_oPage.total = rsp.data.total);
                    } else {
                        records = [];
                    }
                    records.forEach(function(record) {
                        _convertRecord4Table(record);
                        _aRecords.push(record);
                    });
                    defer.resolve(records);
                });

                return defer.promise;
            },
            batchTag: function(rows) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/batchTag.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                        $scope2.appTags = angular.copy(_oApp.tags);
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
                            record = _aRecords[p];
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
                            _oApp.tags = result.appTags;
                        });
                    }
                });
            },
            remove: function(record) {
                if (window.confirm('确认删除？')) {
                    http2.get('/rest/pl/fe/matter/signin/record/remove?site=' + siteId + '&app=' + appId + '&key=' + record.enroll_key, function(rsp) {
                        var i = _aRecords.indexOf(record);
                        _aRecords.splice(i, 1);
                        _oPage.total = _oPage.total - 1;
                    });
                }
            },
            empty: function() {
                var vcode;
                vcode = prompt('是否要删除所有登记信息？，若是，请输入活动名称。');
                if (vcode === $scope.app.title) {
                    http2.get('/rest/pl/fe/matter/signin/record/empty?site=' + siteId + '&app=' + appId, function(rsp) {
                        //$scope.doSearch(1);
                    });
                }
            },
            export: function() {
                var url;

                url = '/rest/pl/fe/matter/signin/record/export';
                url += '?site=' + siteId + '&app=' + appId;
                _oPage.byRound && (url += '&round=' + _oPage.byRound);

                window.open(url);
            },
            exportImage: function() {
                var url;

                url = '/rest/pl/fe/matter/signin/record/exportImage';
                url += '?site=' + siteId + '&app=' + appId;
                _oPage.byRound && (url += '&round=' + _oPage.byRound);

                window.open(url);
            },
            importByEnrollApp: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/signin/record/importByEnrollApp';
                url += '?site=' + siteId + '&app=' + appId;

                http2.get(url, function(rsp) {
                    noticebox.info('更新了（' + rsp.data + '）条数据');
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            },
            convertRecord4Edit: function(col, data) {
                var files;
                if (col.type === 'file') {
                    files = JSON.parse(data[col.id]);
                    files.forEach(function(file) {
                        file.url = $sce.trustAsResourceUrl(file.url);
                    });
                    data[col.id] = files;
                } else if (col.type === 'multiple') {
                    var value = data[col.id].split(','),
                        obj = {};
                    value.forEach(function(p) {
                        obj[p] = true;
                    });
                    data[col.id] = obj;
                } else if (col.type === 'image') {
                    var value = data[col.id],
                        obj = [];
                    if (value && value.length) {
                        value = value.split(',');
                        value.forEach(function(p) {
                            obj.push({
                                imgSrc: p
                            });
                        });
                    }
                    data[col.id] = obj;
                }
                return data;
            },
            syncByEnroll: function(record) {
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
                        _oApp._schemasFromEnrollApp.forEach(function(col) {
                            if (matched[col.id]) {
                                _this.convertRecord4Edit(col, matched);
                            }
                        });
                        angular.extend(record.data, matched);
                    } else {
                        alert('没有找到匹配的记录，请检查数据是否一致');
                    }
                });
            },
            chooseImage: function(imgFieldName) {
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
            },
            notify: function(notifyMatterTypes, rows, isBatch) {
                var options = {
                    matterTypes: notifyMatterTypes
                };
                _oApp.mission && (options.missionId = _oApp.mission.id);
                pushnotify.open(siteId, function(notify) {
                    var url, targetAndMsg = {};
                    if (notify.matters.length) {
                        if (isBatch) {
                            targetAndMsg.users = [];
                            Object.keys(rows.selected).forEach(function(key) {
                                if (rows.selected[key] === true) {
                                    targetAndMsg.users.push(_aRecords[key].userid);
                                }
                            });
                        } else {
                            targetAndMsg.criteria = _oCriteria;
                        }
                        targetAndMsg.message = notify.message;

                        url = '/rest/pl/fe/matter/signin/record/notify';
                        url += '?site=' + siteId;
                        url += '&app=' + appId;
                        url += '&tmplmsg=' + notify.tmplmsg.id;
                        url += _oPage.joinParams();

                        http2.post(url, targetAndMsg, function(data) {
                            noticebox.success('发送成功');
                        });
                    }
                }, options);
            },
        };
    }];
});