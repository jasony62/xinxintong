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
            var oInstance = {
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
                    url = '/rest/pl/fe/site/member/schema/get?site=' + _siteId + '&mschema=' + mschemaId;
                    http2.get(url, function(rsp) {
                        _oMschema = rsp.data;
                        if (!_oMschema.extAttrs) {
                            _oMschema.extAttrs = [];
                        }
                        _getAppDeferred.resolve(_oMschema);
                    });

                    return _getAppDeferred.promise;
                },
                update: function(names) {
                    var defer = $q.defer(),
                        modifiedData = {},
                        url;

                    angular.isString(names) && (names = [names]);
                    names.forEach(function(name) {
                        modifiedData[name] = _oMschema[name];
                    });
                    url = '/rest/pl/fe/matter/plan/update?site=' + _siteId + '&app=' + _mschemaId;
                    http2.post(url, modifiedData, function(rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
            };
            return oInstance;
        }];
    });
    ngMod.provider('srvEnrollPage', function() {
        this.$get = [function() {}];
    });
});