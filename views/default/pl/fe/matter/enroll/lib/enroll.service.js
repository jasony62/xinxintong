angular.module('service.enroll', ['ui.bootstrap', 'ui.xxt']).
provider('srvApp', function() {
    var siteId, appId, oApp;
    this.setSiteId = function(id) {
        siteId = id;
    };
    this.setAppId = function(id) {
        appId = id;
    };
    this.$get = ['$q', 'http2', 'noticebox', 'mattersgallery', function($q, http2, noticebox, mattersgallery) {
        return {
            get: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/enroll/get?site=' + siteId + '&id=' + appId;
                http2.get(url, function(rsp) {
                    oApp = rsp.data;
                    oApp.tags = (!oApp.tags || oApp.tags.length === 0) ? [] : oApp.tags.split(',');
                    oApp.type = 'enroll';
                    oApp.entry_rule === null && (oApp.entry_rule = {});
                    oApp.entry_rule.scope === undefined && (oApp.entry_rule.scope = 'none');
                    try {
                        oApp.data_schemas = oApp.data_schemas && oApp.data_schemas.length ? JSON.parse(oApp.data_schemas) : [];
                    } catch (e) {
                        console.log('data invalid', e, oApp.data_schemas);
                        oApp.data_schemas = [];
                    }
                    if (oApp.enrollApp && oApp.enrollApp.data_schemas) {
                        try {
                            oApp.enrollApp.data_schemas = oApp.enrollApp.data_schemas && oApp.enrollApp.data_schemas.length ? JSON.parse(oApp.enrollApp.data_schemas) : [];
                        } catch (e) {
                            console.log('data invalid', e, oApp.enrollApp.data_schemas);
                            oApp.enrollApp.data_schemas = [];
                        }
                    }
                    if (oApp.groupApp && oApp.groupApp.data_schemas) {
                        var groupAppDS = oApp.groupApp.data_schemas;
                        try {
                            oApp.groupApp.data_schemas = groupAppDS && groupAppDS.length ? JSON.parse(groupAppDS) : [];
                        } catch (e) {
                            console.log('data invalid', e, groupAppDS);
                            oApp.groupApp.data_schemas = [];
                        }
                        if (oApp.groupApp.rounds && oApp.groupApp.rounds.length) {
                            var roundDS = {
                                    id: '_round_id',
                                    type: 'single',
                                    title: '分组名称',
                                },
                                ops = [];
                            oApp.groupApp.rounds.forEach(function(round) {
                                ops.push({
                                    v: round.round_id,
                                    l: round.title
                                });
                            });
                            roundDS.ops = ops;
                            oApp.groupApp.data_schemas.splice(0, 0, roundDS);
                        }
                    }
                    defer.resolve(oApp);
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
                        modifiedData[name] = encodeURIComponent(JSON.stringify(oApp.entry_rule));
                    } else if (name === 'tags') {
                        modifiedData.tags = oApp.tags.join(',');
                    } else {
                        modifiedData[name] = oApp[name];
                    }
                });

                url = '/rest/pl/fe/matter/enroll/update?site=' + siteId + '&app=' + appId;
                http2.post(url, modifiedData, function(rsp) {
                    //noticebox.success('完成保存');
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            remove: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/enroll/remove?site=' + siteId + '&app=' + appId;
                http2.get(url, function(rsp) {
                    defer.resolve();
                });

                return defer.promise;
            },
            jumpPages: function() {
                var defaultInput, pages = oApp.pages,
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
                http2.get('/rest/pl/fe/matter/enroll/entryRuleReset?site=' + siteId + '&app=' + appId, function(rsp) {
                    oApp.entry_rule = rsp.data;
                });
            },
            changeUserScope: function(ruleScope, sns, memberSchemas, defaultInputPage) {
                var entryRule = oApp.entry_rule;
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
                var _this = this,
                    defer = $q.defer();
                mattersgallery.open(siteId, function(matters, type) {
                    var matter;
                    if (matters.length === 1) {
                        matter = {
                            id: appId,
                            type: 'enroll'
                        };
                        http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + siteId + '&id=' + matters[0].mission_id, matter, function(rsp) {
                            var mission = rsp.data,
                                updatedFields = ['mission_id'];

                            oApp.mission = mission;
                            oApp.mission_id = mission.id;
                            if (!oApp.pic || oApp.pic.length === 0) {
                                oApp.pic = mission.pic;
                                updatedFields.push('pic');
                            }
                            if (!oApp.summary || oApp.summary.length === 0) {
                                oApp.summary = mission.summary;
                                updatedFields.push('summary');
                            }
                            _this.update(updatedFields).then(function() {
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
                var _this = this,
                    matter = {
                        id: oApp.id,
                        type: 'enroll',
                        title: oApp.title
                    },
                    defer = $q.defer();
                http2.post('/rest/pl/fe/matter/mission/matter/remove?site=' + siteId + '&id=' + oApp.mission_id, matter, function(rsp) {
                    delete oApp.mission;
                    oApp.mission_id = null;
                    _this.update(['mission_id']).then(function() {
                        defer.resolve();
                    });
                });
                return defer.promise;
            },
            choosePhase: function() {
                var _this = this,
                    phaseId = oApp.mission_phase_id,
                    newPhase, updatedFields = ['mission_phase_id'];

                // 去掉活动标题中现有的阶段后缀
                oApp.mission.phases.forEach(function(phase) {
                    oApp.title = oApp.title.replace('-' + phase.title, '');
                    if (phase.phase_id === phaseId) {
                        newPhase = phase;
                    }
                });
                if (newPhase) {
                    // 给活动标题加上阶段后缀
                    oApp.title += '-' + newPhase.title;
                    updatedFields.push('title');
                    // 设置活动开始时间
                    if (oApp.start_at == 0) {
                        oApp.start_at = newPhase.start_at;
                        updatedFields.push('start_at');
                    }
                    // 设置活动结束时间
                    if (oApp.end_at == 0) {
                        oApp.end_at = newPhase.end_at;
                        updatedFields.push('end_at');
                    }
                } else {
                    updatedFields.push('title');
                }

                _this.update(updatedFields);
            },
            mapSchemas: function(app) {
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
                        if (schemasById[item.id] === undefined) {
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
                angular.forEach(names, function(name) {
                    if (name === 'html') {
                        updated.html = encodeURIComponent(page.html);
                    } else {
                        updated[name] = page[name];
                    }
                });
                url = '/rest/pl/fe/matter/enroll/page/update';
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
                    url = '/rest/pl/fe/matter/enroll/page/remove';

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
    this.$get = ['$q', 'http2', 'noticebox', '$uibModal', 'pushnotify', function($q, http2, noticebox, $uibModal, pushnotify) {
        function _memberAttr(val, schema) {
            var keys;
            if (val.member) {
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
                if (schema.type === 'score') {
                    var label = '';
                    schema.ops.forEach(function(op, index) {
                        label += '<div>' + op.l + ':' + val[op.v] + '</div>';
                    });
                    label = label.replace(/\s\/\s$/, '');
                    return label;
                } else {
                    var aVal, aLab = [];
                    aVal = val.split(',');
                    schema.ops.forEach(function(op, i) {
                        aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                    });
                    if (aLab.length) return aLab.join(',');
                }
            }
            return val;
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
                    record: {
                        verified: ''
                    },
                    tags: [],
                    data: {}
                });
                // records
                _aRecords = oRecords;
            },
            search: function(pageNumber) {
                var _this = this,
                    defer = $q.defer(),
                    url;

                _aRecords.splice(0, _aRecords.length);
                pageNumber && (_oPage.at = pageNumber);
                url = '/rest/pl/fe/matter/enroll/record/list';
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
                        record.data.member && (record.data.member = JSON.parse(record.data.member));
                        _convertRecord4Table(record);
                        _aRecords.push(record);
                    });
                    defer.resolve(records);
                });

                return defer.promise;
            },
            add: function(newRecord) {
                http2.post('/rest/pl/fe/matter/enroll/record/add?site=' + siteId + '&app=' + appId, newRecord, function(rsp) {
                    var record = rsp.data;
                    _convertRecord4Table(record);
                    _aRecords.splice(0, 0, record);
                });
            },
            update: function(record, updated) {
                http2.post('/rest/pl/fe/matter/enroll/record/update?site=' + siteId + '&app=' + appId + '&ek=' + record.enroll_key, updated, function(rsp) {
                    angular.extend(record, rsp.data);
                    _convertRecord4Table(record);
                });
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
                    value.forEach(function(p) {
                        obj.push({
                            imgSrc: p
                        });
                    });
                    data[col.id] = obj;
                }
                return data;
            },
            batchTag: function(rows) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/batchTag.html?_=1',
                    controller: ['$scope', '$uibModalInstance', 'app', function($scope2, $mi, app) {
                        $scope2.appTags = angular.copy(app.tags);
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
                    resolve: {
                        app: function() {
                            return _oApp;
                        },
                    }
                }).result.then(function(result) {
                    var record, selectedRecords = [],
                        eks = [],
                        posted = {};

                    for (var p in rows.selected) {
                        if (rows.selected[p] === true) {
                            record = _aRecords[p];
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
                        http2.post('/rest/pl/fe/matter/enroll/record/batchTag?site=' + siteId + '&app=' + appId, posted, function(rsp) {
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
                    http2.get('/rest/pl/fe/matter/enroll/record/remove?site=' + siteId + '&app=' + appId + '&key=' + record.enroll_key, function(rsp) {
                        var i = _aRecords.indexOf(record);
                        _aRecords.splice(i, 1);
                        _oPage.total = _oPage.total - 1;
                    });
                }
            },
            empty: function() {
                var _this = this,
                    vcode;
                vcode = prompt('是否要删除所有登记信息？，若是，请输入活动名称。');
                if (vcode === _oApp.title) {
                    http2.get('/rest/pl/fe/matter/enroll/record/empty?site=' + siteId + '&app=' + appId, function(rsp) {
                        _this.doSearch(1);
                    });
                }
            },
            verifyAll: function() {
                if (window.confirm('确定审核通过所有记录（共' + _oPage.total + '条）？')) {
                    http2.get('/rest/pl/fe/matter/enroll/record/verifyAll?site=' + siteId + '&app=' + appId, function(rsp) {
                        _aRecords.forEach(function(record) {
                            record.verified = 'Y';
                        });
                        noticebox.success('完成操作');
                    });
                }
            },
            batchVerify: function(rows) {
                var eks = [],
                    selectedRecords = [];
                for (var p in rows.selected) {
                    if (rows.selected[p] === true) {
                        eks.push(_aRecords[p].enroll_key);
                        selectedRecords.push(_aRecords[p]);
                    }
                }
                if (eks.length) {
                    http2.post('/rest/pl/fe/matter/enroll/record/batchVerify?site=' + siteId + '&app=' + appId, {
                        eks: eks
                    }, function(rsp) {
                        selectedRecords.forEach(function(record) {
                            record.verified = 'Y';
                        });
                        noticebox.success('完成操作');
                    });
                }
            },
            notify: function(notifyMatterTypes, rows) {
                var options = {
                    matterTypes: notifyMatterTypes
                };
                _oApp.mission && (options.missionId = _oApp.mission.id);
                pushnotify.open(siteId, function(notify) {
                    var url, targetAndMsg = {};
                    if (notify.matters.length) {
                        if (rows) {
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

                        url = '/rest/pl/fe/matter/enroll/record/notify';
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
            export: function() {
                var url, params = {
                    criteria: _oCriteria
                };

                url = '/rest/pl/fe/matter/enroll/record/export';
                url += '?site=' + siteId + '&app=' + appId;
                window.open(url);
            },
            exportImage: function() {
                var url, params = {
                    criteria: _oCriteria
                };

                url = '/rest/pl/fe/matter/enroll/record/exportImage';
                url += '?site=' + siteId + '&app=' + appId;
                window.open(url);
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
            syncByEnroll: function(record) {
                var url;

                url = '/rest/pl/fe/matter/enroll/record/matchEnroll';
                url += '?site=' + siteId;
                url += '&app=' + appId;

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
            syncByGroup: function(record) {
                var url;

                url = '/rest/pl/fe/matter/enroll/record/matchGroup';
                url += '?site=' + siteId;
                url += '&app=' + appId;

                http2.post(url, record.data, function(rsp) {
                    var matched;
                    if (rsp.data && rsp.data.length === 1) {
                        matched = rsp.data[0];
                        angular.extend(record.data, matched);
                    } else {
                        alert('没有找到匹配的记录，请检查数据是否一致');
                    }
                });
            }
        };
    }];
});