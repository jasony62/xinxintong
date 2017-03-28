angular.module('service.matter', ['ngSanitize', 'ui.xxt']).
provider('srvSite', function() {
    var _siteId, _oSite, _aSns, _aMemberSchemas;
    this.config = function(siteId) {
        _siteId = siteId;
    };
    this.$get = ['$q', 'http2', function($q, http2) {
        return {
            getSiteId: function() {
                return _siteId;
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
            matterList: function() {
                var defer = $q.defer();
                http2.get('/rest/pl/fe/site/matterList', function(rsp) {
                    defer.resolve(rsp.data);
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
            snsList: function() {
                var defer = $q.defer();
                if (_aSns) {
                    defer.resolve(_aSns);
                } else {
                    http2.get('/rest/pl/fe/site/snsList?site=' + _siteId, function(rsp) {
                        _aSns = rsp.data;
                        defer.resolve(_aSns);
                    });
                }
                return defer.promise;
            },
            memberSchemaList: function() {
                var defer = $q.defer();
                if (_aMemberSchemas) {
                    defer.resolve(_aMemberSchemas);
                } else {
                    http2.get('/rest/pl/fe/site/member/schema/list?valid=Y&site=' + _siteId, function(rsp) {
                        _aMemberSchemas = rsp.data;
                        _aMemberSchemas.forEach(function(ms) {
                            var schemas = [];
                            if (ms.attr_name[0] === '0') {
                                schemas.push({
                                    id: 'member.name',
                                    title: '姓名',
                                });
                            }
                            if (ms.attr_mobile[0] === '0') {
                                schemas.push({
                                    id: 'member.mobile',
                                    title: '手机',
                                });
                            }
                            if (ms.attr_email[0] === '0') {
                                schemas.push({
                                    id: 'member.email',
                                    title: '邮箱',
                                });
                            }
                            (function() {
                                var i, ea;
                                if (ms.extattr) {
                                    for (i = ms.extattr.length - 1; i >= 0; i--) {
                                        ea = ms.extattr[i];
                                        schemas.push({
                                            id: 'member.extattr.' + ea.id,
                                            title: ea.label,
                                        });
                                    };
                                }
                            })();
                            ms._schemas = schemas;
                        });
                        defer.resolve(_aMemberSchemas);
                    });
                }
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
}).

provider('srvRecordConverter', function() {
    this.$get = ['$sce', function($sce) {
        function _memberAttr(member, schema) {
            var keys;
            if (member) {
                keys = schema.id.split('.');
                if (keys.length === 2) {
                    return member[keys[1]];
                } else if (member.extattr) {
                    return member.extattr[keys[2]];
                } else {
                    return '';
                }
            } else {
                return '';
            }
        }

        function _value2Html(val, schema) {
            var i, j, aVal, aLab = [];
            if (val === undefined || schema === undefined) return '';
            if (schema.ops && schema.ops.length) {
                if (schema.type === 'score') {
                    var label = '';
                    schema.ops.forEach(function(op, index) {
                        if (val[op.v] !== undefined) {
                            label += '<div>' + op.l + ':' + val[op.v] + '</div>';
                        }
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
        }

        function _forTable(record, mapOfSchemas) {
            var schema, data = {};

            if (record.state !== undefined) {
                record._state = _mapOfRecordState[record.state];
            }
            // enroll data
            if (record.data && mapOfSchemas) {
                for (var schemaId in mapOfSchemas) {
                    schema = mapOfSchemas[schemaId];
                    switch (schema.type) {
                        case 'image':
                            var imgs = record.data[schema.id] ? record.data[schema.id].split(',') : [];
                            data[schema.id] = imgs;
                            break;
                        case 'file':
                            var files = record.data[schema.id] ? record.data[schema.id] : {};
                            data[schema.id] = files;
                            break;
                        case 'member':
                            data[schema.id] = _memberAttr(record.data.member, schema);
                            break;
                        default:
                            data[schema.id] = $sce.trustAsHtml(_value2Html(record.data[schema.id], schema));
                    }
                };
                record._data = data;
            }
            return record;
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
                _forTable(record, mapOfSchemas ? mapOfSchemas : _mapOfSchemas);
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
        $scope.channels = rsp.data;
    });
}]);
