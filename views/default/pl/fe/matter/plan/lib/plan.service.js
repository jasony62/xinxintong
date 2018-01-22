define(['require'], function(require) {
    'use strict';
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
                        _oApp = rsp.data;
                        if (!_oApp.entryRule) {
                            _oApp.entryRule = {};
                        }
                        _getAppDeferred.resolve(_oApp);
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
                    var oEntryRule = _oApp.entryRule;
                    switch (ruleScope) {
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
                    return this.update('entryRule');
                },
            };
            return oInstance;
        }];
    });
    ngMod.provider('srvEnrollPage', function() {
        this.$get = [function() {}];
    });
    ngMod.provider('srvPlanRecord', function() {
        var _siteId, _appId;
        this.config = function(site, app) {
            _siteId = site;
            _appId = app;
        };
        this.$get = ['$q', 'http2', function($q, http2) {
            return {
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
                }
            }
        }];
    })
});