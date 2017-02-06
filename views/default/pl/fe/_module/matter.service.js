angular.module('service.matter', ['ui.bootstrap', 'ui.xxt']).
provider('srvSite', function() {
    var _siteId, _oSite, _aSns, _aMemberSchemas;
    this.config = function(siteId) {
        _siteId = siteId;
    };
    this.$get = ['$q', 'http2', function($q, http2) {
        return {
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
            add: function(taskUrl) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/q/create?site=' + siteId;
                http2.post(url, {
                    url: encodeURI(taskUrl)
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
            }
        };
    }];
}).
provider('srvRecordConverter', function() {
    this.$get = ['$sce', function($sce) {
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
        }

        function _value2Html(val, schema) {
            var i, j, aVal, aLab = [];
            if (val === undefined) return '';
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
            if (record.data) {
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
                            data[schema.id] = _memberAttr(record.data[schema.id], schema);
                            break;
                        default:
                            data[schema.id] = _value2Html(record.data[schema.id], schema);
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
        };
        return {
            forTable: function(record, mapOfSchemas) {
                _forTable(record, mapOfSchemas);
            },
            forEdit: function(schema, data) {
                if (schema.type === 'file') {
                    var files;
                    if (data[schema.id] && data[schema.id].length) {
                        files = data[schema.id];
                        files.forEach(function(file) {
                            file.url = $sce.trustAsResourceUrl(file.url);
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
});
