define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
    'use strict';
    /**
     * 模板任务
     */
    ngApp.provider.controller('ctrlSchemaPlan', ['$scope', '$q', '$uibModal', 'http2', 'srvPlanApp', function($scope, $q, $uibModal, http2, srvPlanApp) {
        var _oApp;
        $scope.submitChange = function() {
            var deferred = $q.defer();
            http2.post('/rest/pl/fe/matter/plan/update?site=' + _oApp.siteid + '&app=' + _oApp.id, { 'checkSchemas': _oApp.checkSchemas }, function(rsp) {
                deferred.resolve();
            });
            return deferred.promise;
        };
        $scope.editPagelet = function(content) {
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
        };
        srvPlanApp.get().then(function(oApp) {
            var schemasById = {};
            oApp.checkSchemas.forEach(function(oSchema) {
                schemasById[oSchema.id] = oSchema;
            });
            oApp._schemasById = schemasById;
            _oApp = oApp;
        });
    }]);
    ngApp.provider.controller('ctrlSchemaList', ['$scope', '$timeout', '$sce', 'CstApp', function($scope, $timeout, $sce, CstApp) {
        $scope.activeSchema = null;
        $scope.CstApp = CstApp;

        $scope.newSchema = function(type) {
            var oMockApp, newSchema, oProto;
            oMockApp = {
                dataSchemas: $scope.app.checkSchemas
            };
            newSchema = schemaLib.newSchema(type, oMockApp, oProto);
            $scope._appendSchema(newSchema);

            return newSchema;
        };
        $scope.copySchema = function(schema) {
            var newSchema = angular.copy(schema);

            newSchema.id = 's' + (new Date * 1);
            newSchema.title += '-2';
            $scope._appendSchema(newSchema, schema);

            return newSchema;
        };
        $scope.makePagelet = function(oSchema, prop) {
            prop = prop || 'content';
            $scope.editPagelet(oSchema[prop] || '').then(function(result) {
                if (prop === 'content') {
                    oSchema.title = $(result.html).text();
                }
                oSchema[prop] = result.html;
                $scope.updSchema(oSchema);
            });
        };
        /**
         * oAfterSchema: false - first, undefined - after active schema
         */
        $scope._appendSchema = function(newSchema, oAfterSchema) {
            var oApp, checkSchemas, afterIndex;

            oApp = $scope.app;
            checkSchemas = oApp.checkSchemas;
            if (oApp._schemasById[newSchema.id]) {
                alert(CstApp.alertMsg['schema.duplicated']);
                return;
            }
            if (undefined === oAfterSchema) {
                oAfterSchema = $scope.activeSchema;
            }
            if (oAfterSchema) {
                afterIndex = checkSchemas.indexOf(oAfterSchema);
                checkSchemas.splice(afterIndex + 1, 0, newSchema);
            } else if (oAfterSchema === false) {
                checkSchemas.splice(0, 0, newSchema);
            } else {
                checkSchemas.push(newSchema);
            }
            oApp._schemasById[newSchema.id] = newSchema;

            return $scope.submitChange();
        };
        $scope._changeSchemaOrder = function(moved) {
            var checkSchemas, i, prevSchema, changedPages;

            checkSchemas = $scope.app.checkSchemas;
            i = checkSchemas.indexOf(moved);
            if (i > 0) prevSchema = checkSchemas[i - 1];
            $scope.submitChange();
        };
        $scope._removeSchema = function(oRemovedSchema) {
            var oApp = $scope.app,
                schemas;

            schemas = oApp.checkSchemas;
            schemas.splice(schemas.indexOf(oRemovedSchema), 1);
            delete oApp._schemasById[oRemovedSchema.id];
            $scope.submitChange().then(function() {});
        };
        var timerOfUpdate = null;
        $scope.updSchema = function() {
            if (timerOfUpdate !== null) {
                $timeout.cancel(timerOfUpdate);
            }
            timerOfUpdate = $timeout(function() {
                $scope.submitChange();
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
            var schemas = $scope.app.checkSchemas,
                index = schemas.indexOf(schema);

            if (index > 0) {
                schemas.splice(index, 1);
                schemas.splice(index - 1, 0, schema);
                $scope._changeSchemaOrder(schema);
            }
        };
        $scope.downSchema = function(schema) {
            var schemas = $scope.app.checkSchemas,
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
            $scope.editPagelet(op[prop] || '').then(function(result) {
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
    }]);
    /**
     * 单个题目
     */
    ngApp.provider.controller('ctrlSchemaEdit', ['$scope', function($scope) {
        var _oEditing;
        $scope.editing = _oEditing = {};
        $scope.changeSchemaType = function() {
            var oBeforeState;
            oBeforeState = angular.copy($scope.activeSchema);
            if (false === schemaLib.changeType($scope.activeSchema, _oEditing.type)) {
                _oEditing.type = $scope.activeSchema.type;
                return;
            }
            $scope.activeConfig = wrapLib.input.newWrap($scope.activeSchema).config;
            $scope.updSchema($scope.activeSchema, oBeforeState);
        };
        $scope.$watch('activeSchema', function() {
            var oActiveSchema;

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
        });
    }])
});