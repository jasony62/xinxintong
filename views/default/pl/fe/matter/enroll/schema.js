define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
    'use strict';
    /**
     * 登记项管理
     */
    ngApp.provider.controller('ctrlSchema', ['$scope', 'cstApp', 'srvEnrollPage', 'srvEnrollApp', function($scope, cstApp, srvEnrollPage, srvEnrollApp) {
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
            srvEnrollApp.update('data_schemas').then(function() {
                $scope.app.pages.forEach(function(page) {
                    if (page.appendSchema(newSchema)) {
                        srvEnrollPage.update(page, ['data_schemas', 'html']);
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
                    srvEnrollPage.update(page, ['data_schemas', 'html']).then(function() {
                        if (++index < l) {
                            removeSchemaFromPage(index);
                        } else {
                            $scope.app.data_schemas.splice($scope.app.data_schemas.indexOf(removedSchema), 1);
                            delete $scope.app._schemasById[removedSchema.id];
                            srvEnrollApp.update('data_schemas');
                        }
                    });
                } else {
                    if (++index < l) {
                        removeSchemaFromPage(index);
                    } else {
                        $scope.app.data_schemas.splice($scope.app.data_schemas.indexOf(removedSchema), 1);
                        delete $scope.app._schemasById[removedSchema.id];
                        srvEnrollApp.update('data_schemas');
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
        $scope.newMember = function(ms, schema) {
            var newSchema = schemaLib.newSchema('member', $scope.app);

            newSchema.schema_id = ms.id;
            newSchema.id = schema.id;
            newSchema.title = schema.title;
            _appendSchema(newSchema);
        };
        $scope.importByOther = function() {
            srvEnrollApp.importSchemaByOther().then(function(schemas) {
                schemas.forEach(function(schema) {
                    var newSchema;
                    newSchema = schemaLib.newSchema(schema.type, $scope.app);
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
                    _appendSchema(newSchema);
                });
            });
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
        $scope.assignEnrollApp = function() {
            srvEnrollApp.assignEnrollApp();
        };
        $scope.cancelEnrollApp = function() {
            $scope.app.enroll_app_id = '';
            srvEnrollApp.update('enroll_app_id');
        };
        $scope.assignGroupApp = function() {
            srvEnrollApp.assignGroupApp();
        };
        $scope.cancelGroupApp = function() {
            $scope.app.group_app_id = '';
            srvEnrollApp.update('group_app_id').then(function() {});
        };
    }]);
    /**
     * 应用的所有登记项
     */
    ngApp.provider.controller('ctrlList', ['$scope', '$timeout', '$sce', 'srvEnrollPage', 'srvEnrollApp', 'srvEnrollSchema', function($scope, $timeout, $sce, srvEnrollPage, srvEnrollApp, srvEnrollSchema) {
        function _changeSchemaOrder(moved) {
            srvEnrollApp.update('data_schemas').then(function() {
                var app = $scope.app;
                if (app.__schemasOrderConsistent === 'Y') {
                    var i = app.data_schemas.indexOf(moved),
                        prevSchema;
                    if (i > 0) prevSchema = app.data_schemas[i - 1];
                    app.pages.forEach(function(page) {
                        page.moveSchema(moved, prevSchema);
                        srvEnrollPage.update(page, ['data_schemas', 'html']);
                    });
                }
            });
        }

        $scope.chooseSchema = function(event, schema) {
            $scope.activeSchema = schema;
            if ($scope.app.scenario && $scope.activeSchema.type === 'multiple') {
                angular.isString($scope.activeSchema.answer) && ($scope.activeSchema.answer = $scope.activeSchema.answer.split(','));
                !$scope.data && ($scope.data = {});
                angular.forEach($scope.activeSchema.answer, function(answer) {
                    $scope.data[answer] = true;
                })
            }
        };
        $scope.updSchemaMultiple = function(activeSchema) {
            angular.forEach($scope.data, function(data, key) {
                var i = $scope.activeSchema.answer.indexOf(key);
                //如果key 在answer中 data为false，则去掉
                //    如果不在answer中，data为true ，则添加
                if (i !== -1 && data === false) {
                    $scope.activeSchema.answer.splice(i, 1);
                } else if (i === -1 && data === true) {
                    $scope.activeSchema.answer.push(key);
                }
            });
            $scope.activeSchema.answer.join(',');
            $scope.updSchema(activeSchema, 'answer');
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
                srvEnrollApp.update('data_schemas').then(function() {
                    $scope.app.pages.forEach(function(page) {
                        srvEnrollPage.update(page, ['data_schemas', 'html']);
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
    ngApp.provider.controller('ctrlSchemaEdit', ['$scope', 'srvEnrollPage', function($scope, srvEnrollPage) {
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
                srvEnrollPage.update(inputPage, ['data_schemas', 'html']);
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
    /**
     * 导入导出记录
     */
    ngApp.provider.controller('ctrlImport', ['$scope', 'http2', 'noticebox', 'srvEnrollApp', function($scope, http2, noticebox, srvEnrollApp) {
        srvEnrollApp.get().then(function(app) {
            var r = new Resumable({
                target: '/rest/pl/fe/matter/enroll/import/upload?site=' + app.siteid + '&app=' + app.id,
                testChunks: false,
            });
            r.assignBrowse(document.getElementById('btnImportRecords'));
            r.on('fileAdded', function(file, event) {
                $scope.$apply(function() {
                    noticebox.progress('开始上传文件');
                });
                r.upload();
            });
            r.on('progress', function(file, event) {
                $scope.$apply(function() {
                    noticebox.progress('正在上传文件：' + Math.floor(r.progress() * 100) + '%');
                });
            });
            r.on('complete', function() {
                var f, lastModified, posted;
                f = r.files.pop().file;
                lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
                posted = {
                    name: f.name,
                    size: f.size,
                    type: f.type,
                    lastModified: lastModified,
                    uniqueIdentifier: f.uniqueIdentifier,
                };
                http2.post('/rest/pl/fe/matter/enroll/import/endUpload?site=' + app.siteid + '&app=' + app.id, posted, function success(rsp) {});
            });
        });
        $scope.options = {
            overwrite: 'Y'
        };
        $scope.downloadTemplate = function() {
            var url = '/rest/pl/fe/matter/enroll/import/downloadTemplate?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            window.open(url);
        };
    }]);
});
