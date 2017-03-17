define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
    'use strict';
    /**
     * 登记项管理
     */
    ngApp.provider.controller('ctrlSchema', ['$scope', 'cstApp', 'srvEnrollPage', 'srvEnrollApp',  'srvTempApp', 'srvTempPage', function($scope, cstApp, srvEnrollPage, srvEnrollApp, srvTempApp, srvTempPage) {
        function _appendSchema(newSchema, afterIndex) {
            if ($scope.app._schemasById[newSchema.id]) {
                alert(cstApp.alertMsg['schema.duplicated']);
                return;
            }
            if (afterIndex === undefined) {
                $scope.app.data_schemas.push(newSchema);
            } else {
                $scope.app.data_schemas.splice(afterIndex + 1, 0, newSchema);
            }
            $scope.app._schemasById[newSchema.id] = newSchema;
            srvTempApp.update('data_schemas').then(function() {
                $scope.app.pages.forEach(function(page) {
                    if (page.appendSchema(newSchema)) {
                        srvTempPage.update(page, ['data_schemas', 'html']);
                    }
                });
            });
        }

        function _removeSchema(removedSchema) {
            var pages = $scope.app.pages,
                l = pages.length;

            (function removeSchemaFromPage(index) {
                var page = pages[index];
                if (page.removeSchema(removedSchema)) {
                    srvTempPage.update(page, ['data_schemas', 'html']).then(function() {
                        if (++index < l) {
                            removeSchemaFromPage(index);
                        } else {
                            $scope.app.data_schemas.splice($scope.app.data_schemas.indexOf(removedSchema), 1);
                            srvTempApp.update('data_schemas');
                        }
                    });
                } else {
                    if (++index < l) {
                        removeSchemaFromPage(index);
                    } else {
                        $scope.app.data_schemas.splice($scope.app.data_schemas.indexOf(removedSchema), 1);
                        delete $scope.app._schemasById[removedSchema.id];
                        srvTempApp.update('data_schemas');
                    }
                }
            })(0);
        }

        $scope.newSchema = function(type) {
            var newSchema, mission;

            if (type === 'phase') {
                mission = $scope.app.mission;
                if (!mission || !mission.phases || mission.phases.length === 0) {
                    alert(cstApp.alertMsg['require.mission.phase']);
                    return;
                }
            }
            newSchema = schemaLib.newSchema(type, $scope.app);
            _appendSchema(newSchema);
        };
        $scope.copySchema = function(schema) {
            var newSchema = angular.copy(schema),
                afterIndex;

            newSchema.id = 's' + (new Date() * 1);
            afterIndex = $scope.app.data_schemas.indexOf(schema);
            _appendSchema(newSchema, afterIndex);
        };
        $scope.removeSchema = function(removedSchema) {
            if (window.confirm('确定从所有页面上删除登记项［' + removedSchema.title + '］？')) {
                _removeSchema(removedSchema);
            }
        };
    }]);
    /**
     * 应用的所有登记项
     */
    ngApp.provider.controller('ctrlList', ['$scope', '$timeout', '$sce', 'srvEnrollPage', 'srvEnrollApp', 'srvEnrollSchema', 'srvTempApp', 'srvTempPage',function($scope, $timeout, $sce, srvEnrollPage, srvEnrollApp, srvEnrollSchema, srvTempApp, srvTempPage) {
        function _changeSchemaOrder(moved) {
            srvTempApp.update('data_schemas').then(function() {
                var app = $scope.app;
                if (app.__schemasOrderConsistent === 'Y') {
                    var i = app.data_schemas.indexOf(moved),
                        prevSchema;
                    if (i > 0) prevSchema = app.data_schemas[i - 1];
                    app.pages.forEach(function(page) {
                        page.moveSchema(moved, prevSchema);
                        srvTempPage.update(page, ['data_schemas', 'html']);
                    });
                }
            });
        }

        $scope.chooseSchema = function(event, schema) {
            $scope.activeSchema = schema;
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
            var schemas = $scope.app.data_schemas,
                index = schemas.indexOf(schema);

            if (index > 0) {
                schemas.splice(index, 1);
                schemas.splice(index - 1, 0, schema);
                _changeSchemaOrder(schema);
            }
        };
        $scope.downSchema = function(schema) {
            var schemas = $scope.app.data_schemas,
                index = schemas.indexOf(schema);

            if (index < schemas.length - 1) {
                schemas.splice(index, 1);
                schemas.splice(index + 1, 0, schema);
                _changeSchemaOrder(schema);
            }
        };
        $scope.$on('schemas.orderChanged', function(e, moved) {
            _changeSchemaOrder(moved);
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
        $scope.$on('title.xxt.editable.changed', function(e, schema) {
            $scope.updSchema(schema);
        });
        $scope.removeOption = function(schema, op) {
            schema.ops.splice(schema.ops.indexOf(op), 1);
            $scope.updSchema(schema);
        };
        // 回车添加选项
        $('body').on('keyup', function(evt) {
            if (event.keyCode === 13) {
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
        $scope.makePagelet = function(schema) {
            srvEnrollSchema.makePagelet(schema).then(function(result) {
                schema.title = $(result.html).text();
                schema.content = result.html;
                $scope.updSchema(schema);
            });
        };
        var timerOfUpdate = null;
        $scope.updSchema = function(schema, beforeState) {
            $scope.app.pages.forEach(function(page) {
                page.updateSchema(schema, beforeState);
            });
            if (timerOfUpdate !== null) {
                $timeout.cancel(timerOfUpdate);
            }
            timerOfUpdate = $timeout(function() {
                srvTempApp.update('data_schemas').then(function() {
                    $scope.app.pages.forEach(function(page) {
                        srvTempPage.update(page, ['data_schemas', 'html']);
                    });
                });
            }, 1000);
            timerOfUpdate.then(function() {
                timerOfUpdate = null;
            });
        };
    }]);
    /**
     * 登记项编辑
     */
    ngApp.provider.controller('ctrlSchemaEdit', ['$scope', 'srvEnrollPage', 'srvTempPage',function($scope, srvEnrollPage, srvTempPage) {
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
        $scope.updConfig = function(prop) {
            var inputPage;
            if (inputPage = $scope.inputPage) {
                inputPage.updateSchema($scope.activeSchema);
                srvTempPage.update(inputPage, ['data_schemas', 'html']);
            }
        };
        $scope.changeSchemaType = function() {
            var beforeState = angular.copy($scope.activeSchema);
            if (schemaLib.changeType($scope.activeSchema, editing.type)) {
                $scope.activeConfig = wrapLib.input.newWrap($scope.activeSchema).config;
                $scope.updSchema($scope.activeSchema, beforeState);
            }
        };
        $scope.$watch('activeSchema', function() {
            var page, wrap;

            editing.type = $scope.activeSchema.type;
            $scope.activeConfig = false;
            $scope.inputPage = false;
            for (var i = $scope.app.pages.length - 1; i >= 0; i--) {
                page = $scope.app.pages[i];
                if (page.type === 'I') {
                    $scope.inputPage = page;
                    if (wrap = page.wrapBySchema($scope.activeSchema)) {
                        $scope.activeConfig = wrap.config;
                    }
                    break;
                }
            }
        });
    }]);
});
