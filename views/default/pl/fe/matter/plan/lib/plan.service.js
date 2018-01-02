define(['require'], function(require) {
    var ngMod;
    ngMod = angular.module('service.plan', ['ui.xxt']);
    ngMod.provider('srvPlanApp', function() {
        var _siteId, _appId, _getAppDeferred, _oApp;
        this.config = function(site, app) {
            _siteId = site;
            _appId = app;
        };
        this.$get = ['$q', 'http2', function($q, http2) {
            var oInstance = {
                get: function() {
                    var url;
                    if (_getAppDeferred) {
                        return _getAppDeferred.promise;
                    }
                    _getAppDeferred = $q.defer();
                    url = '/rest/pl/fe/matter/plan/get?site=' + _siteId + '&id=' + _appId;
                    http2.get(url, function(rsp) {
                        _oApp = app = rsp.data;
                        _getAppDeferred.resolve(app);
                    });

                    return _getAppDeferred.promise;
                },
                update: function(names) {
                    var defer = $q.defer(),
                        modifiedData = {},
                        url;

                    angular.isString(names) && (names = [names]);
                    names.forEach(function(name) {
                        modifiedData[name] = _oApp[name];
                    });
                    url = '/rest/pl/fe/matter/plan/update?site=' + _siteId + '&app=' + _appId;
                    http2.post(url, modifiedData, function(rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                changeUserScope: function(ruleScope, oSiteSns) {
                    var oEntryRule = _oApp.entry_rule;
                    oEntryRule.scope = ruleScope;
                    switch (oEntryRule.scope) {
                        case 'member':
                            oEntryRule.member === undefined && (oEntryRule.member = {});
                            break;
                        case 'sns':
                            oEntryRule.sns === undefined && (oEntryRule.sns = {});
                            Object.keys(oSiteSns).forEach(function(snsName) {
                                if (oEntryRule.sns[snsName] === undefined) {
                                    oEntryRule.sns[snsName] = { entry: 'Y' };
                                }
                            });
                            break;
                        default:
                    }
                    return this.update('entry_rule');
                },
            };
            return oInstance;
        }];
    });
    ngMod.provider('srvEnrollPage', function() {
        this.$get = [function() {}];
    });
});