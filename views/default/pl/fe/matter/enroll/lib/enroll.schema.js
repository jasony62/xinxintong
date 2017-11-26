define(['schema', 'wrap'], function(schemaLib, wrapLib) {
    'use strict';
    var ngMod = angular.module('schema.enroll', []);
    ngMod.provider('srvEnrollSchema', function() {
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
                srvApp.assignEnrollApp();
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

                if (type === 'phase') {
                    mission = $scope.app.mission;
                    if (!mission || !mission.phases || mission.phases.length === 0) {
                        alert(cstApp.alertMsg['require.mission.phase']);
                        return;
                    }
                    oProto = { title: cstApp.naming.mission_phase };
                }
                newSchema = schemaLib.newSchema(type, $scope.app, oProto);
                $scope._appendSchema(newSchema);

                return newSchema;
            };
            $scope.newMember = function(ms, schema) {
                var newSchema = schemaLib.newSchema('member', $scope.app);

                newSchema.schema_id = ms.id;
                newSchema.id = schema.id;
                newSchema.title = schema.title;
                $scope._appendSchema(newSchema);

                return newSchema;
            };
            $scope.newByOtherApp = function(oProtoSchema, oOtherApp, oAfterSchema) {
                var oNewSchema;

                oNewSchema = schemaLib.newSchema(oProtoSchema.type, $scope.app, oProtoSchema);
                oNewSchema.type === 'member' && (oNewSchema.schema_id = oProtoSchema.schema_id);
                oNewSchema.id = oProtoSchema.id;
                oNewSchema.requireCheck = 'Y';
                oNewSchema.fromApp = oOtherApp.id;
                if (oProtoSchema.ops) {
                    oNewSchema.ops = oProtoSchema.ops;
                }
                $scope._appendSchema(oNewSchema, oAfterSchema);

                return oNewSchema;
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

                oApp.dataSchemas.splice(oApp.dataSchemas.indexOf(oRemovedSchema), 1);
                delete oApp._schemasById[oRemovedSchema.id];
                $scope.app.pages.forEach(function(oPage) {
                    if (oPage.removeSchema(oRemovedSchema)) {
                        changedPages.push(oPage);
                    }
                });
                srvEnrollSchema.submitChange(changedPages).then(function() {
                    /* 放入回收站 */
                    var aNewRecycleSchemas;
                    aNewRecycleSchemas = [];
                    for (var i = oApp.recycleSchemas.length - 1; i >= 0; i--) {
                        if (oApp.recycleSchemas[i].id !== oRemovedSchema.id) {
                            aNewRecycleSchemas.push(oApp.recycleSchemas[i]);
                        }
                    }
                    aNewRecycleSchemas.push(oRemovedSchema);
                    oApp.recycleSchemas = aNewRecycleSchemas;
                    srvApp.update('recycleSchemas');
                });
            };
            var timerOfUpdate = null;
            $scope.updSchema = function(oSchema, oBeforeState, prop) {
                srvEnrollSchema.update(oSchema, oBeforeState, prop);
                if (timerOfUpdate !== null) {
                    $timeout.cancel(timerOfUpdate);
                }
                timerOfUpdate = $timeout(function() {
                    srvEnrollSchema.submitChange($scope.app.pages);
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
                } else if (oSchema.type === 'phase') {

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
        function _setSelectedMemberSchema(oActiveSchema) {
            var i, j, memberSchema, schema, selectedMemberSchema;
            /*自定义用户*/
            for (i = $scope.memberSchemas.length - 1; i >= 0; i--) {
                memberSchema = $scope.memberSchemas[i];
                if (oActiveSchema.schema_id === memberSchema.id) {
                    for (j = memberSchema._schemas.length - 1; j >= 0; j--) {
                        schema = memberSchema._schemas[j];
                        if (oActiveSchema.id === schema.id) {
                            selectedMemberSchema = {
                                schema: memberSchema,
                                attr: schema
                            };
                            return selectedMemberSchema;
                        }
                    }
                }
            }
            return false;
        }

        var editing;

        $scope.editing = editing = {};
        $scope.assocAppName = function(appId) {
            var assocApp;
            if ($scope.app.enrollApp && $scope.app.enrollApp.id === appId) {
                return $scope.app.enrollApp.title;
            } else if ($scope.app.groupApp && $scope.app.groupApp.id === appId) {
                return $scope.app.groupApp.title;
            } else {
                return '';
            }
        };
        $scope.changeSchemaType = function() {
            var oBeforeState;
            if (false === schemaLib.changeType($scope.activeSchema, editing.type)) {
                editing.type = $scope.activeSchema.type;
                return;
            }
            oBeforeState = angular.copy($scope.activeSchema);
            if ($scope.activeSchema.type === 'member') {
                if ($scope.app.entry_rule.member) {
                    var mschemaIds = Object.keys($scope.app.entry_rule.member);
                    if (mschemaIds.length) {
                        $scope.activeSchema.schema_id = mschemaIds[0];
                        $scope.selectedMemberSchema = _setSelectedMemberSchema($scope.activeSchema);
                    }
                }
            }
            $scope.activeConfig = wrapLib.input.newWrap($scope.activeSchema).config;
            $scope.updSchema($scope.activeSchema, oBeforeState);
        };
        $scope.$watch('activeSchema', function() {
            var oActiveSchema, oPage, oWrap;

            $scope.selectedMemberSchema = false;
            oActiveSchema = $scope.activeSchema;
            editing.type = oActiveSchema.type;
            switch (editing.type) {
                case 'member':
                    $scope.selectedMemberSchema = _setSelectedMemberSchema(oActiveSchema);
                    break;
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
    }])
});