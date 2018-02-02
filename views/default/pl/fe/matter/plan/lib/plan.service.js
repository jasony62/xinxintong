define(['require'], function(require) {
    'use strict';
    var ngMod;
    ngMod = angular.module('service.plan', ['ui.xxt']);
    ngMod.provider('srvPlanApp', function() {
        var _siteId, _appId, _accessId, _getAppDeferred, _oApp;
        this.config = function(site, app, accessId) {
            _siteId = site;
            _appId = app;
            _accessId = accessId;
        };
        this.$get = ['$q', 'http2', function($q, http2) {
            function _fnMakeApiUrl(action) {
                var url;
                url = '/rest/pl/fe/matter/plan/' + action + '?site=' + _siteId + '&id=' + _appId;
                return url;
            }

            function _fnGetApp(url) {
                if (_getAppDeferred) {
                    return _getAppDeferred.promise;
                }
                _getAppDeferred = $q.defer();
                http2.get(url, function(rsp) {
                    _oApp = rsp.data;
                    if (!_oApp.entryRule) {
                        _oApp.entryRule = {};
                    }
                    _getAppDeferred.resolve(_oApp);
                });

                return _getAppDeferred.promise;
            }
            var oInstance = {
                get: function() {
                    return _fnGetApp(_fnMakeApiUrl('get'));
                },
                opGet: function() {
                    var url;
                    url = '/rest/site/op/matter/plan/get?site=' + _siteId + '&id=' + _appId + '&accessToken=' + _accessId;
                    return _fnGetApp(url);
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
    });
    ngMod.provider('srvPlanLog', function() {
        var _siteId, _appId, _plOperations, _siteOperations;;
        this.config = function(site, app) {
            _siteId = site;
            _appId = app;
            _plOperations = [{
                value: 'C',
                title: '创建活动'
            },{
                value: 'U',
                title: '修改活动'
            },{
                value: 'addSchemaTask',
                title: '添加任务'
            },{
                value: 'batchSchemaTask',
                title: '批量添加任务'
            }, {
                value: 'updateSchemaTask',
                title: '修改任务'
            }, {
                value: 'removeSchemaTask',
                title: '删除任务'
            }, {
                value: 'addSchemaAction',
                title: '增加行动项'
            }, {
                value: 'updateSchemaAction',
                title: '修改行动项'
            },{
                value: 'removeSchemaAction',
                title: '删除行动项'
            },{
                value: 'updateTask',
                title: '修改用户任务'
            },{
                value: 'addUser',
                title: '添加用户'
            },{
                value: 'updateUser',
                title: '修改用户备注信息'
            },{
                value: 'verify.batch',
                title: '审核通过指定记录'
            }, {
                value: 'verify.all',
                title: '审核通过全部记录'
            }];
            _siteOperations = [{
                value: 'read',
                title: '阅读'
            },{
                value: 'submit',
                title: '提交'
            }, {
                value: 'updateData',
                title: '修改'
            }];
        };
        this.$get = ['$q', 'http2', '$uibModal', function($q, http2, $uibModal) {
            return {
                list: function(page, type, criteria) {
                    var defer = $q.defer(),
                        url;
                    if (!page || !page._j) {
                        angular.extend(page, {
                            at: 1,
                            size: 30,
                            orderBy: 'time',
                            _j: function() {
                                var p;
                                p = '&page=' + this.at + '&size=' + this.size;
                                p += '&orderby=' + this.orderBy;
                                return p;
                            }
                        });
                    }
                    url = '/rest/pl/fe/matter/plan/log/list?logType=' + type + '&app=' + _appId + page._j();
                    http2.post(url, criteria, function(rsp) {
                        rsp.data.total && (page.total = rsp.data.total);
                        defer.resolve(rsp.data.logs);
                    });
                    return defer.promise;
                },
                filter: function(type) {
                    var defer = $q.defer();
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/plan/component/logFilter.html?_=1',
                        controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                            var oCriteria;
                            $scope2.type = type;
                            $scope2.siteOperations = _siteOperations;
                            $scope2.plOperations = _plOperations;
                            $scope2.pageOfRound = {
                                at: 1,
                                size: 5,
                                j: function() {
                                    return '&page=' + this.at + '&size=' + this.size;
                                }
                            };
                            $scope2.criteria = oCriteria = {
                                byUser: '',
                                byRid: '',
                                byOp: 'ALL'
                            };
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                            $scope2.ok = function() {
                                defer.resolve(oCriteria);
                                $mi.close();
                            };
                        }],
                        backdrop: 'static',
                    });
                    return defer.promise;
                },
            }
        }];
    });
});