define(['frame', 'schema', 'editor'], function(ngApp, schemaLib, editorProxy) {
    'use strict';
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlPage', ['$scope', 'srvApp', 'srvPage', function($scope, srvApp, srvPage) {
        window.onbeforeunload = function(e) {
            var message;
            if ($scope.ep.$$modified) {
                message = '已经修改的页面还没有保存，确定离开？';
                e = e || window.event;
                if (e) {
                    e.returnValue = message;
                }
                return message;
            }
        };
        $scope.addPage = function() {
            $scope.createPage().then(function(page) {
                $scope.choosePage(page);
            });
        };
        $scope.updPage = function(page, names) {
            if (names.indexOf('html') !== -1) {
                if (page === $scope.ep) {
                    page.html = editorProxy.getEditor().getContent();
                }
                editorProxy.purifyPage(page, true);
            }

            return srvPage.update(page, names);
        };
        $scope.delPage = function() {
            if (window.confirm('确定删除页面？')) {
                srvPage.remove($scope.ep).then(function() {
                    $scope.app.pages.splice($scope.app.pages.indexOf($scope.ep), 1);
                    if ($scope.app.pages.length) {
                        $scope.choosePage($scope.app.pages[0]);
                    } else {
                        $scope.ep = null;
                    }
                });
            }
        };
        $scope.choosePage = function(page) {
            if (angular.isString(page)) {
                for (var i = $scope.app.pages.length - 1; i >= 0; i--) {
                    if ($scope.app.pages[i].name === page) {
                        page = $scope.app.pages[i];
                        break;
                    }
                }
                if (i === -1) return;
            }
            $scope.ep = page;
        };
        $scope.cleanPage = function() {
            $scope.ep.html = '';
            $scope.ep.data_schemas = [];
            $scope.ep.act_schemas = [];
            srvPage.update($scope.ep, ['data_schemas', 'act_schemas', 'html']).then(function() {
                editorProxy.getEditor().setContent('');
            });
        };
        /**
         * 修改schema
         */
        $scope.$on('xxt.matter.signin.app.data_schemas.modified', function(event, state) {
            var originator = state.originator,
                modifiedSchema = state.schema;

            $scope.app.pages.forEach(function(page) {
                if (originator === $scope.ep && page !== $scope.ep) {
                    page.updateSchema(modifiedSchema);
                }
            });
        });
        $scope.save = function() {
            // 更新应用
            srvApp.update('data_schemas').then(function() {
                // 更新页面
                $scope.app.pages.forEach(function(page) {
                    $scope.updPage(page, ['data_schemas', 'act_schemas', 'html']);
                });
            });
        };
        $scope.$watch('app', function(app) {
            if (!app) return;
            $scope.choosePage(app.pages[0]);
        });
    }]);
    /**
     * page editor
     */
    ngApp.provider.controller('ctrlPageEdit', ['$scope', '$timeout', '$q', 'mediagallery', 'mattersgallery', function($scope, $timeout, $q, mediagallery, mattersgallery) {
        function removeSchema(removedSchema) {
            var deferred = $q.defer();

            if (editorProxy.removeSchema(removedSchema)) {
                if ($scope.activeWrap && removedSchema.id === $scope.activeWrap.schema.id) {
                    $scope.setActiveWrap(null);
                }
                deferred.resolve(removedSchema);
            } else {
                deferred.resolve(removedSchema);
            }

            return deferred.promise;
        };

        function addInputSchema(addedSchema) {
            var deferred = $q.defer(),
                domNewWrap;

            // 在当前页面上添加新登记项
            domNewWrap = editorProxy.appendSchema(addedSchema);
            // 更新后台数据
            $scope.setActiveWrap(domNewWrap);
            deferred.resolve();
            // 页面滚动到新元素
            editorProxy.scroll(domNewWrap);

            return deferred.promise;
        };

        var tinymceEditor;
        $scope.activeWrap = false;
        $scope.innerlinkTypes = [{
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'news',
            title: '多图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'channel',
            title: '频道',
            url: '/rest/pl/fe/matter'
        }];

        $scope.setActiveWrap = function(domWrap) {
            $scope.activeWrap = editorProxy.setActiveWrap(domWrap);
        };
        $scope.wrapEditorHtml = function() {
            var url = '/views/default/pl/fe/matter/enroll/wrap/' + $scope.activeWrap.type + '.html?_=' + (new Date()).getMinutes();
            return url;
        };
        /*创建了新的schema*/
        $scope.$on('xxt.matter.signin.app.data_schemas.created', function(event, newSchema) {
            var newWrap;
            if ($scope.ep.type === 'I') {
                addInputSchema(newSchema).then(function() {
                    $scope.$broadcast('xxt.matter.signin.page.data_schemas.added', newSchema);
                });
            }
            angular.forEach($scope.app.pages, function(page) {
                if (page.type === 'V') {
                    /* 更新内存的数据 */
                    page.appendSchema(newSchema);
                }
            });
        });
        $scope.$on('xxt.matter.signin.page.data_schemas.requestAdd', function(event, addedSchema) {
            addInputSchema(addedSchema).then(function() {
                $scope.$broadcast('xxt.matter.signin.page.data_schemas.added', addedSchema);
            });
        });
        $scope.$on('xxt.matter.signin.page.data_schemas.requestRemove', function(event, removedSchema) {
            removeSchema(removedSchema).then(function() {
                $scope.$broadcast('xxt.matter.signin.page.data_schemas.removed', removedSchema);
            });
        });
        $scope.newButton = function(btn) {
            var domWrap = editorProxy.appendButton(btn);
            $scope.setActiveWrap(domWrap);
        };
        $scope.newList = function(pattern) {
            if (pattern === 'records') {
                var domWrap = $scope.ep.appendRecordList($scope.app);
            } else if (pattern === 'rounds') {
                var domWrap = $scope.ep.appendRoundList($scope.app);
            }
            $scope.setActiveWrap(domWrap);
        };
        $scope.refreshWrap = function(wrap) {
            editorProxy.modifySchema(wrap);
        };
        $scope.removeSchema = function(removedSchema) {
            var deferred = $q.defer();
            removeSchema(removedSchema).then(function() {
                // 通知应用删除登记项
                $scope.$broadcast('xxt.matter.signin.page.data_schemas.removed', removedSchema);
                deferred.resolve();
            });
            return deferred.promise;
        };
        $scope.removeWrap = function() {
            var wrapType = $scope.activeWrap.type,
                schema;

            if (/button|text/.test(wrapType)) {
                editorProxy.removeWrap($scope.activeWrap);

            } else if (/radio|checkbox/.test(wrapType)) {
                var optionSchema;
                schema = editorProxy.optionSchemaByDom($scope.activeWrap.dom, $scope.app);
                optionSchema = schema[1];
                schema = schema[0];
                schema.ops.splice(schema.ops.indexOf(optionSchema), 1);
                // 更新当前页面
                editorProxy.removeWrap($scope.activeWrap);
                // 更新其它页面
                $scope.$emit('xxt.matter.signin.app.data_schemas.modified', {
                    originator: $scope.ep,
                    schema: schema
                });
            } else {
                schema = $scope.activeWrap.schema;
                $scope.removeSchema(schema).then(function() {
                    editorProxy.removeWrap($scope.activeWrap);
                });
            }
            $scope.setActiveWrap(null);
        };
        $scope.moveWrap = function(action) {
            $scope.activeWrap = editorProxy.moveWrap(action);
        };
        $scope.embedMatter = function(page) {
            var options = {
                matterTypes: $scope.innerlinkTypes,
                singleMatter: true
            };
            if ($scope.app.mission) {
                options.mission = $scope.app.mission;
            }
            mattersgallery.open($scope.siteId, function(matters, type) {
                var dom = tinymceEditor.dom,
                    style = "cursor:pointer",
                    fn, domMatter, sibling;

                if ($scope.activeWrap) {
                    sibling = $scope.activeWrap.dom;
                    while (sibling.parentNode !== tinymceEditor.getBody()) {
                        sibling = sibling.parentNode;
                    }
                    // 加到当前选中元素的后面
                    matters.forEach(function(matter) {
                        fn = "openMatter(" + matter.id + ",'" + type + "')";
                        domMatter = dom.create('div', {
                            'wrap': 'matter',
                            'class': 'form-group',
                        }, dom.createHTML('span', {
                            "style": style,
                            "ng-click": fn
                        }, dom.encode(matter.title)));
                        dom.insertAfter(domMatter, sibling);
                    });
                } else {
                    // 加到页面的结尾
                    matters.forEach(function(matter) {
                        fn = "openMatter(" + matter.id + "','" + type + "')";
                        domMatter = dom.add(tinymceEditor.getBody(), 'div', {
                            'wrap': 'matter',
                            'class': 'form-group',
                        }, dom.createHTML('span', {
                            "style": style,
                            "ng-click": fn
                        }, dom.encode(matter.title)));
                    });
                }
            }, options);
        };
        $scope.gotoCode = function() {
            window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + $scope.ep.code_name, '_self');
        };
        $scope.$on('tinymce.content.change', function(event, changedNode) {
            var status, html;
            if (changedNode) {
                // 文档中的节点发生变化
                status = editorProxy.nodeChange(changedNode.node);
            } else {
                status = {};
                html = editorProxy.purifyPage({
                    type: 'I',
                    page: tinymceEditor.getContent()
                });
                if (html !== $scope.ep.html) {
                    status.htmlChanged = true;
                }
            }
            if (status.schemaChanged === true) {
                // 更新其他页面
                $scope.$emit('xxt.matter.signin.app.data_schemas.modified', {
                    originator: $scope.ep,
                    schema: $scope.activeWrap.schema
                });
            }
        });
        //添加选项
        $scope.$on('tinymce.option.add', function(event, domWrap) {
            if (/radio|checkbox/.test(domWrap.getAttribute('wrap'))) {
                var parentNode = domWrap,
                    optionDom, schemaOptionId, schemaId, schema;

                optionDom = domWrap.querySelector('input');
                schemaId = optionDom.getAttribute('name');
                if (/radio/.test(domWrap.getAttribute('wrap'))) {
                    schemaOptionId = optionDom.getAttribute('value');
                } else {
                    schemaOptionId = optionDom.getAttribute('ng-model');
                    schemaOptionId = schemaOptionId.split('.')[2];
                }

                for (var i = $scope.app.data_schemas.length - 1; i >= 0; i--) {
                    if (schemaId === $scope.app.data_schemas[i].id) {
                        schema = $scope.app.data_schemas[i];
                        break;
                    }
                }

                if (schema.ops) {
                    var newOp;

                    newOp = schemaLib.addOption(schema, schemaOptionId);
                    editorProxy.addOptionWrap(domWrap, schema, newOp);
                }
            }
        });
        $scope.$on('tinymce.wrap.add', function(event, domWrap) {
            $scope.$apply(function() {
                $scope.activeWrap = editorProxy.selectWrap(domWrap);
            });
        });
        $scope.$on('tinymce.wrap.select', function(event, domWrap) {
            $scope.$apply(function() {
                $scope.activeWrap = editorProxy.selectWrap(domWrap);
            });
        });
        $scope.$on('tinymce.multipleimage.open', function(event, callback) {
            var options = {
                callback: callback,
                multiple: true,
                setshowname: true
            };
            mediagallery.open($scope.siteId, options);
        });
        // 切换编辑的页面
        $scope.$watch('ep', function(newPage) {
            if (!newPage) return;
            $scope.setActiveWrap(null);
            // page's content
            if (tinymceEditor) {
                var oldPage = editorProxy.getPage();
                if (oldPage) {
                    oldPage.html = editorProxy.purifyPage({
                        type: oldPage.type,
                        html: tinymceEditor.getContent()
                    });
                }
                editorProxy.load(tinymceEditor, newPage);
            }
            // page's buttons
            var buttons = [],
                button, btnName;
            for (btnName in schemaLib.buttons) {
                if (btnName === 'addRecord') continue;
                button = schemaLib.buttons[btnName];
                if (button.scope && button.scope.indexOf(newPage.type) !== -1) {
                    buttons.push(button);
                }
            }
            $scope.buttons = buttons;
        });
        $scope.$on('tinymce.instance.init', function(event, editor) {
            tinymceEditor = editor;
            if ($scope.ep) {
                editorProxy.load(editor, $scope.ep);
            }
        });
    }]);
    /**
     * 在当前编辑页面中选择应用的登记项
     */
    ngApp.provider.controller('ctrlAppSchemas4Input', ['$scope', function($scope) {
        var pageSchemas = $scope.ep.data_schemas,
            appSchemas = $scope.app.data_schemas,
            chooseState = {};

        pageSchemas.forEach(function(dataWrap) {
            if (dataWrap.schema) {
                chooseState[dataWrap.schema.id] = true;
            } else {
                console.error('page[' + $scope.ep.name + '] schema not exist', dataWrap);
            }
        });

        $scope.appSchemas = appSchemas;
        $scope.chooseState = chooseState;
        $scope.choose = function(schema) {
            if (chooseState[schema.id]) {
                $scope.$emit('xxt.matter.signin.page.data_schemas.requestAdd', schema);
            } else {
                $scope.$emit('xxt.matter.signin.page.data_schemas.requestRemove', schema);
            }
        };
        $scope.$on('xxt.matter.signin.page.data_schemas.removed', function(event, removedSchema) {
            chooseState[removedSchema.id] = false;
        });
        $scope.$on('xxt.matter.signin.page.data_schemas.added', function(event, addedSchema) {
            chooseState[addedSchema.id] = true;
        });
    }]);
    /**
     * view
     */
    ngApp.provider.controller('ctrlAppSchemas4View', ['$scope', function($scope) {
        var pageSchemas = $scope.ep.data_schemas,
            chooseState = {};

        $scope.appSchemas = $scope.app.data_schemas;
        $scope.otherSchemas = [{
            id: 'enrollAt',
            type: '_enrollAt',
            title: '登记时间'
        }];
        pageSchemas.forEach(function(config) {
            config.schema && config.schema.id && (chooseState[config.schema.id] = true);
        });
        chooseState['enrollAt'] === undefined && (chooseState['enrollAt'] = false);
        $scope.chooseState = chooseState;
        $scope.choose = function(schema) {
            if (chooseState[schema.id]) {
                editorProxy.appendSchema(schema);
            } else {
                $scope.$emit('xxt.matter.signin.page.data_schemas.requestRemove', schema);
            }
        };
        $scope.$on('xxt.matter.signin.page.data_schemas.removed', function(event, removedSchema) {
            chooseState[removedSchema.id] = false;
        });
    }]);
    /**
     * 登记项编辑
     */
    ngApp.provider.controller('ctrlInputWrap', ['$scope', '$timeout', function($scope, $timeout) {
        $scope.addOption = function() {
            var newOp;
            newOp = schemaLib.addOption($scope.activeWrap.schema);
            $timeout(function() {
                $scope.$broadcast('xxt.editable.add', newOp);
            });
        };
        $scope.onKeyup = function(event) {
            // 回车时自动添加选项
            if (event.keyCode === 13) {
                $scope.addOption();
            }
        };
        $scope.$on('xxt.editable.changed', function(e, op) {
            $scope.updWrap();
        });
        $scope.$on('xxt.editable.remove', function(e, op) {
            var schema = $scope.activeWrap.schema,
                i = schema.ops.indexOf(op);

            schema.ops.splice(i, 1);
            $scope.updWrap();
        });
        $scope.$watch('activeWrap.schema.setUpper', function(nv) {
            var schema = $scope.activeWrap.schema;
            if (nv === 'Y') {
                schema.upper = schema.ops ? schema.ops.length : 0;
            }
        });
        $scope.updWrap = function() {
            editorProxy.modifySchema($scope.activeWrap);
            $scope.$emit('xxt.matter.signin.app.data_schemas.modified', {
                originator: $scope.ep,
                schema: $scope.activeWrap.schema
            });
        };
        if ($scope.activeWrap.schema.type === 'member') {
            if ($scope.activeWrap.schema.schema_id) {
                (function() {
                    var i, j, memberSchema, schema;
                    /*自定义用户*/
                    for (i = $scope.memberSchemas.length - 1; i >= 0; i--) {
                        memberSchema = $scope.memberSchemas[i];
                        if ($scope.activeWrap.schema.schema_id === memberSchema.id) {
                            for (j = memberSchema._schemas.length - 1; j >= 0; j--) {
                                schema = memberSchema._schemas[j];
                                if ($scope.activeWrap.schema.id === schema.id) {
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
    }]);
    /**
     * value wrap controller
     */
    ngApp.provider.controller('ctrlValueWrap', ['$scope', function($scope) {
        $scope.updWrap = function(obj, prop) {
            editorProxy.modifySchema($scope.activeWrap);
        };
    }]);
    /**
     * button wrap controller
     */
    ngApp.provider.controller('ctrlButtonWrap', ['$scope', function($scope) {
        var targetPages = {},
            inputPages = {},
            schema = $scope.activeWrap.schema;

        $scope.$watch('app', function(app) {
            if (!app) return;
            app.pages.forEach(function(page) {
                targetPages[page.name] = {
                    l: page.title
                };
                if (page.type === 'I') {
                    inputPages[page.name] = {
                        l: page.title
                    };
                }
            });
        });
        targetPages.closeWindow = {
            l: '关闭页面'
        };
        $scope.pages = targetPages;
        $scope.inputPages = inputPages;
        $scope.choose = function() {
            var names;
            schema.label = $scope.buttons[schema.name].l;
            schema.next = '';
            if (['addRecord', 'editRecord', 'removeRecord'].indexOf(schema.name) !== -1) {
                names = Object.keys(inputPages);
                if (names.length === 0) {
                    alert('没有类型为“填写页”的页面');
                } else {
                    schema.next = names[0];
                }
            }
        };
        /*直接给带有导航功能的按钮创建页面*/
        $scope.newPage = function(prop) {
            $scope.createPage().then(function(page) {
                targetPages[page.name] = {
                    l: page.title
                };
                if (page.type === 'I') {
                    inputPages[page.name] = {
                        l: page.title
                    };
                }
                schema[prop] = page.name;
            });
        };
        /*更新按钮定义*/
        $scope.updWrap = function(obj, prop) {
            editorProxy.modifyButton($scope.activeWrap);
        };
    }]);
});