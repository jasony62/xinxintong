'use strict';
angular.module('service.matter', ['ngSanitize', 'ui.bootstrap', 'ui.tms', 'http.ui.xxt']).
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
                http2.get(url).then(function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            get: function() {
                var defer = $q.defer();
                if (_oSite) {
                    defer.resolve(_oSite);
                } else {
                    http2.get('/rest/pl/fe/site/get?site=' + _siteId).then(function(rsp) {
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
                http2.post('/rest/pl/fe/site/update?site=' + _siteId, oUpdated).then(function(rsp) {
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
                http2.get(url).then(function(rsp) {
                    page.total = rsp.data.total;
                    defer.resolve({ matters: rsp.data.matters, page: page });
                });
                return defer.promise;
            },
            openGallery: function(options) {
                var defer = $q.defer();
                $uibModal.open({
                    templateUrl: '/static/template/mattersgallery.html?_=1',
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
                            if ($scope.p.fromPlatform === 'Y') {
                                url += '&platform=Y';
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
                http2.get('/rest/pl/fe/site/publicList').then(function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            friendList: function() {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/site/friendList').then(function(rsp) {
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
                http2.get('/rest/pl/fe/site/subscriberList?site=' + _siteId + '&category=' + category + '&' + page._j()).then(function(rsp) {
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
                    http2.get('/rest/pl/fe/site/snsList?site=' + (siteId || _siteId)).then(function(rsp) {
                        _aSns = rsp.data;
                        _aSns.names = Object.keys(_aSns);
                        _aSns.count = _aSns.names.length;
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
                    http2.get('/rest/pl/fe/matter/tag/listTags?site=' + _siteId + '&subType=' + subType).then(function(rsp) {
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
                    http2.get(url).then(function(rsp) {
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
                            if (ms.extAttrs) {
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
                            }
                            ms._schemas = schemas;
                            ms._schemasById = schemasById;
                            ms._mschemas = mschemas;
                        });
                        defer.resolve(_aMemberSchemas);
                    });
                }
                return defer.promise;
            },
            chooseMschema: function(oMatter, $oMission) {
                var _this = this;
                return $uibModal.open({
                    templateUrl: '/views/default/pl/fe/_module/chooseMschema.html?_=1',
                    resolve: {
                        mschemas: function() {
                            if (oMatter && oMatter.id && oMatter.id !== '_pending') {
                                return _this.memberSchemaList(oMatter);
                            } else if ($oMission) {
                                return _this.memberSchemaList($oMission);
                            } else {
                                return _this.memberSchemaList();
                            }
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
                                http2.post(url, proto).then(function(rsp) {
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
                                http2.post('/rest/pl/fe/matter/tag/create?site=' + oApp.siteid + '&subType=' + subType, newTags).then(function(rsp) {
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
                            http2.post(url, addMatterTag).then(function(rsp) {
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
                }).then(function(rsp) {
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
                }).then(function(rsp) {
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
                }).then(function(rsp) {
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
                }).then(function(rsp) {
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            },
            update: function(code, data) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/q/update?site=' + siteId + '&code=' + code;
                http2.post(url, data).then(function(rsp) {
                    defer.resolve(rsp.data);
                });

                return defer.promise;
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
                http2.get(url).then(function(rsp) {
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
                http2.get(url).then(function(rsp) {
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
                http2.get(url).then(function(rsp) {
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
                http2.get(url).then(function(rsp) {
                    defer.resolve(rsp.data.logs);
                });

                return defer.promise;
            }
        };
    }];
}).
controller('ctrlSetChannel', ['$scope', 'http2', 'srvSite', function($scope, http2, srvSite) {
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
        http2.post('/rest/pl/fe/matter/channel/addMatter?site=' + srvSite.getSiteId(), relations).then(function() {
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
        http2.post('/rest/pl/fe/matter/channel/removeMatter?site=' + srvSite.getSiteId() + '&id=' + removed.id, param).then(function(rsp) {
            matter.channels.splice(matter.channels.indexOf(removed), 1);
        });
    });
    http2.get('/rest/pl/fe/matter/channel/list?site=' + srvSite.getSiteId() + '&cascade=N').then(function(rsp) {
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
                http2.get(url).then(function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            make: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/invite/create?matter=' + _matterType + ',' + _matterId;
                http2.get(url).then(function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            addCode: function(oInvite) {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/invite/code/add?invite=' + oInvite.id).then(function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            }
        }
    }];
}).
provider('srvMemberPicker', function() {
    this.$get = ['$q', 'http2', '$uibModal', 'noticebox', function($q, http2, $uibModal, noticebox) {
        return {
            open: function(oMatter, oMschema) {
                var defer = $q.defer(),
                    url;
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/_module/memberPicker.html',
                    resolve: {
                        action: function() {
                            return {
                                label: '加入',
                                execute: function(members, schemas) {
                                    var ids, defer;
                                    defer = $q.defer();
                                    if (members.length) {
                                        ids = [];
                                        members.forEach(function(oMember) {
                                            oMember.checkedId = oMatter.type == 'mschema' ? oMember.userid : oMember.id;
                                            ids.push(oMember.checkedId);
                                        });
                                        schemas && schemas.length ? schemaUser(schemas, 0) : matterUser();
                                    }

                                    function schemaUser(schemas, rounds) {
                                        http2.post('/rest/pl/fe/site/member/schema/importSchema?site=' + oMatter.siteid + '&id=' + oMatter.id + '&rounds=' + rounds, { 'schemas': schemas, 'users': ids }).then(function(rsp) {
                                            if (rsp.data.state !== 'end') {
                                                var group = parseInt(rsp.data.group) + 1;
                                                noticebox.success('已导入用户' + rsp.data.plan + '/' + rsp.data.total);
                                                schemaUser(schemas, group);
                                            } else {
                                                defer.resolve(rsp.data);
                                                noticebox.success('已导入用户' + rsp.data.plan + '/' + rsp.data.total);
                                            }
                                        });
                                    };

                                    function matterUser() {
                                        http2.post('/rest/pl/fe/matter/group/player/addByApp?app=' + oMatter.id, ids).then(function(rsp) {
                                            noticebox.success('加入【' + rsp.data + '】个用户');
                                            defer.resolve(rsp.data);
                                        });
                                    }
                                    return defer.promise;
                                }
                            }
                        }
                    },
                    controller: ['$scope', '$uibModalInstance', 'http2', 'tmsSchema', 'action', function($scope2, $mi, http2, tmsSchema, _oAction) {
                        var _oPage, _oRows, _bAdded, _oMschema, doSearch;
                        $scope2.action = _oAction;
                        $scope2.page = _oPage = {
                            at: 1,
                            size: 30,
                            keyword: '',
                            //searchBy: $scope2.searchBys[0].v
                        };
                        // 选中的记录
                        $scope2.rows = _oRows = {
                            schemas: {},
                            selected: {},
                            count: 0,
                            impschemaId: '',
                            change: function(index) {
                                this.selected[index] ? this.count++ : this.count--;
                            },
                            reset: function() {
                                this.selected = {};
                                this.count = 0;
                            }
                        };

                        function doSchemas() {
                            http2.get('/rest/pl/fe/site/member/schema/listImportSchema?site=' + oMschema.siteid + '&id=' + oMschema.id).then(function(rsp) {
                                $scope2.importSchemas = rsp.data;
                                _oRows.impschemaId = rsp.data[0].id;
                                rsp.data.forEach(function(oSchema) {
                                    _oRows.schemas[oSchema.id] = oSchema;
                                });
                                doSearch(1);
                            });
                        };
                        $scope2.doSearch = doSearch = function(pageAt) {
                            pageAt && (_oPage.at = pageAt);
                            var url, filter = '',
                                selectedSchemaId;
                            selectedSchemaId = _oRows.impschemaId ? _oRows.impschemaId : oMschema.id;
                            $scope2.mschema = _oMschema = oMatter.type == 'mschema' ? _oRows.schemas[selectedSchemaId] : oMschema;
                            if (_oPage.keyword !== '') {
                                filter = '&kw=' + _oPage.keyword;
                                filter += '&by=' + _oPage.searchBy;
                            }
                            url = '/rest/pl/fe/site/member/list?site=' + oMschema.siteid + '&schema=' + selectedSchemaId;
                            url += '&page=' + _oPage.at + '&size=' + _oPage.size + filter
                            url += '&contain=total';
                            http2.get(url).then(function(rsp) {
                                var members;
                                members = rsp.data.members;
                                if (members.length) {
                                    if (_oMschema.extAttrs.length) {
                                        members.forEach(function(oMember) {
                                            oMember._extattr = tmsSchema.member.getExtattrsUIValue(_oMschema.extAttrs, oMember);
                                        });
                                    }
                                }
                                $scope2.members = members;
                                _oPage.total = rsp.data.total;
                                _oRows.reset();
                            });
                        };
                        $scope2.cancel = function() {
                            _bAdded ? $mi.close() : $mi.dismiss();
                        };
                        $scope2.execute = function(bClose) {
                            var schemas, pickedMembers;
                            if (_oRows.impschemaId) {
                                schemas = [];
                                schemas.push(_oRows.impschemaId);
                            }
                            if (_oRows.count) {
                                pickedMembers = [];
                                for (var i in _oRows.selected) {
                                    pickedMembers.push($scope2.members[i]);
                                }
                                _oAction.execute(pickedMembers, schemas).then(function() {
                                    if (bClose) {
                                        $mi.close();
                                    } else {
                                        _bAdded = true;
                                    }
                                });
                            }
                        };
                        oMatter.type == 'mschema' ? doSchemas() : doSearch(1);
                    }],
                    size: 'lg',
                    backdrop: 'static',
                    windowClass: 'auto-height mattersgallery'
                }).result.then(function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    }];
}).
/* 选取访客账号 */
service('tkAccount', ['$q', 'http2', '$uibModal', 'noticebox', function($q, http2, $uibModal, noticebox) {
    this.pick = function(oSite, oConfig) {
        var defer = $q.defer();
        http2.post('/rest/script/time', { html: { 'picker': '/views/default/pl/fe/_module/accountPicker' } }).then(function(oTemplateTimes) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/_module/accountPicker.html?_=' + oTemplateTimes.data.html.picker.time,
                controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                    var _oPage, _oRows, _fnSearch;
                    $scope2.config = oConfig || {};
                    $scope2.page = _oPage = { size: 30 };
                    $scope2.rows = _oRows = {
                        change: function(index) {
                            this.selected[index] ? this.count++ : this.count--;
                        },
                        reset: function() {
                            this.selected = $scope2.config.single === true ? '-1' : {};
                            this.count = 0;
                        }
                    };
                    _oRows.reset();
                    $scope2.doSearch = _fnSearch = function(pageAt) {
                        var url, data;
                        pageAt && (_oPage.at = pageAt);
                        url = '/rest/pl/fe/site/user/account/list?site=' + oSite.id;
                        http2.post(url, {}, { page: _oPage }).then(function(rsp) {
                            $scope2.users = rsp.data.users;
                        });
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.execute = function() {
                        var pickedAccounts;
                        if ($scope2.config.single === true) {
                            if (_oRows.selected >= 0 && $scope2.users[_oRows.selected]) {
                                $mi.close($scope2.users[_oRows.selected]);
                            }
                        } else {
                            if (_oRows.count) {
                                pickedAccounts = [];
                                for (var i in _oRows.selected) {
                                    pickedAccounts.push($scope2.users[i]);
                                }
                            }
                            $mi.close(pickedAccounts);
                        }
                    };
                    _fnSearch(1);
                }],
                size: 'lg',
                backdrop: 'static',
                windowClass: 'auto-height'
            }).result.then(function(accounts) {
                defer.resolve(accounts);
            });
        });
        return defer.promise;
    };
}]).
/* 通讯录用户通用服务 */
service('tkMember', ['$q', 'http2', '$uibModal', 'noticebox', function($q, http2, $uibModal, noticebox) {
    this.create = function(oMschema, oProto) {
        var defer = $q.defer();
        http2.post('/rest/script/time', { html: { 'editor': '/views/default/pl/fe/_module/memberEditor' } }).then(function(oTemplateTimes) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/_module/memberEditor.html?_=' + oTemplateTimes.data.html.editor.time,
                controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
                    $scope2.schema = oMschema;
                    $scope2.member = oProto ? angular.copy(oProto) : {};
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        http2.post('/rest/pl/fe/site/member/create?schema=' + oMschema.id, $scope2.member).then(function(rsp) {
                            $mi.close(rsp.data);
                        });
                    };
                }],
                backdrop: 'static',
            }).result.then(function(oNewMember) {
                defer.resolve(oNewMember);
            });
        });
        return defer.promise;
    };
    this.edit = function(oMschema, oMember) {
        var defer = $q.defer();
        http2.post('/rest/script/time', { html: { 'editor': '/views/default/pl/fe/_module/memberEditor' } }).then(function(oTemplateTimes) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/_module/memberEditor.html?_=' + oTemplateTimes.data.html.editor.time,
                controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
                    $scope2.schema = oMschema;
                    $scope2.member = angular.copy(oMember);
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close({
                            action: 'update',
                            data: $scope2.member
                        });
                    };
                    $scope2.remove = function() {
                        $mi.close({
                            action: 'remove'
                        });
                    };
                }],
                backdrop: 'static',
            }).result.then(function(rst) {
                if (rst.action === 'update') {
                    var data = rst.data,
                        oUpdated = {
                            verified: data.verified,
                            name: data.name,
                            mobile: data.mobile,
                            email: data.email,
                            email_verified: data.email_verified,
                            extattr: data.extattr
                        };
                    http2.post('/rest/pl/fe/site/member/update?id=' + oMember.id, oUpdated).then(function(rsp) {
                        angular.extend(oMember, oUpdated);
                        defer.resolve({ action: 'update' });
                    });
                } else if (rst.action === 'remove') {
                    http2.get('/rest/pl/fe/site/member/remove?id=' + oMember.id).then(function() {
                        defer.resolve({ action: 'remove' });
                    });
                }
            });
        });
        return defer.promise;
    };
}]).
controller('ctrlStat', ['$scope', 'http2', '$uibModal', '$compile', function($scope, http2, $uibModal, $compile) {
    var page, criteria, time1, time2, app;
    time1 = (function() {
        var t;
        t = new Date;
        t.setHours(8);
        t.setMinutes(0);
        t.setMilliseconds(0);
        t.setSeconds(0);
        t = parseInt(t / 1000);
        return t;
    })();
    time2 = (function() {
        var t = new Date;
        t = new Date(t.setDate(t.getDate() + 1));
        t.setHours(8);
        t.setMinutes(0);
        t.setMilliseconds(0);
        t.setSeconds(0);
        t = parseInt(t / 1000);
        return t;
    })();
    $scope.page = page = {
        at: 1,
        size: 30,
        _j: function() {
            return '&page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.criteria = criteria = {
        startAt: '',
        endAt: '',
        byEvent: ''
    };
    $scope.events = [{
        id: 'read',
        value: '阅读'
    }, {
        id: 'shareT',
        value: '分享'
    }, {
        id: 'shareF',
        value: '转发'
    }];
    $scope.operation = {
        'read': '阅读',
        'shareT': '分享',
        'shareF': '转发'
    };
    $scope.list = function() {
        var url;
        url = '/rest/pl/fe/matter/' + app.type + '/log/matterActionLog?site=' + app.siteid + '&appId=' + app.id + page._j();
        http2.post(url, criteria).then(function(rsp) {
            $scope.logs = rsp.data.logs;
            page.total = rsp.data.total;
        });
    };
    $scope.export = function(user) {
        var url;
        url = '/rest/pl/fe/matter/' + app.type + '/log/exportMatterActionLog?site=' + app.siteid + '&appId=' + app.id;
        url += '&startAt=' + criteria.startAt + '&endAt=' + criteria.endAt + '&byEvent=' + criteria.byEvent;
        window.open(url);
    };

    $scope.$watch('editing', function(nv) {
        if (!nv) return;
        app = nv;
        criteria.startAt = time1;
        criteria.endAt = time2;
        $scope.list();
    });
}]).
/**
 * 轮次生成规则
 */
service('tkRoundCron', ['$rootScope', '$q', '$uibModal', 'http2', function($rootScope, $q, $uibModal, http2) {
    var _aCronRules, _$scope;
    this.editing = { modified: false };
    this.mdays = [];
    while (this.mdays.length < 28) {
        this.mdays.push('' + (this.mdays.length + 1));
    }
    this.init = function(oMatter) {
        this.matter = oMatter;
        this.editing.rules = _aCronRules = (oMatter.roundCron ? angular.copy(oMatter.roundCron) : []);
        _$scope = $rootScope.$new(true);
        _$scope.editing = this.editing;
        _$scope.$on('xxt.tms-datepicker.change', function(event, oData) {
            oData.obj[oData.state] = oData.value;
        });
        _$scope.$watch('editing.rules', function(newRules, oldRules) {
            if (newRules !== oldRules) {
                _$scope.editing.modified = true;
            }
        }, true);
        return this;
    };
    this.example = function(oRule) {
        http2.post('/rest/pl/fe/matter/' + this.matter.type + '/round/getCron', { roundCron: oRule }).then(function(rsp) {
            oRule.case = rsp.data;
        });
    };
    this.addPeriod = function() {
        var oNewRule;
        oNewRule = {
            purpose: 'C',
            pattern: 'period',
            period: 'D',
            hour: '8',
            notweekend: true,
            enabled: 'N',
        };
        _aCronRules.push(oNewRule);
    };
    this.changePeriod = function(oRule) {
        switch (oRule.period) {
            case 'W':
                !oRule.wday && (oRule.wday = '1');
                break;
            case 'M':
                !oRule.mday && (oRule.mday = '1');
                break;
        }!oRule.hour && (oRule.hour = '8');
    };
    this.addInterval = function() {
        var oNewRule;
        oNewRule = {
            pattern: 'interval',
            start_at: parseInt(new Date * 1 / 1000),
            enabled: 'N',
        };
        _aCronRules.push(oNewRule);
    };
    this.removeRule = function(oRule) {
        _aCronRules.splice(_aCronRules.indexOf(oRule), 1);
    };
    this.choose = function(oMatter) {
        var defer = $q.defer();
        http2.post('/rest/script/time', { html: { 'picker': '/views/default/pl/fe/_module/chooseRoundCron' } }).then(function(oTemplateTimes) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/_module/chooseRoundCron.html?_=' + oTemplateTimes.data.html.picker.time,
                controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
                    $scope2.roundCron = oMatter.roundCron;
                    $scope2.data = {};
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data.chosen);
                    };
                }],
                backdrop: 'static',
            }).result.then(function(oRule) {
                if (oRule)
                    defer.resolve(oRule);
            });
        });
        return defer.promise;
    };
}]).
/**
 * 定时通知
 */
service('srvTimerNotice', ['$rootScope', '$parse', '$q', '$timeout', 'http2', 'tkRoundCron', function($rootScope, $parse, $q, $timeout, http2, tkRoundCron) {
    function fnDbToLocal(oDb, oLocal) {
        ['pattern', 'task_expire_at', 'task_model', 'enabled', 'notweekend', 'offset_matter_type', 'offset_matter_id', 'offset_mode'].forEach(function(prop) {
            oLocal.task[prop] = oDb[prop];
        });
        ['min', 'hour', 'wday', 'mday', 'mon', 'offset_min', 'offset_hour'].forEach(function(prop) {
            oLocal.task[prop] = '' + oDb[prop];
        });
        oLocal.task.task_arguments = oDb.task_arguments ? oDb.task_arguments : { page: '' };
    }

    function fnAppendLocal(oDbTimer, oMatter) {
        var oLocalTimer;
        oLocalTimer = {
            id: oDbTimer.id,
            task: {}
        };
        if (oMatter) {
            oLocalTimer.matter = oMatter;
            /* 参照轮次规则 */
            if (oDbTimer.offset_matter_type === 'RC' && oDbTimer.offset_matter_id) {
                if (oMatter.roundCron && oMatter.roundCron.length) {
                    for (var i = 0, ii = oMatter.roundCron.length; i < ii; i++) {
                        if (oMatter.roundCron[i].id === oDbTimer.offset_matter_id) {
                            $parse('surface.offset.matter').assign(oLocalTimer, { name: oMatter.roundCron[i].name })
                            break;
                        }
                    }
                }
            }
        }
        fnDbToLocal(oDbTimer, oLocalTimer);
        oWatcher['t_' + oLocalTimer.id] = oLocalTimer;
        $scope.$watch('watcher.t_' + oLocalTimer.id, function(oUpdTask, oOldTask) {
            if (oUpdTask && oUpdTask.task) {
                if (!angular.equals(oUpdTask.task, oOldTask.task)) {
                    oUpdTask.modified = true;
                    if (oUpdTask.task.offset_matter_type !== oOldTask.task.offset_matter_type) {
                        switch (oUpdTask.task.offset_matter_type) {
                            case 'RC':
                                !oUpdTask.task.offset_mode && (oUpdTask.task.offset_mode = 'AS');
                                oUpdTask.task.offset_hour = '0';
                                oUpdTask.task.offset_min = '0';
                                break;
                        }
                    }
                }
            }
        }, true);

        return oLocalTimer
    }
    var $scope, oWatcher; // 监控数据的变化情况
    $scope = $rootScope.$new(true);
    $scope.watcher = oWatcher = {};
    var fnBeforeSaves = []; // 保存数据前进行处理
    /* 添加定时任务 */
    this.add = function(oMatter, timers, model, oArgs) {
        var oConfig;
        oConfig = {
            matter: { id: oMatter.id, type: oMatter.type },
            task: { model: model }
        };
        if (oArgs) oConfig.task.arguments = oArgs;
        http2.post('/rest/pl/fe/matter/timer/create', oConfig).then(function(rsp) {
            var oNewTimer;
            oNewTimer = fnAppendLocal(rsp.data, oMatter);
            timers.push(oNewTimer);
        });
    };
    /* 保存定时任务设置 */
    this.save = function(oTimer) {
        function fnOne(i) {
            if (i < fnBeforeSaves.length) {
                fnBeforeSaves[i](oTimer).then(function() {
                    fnOne(++i);
                });
            } else {
                http2.post('/rest/pl/fe/matter/timer/update?id=' + oTimer.id, oTimer.task).then(function(rsp) {
                    fnDbToLocal(rsp.data, oTimer);
                    oTimer.modified = false;
                });
            }
        }
        if (fnBeforeSaves.length) {
            fnOne(0);
        } else {
            http2.post('/rest/pl/fe/matter/timer/update?id=' + oTimer.id, oTimer.task).then(function(rsp) {
                fnDbToLocal(rsp.data, oTimer);
                $timeout(function() {
                    oTimer.modified = false;
                });
            });
        }
    };
    /* 保存前进行处理 */
    this.onBeforeSave = function(fnHandler) {
        fnBeforeSaves.push(fnHandler);
    };
    /* 删除定时任务 */
    this.del = function(timers, index) {
        var oTimer;
        if (window.confirm('确定删除定时规则？')) {
            oTimer = timers[index];
            http2.get('/rest/pl/fe/matter/timer/remove?id=' + oTimer.id).then(function(rsp) {
                timers.splice(index, 1);
            });
        }
    };
    /* 设置偏移的素材 */
    this.setOffsetMatter = function(oTimer) {
        if (oTimer.task.offset_matter_type === 'RC') {
            tkRoundCron.choose(oTimer.matter).then(function(oRule) {
                oTimer.task.offset_matter_id = oRule.id;
                $parse('surface.offset.matter').assign(oTimer, { name: oRule.name })
            });
        }
    };
    /* 根据id获得定时任务 */
    this.timerById = function(id) {
        return oWatcher[id];
    };
    /* 定时任务列表 */
    this.list = function(oMatter, model) {
        var defer = $q.defer();
        http2.get('/rest/pl/fe/matter/timer/byMatter?type=' + oMatter.type + '&id=' + oMatter.id + '&model=' + model).then(function(rsp) {
            var timers = [];
            rsp.data.forEach(function(oTask) {
                var oNewTimer;
                oNewTimer = fnAppendLocal(oTask, oMatter);
                timers.push(oNewTimer);
            });
            defer.resolve(timers);
        });

        return defer.promise;
    };
}]).
/**
 * enroll
 */
service('tkEnrollApp', ['$q', '$uibModal', 'http2', function($q, $uibModal, http2) {
    function _fnMakeApiUrl(oApp, action) {
        var url;
        url = '/rest/pl/fe/matter/enroll/' + action + '?site=' + oApp.siteid + '&app=' + oApp.id;
        return url;
    }
    this.update = function(oApp, oModifiedData) {
        var defer = $q.defer();
        http2.post(_fnMakeApiUrl(oApp, 'update'), oModifiedData).then(function(rsp) {
            defer.resolve(rsp.data);
        });
        return defer.promise;
    };
    this.choose = function(oApp) {
        var defer;
        defer = $q.defer();
        http2.post('/rest/script/time', { html: { 'enrollApp': '/views/default/pl/fe/_module/chooseEnrollApp' } }).then(function(rsp) {
            return $uibModal.open({
                templateUrl: '/views/default/pl/fe/_module/chooseEnrollApp.html?_=' + rsp.data.html.enrollApp.time,
                controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                    $scope2.app = oApp;
                    $scope2.data = {};
                    oApp.mission && ($scope2.data.sameMission = 'Y');
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                    var url = '/rest/pl/fe/matter/enroll/list?site=' + oApp.siteid + '&size=999';
                    oApp.mission && (url += '&mission=' + oApp.mission.id);
                    http2.get(url).then(function(rsp) {
                        $scope2.apps = rsp.data.apps;
                    });
                }],
                backdrop: 'static'
            }).result.then(function(oResult) {
                defer.resolve(oResult);
            });
        });
        return defer.promise;
    };
}]).
/**
 * group app
 */
service('tkGroupApp', ['$uibModal', function($uibModal) {
    this.choose = function(oMatter) {
        return $uibModal.open({
            templateUrl: '/views/default/pl/fe/matter/enroll/component/chooseGroupApp.html',
            controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                $scope2.app = oMatter;
                $scope2.data = {
                    app: null,
                    round: null
                };
                oMatter.mission && ($scope2.data.sameMission = 'Y');
                $scope2.cancel = function() {
                    $mi.dismiss();
                };
                $scope2.ok = function() {
                    $mi.close($scope2.data);
                };
                $scope2.$watch('data.app', function(oGrpApp) {
                    if (oGrpApp) {
                        var url = '/rest/pl/fe/matter/group/round/list?app=' + oGrpApp.id + '&roundType=';
                        http2.get(url).then(function(rsp) {
                            $scope2.rounds = rsp.data;
                        });
                    }
                });
                var url = '/rest/pl/fe/matter/group/list?site=' + oMatter.siteid + '&size=999';
                oMatter.mission && (url += '&mission=' + oMatter.mission.id);
                http2.get(url).then(function(rsp) {
                    $scope2.apps = rsp.data.apps;
                });
            }],
            backdrop: 'static'
        }).result;
    };
}]).
/**
 * 素材进入规则
 */
factory('tkEntryRule', ['$rootScope', '$timeout', 'noticebox', 'http2', 'srvSite', 'tkEnrollApp', 'tkGroupApp', function($rootScope, $timeout, noticebox, http2, srvSite, tkEnrollApp, tkGroupApp) {
    var RelativeProps = ['scope', 'member', 'group', 'enroll', 'sns'];
    /**
     * bNoSave 不对修改进行保存，直接修改传入的原始数据
     */
    function TK(oMatter, oSns, bNoSave, aExcludeRules) {
        var _self, _oRule, _bJumpModifyWatch, $scope;
        $scope = $rootScope.$new(true);
        this.noSave = !!bNoSave;
        this.excludeRules = aExcludeRules || [];
        this.matter = oMatter;
        this.sns = oSns;
        this.rule = _oRule = $scope.rule = (this.noSave ? oMatter.entryRule : angular.copy(oMatter.entryRule));
        _self = this;
        if (!this.noSave) {
            this.originalRule = $scope.originalRule = oMatter.entryRule;
            this.modified = false;
            _bJumpModifyWatch = false;
            $scope.$watch('rule', function(nv, ov) {
                if (nv && nv !== ov) {
                    if (_bJumpModifyWatch === false) {
                        _self.modified = true;
                    }
                    _bJumpModifyWatch = false;
                }
            }, true);
            $scope.$watch('originalRule', function(nv, ov) {
                if (nv && nv !== ov) {
                    http2.merge(_oRule, nv, RelativeProps);
                    _bJumpModifyWatch = true;
                }
            }, true);
        }
        this.chooseMschema = function() {
            srvSite.chooseMschema(oMatter).then(function(oResult) {
                if (!_oRule.member) {
                    _oRule.member = {};
                }
                _oRule.member[oResult.chosen.id] = {
                    entry: 'Y',
                    title: oResult.chosen.title
                };
            });
        };
        this.removeMschema = function(mschemaId) {
            if (!mschemaId) {
                if (Object.keys(_oRule.member).length) {
                    mschemaId = Object.keys(_oRule.member)[0];
                }
            }
            if (mschemaId && _oRule.member[mschemaId]) {
                if (oMatter.dataSchemas && oMatter.dataSchemas.length) {
                    /* 取消题目和通信录的关联 */
                    var aAssocSchemas = [];
                    oMatter.dataSchemas.forEach(function(oSchema) {
                        if (oSchema.schema_id && oSchema.schema_id === mschemaId) {
                            aAssocSchemas.push(oSchema.title);
                        }
                    });
                    if (aAssocSchemas.length) {
                        noticebox.warn('已经有题目<b style="color:red">' + aAssocSchemas.join('，') + '</b>和通讯录关联，请解除关联后再删除进入规则');
                        return false;
                    }
                }
                delete _oRule.member[mschemaId];
            }
            if (_oRule.optional) {
                delete _oRule.optional.member;
            }
            return true;
        };
        this.chooseGroupApp = function() {
            tkGroupApp.choose(oMatter).then(function(oResult) {
                if (oResult.app) {
                    _oRule.group = { id: oResult.app.id, title: oResult.app.title };
                    if (oResult.round) {
                        _oRule.group.round = { id: oResult.round.round_id, title: oResult.round.title };
                    }
                }
            });
        };
        this.removeGroupApp = function() {
            if (_oRule.group.id) {
                /* 取消题目和通信录的关联 */
                if (oMatter.dataSchemas && oMatter.dataSchemas.length) {
                    var aAssocSchemas = [];
                    oMatter.dataSchemas.forEach(function(oSchema) {
                        if (oSchema.fromApp && oSchema.fromApp === _oRule.group.id) {
                            aAssocSchemas.push(oSchema.title);
                        }
                    });
                    if (aAssocSchemas.length) {
                        noticebox.warn('已经有题目<b style="color:red">' + aAssocSchemas.join('，') + '</b>和分组活动关联，请解除关联后再删除进入规则');
                        return false;
                    }
                }
                delete _oRule.group;
                if (_oRule.optional) {
                    delete _oRule.optional.group;
                }
            }
            return true;
        };
        this.chooseEnrollApp = function() {
            tkEnrollApp.choose(oMatter).then(function(oResult) {
                _oRule.enroll = { id: oResult.app.id, title: oResult.app.title };
            });
        };
        this.removeEnrollApp = function() {
            if (_oRule.enroll.id) {
                /* 取消题目和通信录的关联 */
                if (oMatter.dataSchemas && oMatter.dataSchemas.length) {
                    var aAssocSchemas = [];
                    oMatter.dataSchemas.forEach(function(oSchema) {
                        if (oSchema.fromApp && oSchema.fromApp === _oRule.enroll.id) {
                            aAssocSchemas.push(oSchema.title);
                        }
                    });
                    if (aAssocSchemas.length) {
                        noticebox.warn('已经有题目<b style="color:red">' + aAssocSchemas.join('，') + '</b>和记录活动关联，请解除关联后再删除进入规则');
                        return false;
                    }
                }
                delete _oRule.enroll;
                if (_oRule.optional) {
                    delete _oRule.optional.enroll;
                }
            }
            return true;
        };
        this.changeUserScope = function(userScope) {
            switch (userScope) {
                case 'member':
                    if (_oRule.scope.member === 'Y') {
                        if (!_oRule.member || Object.keys(_oRule.member).length === 0) {
                            this.chooseMschema();
                        }
                    } else {
                        if (false === this.removeMschema()) {
                            _oRule.scope.member = 'Y';
                        }
                    }
                    break;
                case 'sns':
                    if (_oRule.scope.sns === 'Y') {
                        if (!_oRule.sns) {
                            _oRule.sns = {};
                        }
                        if (oSns.count === 1) {
                            _oRule.sns[oSns.names[0]] = { 'entry': 'Y' };
                        }
                    } else {
                        delete _oRule.sns;
                    }
                    break;
                case 'group':
                    if (_oRule.scope.group === 'Y') {
                        if (!_oRule.group) {
                            this.chooseGroupApp();
                        }
                    } else {
                        if (false === this.removeGroupApp()) {
                            _oRule.scope.group = 'Y';
                        }
                    }
                    break;
                case 'enroll':
                    if (_oRule.scope.enroll === 'Y') {
                        if (!_oRule.enroll) {
                            this.chooseEnrollApp();
                        }
                    } else {
                        if (false === this.removeEnrollApp()) {
                            _oRule.scope.enroll = 'Y';
                        }
                    }
                    break;
            }
        };
        this.save = function() {
            http2.post('/rest/pl/fe/matter/updateEntryRule?matter=' + oMatter.id + ',' + oMatter.type, _oRule).then(function(rsp) {
                http2.merge(_oRule, rsp.data);
                oMatter.entryRule = _oRule;
                _self.modified = false;
                _bJumpModifyWatch = true;
            });
        };
    }

    return TK;
}]);