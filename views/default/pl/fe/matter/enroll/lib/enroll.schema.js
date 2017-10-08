define(['require', 'schema', 'wrap'], function(require, schemaLib, wrapLib) {
    'use strict';
    var ngMod = angular.module('schema.enroll', []);
    ngMod.provider('srvEnrollSchema', function() {
        var _siteId, _appId;

        this.config = function(siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$uibModal', '$q', function($uibModal, $q) {
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
            };
            return _self;
        }];
    });
    /**
     * 所有题目
     */
    ngMod.controller('ctrlSchemaList', ['$scope', '$timeout', '$sce', '$uibModal', 'http2', 'cstApp', 'srvEnrollSchema', function($scope, $timeout, $sce, $uibModal, http2, cstApp, srvEnrollSchema) {
        $scope.activeSchema = null;
        $scope.cstApp = cstApp;
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
        };
        $scope.newMember = function(ms, schema) {
            var newSchema = schemaLib.newSchema('member', $scope.app);

            newSchema.schema_id = ms.id;
            newSchema.id = schema.id;
            newSchema.title = schema.title;
            $scope._appendSchema(newSchema);
        };
        $scope.newByOtherApp = function(schema, otherApp) {
            var newSchema;

            newSchema = schemaLib.newSchema(schema.type, $scope.app);
            newSchema.type === 'member' && (newSchema.schema_id = schema.schema_id);
            newSchema.id = schema.id;
            newSchema.title = schema.title;
            newSchema.requireCheck = 'Y';
            newSchema.fromApp = otherApp.id;
            if (schema.ops) {
                newSchema.ops = schema.ops;
            }
            $scope._appendSchema(newSchema);
        };
        $scope.copySchema = function(schema) {
            var newSchema = angular.copy(schema);

            newSchema.id = 's' + (new Date * 1);
            newSchema.title += '-2';
            delete newSchema.fromApp;
            delete newSchema.requireCheck;
            $scope._appendSchema(newSchema, schema);
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
        $scope._appendSchema = function(newSchema, afterSchema) {
            var oApp, afterIndex, changedPages = [];
            oApp = $scope.app;
            if (oApp._schemasById[newSchema.id]) {
                alert(cstApp.alertMsg['schema.duplicated']);
                return;
            }
            if (!afterSchema) {
                afterSchema = $scope.activeSchema;
            }
            if (afterSchema) {
                afterIndex = oApp.dataSchemas.indexOf(afterSchema);
                oApp.dataSchemas.splice(afterIndex + 1, 0, newSchema);
            } else {
                oApp.dataSchemas.push(newSchema);
            }
            oApp._schemasById[newSchema.id] = newSchema;
            oApp.pages.forEach(function(oPage) {
                if (oPage.appendSchema(newSchema, afterSchema)) {
                    changedPages.push(oPage);
                }
            });
            $scope._submitChange(changedPages);
        };
        $scope._changeSchemaOrder = function(moved) {
            var oApp, changedPages = [];
            oApp = $scope.app;
            if (oApp.__schemasOrderConsistent === 'Y') {
                var i = oApp.dataSchemas.indexOf(moved),
                    prevSchema;
                if (i > 0) prevSchema = oApp.dataSchemas[i - 1];
                oApp.pages.forEach(function(oPage) {
                    oPage.moveSchema(moved, prevSchema);
                    changedPages.push(oPage);
                });
            }
            $scope._submitChange(changedPages);
        };
        $scope._removeSchema = function(removedSchema) {
            var oApp = $scope.app,
                changedPages = [];

            oApp.dataSchemas.splice(oApp.dataSchemas.indexOf(removedSchema), 1);
            delete oApp._schemasById[removedSchema.id];
            $scope.app.pages.forEach(function(oPage) {
                if (oPage.removeSchema(removedSchema)) {
                    changedPages.push(oPage);
                }
            });
            $scope._submitChange(changedPages);
        };
        var timerOfUpdate = null;
        $scope.updSchema = function(oSchema, oBeforeState) {
            if (oSchema.format === 'number') {
                if (oSchema.weight === undefined) {
                    oSchema.weight = 1;
                } else {
                    if (false === /^\d+\.?\d*$/.test(oSchema.weight)) {
                        oSchema.weight = 1;
                    } else if (/\.$/.test(oSchema.weight)) {
                        // 这样会导致无法输入“点”
                        //oSchema.weight = oSchema.weight.slice(0, -1);
                    }
                }
            }
            $scope.app.pages.forEach(function(oPage) {
                oPage.updateSchema(oSchema, oBeforeState);
            });
            if (timerOfUpdate !== null) {
                $timeout.cancel(timerOfUpdate);
            }
            timerOfUpdate = $timeout(function() {
                $scope._submitChange($scope.app.pages);
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
        $scope.$watch('memberSchemas', function(nv) {
            if (!nv) return;
            $scope.mschemasById = {};
            $scope.memberSchemas.forEach(function(mschema) {
                $scope.mschemasById[mschema.id] = mschema;
            });
        }, true);
    }]);
    /**
     * 单个题目
     */
    ngMod.controller('ctrlSchemaEdit', ['$scope', function($scope) {
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
            var oBeforeState = angular.copy($scope.activeSchema);
            if (schemaLib.changeType($scope.activeSchema, editing.type)) {
                $scope.activeConfig = wrapLib.input.newWrap($scope.activeSchema).config;
                $scope.updSchema($scope.activeSchema, oBeforeState);
            }
        };
        $scope.$watch('activeSchema', function(oNew, oOld) {
            var oPage, oWrap;

            editing.type = $scope.activeSchema.type;
            if (editing.type === 'member') {
                if ($scope.activeSchema.schema_id) {
                    (function() {
                        var i, j, memberSchema, schema;
                        /*自定义用户*/
                        for (i = $scope.memberSchemas.length - 1; i >= 0; i--) {
                            memberSchema = $scope.memberSchemas[i];
                            if ($scope.activeSchema.schema_id === memberSchema.id) {
                                for (j = memberSchema._schemas.length - 1; j >= 0; j--) {
                                    schema = memberSchema._schemas[j];
                                    if ($scope.activeSchema.id === schema.id) {
                                        break;
                                    }
                                }
                                $scope.selectedMemberSchema = {
                                    schema: memberSchema,
                                    attr: schema
                                };
                                break;
                            }
                        }
                    })();
                }
            }
            $scope.activeConfig = false;
            $scope.inputPage = false;
            for (var i = $scope.app.pages.length - 1; i >= 0; i--) {
                oPage = $scope.app.pages[i];
                if (oPage.type === 'I') {
                    $scope.inputPage = oPage;
                    if (oWrap = oPage.wrapBySchema($scope.activeSchema)) {
                        $scope.activeConfig = oWrap.config;
                    }
                    break;
                }
            }
        });
    }])
});