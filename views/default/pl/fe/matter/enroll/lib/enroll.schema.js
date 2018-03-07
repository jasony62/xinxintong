define(['schema', 'wrap'], function(schemaLib, wrapLib) {
    'use strict';
    var ngMod = angular.module('schema.enroll', []);
    ngMod.provider('srvEnrollSchema', function() {
        var _siteId;
        this.config = function(siteId) {
            _siteId = siteId;
        };
        this.$get = ['$uibModal', '$q', 'srv' + window.MATTER_TYPE + 'App', 'srvEnrollPage', function($uibModal, $q, srvApp, srvAppPage) {
            var _self = {
                makePagelet: function(content) {
                    var deferred = $q.defer();
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/pagelet.html',
                        controller: ['$scope', '$uibModalInstance', 'mediagallery', function($scope2, $mi, mediagallery) {
                            var tinymceEditor;
                            $scope2.reset = function() {
                                tinymceEditor.setContent('');
                            };
                            $scope2.ok = function() {
                                var html = tinymceEditor.getContent();
                                tinymceEditor.remove();
                                $mi.close({
                                    html: html
                                });
                            };
                            $scope2.cancel = function() {
                                tinymceEditor.remove();
                                $mi.dismiss();
                            };
                            $scope2.$on('tinymce.multipleimage.open', function(event, callback) {
                                var options = {
                                    callback: callback,
                                    multiple: true,
                                    setshowname: true
                                };
                                mediagallery.open(_siteId, options);
                            });
                            $scope2.$on('tinymce.instance.init', function(event, editor) {
                                var page;
                                tinymceEditor = editor;
                                editor.setContent(content);
                            });
                        }],
                        size: 'lg',
                        backdrop: 'static'
                    }).result.then(function(result) {
                        deferred.resolve(result);
                    });
                    return deferred.promise;
                },
                update: function(oUpdatedSchema, oBeforeState, prop) {
                    if (oUpdatedSchema.format === 'number') {
                        if (oUpdatedSchema.weight === undefined) {
                            oUpdatedSchema.weight = 1;
                        } else {
                            if (false === /^\d+\.?\d*$/.test(oUpdatedSchema.weight)) {
                                oUpdatedSchema.weight = 1;
                            } else if (/\.$/.test(oUpdatedSchema.weight)) {
                                // 这样会导致无法输入“点”
                                //oSchema.weight = oSchema.weight.slice(0, -1);
                            }
                        }
                    }
                    if (prop && prop === 'shareable') {
                        if (oUpdatedSchema.shareable === 'Y') {
                            oUpdatedSchema.remarkable = 'Y';
                        }
                    }
                    srvApp.get().then(function(oApp) {
                        oApp.pages.forEach(function(oPage) {
                            oPage.updateSchema(oUpdatedSchema, oBeforeState);
                        });
                    });
                },
                submitChange: function(changedPages) {
                    var deferred = $q.defer();
                    srvApp.get().then(function(oApp) {
                        var updatedAppProps = ['data_schemas'],
                            oSchema, oNicknameSchema, oAppNicknameSchema;
                        for (var i = oApp.dataSchemas.length - 1; i >= 0; i--) {
                            oSchema = oApp.dataSchemas[i];
                            if (oSchema.required === 'Y') {
                                if (oSchema.type === 'shorttext' || oSchema.type === 'member') {
                                    if (oSchema.title === '姓名') {
                                        oNicknameSchema = oSchema;
                                        break;
                                    }
                                    if (oSchema.title.indexOf('姓名') !== -1) {
                                        if (!oNicknameSchema || oSchema.title.length < oNicknameSchema.title.length) {
                                            oNicknameSchema = oSchema;
                                        }
                                    } else if (oSchema.format && oSchema.format === 'name') {
                                        oNicknameSchema = oSchema;
                                    }
                                }
                            }
                        }
                        if (oNicknameSchema) {
                            if (oAppNicknameSchema = oApp.assignedNickname) {
                                if (oAppNicknameSchema.schema) {
                                    if (oAppNicknameSchema.schema.id !== '') {
                                        oAppNicknameSchema.schema.id = oNicknameSchema.id;
                                        updatedAppProps.push('assignedNickname');
                                    }
                                } else {
                                    oAppNicknameSchema.valid = 'Y';
                                    oAppNicknameSchema.schema = { id: oNicknameSchema.id };
                                    updatedAppProps.push('assignedNickname');
                                }
                            }
                        } else {
                            if (oApp.assignedNickname.schema) {
                                delete oApp.assignedNickname.schema;
                                updatedAppProps.push('assignedNickname');
                            }
                        }
                        srvApp.update(updatedAppProps).then(function() {
                            if (!changedPages || changedPages.length === 0) {
                                deferred.resolve();
                            } else {
                                var fnOnePage;
                                fnOnePage = function(index) {
                                    srvAppPage.update(changedPages[index], ['data_schemas', 'html']).then(function() {
                                        index++;
                                        if (index === changedPages.length) {
                                            deferred.resolve();
                                        } else {
                                            fnOnePage(index);
                                        }
                                    });
                                };
                                fnOnePage(0);
                            }
                        });
                    });
                    return deferred.promise;
                }
            };
            return _self;
        }];
    });
    /**
     * 所有题目
     */
    ngMod.controller('ctrlSchemaList', ['$scope', '$timeout', '$sce', '$uibModal', 'http2', 'cstApp', 'srv' + window.MATTER_TYPE + 'App', 'srvEnrollPage', 'srvEnrollSchema',
        function($scope, $timeout, $sce, $uibModal, http2, cstApp, srvApp, srvAppPage, srvEnrollSchema) {
            $scope.activeSchema = null;
            $scope.cstApp = cstApp;

            $scope.assignGroupApp = function() {
                srvApp.assignGroupApp().then(function(oGroupApp) {
                    var oRoundDS, ops, oAppSchema, oAppRoundSchema, oAssignedNickname, oGrpNicknameSchema, oAppNicknameSchema;
                    /* 添加分组轮次 */
                    oRoundDS = {
                        id: '_round_id',
                        type: 'single',
                        title: '分组名称',
                    };
                    ops = [];
                    oGroupApp.rounds.forEach(function(round) {
                        ops.push({
                            v: round.round_id,
                            l: round.title
                        });
                    });
                    oRoundDS.ops = ops;
                    oRoundDS.assocState = 'yes';
                    oGroupApp.dataSchemas.splice(0, 0, oRoundDS);
                    /* 匹配分组轮次字段 */
                    for (var i = 0; i < $scope.app.dataSchemas.length; i++) {
                        oAppSchema = $scope.app.dataSchemas[i];
                        if (oAppSchema.id === '_round_id') {
                            oAppRoundSchema = oAppSchema;
                            break;
                        }
                    }
                    if (oAppRoundSchema) {
                        var oBefore;
                        oBefore = angular.copy(oAppRoundSchema);
                        oAppRoundSchema.fromApp = oGroupApp.id;
                        oAppRoundSchema.requireCheck = 'Y';
                        $scope.updSchema(oAppRoundSchema, oBefore);
                    } else {
                        oAppRoundSchema = $scope.newByOtherApp(oRoundDS, oGroupApp, false);
                    }
                    /* 匹配昵称字段 */
                    if (oAssignedNickname = oGroupApp.assignedNickname) {
                        if (oAssignedNickname.valid && oAssignedNickname.valid === 'Y' && oAssignedNickname.schema) {
                            for (var i = 1; i < oGroupApp.dataSchemas.length; i++) {
                                if (oGroupApp.dataSchemas[i].id === oAssignedNickname.schema.id) {
                                    oGrpNicknameSchema = oGroupApp.dataSchemas[i];
                                    break;
                                }
                            }
                        }
                    }
                    if (oGrpNicknameSchema) {
                        for (var i = 0; i < $scope.app.dataSchemas.length; i++) {
                            oAppSchema = $scope.app.dataSchemas[i];
                            if (oAppSchema.title === oGrpNicknameSchema.title) {
                                if (/shorttext|member/.test(oAppSchema.type) && oAppSchema.required === 'Y') {
                                    oAppNicknameSchema = oAppSchema;
                                    break;
                                }
                            }
                        }
                    }
                    if (oAppNicknameSchema) {
                        var oBefore;
                        oBefore = angular.copy(oAppNicknameSchema);
                        if (oAppNicknameSchema.type === 'member') {
                            delete oAppNicknameSchema.schema_id;
                            oAppNicknameSchema.type = 'shorttext';
                        }
                        oAppNicknameSchema.fromApp = oGroupApp.id;
                        oAppNicknameSchema.requireCheck = 'Y';
                        oAppNicknameSchema.format = 'name';
                        oGrpNicknameSchema.assocState = 'yes';
                        $scope.updSchema(oAppNicknameSchema, oBefore);
                    } else if (oGrpNicknameSchema) {
                        $scope.newByOtherApp(oGrpNicknameSchema, oGroupApp, oAppRoundSchema);
                    }
                });
            };
            $scope.cancelGroupApp = function() {
                srvApp.get().then(function(oApp) {
                    oApp.group_app_id = '';
                    delete oApp.groupApp;
                    oApp.dataSchemas.forEach(function(oSchema) {
                        delete oSchema.fromApp;
                        delete oSchema.requireCheck;
                    });
                    srvApp.update(['group_app_id', 'data_schemas']);
                });
            };
            $scope.assignEnrollApp = function() {
                srvApp.assignEnrollApp().then(function(oEnlApp) {
                    var oAppSchema, oEnlSchema, oBefore;
                    /* 自动关联字段 */
                    for (var i = 0; i < $scope.app.dataSchemas.length; i++) {
                        oAppSchema = $scope.app.dataSchemas[i];
                        for (var j = 0; j < oEnlApp.dataSchemas.length; j++) {
                            oEnlSchema = oEnlApp.dataSchemas[j];
                            if (oAppSchema.id === oEnlSchema.id && oAppSchema.type === oEnlSchema.type && oAppSchema.title === oEnlSchema.title) {
                                oBefore = angular.copy(oAppSchema);
                                oAppSchema.fromApp = oEnlApp.id;
                                oAppSchema.requireCheck = 'Y';
                                $scope.updSchema(oAppSchema, oBefore);
                                oEnlSchema.assocState = 'yes';
                                break;
                            }
                        }
                    }
                });
            };
            $scope.cancelEnrollApp = function() {
                srvApp.get().then(function(oApp) {
                    oApp.enroll_app_id = '';
                    delete oApp.enrollApp;
                    oApp.dataSchemas.forEach(function(oSchema) {
                        delete oSchema.fromApp;
                        delete oSchema.requireCheck;
                    });
                    srvApp.update(['enroll_app_id', 'data_schemas']);
                });
            };
            $scope.assocApp = function(appId) {
                var oApp, assocApp;
                oApp = $scope.app;
                if (oApp.enrollApp && oApp.enrollApp.id === appId) {
                    return oApp.enrollApp;
                } else if (oApp.groupApp && oApp.groupApp.id === appId) {
                    return oApp.groupApp;
                } else {
                    return false;
                }
            };
            $scope.assocAppSchema = function(oSchema) {
                var oAssocApp, oAssocSchema;
                if (oAssocApp = $scope.assocApp(oSchema.fromApp)) {
                    if (undefined === oAssocApp._schemasById) {
                        oAssocApp._schemasById = {};
                        oAssocApp.dataSchemas.forEach(function(oAssocSchema) {
                            oAssocApp._schemasById[oAssocSchema.id] = oAssocSchema;
                        });
                    }
                    oAssocSchema = oAssocApp._schemasById[oSchema.id];
                }
                return oAssocSchema;
            };
            $scope.updConfig = function(oActiveSchema) {
                srvApp.get().then(function(oApp) {
                    var pages, oPage;
                    pages = oApp.pages;
                    for (var i = pages.length - 1; i >= 0; i--) {
                        oPage = pages[i];
                        if (oPage.type === 'I') {
                            oPage.updateSchema(oActiveSchema);
                            srvAppPage.update(oPage, ['data_schemas', 'html']);
                        }
                    }
                });
            };
            $scope.newSchema = function(type) {
                var newSchema, mission, oProto;

                newSchema = schemaLib.newSchema(type, $scope.app, oProto);
                $scope._appendSchema(newSchema);

                return newSchema;
            };
            $scope.newMember = function(ms, oMsSchema) {
                var oNewSchema = schemaLib.newSchema(oMsSchema.type, $scope.app);

                oNewSchema.schema_id = ms.id;
                oNewSchema.id = oMsSchema.id;
                oNewSchema.title = oMsSchema.title;
                oNewSchema.format = oMsSchema.format;
                if (oMsSchema.ops) {
                    oNewSchema.ops = oMsSchema.ops;
                }
                $scope._appendSchema(oNewSchema);
                oMsSchema.assocState = 'yes';

                return newSchema;
            };
            $scope.newByOtherApp = function(oProtoSchema, oOtherApp, oAfterSchema) {
                var oNewSchema, schemaType;

                schemaType = oProtoSchema.type === 'member' ? 'shorttext' : oProtoSchema.type;
                oNewSchema = schemaLib.newSchema(schemaType, $scope.app, oProtoSchema);
                oNewSchema.id = oProtoSchema.id;
                oNewSchema.requireCheck = 'Y';
                oNewSchema.fromApp = oOtherApp.id;
                if (oProtoSchema.ops) {
                    oNewSchema.ops = oProtoSchema.ops;
                }
                oProtoSchema.assocState = 'yes';
                $scope._appendSchema(oNewSchema, oAfterSchema);

                return oNewSchema;
            };
            $scope.unassocWithOtherApp = function(oSchema, bOnlyAssocState) {
                var oAssocApp, oAssocSchema;
                if (oSchema.fromApp) {
                    oAssocApp = $scope.assocApp(oSchema.fromApp);
                    for (var i = oAssocApp.dataSchemas.length - 1; i >= 0; i--) {
                        if (oAssocApp.dataSchemas[i].id === oSchema.id) {
                            oAssocSchema = oAssocApp.dataSchemas[i];
                            oAssocSchema.assocState = 'no';
                            break;
                        }
                    }
                    if (!bOnlyAssocState) {
                        delete oSchema.fromApp;
                        delete oSchema.requireCheck;
                        $scope.updSchema(oSchema);
                    }
                }
                return oAssocSchema;
            };
            $scope.assocWithOtherApp = function(oOtherSchema, oAssocApp) {
                var oAppSchema;
                oAppSchema = $scope.app._schemasById[oOtherSchema.id];
                if (oAppSchema) {
                    if (oAppSchema.type !== oOtherSchema.type) {
                        if (!/shorttext|member/.test(oAppSchema.type) || !/shorttext|member/.test(oOtherSchema.type)) {
                            alert('题目【' + oOtherSchema.title + '】和【' + oAppSchema.title + '】的类型不一致，无法关联');
                            return;
                        }
                    }
                    if (oAppSchema.title !== oOtherSchema.title) {
                        alert('题目【' + oOtherSchema.title + '】和【' + oAppSchema.title + '】的名称不一致，无法关联');
                        return;
                    }
                    oAppSchema.fromApp = oAssocApp.id;
                    oAppSchema.requireCheck = 'Y';
                    oOtherSchema.assocState = 'yes';
                    $scope.updSchema(oAppSchema);
                }
            };
            $scope.$watch('app', function(oApp) {
                if (oApp) {
                    $scope.$watch('mschemasById', function(oMschemasById) {
                        var oMschema;
                        if (oMschemasById) {
                            for (var msid in oMschemasById) {
                                oMschema = oMschemasById[msid];
                                if (oMschema._schemas) {
                                    oMschema._schemas.forEach(function(oMsSchema) {
                                        if (oApp._schemasById[oMsSchema.id] === undefined) {
                                            oMsSchema.assocState = '';
                                        } else if (oApp._schemasById[oMsSchema.id].type === 'member') {
                                            oMsSchema.assocState = 'yes';
                                        } else {
                                            oMsSchema.assocState = 'no';
                                        }
                                    });
                                }
                            }
                        }
                    });
                }
            });
            $scope.unassocWithMschema = function(oSchema, bOnlyAssocState) {
                var oAssocMschema, oMsSchema, oBefore;
                if (oSchema.schema_id) {
                    oBefore = angular.copy(oSchema);
                    oAssocMschema = $scope.mschemasById[oSchema.schema_id];
                    if (oMsSchema = oAssocMschema._schemasById[oSchema.id]) {
                        oMsSchema.assocState = 'no';
                    }
                    if (!bOnlyAssocState) {
                        delete oSchema.schema_id;
                        $scope.updSchema(oSchema, oBefore);
                    }
                }
                return oMsSchema;
            };
            $scope.assocWithMschema = function(oMsSchema, oMschema) {
                var oAppSchema, oBefore;
                oAppSchema = $scope.app._schemasById[oMsSchema.id];
                if (oAppSchema) {
                    if (oAppSchema.title !== oMsSchema.title) {
                        alert('题目【' + oMsSchema.title + '】和【' + oAppSchema.title + '】的名称不一致，无法关联');
                        return;
                    }
                    if (oAppSchema.id !== oMsSchema.id) {
                        alert('题目【' + oMsSchema.title + '】和【' + oAppSchema.title + '】的ID不一致，无法关联');
                        return;
                    }
                    oBefore = angular.copy(oAppSchema);
                    oAppSchema.schema_id = oMschema.id;
                    oMsSchema.assocState = 'yes';
                    $scope.updSchema(oAppSchema, oBefore);
                }
            };
            $scope.copySchema = function(schema) {
                var newSchema = angular.copy(schema);

                newSchema.id = 's' + (new Date * 1);
                newSchema.title += '-2';
                delete newSchema.fromApp;
                delete newSchema.requireCheck;
                $scope._appendSchema(newSchema, schema);

                return newSchema;
            };
            $scope.importByOther = function() {
                var _oApp;
                _oApp = $scope.app;
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/importSchemaByOther.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                        var oPage, oResult, oFilter;
                        $scope2.page = oPage = {
                            at: 1,
                            size: 12,
                            j: function() {
                                return 'page=' + this.at + '&size=' + this.size;
                            }
                        };
                        $scope2.result = oResult = {};
                        $scope2.filter = oFilter = {};
                        $scope2.selectApp = function() {
                            if (angular.isString(oResult.fromApp.data_schemas) && oResult.fromApp.data_schemas) {
                                oResult.fromApp.dataSchemas = JSON.parse(oResult.fromApp.data_schemas);
                            }
                            oResult.schemas = [];
                        };
                        $scope2.selectSchema = function(schema) {
                            if (schema._selected) {
                                oResult.schemas.push(schema);
                            } else {
                                oResult.schemas.splice(oResult.schemas.indexOf(schema), 1);
                            }
                        };
                        $scope2.ok = function() {
                            $mi.close(oResult.schemas);
                        };
                        $scope2.cancel = function() {
                            $mi.dismiss();
                        };
                        $scope2.doFilter = function() {
                            oPage.at = 1;
                            $scope2.doSearch();
                        };
                        $scope2.doSearch = function() {
                            var url = '/rest/pl/fe/matter/enroll/list?site=' + _oApp.siteid + '&' + oPage.j();
                            http2.post(url, {
                                byTitle: oFilter.byTitle
                            }, function(rsp) {
                                $scope2.apps = rsp.data.apps;
                                if ($scope2.apps.length) {
                                    oResult.fromApp = $scope2.apps[0];
                                    $scope2.selectApp();
                                }
                                oPage.total = rsp.data.total;
                            });
                        };
                        $scope2.doSearch();
                    }],
                    backdrop: 'static',
                }).result.then(function(schemas) {
                    schemas.forEach(function(schema) {
                        var newSchema;
                        newSchema = schemaLib.newSchema(schema.type, _oApp);
                        newSchema.type === 'member' && (newSchema.schema_id = schema.schema_id);
                        newSchema.title = schema.title;
                        if (schema.ops) {
                            newSchema.ops = schema.ops;
                        }
                        if (schema.range) {
                            newSchema.range = schema.range;
                        }
                        if (schema.count) {
                            newSchema.count = schema.count;
                        }
                        $scope._appendSchema(newSchema);
                    });
                });
            };
            $scope.makePagelet = function(schema, prop) {
                prop = prop || 'content';
                srvEnrollSchema.makePagelet(schema[prop] || '').then(function(result) {
                    if (prop === 'content') {
                        schema.title = $(result.html).text();
                    }
                    schema[prop] = result.html;
                    $scope.updSchema(schema);
                });
            };
            $scope.setOptGroup = function(oSchema) {
                if (!oSchema || !/single|multiple/.test(oSchema.type)) {
                    return false;
                }
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/setOptGroup.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                        function genId() {
                            var newKey = 1;
                            _groups.forEach(function(oGroup) {
                                var gKey;
                                gKey = parseInt(oGroup.i.split('_')[1]);
                                if (gKey >= newKey) {
                                    newKey = gKey + 1;
                                }
                            });
                            return 'i_' + newKey;
                        }
                        var _oSchema, _groups, _options, singleSchemas;
                        _oSchema = angular.copy(oSchema);
                        if (_oSchema.optGroups === undefined) {
                            _oSchema.optGroups = [];
                        }
                        if (_oSchema.ops === undefined) {
                            _oSchema.ops = [];
                        }
                        singleSchemas = []; //所有单项选择题
                        $scope.app.dataSchemas.forEach(function(oAppSchema) {
                            if (oAppSchema.type === 'single' && oAppSchema.id !== oSchema.id) {
                                singleSchemas.push(oAppSchema);
                            }
                        });
                        $scope2.singleSchemas = singleSchemas;
                        $scope2.groups = _groups = _oSchema.optGroups;
                        $scope2.options = _options = _oSchema.ops;
                        $scope2.addGroup = function() {
                            var oNewGroup;
                            oNewGroup = {
                                i: genId(),
                                l: '分组-' + (_groups.length + 1)
                            };
                            _groups.push(oNewGroup);
                            $scope2.toggleGroup(oNewGroup);
                        };
                        $scope2.toggleGroup = function(oGroup) {
                            var oAppSchema;
                            $scope2.activeGroup = oGroup;
                            $scope2.activeOps = [];
                            if (oGroup.assocOp && oGroup.assocOp.schemaId) {
                                for (var i = 0, ii = singleSchemas.length; i < ii; i++) {
                                    oAppSchema = singleSchemas[i];
                                    if (oAppSchema.id === oGroup.assocOp.schemaId) {
                                        oGroup.assocOp.schema = oAppSchema;
                                        break;
                                    }
                                }
                            }
                        };
                        $scope2.ok = function() {
                            _groups.forEach(function(oGroup) {
                                if (oGroup.assocOp && oGroup.assocOp.schema) {
                                    oGroup.assocOp.schemaId = oGroup.assocOp.schema.id;
                                    delete oGroup.assocOp.schema;
                                }
                            });
                            $mi.close({ groups: _groups, options: _options });
                        };
                        $scope2.cancel = function() {
                            $mi.dismiss();
                        };
                    }],
                    backdrop: 'static',
                }).result.then(function(groupAndOps) {
                    oSchema.ops = groupAndOps.options;
                    oSchema.optGroups = groupAndOps.groups;
                    $scope.updSchema(oSchema);
                });
            };
            $scope.setVisibility = function(oSchema) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/setVisibility.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                        var _optSchemas, _rules, _oBeforeRules;
                        _optSchemas = []; //所有选择题
                        _rules = []; // 当前题目的可见规则
                        if (oSchema.visibility && oSchema.visibility.rules && oSchema.visibility.rules.length) {
                            _oBeforeRules = {};
                            oSchema.visibility.rules.forEach(function(oRule) {
                                if (_oBeforeRules[oRule.schema]) {
                                    _oBeforeRules[oRule.schema].push(oRule.op);
                                } else {
                                    _oBeforeRules[oRule.schema] = [oRule.op];
                                }
                            });
                        }
                        $scope.app.dataSchemas.forEach(function(oAppSchema) {
                            if (/single|multiple/.test(oAppSchema.type) && oAppSchema.id !== oSchema.id) {
                                _optSchemas.push(oAppSchema);
                                if (_oBeforeRules && _oBeforeRules[oAppSchema.id]) {
                                    var oBeforeRule;
                                    for (var i = 0, ii = oAppSchema.ops.length; i < ii; i++) {
                                        if (_oBeforeRules[oAppSchema.id].indexOf(oAppSchema.ops[i].v) !== -1) {
                                            oBeforeRule = { schema: oAppSchema };
                                            oBeforeRule.op = oAppSchema.ops[i];
                                            _rules.push(oBeforeRule);
                                        }
                                    }
                                }
                            }
                        });
                        $scope2.optSchemas = _optSchemas;
                        $scope2.rules = _rules;
                        $scope2.addRule = function() {
                            _rules.push({});
                        };
                        $scope2.removeRule = function(oRule) {
                            _rules.splice(_rules.indexOf(oRule), 1);
                        };
                        $scope2.ok = function() {
                            var oConfig = { rules: [] };
                            _rules.forEach(function(oRule) {
                                oConfig.rules.push({ schema: oRule.schema.id, op: oRule.op.v });
                            });
                            oSchema.visibility = oConfig;
                            $scope.updSchema(oSchema);
                            $mi.close(oSchema);
                        };
                        $scope2.cancel = function() {
                            $mi.dismiss();
                        };
                    }],
                    backdrop: 'static',
                });
            };
            /**
             * oAfterSchema: false - first, undefined - after active schema
             */
            $scope._appendSchema = function(newSchema, oAfterSchema) {
                var oApp, afterIndex, changedPages = [];
                oApp = $scope.app;
                if (oApp._schemasById[newSchema.id]) {
                    alert(cstApp.alertMsg['schema.duplicated']);
                    return;
                }
                if (undefined === oAfterSchema) {
                    oAfterSchema = $scope.activeSchema;
                }
                if (oAfterSchema) {
                    afterIndex = oApp.dataSchemas.indexOf(oAfterSchema);
                    oApp.dataSchemas.splice(afterIndex + 1, 0, newSchema);
                } else if (oAfterSchema === false) {
                    oApp.dataSchemas.splice(0, 0, newSchema);
                } else {
                    oApp.dataSchemas.push(newSchema);
                }
                oApp._schemasById[newSchema.id] = newSchema;
                oApp.pages.forEach(function(oPage) {
                    if (oPage.appendSchema(newSchema, oAfterSchema)) {
                        changedPages.push(oPage);
                    }
                });
                return srvEnrollSchema.submitChange(changedPages);
            };
            $scope._changeSchemaOrder = function(moved) {
                var oApp, i, prevSchema, changedPages;

                oApp = $scope.app;
                i = oApp.dataSchemas.indexOf(moved);
                if (i > 0) prevSchema = oApp.dataSchemas[i - 1];
                changedPages = []
                oApp.pages.forEach(function(oPage) {
                    oPage.moveSchema(moved, prevSchema);
                    changedPages.push(oPage);
                });
                srvEnrollSchema.submitChange(changedPages);
            };
            $scope._removeSchema = function(oRemovedSchema) {
                var oApp = $scope.app,
                    changedPages = [];

                /* 更新定义 */
                oApp.dataSchemas.splice(oApp.dataSchemas.indexOf(oRemovedSchema), 1);
                delete oApp._schemasById[oRemovedSchema.id];
                $scope.app.pages.forEach(function(oPage) {
                    if (oPage.removeSchema(oRemovedSchema)) {
                        changedPages.push(oPage);
                    }
                });
                srvEnrollSchema.submitChange(changedPages).then(function() {
                    var aNewRecycleSchemas, oAssocSchema;
                    /* 放入回收站 */
                    aNewRecycleSchemas = [];
                    for (var i = oApp.recycleSchemas.length - 1; i >= 0; i--) {
                        if (oApp.recycleSchemas[i].id !== oRemovedSchema.id) {
                            aNewRecycleSchemas.push(oApp.recycleSchemas[i]);
                        }
                    }
                    aNewRecycleSchemas.push(oRemovedSchema);
                    oApp.recycleSchemas = aNewRecycleSchemas;
                    srvApp.update('recycleSchemas');
                    /* 去除关联状态 */
                    if (oRemovedSchema.type === 'member') {
                        if (oAssocSchema = $scope.unassocWithMschema(oRemovedSchema, true)) {
                            oAssocSchema.assocState = '';
                        }
                    }
                    if (oRemovedSchema.fromApp) {
                        if (oAssocSchema = $scope.unassocWithOtherApp(oRemovedSchema, true)) {
                            oAssocSchema.assocState = '';
                        }
                    }
                });
            };
            var timerOfUpdate = null;
            $scope.updSchema = function(oSchema, oBeforeState, prop) {
                srvEnrollSchema.update(oSchema, oBeforeState, prop);
                if (timerOfUpdate !== null) {
                    $timeout.cancel(timerOfUpdate);
                }
                timerOfUpdate = $timeout(function() {
                    srvEnrollSchema.submitChange($scope.app.pages).then(function() {
                        if (prop && prop === 'weight') {
                            srvApp.opData().then(function(oData) {
                                if (oData.total > 0 || (oData.length && oData[0].total > 0)) {
                                    $uibModal.open({
                                        templateUrl: '/views/default/pl/fe/matter/enroll/component/renewScore.html',
                                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                                            $scope2.ok = function() {
                                                srvApp.renewScore().then(function() {
                                                    $mi.close();
                                                });
                                            };
                                            $scope2.cancel = function() {
                                                $mi.dismiss();
                                            };
                                        }],
                                        backdrop: 'static'
                                    });
                                }
                            });
                        }
                    });
                }, 1000);
                timerOfUpdate.then(function() {
                    timerOfUpdate = null;
                });
            };
            $scope.removeSchema = function(removedSchema) {
                if (window.confirm('确定从所有页面上删除登记项［' + removedSchema.title + '］？')) {
                    $scope._removeSchema(removedSchema);
                }
            };
            $scope.chooseSchema = function(event, schema) {
                var activeSchema;
                $scope.activeSchema = activeSchema = schema;
                if ($scope.app.scenario && activeSchema.type === 'multiple') {
                    angular.isString(activeSchema.answer) && (activeSchema.answer = activeSchema.answer.split(','));
                    !$scope.data && ($scope.data = {});
                    angular.forEach(activeSchema.answer, function(answer) {
                        $scope.data[answer] = true;
                    })
                }
            };
            $scope.updSchemaMultiple = function(oUpdatedSchema) {
                !oUpdatedSchema.answer && (oUpdatedSchema.answer = []);
                angular.forEach($scope.data, function(data, key) {
                    var i = oUpdatedSchema.answer.indexOf(key);
                    // 如果key 在answer中 data为false，则去掉
                    // 如果不在answer中，data为true ，则添加
                    if (i !== -1 && data === false) {
                        oUpdatedSchema.answer.splice(i, 1);
                    } else if (i === -1 && data === true) {
                        oUpdatedSchema.answer.push(key);
                    }
                });
                $scope.updSchema(oUpdatedSchema);
            };
            $scope.schemaHtml = function(schema) {
                if (schema) {
                    var bust = (new Date()).getMinutes();
                    return '/views/default/pl/fe/matter/enroll/schema/' + schema.type + '.html?_=' + bust;
                }
            };
            $scope.schemaEditorHtml = function() {
                if ($scope.activeSchema) {
                    var bust = (new Date()).getMinutes();
                    return '/views/default/pl/fe/matter/enroll/schema/main.html?_=' + bust;
                } else {
                    return '';
                }
            };
            $scope.upSchema = function(schema) {
                var schemas = $scope.app.dataSchemas,
                    index = schemas.indexOf(schema);

                if (index > 0) {
                    schemas.splice(index, 1);
                    schemas.splice(index - 1, 0, schema);
                    $scope._changeSchemaOrder(schema);
                }
            };
            $scope.downSchema = function(schema) {
                var schemas = $scope.app.dataSchemas,
                    index = schemas.indexOf(schema);

                if (index < schemas.length - 1) {
                    schemas.splice(index, 1);
                    schemas.splice(index + 1, 0, schema);
                    $scope._changeSchemaOrder(schema);
                }
            };
            $scope.$on('schemas.orderChanged', function(e, moved) {
                $scope._changeSchemaOrder(moved);
            });
            $scope.showSchemaProto = function($event) {
                var target = event.target;
                if (target.dataset.isOpen === 'Y') {
                    delete target.dataset.isOpen;
                    $(target).trigger('hide');
                } else {
                    target.dataset.isOpen = 'Y';
                    $(target).trigger('show');
                }
            };
            $scope.addOption = function(schema, afterIndex) {
                var maxSeq = 0,
                    newOp = {
                        l: ''
                    };

                if (schema.ops === undefined) {
                    schema.ops = [];
                }
                schema.ops.forEach(function(op) {
                    var opSeq = parseInt(op.v.substr(1));
                    opSeq > maxSeq && (maxSeq = opSeq);
                });
                newOp.v = 'v' + (++maxSeq);
                if (afterIndex === undefined) {
                    schema.ops.push(newOp);
                } else {
                    schema.ops.splice(afterIndex + 1, 0, newOp);
                }
                $timeout(function() {
                    $scope.$broadcast('xxt.editable.add', newOp);
                });
            };
            $scope.editOption = function(schema, op, prop) {
                prop = prop || 'content';
                srvEnrollSchema.makePagelet(op[prop] || '').then(function(result) {
                    if (prop === 'content') {
                        schema.title = $(result.html).text();
                    }
                    op[prop] = result.html;
                    $scope.updSchema(schema);
                });
            };
            $scope.moveUpOption = function(schema, op) {
                var ops = schema.ops,
                    index = ops.indexOf(op);

                if (index > 0) {
                    ops.splice(index, 1);
                    ops.splice(index - 1, 0, op);
                    $scope.updSchema(schema);
                }
            };
            $scope.moveDownOption = function(schema, op) {
                var ops = schema.ops,
                    index = ops.indexOf(op);

                if (index < ops.length - 1) {
                    ops.splice(index, 1);
                    ops.splice(index + 1, 0, op);
                    $scope.updSchema(schema);
                }
            };
            $scope.removeOption = function(schema, op) {
                schema.ops.splice(schema.ops.indexOf(op), 1);
                $scope.updSchema(schema);
            };
            $scope.refreshSchema = function(oSchema) {
                var oApp;
                oApp = $scope.app;
                if (oSchema.id === '_round_id' && oApp.groupApp) {
                    http2.get('/rest/pl/fe/matter/group/round/list?site=' + oApp.siteid + '&app=' + oApp.groupApp.id, function(rsp) {
                        var newOp, opById;
                        if (rsp.data.length) {
                            opById = {};
                            if (oSchema.ops === undefined) {
                                oSchema.ops = [];
                            } else {
                                oSchema.ops.forEach(function(op) {
                                    opById[op.v] = op;
                                });
                            }
                            rsp.data.forEach(function(oRound) {
                                if (undefined === opById[oRound.round_id]) {
                                    newOp = {};
                                    newOp.l = oRound.title;
                                    newOp.v = oRound.round_id;
                                    oSchema.ops.push(newOp);
                                }
                            });
                            if (newOp) {
                                $scope.updSchema(oSchema);
                            }
                        }
                    });
                }
            };
            $scope.recycleSchema = function(oSchema) {
                $scope._appendSchema(oSchema).then(function() {
                    $scope.app.recycleSchemas.splice($scope.app.recycleSchemas.indexOf(oSchema), 1);
                    $scope.update('recycleSchemas');
                });
            };
            $scope.$on('title.xxt.editable.changed', function(e, schema) {
                $scope.updSchema(schema);
            });
            // 回车添加选项
            $('body').on('keyup', function(evt) {
                if (evt.keyCode === 13) {
                    var schemaId, opNode, opIndex;
                    opNode = evt.target.parentNode;
                    if (opNode && opNode.getAttribute('evt-prefix') === 'option') {
                        schemaId = opNode.getAttribute('state');
                        opIndex = parseInt(opNode.dataset.index);
                        $scope.$apply(function() {
                            $scope.addOption($scope.app._schemasById[schemaId], opIndex);
                        });
                    }
                }
            });
            $scope.$on('options.orderChanged', function(e, moved, schemaId) {
                $scope.updSchema($scope.app._schemasById[schemaId]);
            });
            $scope.$on('option.xxt.editable.changed', function(e, op, schemaId) {
                $scope.updSchema($scope.app._schemasById[schemaId]);
            });
            $scope.trustAsHtml = function(schema, prop) {
                return $sce.trustAsHtml(schema[prop]);
            };
        }
    ]);
    /**
     * 单个题目
     */
    ngMod.controller('ctrlSchemaEdit', ['$scope', function($scope) {
        var _oEditing;
        $scope.editing = _oEditing = {};
        $scope.changeSchemaType = function() {
            var oBeforeState;
            if (_oEditing.type === 'member') {
                _oEditing.type = $scope.activeSchema.type;
                return;
            }
            oBeforeState = angular.copy($scope.activeSchema);
            if (false === schemaLib.changeType($scope.activeSchema, _oEditing.type)) {
                _oEditing.type = $scope.activeSchema.type;
                return;
            }
            $scope.activeConfig = wrapLib.input.newWrap($scope.activeSchema).config;
            $scope.updSchema($scope.activeSchema, oBeforeState);
        };
        $scope.$watch('activeSchema', function() {
            var oActiveSchema, oPage, oWrap;

            oActiveSchema = $scope.activeSchema;
            _oEditing.type = oActiveSchema.type;
            switch (_oEditing.type) {
                case 'multiple':
                    if (!oActiveSchema.limitChoice) {
                        oActiveSchema.limitChoice = 'N';
                    }
                    if (!oActiveSchema.range) {
                        oActiveSchema.range = [1, oActiveSchema.ops ? oActiveSchema.ops.length : 1];
                    }
                    break;
            }
            $scope.activeConfig = false;
            $scope.inputPage = false;
            for (var i = $scope.app.pages.length - 1; i >= 0; i--) {
                oPage = $scope.app.pages[i];
                if (oPage.type === 'I') {
                    $scope.inputPage = oPage;
                    if (oWrap = oPage.wrapBySchema(oActiveSchema)) {
                        $scope.activeConfig = oWrap.config;
                    }
                    break;
                }
            }
        });
    }]);
});