define(['frame', 'schema', 'page', 'editor'], function(ngApp, schemaLib, pageLib, editorProxy) {
    'use strict';
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlPage', ['$scope', '$location', 'srvEnrollApp', 'srvEnrollPage', function($scope, $location, srvEnrollApp, srvEnrollPage) {
        $scope.ep = null;
        window.onbeforeunload = function(e) {
            var message;
            if ($scope.ep && $scope.ep.$$modified) {
                message = '已经修改的页面还没有保存，确定离开？';
                e = e || window.event;
                if (e) {
                    e.returnValue = message;
                }
                return message;
            }
        };
        $scope.addPage = function() {
            $('body').click();
            srvEnrollPage.create().then(function(page) {
                $scope.choosePage(page);
            });
        };
        $scope.updPage = function(page, props) {
            if (props.indexOf('html') !== -1) {
                /* 如果是当前编辑页面 */
                if (page === $scope.ep) {
                    page.html = editorProxy.getEditor().getContent();
                }
                editorProxy.purifyPage(page, true);
            }

            return srvEnrollPage.update(page, props);
        };
        $scope.delPage = function() {
            $('body').click();
            if (window.confirm('确定删除页面【' + $scope.ep.title + '】？')) {
                srvEnrollPage.remove($scope.ep).then(function(pages) {
                    $scope.choosePage(pages.length ? pages[0] : null);
                });
            }
        };
        $scope.cleanPage = function() {
            $('body').click();
            if (window.confirm('确定清除页面【' + $scope.ep.title + '】的所有内容？')) {
                srvEnrollPage.clean($scope.ep).then(function() {
                    editorProxy.getEditor().setContent('');
                });
            }
        };
        $scope.choosePage = function(page) {
            var pages = $scope.app.pages;
            if (angular.isString(page)) {
                for (var i = pages.length - 1; i >= 0; i--) {
                    if (pages[i].name === page) {
                        page = pages[i];
                        break;
                    }
                }
                if (i === -1) return false;
            }
            return $scope.ep = page;
        };
        $scope.$on('xxt.matter.enroll.app.data_schemas.modified', function(event, state) {
            var originator = state.originator,
                modifiedSchema = state.schema;

            $scope.app.pages.forEach(function(page) {
                if (originator === $scope.ep && page !== $scope.ep) {
                    page.updateSchema(modifiedSchema);
                }
            });
        });
        //??? 提交前如何检查数据的一致性？
        $scope.save = function() {
            // 更新应用
            srvEnrollApp.update('data_schemas').then(function() {
                // 更新页面
                $scope.app.pages.forEach(function(page) {
                    $scope.updPage(page, ['data_schemas', 'act_schemas', 'html']);
                });
            });
        };
        $scope.gotoCode = function() {
            window.open('/rest/pl/fe/code?site=' + $scope.app.siteid + '&name=' + $scope.ep.code_name, '_self');
        };
        srvEnrollApp.get().then(function(app) {
            var pageName;
            if (pageName = $location.search().page) {
                $scope.choosePage(pageName);
            }
            if (!$scope.ep) $scope.ep = app.pages[0];
        });
    }]);
    /**
     * page editor
     */
    ngApp.provider.controller('ctrlPageEdit', ['$scope', '$q', '$timeout', 'cstApp', 'mediagallery', 'mattersgallery', function($scope, $q, $timeout, cstApp, mediagallery, mattersgallery) {
        var tinymceEditor;
        $scope.activeWrap = false;
        $scope.setActiveWrap = function(domWrap) {
            var activeWrap;
            $scope.activeWrap = editorProxy.setActiveWrap(domWrap);
            activeWrap = $scope.activeWrap;
        };
        $scope.wrapEditorHtml = function() {
            var url = null;
            if ($scope.activeWrap.type) {
                url = '/views/default/pl/fe/matter/enroll/wrap/' + $scope.activeWrap.type + '.html?_=' + (new Date()).getMinutes();
            }
            return url;
        };
        $scope.refreshWrap = function(wrap) {
            if ('phase' === wrap.schema.type) {
                // 更新项目阶段
                var ops = [];
                $scope.app.mission.phases.forEach(function(phase) {
                    ops.push({
                        l: phase.title,
                        v: phase.phase_id
                    });
                });
                wrap.schema.ops = ops;
            }
            editorProxy.modifySchema(wrap);
        };
        $scope.newButton = function(btn) {
            var domWrap = editorProxy.appendButton(btn);
            $scope.setActiveWrap(domWrap);
        };
        $scope.newList = function() {
            var domWrap;
            domWrap = editorProxy.appendRecordList($scope.app);
            $scope.setActiveWrap(domWrap);
        };
        $scope.removeActiveWrap = function() {
            var activeWrap = $scope.activeWrap,
                wrapType = activeWrap.type,
                schema;

            if (/input|value/.test(wrapType)) {
                editorProxy.removeWrap(activeWrap);
                if (/I|V/.test($scope.ep.type)) {
                    schema = activeWrap.schema
                    $scope.$broadcast('xxt.matter.enroll.page.data_schemas.removed', schema);
                }
                $scope.setActiveWrap(null);
            } else if (/radio|checkbox|score/.test(wrapType)) {
                var optionSchema;
                schema = editorProxy.optionSchemaByDom(activeWrap.dom, $scope.app);
                optionSchema = schema[1];
                schema = schema[0];
                schema.ops.splice(schema.ops.indexOf(optionSchema), 1);
                // 更新当前页面
                editorProxy.removeWrap(activeWrap);
                // 更新其它页面
                $scope.$emit('xxt.matter.enroll.app.data_schemas.modified', {
                    originator: $scope.ep,
                    schema: schema
                });
                $scope.setActiveWrap(null);
            } else if (/records/.test(wrapType)) {
                editorProxy.removeWrap(activeWrap);
                for (var i = $scope.ep.data_schemas.length - 1; i >= 0; i--) {
                    if ($scope.ep.data_schemas[i].config.id === activeWrap.config.id) {
                        $scope.ep.data_schemas.splice(i, 1);
                        break;
                    }
                }
                $scope.setActiveWrap(null);
            } else if (/button|text/.test(wrapType)) {
                editorProxy.removeWrap(activeWrap);
                $scope.setActiveWrap(null);
            }
        };
        $scope.moveWrap = function(action) {
            $scope.activeWrap = editorProxy.moveWrap(action);
        };
        $scope.embedMatter = function(page) {
            var options = {
                matterTypes: cstApp.innerlink,
                singleMatter: true
            };
            if ($scope.app.mission) {
                options.mission = $scope.app.mission;
            }
            mattersgallery.open($scope.app.siteid, function(matters, type) {
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
                        fn = "openMatter('" + matter.id + "','" + type + "')";
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
                        fn = "openMatter('" + matter.id + "','" + type + "')";
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
                $scope.$emit('xxt.matter.enroll.app.data_schemas.modified', {
                    originator: $scope.ep,
                    schema: $scope.activeWrap.schema || status.schema
                });
            }
        });
        //添加选项
        $scope.$on('tinymce.option.add', function(event, domWrap) {
            var optionDom, schemaOptionId, schemaId, schema;

            if (/radio|checkbox/.test(domWrap.getAttribute('wrap'))) {
                optionDom = domWrap.querySelector('input');
                schemaId = optionDom.getAttribute('name');
                if (/radio/.test(domWrap.getAttribute('wrap'))) {
                    schemaOptionId = optionDom.getAttribute('value');
                } else if (/checkbox/.test(domWrap.getAttribute('wrap'))) {
                    schemaOptionId = optionDom.getAttribute('ng-model');
                    schemaOptionId = schemaOptionId.split('.')[2];
                }
            } else if (/score/.test(domWrap.getAttribute('wrap'))) {
                schemaId = domWrap.parentNode.parentNode.getAttribute('schema');
                schemaOptionId = domWrap.getAttribute('opvalue');
            }
            for (var i = $scope.app.data_schemas.length - 1; i >= 0; i--) {
                if (schemaId === $scope.app.data_schemas[i].id) {
                    schema = $scope.app.data_schemas[i];
                    if (schema.ops) {
                        var newOp;
                        newOp = schemaLib.addOption(schema, schemaOptionId);
                        editorProxy.addOptionWrap(domWrap, schema, newOp);
                    }
                    break;
                }
            }
        });
        $scope.$on('tinymce.wrap.add', function(event, domWrap) {
            $scope.$apply(function() {
                $scope.setActiveWrap(domWrap);
            });
        });
        $scope.$on('tinymce.wrap.select', function(event, domWrap) {
            $scope.$apply(function() {
                $scope.setActiveWrap(domWrap);
            });
        });
        $scope.$on('tinymce.multipleimage.open', function(event, callback) {
            var options = {
                callback: callback,
                multiple: true,
                setshowname: true
            };
            mediagallery.open($scope.app.siteid, options);
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
            var buttons = {},
                button, btnName;
            for (btnName in schemaLib.buttons) {
                button = schemaLib.buttons[btnName];
                if (button.scope && button.scope.indexOf(newPage.type) !== -1) {
                    buttons[btnName] = button;
                }
            }
            $scope.buttons = buttons;
        });
        $scope.$on('tinymce.instance.init', function(event, editor) {
            tinymceEditor = editor;
            if ($scope.ep) {
                editorProxy.load(editor, $scope.ep);
            } else {
                editorProxy.setPage(null);
            }
        });
    }]);
    /**
     * input
     */
    ngApp.provider.controller('ctrlAppSchemas4IV', ['$scope', function($scope) {
        var chooseState = {};

        $scope.app.data_schemas.forEach(function(schema) {
            chooseState[schema.id] = false;
        });
        if ($scope.ep.type === 'I') {
            $scope.ep.data_schemas.forEach(function(dataWrap) {
                if (dataWrap.schema) {
                    chooseState[dataWrap.schema.id] = true;
                }
            });
        } else if ($scope.ep.type === 'V') {
            $scope.otherSchemas = [{
                id: 'enrollAt',
                type: '_enrollAt',
                title: '填写时间'
            }];
            $scope.ep.data_schemas.forEach(function(config) {
                config.schema && config.schema.id && (chooseState[config.schema.id] = true);
            });
            chooseState['enrollAt'] === undefined && (chooseState['enrollAt'] = false);
        }
        $scope.chooseState = chooseState;
        $scope.choose = function(schema) {
            $scope.ep.$$modified = true;
            if (chooseState[schema.id]) {
                var ia, sibling, domNewWrap;
                ia = $scope.app.dataSchemas.indexOf(schema);
                if (ia === 0) {
                    sibling = $scope.app.dataSchemas[++ia];
                    while (ia < $scope.app.dataSchemas.length && !chooseState[sibling.id]) {
                        sibling = $scope.app.dataSchemas[++ia];
                    }
                    domNewWrap = editorProxy.appendSchema(schema, sibling, true);
                } else {
                    sibling = $scope.app.dataSchemas[--ia];
                    while (ia > 0 && !chooseState[sibling.id]) {
                        sibling = $scope.app.dataSchemas[--ia];
                    }
                    if (chooseState[sibling.id]) {
                        domNewWrap = editorProxy.appendSchema(schema, sibling);
                    } else {
                        ia = $scope.app.dataSchemas.indexOf(schema);
                        sibling = $scope.app.dataSchemas[++ia];
                        while (ia < $scope.app.dataSchemas.length && !chooseState[sibling.id]) {
                            sibling = $scope.app.dataSchemas[++ia];
                        }
                        domNewWrap = editorProxy.appendSchema(schema, sibling, true);
                    }
                }
                $scope.setActiveWrap(domNewWrap);
                editorProxy.scroll(domNewWrap);
            } else {
                if (editorProxy.removeSchema(schema)) {
                    if ($scope.activeWrap && schema.id === $scope.activeWrap.schema.id) {
                        $scope.setActiveWrap(null);
                    }
                }
            }
        };
        $scope.$on('xxt.matter.enroll.page.data_schemas.removed', function(event, removedSchema) {
            if (removedSchema && removedSchema.id) {
                chooseState[removedSchema.id] = false;
            }
        });
    }]);
    /**
     * record list wrap controller
     */
    ngApp.provider.controller('ctrlRecordListWrap', ['$scope', '$timeout', function($scope, $timeout) {
        var listSchemas = $scope.activeWrap.schemas,
            chooseState = {};
        $scope.otherSchemas = [{
            id: 'enrollAt',
            type: '_enrollAt',
            title: '填写时间'
        }];
        $scope.app.dataSchemas.forEach(function(schema) {
            chooseState[schema.id] = false;
        });
        listSchemas.forEach(function(schema) {
            chooseState[schema.id] = true;
        });
        $scope.chooseState = chooseState;
        /* 在处理activeSchema中提交 */
        $scope.choose = function(schema) {
            $scope.ep.$$modified = true;
            if (chooseState[schema.id]) {
                var ia, ibl, brother, domNewWrap;
                ia = $scope.app.dataSchemas.indexOf(schema);
                if (ia === 0) {
                    listSchemas.splice(0, 0, schema);
                } else {
                    brother = $scope.app.dataSchemas[--ia];
                    while (ia > 0 && !chooseState[brother.id]) {
                        brother = $scope.app.dataSchemas[--ia];
                    }
                    for (var ibl = listSchemas.length - 1; ibl >= 0; ibl--) {
                        if (listSchemas[ibl].id === brother.id) {
                            break;
                        }
                    }
                    listSchemas.splice(ibl + 1, 0, schema);
                }
            } else {
                for (var i = listSchemas.length - 1; i >= 0; i--) {
                    if (schema.id === listSchemas[i].id) {
                        listSchemas.splice(i, 1);
                        break;
                    }
                }
            }
            $scope.updWrap();
        };
        $scope.updWrap = function() {
            editorProxy.modifySchema($scope.activeWrap);
        };
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
            $scope.$emit('xxt.matter.enroll.app.data_schemas.modified', {
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
        $scope.updWrap = function() {
            editorProxy.modifySchema($scope.activeWrap);
        };
    }]);
    /**
     * button wrap controller
     */
    ngApp.provider.controller('ctrlButtonWrap', ['$scope', 'srvEnrollPage', function($scope, srvEnrollPage) {
        var schema = $scope.activeWrap.schema;

        $scope.chooseType = function() {
            schema.label = $scope.buttons[schema.name].l;
            schema.next = '';
            if (['addRecord', 'editRecord', 'removeRecord'].indexOf(schema.name) !== -1) {
                for (var i = 0, ii = $scope.app.pages.length; i < ii; i++) {
                    if ($scope.app.pages[i].type === 'I') {
                        schema.next = $scope.app.pages[i].name;
                        break;
                    }
                }
                if (i === ii) alert('没有类型为“填写页”的页面');
            }
            editorProxy.modifyButton($scope.activeWrap);
        };
        /* 直接给带有导航功能的按钮创建页面 */
        $scope.newPage = function(prop) {
            srvEnrollPage.create().then(function(page) {
                schema[prop] = page.name;
            });
        };
        $scope.updWrap = function(obj, prop) {
            editorProxy.modifyButton($scope.activeWrap);
        };
    }]);
});
