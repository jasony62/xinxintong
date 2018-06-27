define(['require'], function(require) {
    'use strict';
    var ngMod;
    ngMod = angular.module('service.mschema', ['ui.xxt']);
    ngMod.provider('srvMschema', function() {
        var _siteId, _mschemaId, _getAppDeferred, _oMschema;
        this.config = function(site) {
            _siteId = site;
        };
        this.$get = ['$q', 'http2', function($q, http2) {
            var _baseUrl, _oInstance;
            _baseUrl = '/rest/pl/fe/site/member/schema/';
            _oInstance = {
                get: function(mschemaId) {
                    var url;
                    if ((!mschemaId || mschemaId === _mschemaId) && _getAppDeferred) {
                        return _getAppDeferred.promise;
                    }
                    if (!mschemaId) {
                        alert('参数错误[srvMschema.get]');
                        return;
                    }
                    _mschemaId = mschemaId;
                    _getAppDeferred = $q.defer();
                    url = _baseUrl + 'get?site=' + _siteId + '&mschema=' + mschemaId;
                    http2.get(url, function(rsp) {
                        _oMschema = rsp.data;
                        if (!_oMschema.extAttrs) {
                            _oMschema.extAttrs = [];
                        }
                        _getAppDeferred.resolve(_oMschema);
                    });

                    return _getAppDeferred.promise;
                },
                list: function(own) {
                    var deferred, url;
                    deferred = $q.defer();
                    own === undefined && (own === 'N');
                    url = _baseUrl;
                    url += 'list?site=' + _siteId;
                    url += '&own=' + own;
                    http2.get(url, function(rsp) {
                        deferred.resolve(rsp.data);
                    });
                    return deferred.promise;
                },
                update: function(oSchema, updated) {
                    var deferred, url;
                    deferred = $q.defer();
                    url = _baseUrl;
                    url += 'update?site=' + _siteId;
                    url += '&type=' + oSchema.type;
                    if (oSchema.id) url += '&id=' + oSchema.id;
                    http2.post(url, updated, function(rsp) {
                        deferred.resolve(rsp.data);
                    });
                    return deferred.promise;
                },
            };
            return _oInstance;
        }];
    });
    ngMod.provider('srvMschemaNotice', function() {
        this.$get = ['$q', 'http2', 'srvGroupApp', function($q, http2, srvGroupApp) {
            return {
                detail: function(batch) {
                    var defer = $q.defer(),
                        url;
                    srvGroupApp.get().then(function(oApp) {
                        url = '/rest/pl/fe/matter/group/notice/logList?batch=' + batch.id + '&app=' + oApp.id;
                        http2.get(url, function(rsp) {
                            defer.resolve(rsp.data);
                        });
                    });

                    return defer.promise;
                }
            }
        }]
    });
    ngMod.provider('srvEnrollPage', function() {
        this.$get = [function() {}];
    });
});