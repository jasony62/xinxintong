'use strict';
angular.module('service.matter', ['ngSanitize', 'ui.bootstrap', 'ui.tms']).
provider('srvSite', function() {
    var _siteId, _oSite, _aSns, _aMemberSchemas, _oTag;
    this.config = function(siteId) {
        _siteId = siteId;
    };
    this.$get = ['$q', '$uibModal', 'http2', function($q, $uibModal, http2) {
        return {
            getSiteId: function() {
                return _siteId;
            },
            getLoginUser: function() {
                var defer, url;
                defer = $q.defer();
                url = '/rest/pl/fe/user/get?_=' + (new Date * 1);
                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            get: function() {
                var defer = $q.defer();
                if (_oSite) {
                    defer.resolve(_oSite);
                } else {
                    http2.get('/rest/pl/fe/site/get?site=' + _siteId, function(rsp) {
                        _oSite = rsp.data;
                        defer.resolve(_oSite);
                    });
                }
                return defer.promise;
            },
            update: function(prop) {
                var oUpdated = {},
                    defer = $q.defer();
                oUpdated[prop] = _oSite[prop];
                http2.post('/rest/pl/fe/site/update?site=' + _siteId, oUpdated, function(rsp) {
                    defer.resolve(_oSite);
                });
                return defer.promise;
            },
            matterList: function(moduleTitle, site, page) {
                if (!site) {
                    site = '';
                } else {
                    site = site;
                }
                if (!page) {
                    page = {
                        at: 1,
                        size: 10,
                        total: 0,
                        _j: function() {
                            return 'page=' + this.at + '&size=' + this.size;
                        }
                    }
                } else {
                    page.at++;
                }
                var url, title = moduleTitle,
                    defer = $q.defer();
                switch (title) {
                    case 'subscribeSite':
                        url = '/rest/pl/fe/site/matterList';
                        break;
                    case 'contributeSite':
                        url = '/rest/pl/fe/site/contribute/list';
                        break;
                    case 'favorSite':
                        url = '/rest/pl/fe/site/favor/list';
                        break;
                }
                url += '?' + page._j() + '&site=' + site;
                http2.get(url, function(rsp) {
                    page.total = rsp.data.total;
                    defer.resolve({ matters: rsp.data.matters, page: page });
                });
                return defer.promise;
            },
            openGallery: function(options) {
                var defer = $q.defer();
                $uibModal.open({
                    templateUrl: '/static/template/mattersgallery2.html?_=8',
                    controller: ['$scope', '$http', '$uibModalInstance', function($scope, $http, $mi) {
                        var fields = ['id', 'title'];
                        $scope.filter = {};
                        $scope.matterTypes = options.matterTypes;
                        $scope.singleMatter = options.singleMatter;
                        $scope.p = {};
                        if ($scope.matterTypes && $scope.matterTypes.length) {
                            $scope.p.matterType = $scope.matterTypes[0];
                        }
                        if (options.mission) {
                            $scope.mission = options.mission;
                            $scope.p.sameMission = 'Y';
                            $scope.p.onlySameMission = options.onlySameMission || false;
                        }
                        $scope.page = {
                            at: 1,
                            size: 10
                        };
                        $scope.aChecked = [];
                        $scope.doCheck = function(matter) {
                            if ($scope.singleMatter) {
                                $scope.aChecked = [matter];
                            } else {
                                var i = $scope.aChecked.indexOf(matter);
                                if (i === -1) {
                                    $scope.aChecked.push(matter);
                                } else {
                                    $scope.aChecked.splice(i, 1);
                                }
                            }
                        };
                        $scope.doSearch = function(pageAt) {
                            if (!$scope.p.matterType) return;
                            var matter = $scope.p.matterType,
                                url = matter.url,
                                params = {};

                            pageAt && ($scope.page.at = pageAt);
                            params.byTitle = $scope.filter.byTitle ? $scope.filter.byTitle : '';
                            url += '/' + matter.value;
                            url += '/list?site=' + _siteId + '&page=' + $scope.page.at + '&size=' + $scope.page.size + '&fields=' + fields;
                            /*指定登记活动场景*/
                            if (matter.value === 'enroll' && matter.scenario) {
                                url += '&scenario=' + matter.scenario;
                            }
                            /*同一个项目*/
                            if ($scope.p.sameMission === 'Y') {
                                url += '&mission=' + $scope.mission.id;
                            }
                            $http.post(url, params).success(function(rsp) {
                                $scope.matters = rsp.data.docs || rsp.data.apps || rsp.data.missions;
                                $scope.page.total = rsp.data.total;
                            });
                        };
                        $scope.cleanFilter = function() {
                            $scope.filter.byTitle = '';
                            $scope.doSearch();
                        }
                        $scope.ok = function() {
                            $mi.close([$scope.aChecked, $scope.p.matterType ? $scope.p.matterType.value : 'article']);
                        };
                        $scope.cancel = function() {
                            $mi.dismiss('cancel');
                        };
                        $scope.createMatter = function() {
                            if ($scope.p.matterType.value === 'article') {
                                $http.get('/rest/pl/fe/matter/article/create?site=' + _siteId).success(function(rsp) {
                                    $mi.close([
                                        [rsp.data], 'article'
                                    ]);
                                });
                            } else if ($scope.p.matterType.value === 'channel') {
                                $http.get('/rest/pl/fe/matter/channel/create?site=' + _siteId).success(function(rsp) {
                                    $mi.close([
                                        [rsp.data], 'channel'
                                    ]);
                                });
                            }
                        };
                        $scope.$watch('p.matterType', function(nv) {
                            $scope.doSearch();
                        });
                    }],
                    size: 'lg',
                    backdrop: 'static',
                    windowClass: 'auto-height mattersgallery'
                }).result.then(function(result) {
                    defer.resolve({ matters: result[0], type: result[1] });
                });
                return defer.promise;
            },
            publicList: function() {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/site/publicList', function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            friendList: function() {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/site/friendList', function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            subscriberList: function(category, page) {
                if (!page) {
                    page = {
                        at: 1,
                        size: 10,
                        total: 0,
                        _j: function() {
                            return 'page=' + this.at + '&size=' + this.size;
                        }
                    };
                } else {
                    page.at++;
                }
                var defer = $q.defer();
                http2.get('/rest/pl/fe/site/subscriberList?site=' + _siteId + '&category=' + category + '&' + page._j(), function(rsp) {
                    page.total = rsp.data.total;
                    defer.resolve({ subscribers: rsp.data.subscribers, page: page });
                });
                return defer.promise;
            },
            snsList: function(siteId) {
                var defer = $q.defer();
                if (!siteId && _aSns) {
                    defer.resolve(_aSns);
                } else {
                    http2.get('/rest/pl/fe/site/snsList?site=' + (siteId || _siteId), function(rsp) {
                        _aSns = rsp.data;
                        defer.resolve(_aSns);
                    });
                }
                return defer.promise;
            },
            tagList: function(subType) {
                var subType = arguments[0] ? arguments[0] : 'M';
                var defer = $q.defer();
                if (_oTag) {
                    defer.resolve(_oTag);
                } else {
                    http2.get('/rest/pl/fe/matter/tag/listTags?site=' + _siteId + '&subType=' + subType, function(rsp) {
                        _oTag = rsp.data;
                        defer.resolve(_oTag);
                    });
                }
                return defer.promise;
            },
            memberSchemaList: function(oMatter, bOnlyMatter) {
                var url, defer = $q.defer();
                if (_aMemberSchemas) {
                    defer.resolve(_aMemberSchemas);
                } else {
                    url = '/rest/pl/fe/site/member/schema/list?valid=Y&site=' + _siteId;
                    if (oMatter && oMatter.id && oMatter.id !== '_pending') {
                        url += '&matter=' + oMatter.id + ',' + oMatter.type;
                        if (bOnlyMatter) {
                            url += '&onlyMatter=Y';
                        }
                    }
                    http2.get(url, function(rsp) {
                        _aMemberSchemas = rsp.data;
                        _aMemberSchemas.forEach(function(ms) {
                            var oSchema, schemas = [],
                                schemasById = {},
                                mschemas = [];
                            if (!ms.attrs.name.hide) {
                                oSchema = {
                                    id: 'member.name',
                                    title: '姓名',
                                    type: 'shorttext',
                                    format: 'name'
                                };
                                schemas.push(oSchema);
                                schemasById[oSchema.id] = oSchema;
                                mschemas.push({
                                    id: 'name',
                                    title: '姓名',
                                    type: 'address'
                                });
                            }
                            if (!ms.attrs.mobile.hide) {
                                oSchema = {
                                    id: 'member.mobile',
                                    title: '手机',
                                    type: 'shorttext',
                                    format: 'mobile'
                                };
                                schemas.push(oSchema);
                                schemasById[oSchema.id] = oSchema;
                                mschemas.push({
                                    id: 'mobile',
                                    title: '手机',
                                    type: 'address'
                                });
                            }
                            if (!ms.attrs.email.hide) {
                                oSchema = {
                                    id: 'member.email',
                                    title: '邮箱',
                                    type: 'shorttext',
                                    format: 'email'
                                };
                                schemas.push(oSchema);
                                schemasById[oSchema.id] = oSchema;
                                mschemas.push({
                                    id: 'email',
                                    title: '邮箱',
                                    type: 'address'
                                });
                            }
                            ms.extAttrs.forEach(function(ea) {
                                var oSchema;
                                oSchema = angular.copy(ea);
                                oSchema.id = 'member.extattr.' + oSchema.id;
                                schemas.push(oSchema);
                                schemasById[oSchema.id] = oSchema;
                                mschemas.push({
                                    id: oSchema.id,
                                    title: oSchema.title,
                                    type: 'address'
                                });
                            });
                            ms._schemas = schemas;
                            ms._schemasById = schemasById;
                            ms._mschemas = mschemas;
                        });
                        defer.resolve(_aMemberSchemas);
                    });
                }
                return defer.promise;
            },
            chooseMschema: function(oMatter) {
                var _this = this;
                return $uibModal.open({
                    templateUrl: '/views/default/pl/fe/_module/chooseMschema.html?_=1',
                    resolve: {
                        mschemas: function() {
                            return _this.memberSchemaList(oMatter);
                        }
                    },
                    controller: ['$scope', '$uibModalInstance', 'mschemas', function($scope2, $mi, mschemas) {
                        $scope2.mschemas = mschemas;
                        $scope2.data = {};
                        if (mschemas.length) {
                            $scope2.data.chosen = mschemas[0];
                        }
                        $scope2.create = function() {
                            var url, proto, oNewSchema;
                            url = '/rest/pl/fe/site/member/schema/create?site=' + _siteId;
                            proto = { valid: 'Y' };
                            if (oMatter && oMatter.id === '_pending') {
                                oNewSchema = {
                                    id: '_pending',
                                    title: (oMatter.title ? oMatter.title + '-' + '通讯录' : '通讯录') + '（待新建）'
                                };
                                mschemas.push(oNewSchema);
                                $scope2.data.chosen = oNewSchema;
                            } else {
                                if (oMatter && oMatter.id) {
                                    proto.matter_id = oMatter.id;
                                    proto.matter_type = oMatter.type;
                                    if (oMatter.title) {
                                        proto.title = oMatter.title + '-' + '通讯录';
                                    }
                                }
                                http2.post(url, proto, function(rsp) {
                                    mschemas.push(rsp.data);
                                    $scope2.data.chosen = rsp.data;
                                });
                            }
                        };
                        $scope2.ok = function() {
                            $mi.close($scope2.data);
                        };
                        $scope2.cancel = function() {
                            $mi.dismiss();
                        };
                    }],
                    backdrop: 'static'
                }).result;
            }
        };
    }];
}).
provider('srvTag', function() {
    var _siteId;
    this.config = function(siteId) {
        _siteId = siteId;
    };
    this.$get = ['$q', '$uibModal', 'http2', function($q, $uibModal, http2) {
        return {
            _tagMatter: function(matter, oTags, subType) {
                var oApp, oTags, tagsOfData, template, defer;
                defer = $q.defer();
                oApp = matter;
                template = '<div class="modal-header">';
                template += '<h5 class="modal-title">打标签 - {{tagTitle}}</h5>';
                template += '</div>';
                template += '<div class="modal-body">';
                template += '<div class=\'list-group\' style=\'max-height:300px;overflow-y:auto\'>';
                template += '<div class=\'list-group-item\' ng-repeat="tag in apptags">';
                template += '<label class=\'checkbox-inline\'>';
                template += '<input type=\'checkbox\' ng-model="model.selected[$index]"> {{tag.title}}</label>';
                template += '</div>';
                template += '</div>';
                template += '<div class=\'form-group\'>';
                template += '<div class=\'input-group\'>';
                template += '<input class=\'form-control\' ng-model="model.newtag">';
                template += '<div class=\'input-group-btn\'>';
                template += '<button ng-disabled="model.newtag.length > 16" class=\'btn btn-default\' ng-click="createTag()" ><span class=\'glyphicon glyphicon-plus\'></span></button>';
                template += '</div>';
                template += '</div>';
                template += '</div>';
                template += '<div ng-show="model.newtag.length > 16" class=\'text-danger\'>标签最多支持16个字，已超过{{model.newtag.length - 16}}字</div>';
                template += '</div>';
                template += '<div class="modal-footer">';
                template += '<button class="btn btn-default" ng-click="cancel()">关闭</button>';
                template += '<button class="btn btn-primary" ng-click="ok()">设置标签</button>';
                template += '</div>';
                $uibModal.open({
                    template: template,
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                        var model;
                        $scope2.apptags = oTags;

                        if (subType === 'C') {
                            tagsOfData = oApp.matter_cont_tag;
                            $scope2.tagTitle = '内容标签';
                        } else {
                            tagsOfData = oApp.matter_mg_tag;
                            $scope2.tagTitle = '管理标签';
                        }
                        $scope2.model = model = {
                            selected: []
                        };
                        if (tagsOfData) {
                            tagsOfData.forEach(function(oTag) {
                                var index;
                                if (-1 !== (index = $scope2.apptags.indexOf(oTag))) {
                                    model.selected[$scope2.apptags.indexOf(oTag)] = true;
                                }
                            });
                        }
                        $scope2.createTag = function() {
                            var newTags;
                            if ($scope2.model.newtag) {
                                newTags = $scope2.model.newtag.replace(/\s/, ',');
                                newTags = newTags.split(',');
                                http2.post('/rest/pl/fe/matter/tag/create?site=' + oApp.siteid + '&subType=' + subType, newTags, function(rsp) {
                                    rsp.data.forEach(function(oNewTag) {
                                        $scope2.apptags.push(oNewTag);
                                    });
                                });
                                $scope2.model.newtag = '';
                            }
                        };
                        $scope2.cancel = function() {
                            $mi.dismiss();
                            defer.resolve();
                        };
                        $scope2.ok = function() {
                            var addMatterTag = [];
                            model.selected.forEach(function(selected, index) {
                                if (selected) {
                                    addMatterTag.push($scope2.apptags[index]);
                                }
                            });
                            var url = '/rest/pl/fe/matter/tag/add?site=' + oApp.siteid + '&resId=' + oApp.id + '&resType=' + oApp.type + '&subType=' + subType;
                            http2.post(url, addMatterTag, function(rsp) {
                                if (subType === 'C') {
                                    matter.matter_cont_tag = addMatterTag;
                                } else {
                                    matter.matter_mg_tag = addMatterTag;
                                }
                            });
                            $mi.close();
                            defer.resolve();
                        };
                    }],
                    backdrop: 'static',
                });
                return defer.promise;
            }
        };
    }];
}).
provider('srvQuickEntry', function() {
    var siteId;
    this.setSiteId = function(id) {
        siteId = id;
    };
    this.$get = ['$q', 'http2', 'noticebox', function($q, http2, noticebox) {
        return {
            get: function(taskUrl) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/q/get?site=' + siteId;
                http2.post(url, {
                    url: encodeURI(taskUrl)
                }, function(rsp) {
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            },
            add: function(taskUrl, title) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/q/create?site=' + siteId;
                http2.post(url, {
                    url: encodeURI(taskUrl),
                    title: title
                }, function(rsp) {
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            },
            remove: function(taskUrl) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/q/remove?site=' + siteId;
                http2.post(url, {
                    url: encodeURI(taskUrl)
                }, function(rsp) {
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            },
            config: function(taskUrl, config) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/q/config?site=' + siteId;
                http2.post(url, {
                    url: encodeURI(taskUrl),
                    config: config
                }, function(rsp) {
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            },
            update: function(code, data) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/q/update?site=' + siteId + '&code=' + code;
                http2.post(url, data, function(rsp) {
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            }
        };
    }];
}).provider('srvRecordConverter', function() {
    this.$get = ['$sce', function($sce) {
        function _memberAttr(oMember, oSchema) {
            var keys, originalValue, afterValue;
            if (oMember) {
                keys = oSchema.id.split('.');
                if (keys.length === 2) {
                    return oMember[keys[1]];
                } else if (keys.length === 3 && oMember.extattr) {
                    if (originalValue = oMember.extattr[keys[2]]) {
                        switch (oSchema.type) {
                            case 'single':
                                if (oSchema.ops && oSchema.ops.length) {
                                    for (var i = oSchema.ops.length - 1; i >= 0; i--) {
                                        if (originalValue === oSchema.ops[i].v) {
                                            afterValue = oSchema.ops[i].l;
                                        }
                                    }
                                }
                                break;
                            case 'multiple':
                                if (oSchema.ops && oSchema.ops.length) {
                                    afterValue = [];
                                    oSchema.ops.forEach(function(op) {
                                        originalValue[op.v] && afterValue.push(op.l);
                                    });
                                    afterValue = afterValue.join(',');
                                }
                                break;
                            default:
                                afterValue = originalValue;
                        }
                    }
                    return afterValue;
                } else {
                    return '';
                }
            } else {
                return '';
            }
        }

        function _value2Html(val, oSchema) {
            if (!val || !oSchema) return '';
            if (oSchema.ops && oSchema.ops.length) {
                if (oSchema.type === 'score') {
                    var label = '';
                    oSchema.ops.forEach(function(op, index) {
                        if (val[op.v] !== undefined) {
                            label += '<div>' + op.l + ':' + val[op.v] + '</div>';
                        }
                    });
                    label = label.replace(/\s\/\s$/, '');
                    return label;
                } else if (angular.isString(val)) {
                    var aVal, aLab = [];
                    aVal = val.split(',');
                    oSchema.ops.forEach(function(op, i) {
                        aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                    });
                    if (aLab.length) return aLab.join(',');
                } else if (angular.isObject(val) || angular.isArray(val)) {
                    val = JSON.stringify(val);
                }
            }
            return val;
        }

        function _forTable(oRecord, mapOfSchemas) {
            var oSchema, type, data = {};

            if (oRecord.state !== undefined) {
                oRecord._state = _mapOfRecordState[oRecord.state];
            }
            if (oRecord.data && mapOfSchemas) {
                for (var schemaId in mapOfSchemas) {
                    oSchema = mapOfSchemas[schemaId];
                    type = oSchema.type;
                    /* 分组活动导入数据时会将member题型改为shorttext题型 */
                    if (oSchema.schema_id && oRecord.data.member) {
                        type = 'member';
                    }
                    switch (type) {
                        case 'image':
                            var imgs = oRecord.data[oSchema.id] ? oRecord.data[oSchema.id].split(',') : [];
                            data[oSchema.id] = imgs;
                            break;
                        case 'file':
                            var files = oRecord.data[oSchema.id] ? oRecord.data[oSchema.id] : {};
                            data[oSchema.id] = files;
                            break;
                        case 'multitext':
                            var multitexts = oRecord.data[oSchema.id] ? oRecord.data[oSchema.id] : [];
                            data[oSchema.id] = multitexts;
                            break;
                        case 'member':
                            data[oSchema.id] = _memberAttr(oRecord.data.member, oSchema);
                            break;
                        default:
                            data[oSchema.id] = $sce.trustAsHtml(_value2Html(oRecord.data[oSchema.id], oSchema));
                    }
                };
                oRecord._data = data;
            }
            return oRecord;
        }

        var _mapOfRecordState = {
                '0': '删除',
                '1': '正常',
                '100': '删除',
                '101': '用户删除',
            },
            _mapOfSchemas;
        return {
            config: function(schemas) {
                if (angular.isString(schemas)) {
                    schemas = JSON.parse(schemas);
                }
                if (angular.isArray(schemas)) {
                    _mapOfSchemas = {};
                    schemas.forEach(function(schema) {
                        _mapOfSchemas[schema.id] = schema;
                    });
                } else {
                    _mapOfSchemas = schemas;
                }
            },
            forTable: function(record, mapOfSchemas) {
                var map;
                if (mapOfSchemas && angular.isArray(mapOfSchemas)) {
                    map = {};
                    mapOfSchemas.forEach(function(oSchema) {
                        map[oSchema.id] = oSchema;
                    });
                    mapOfSchemas = map;
                }
                return _forTable(record, mapOfSchemas ? mapOfSchemas : _mapOfSchemas);
            },
            forEdit: function(schema, data) {
                if (schema.type === 'file') {
                    var files;
                    if (data[schema.id] && data[schema.id].length) {
                        files = data[schema.id];
                        files.forEach(function(file) {
                            file.url && $sce.trustAsUrl(file.url);
                        });
                    }
                    data[schema.id] = files;
                } else if (schema.type === 'multiple') {
                    var obj = {},
                        value;
                    if (data[schema.id] && data[schema.id].length) {
                        value = data[schema.id].split(',')
                        value.forEach(function(p) {
                            obj[p] = true;
                        });
                    }
                    data[schema.id] = obj;
                } else if (schema.type === 'image') {
                    var value = data[schema.id],
                        obj = [];
                    if (value && value.length) {
                        value = value.split(',');
                        value.forEach(function(p) {
                            obj.push({
                                imgSrc: p
                            });
                        });
                    }
                    data[schema.id] = obj;
                }

                return data;
            },
            value2Html: function(val, schema) {
                return _value2Html(val, schema);
            }
        };
    }];
}).
provider('srvUserNotice', function() {
    var _logs, _oPage;
    _logs = [];
    _oPage = {
        at: 1,
        size: 10,
        j: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    };
    this.$get = ['$q', 'http2', function($q, http2) {
        return {
            uncloseList: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/user/notice/uncloseList?' + _oPage.j();
                http2.get(url, function(rsp) {
                    _logs.splice(0, _logs.length);
                    rsp.data.logs.forEach(function(log) {
                        if (log.data) {
                            log._message = JSON.parse(log.data);
                            log._message = log._message.join('\n');
                        }
                        _logs.push(log);
                    });
                    _oPage.total = rsp.data.total;
                    defer.resolve({ logs: _logs, page: _oPage });
                });
                return defer.promise;
            },
            closeNotice: function(log) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/user/notice/close?id=' + log.id;
                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            }
        }
    }];
}).
provider('srvTmplmsgNotice', function() {
    this.$get = ['$q', 'http2', function($q, http2) {
        return {
            init: function(sender, oPage, aBatches) {
                this._sender = sender;
                // pagination
                this._oPage = oPage;
                angular.extend(this._oPage, {
                    at: 1,
                    size: 30,
                    j: function() {
                        var p;
                        p = '&page=' + this.at + '&size=' + this.size;
                        return p;
                    }
                });
                // records
                this._aBatches = aBatches;
            },
            list: function(_appId, page) {
                var that = this,
                    defer = $q.defer(),
                    url;

                this._aBatches.splice(0, this._aBatches.length);
                url = '/rest/pl/fe/matter/tmplmsg/notice/list?sender=' + this._sender + this._oPage.j();
                http2.get(url, function(rsp) {
                    that._oPage.total = rsp.data.total;
                    rsp.data.batches.forEach(function(batch) {
                        that._aBatches.push(batch);
                    });
                    defer.resolve(that._aBatches);
                });

                return defer.promise;
            },
            detail: function(batch) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/tmplmsg/notice/detail?batch=' + batch.id;
                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data.logs);
                });

                return defer.promise;
            }
        };
    }];
}).controller('ctrlSetChannel', ['$scope', 'http2', 'srvSite', function($scope, http2, srvSite) {
    $scope.$on('channel.xxt.combox.done', function(event, aSelected) {
        var i, j, existing, aNewChannels = [],
            relations = {},
            matter = $scope.$parent[$scope.matterObj];
        for (i in aSelected) {
            existing = false;
            for (j in matter.channels) {
                if (aSelected[i].id === matter.channels[j].id) {
                    existing = true;
                    break;
                }
            }!existing && aNewChannels.push(aSelected[i]);
        }
        relations.channels = aNewChannels;
        relations.matter = {
            id: matter.id,
            type: $scope.matterType
        };
        http2.post('/rest/pl/fe/matter/channel/addMatter?site=' + srvSite.getSiteId(), relations, function() {
            matter.channels = matter.channels.concat(aNewChannels);
        });
    });
    $scope.$on('channel.xxt.combox.link', function(event, clicked) {
        location.href = '/rest/pl/fe/matter/channel?site=' + $scope.editing.siteid + '&id=' + clicked.id;
    });
    $scope.$on('channel.xxt.combox.del', function(event, removed) {
        var matter = $scope.$parent[$scope.matterObj],
            param = {
                id: matter.id,
                type: $scope.matterType
            };
        http2.post('/rest/pl/fe/matter/channel/removeMatter?site=' + srvSite.getSiteId() + '&id=' + removed.id, param, function(rsp) {
            matter.channels.splice(matter.channels.indexOf(removed), 1);
        });
    });
    http2.get('/rest/pl/fe/matter/channel/list?site=' + srvSite.getSiteId() + '&cascade=N', function(rsp) {
        $scope.channels = rsp.data.docs;
    });
}]).
provider('srvInvite', function() {
    var _matterType, _matterId;
    this.config = function(matterType, matterId) {
        _matterType = matterType;
        _matterId = matterId;
    };
    this.$get = ['$q', 'http2', function($q, http2) {
        return {
            get: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/invite/get?matter=' + _matterType + ',' + _matterId;
                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            make: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/invite/create?matter=' + _matterType + ',' + _matterId;
                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            addCode: function(oInvite) {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/invite/code/add?invite=' + oInvite.id, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            }
        }
    }];
});