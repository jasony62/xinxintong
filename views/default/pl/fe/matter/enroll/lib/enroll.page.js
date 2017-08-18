define(['require', 'schema', 'wrap', 'editor'], function(require, schemaLib, wrapLib, editorProxy) {
    'use strict';
    var ngMod = angular.module('page.enroll', []);
    /**
     * page editor
     */
    ngMod.controller('ctrlPageEdit', ['$scope', 'cstApp', 'mediagallery', 'mattersgallery', function($scope, cstApp, mediagallery, mattersgallery) {
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
            var oSchema, domWrap, pages;
            oSchema = angular.copy(btn);
            pages = $scope.app.pages;
            switch (oSchema.n) {
                case 'submit':
                    var oFirstViewPage;
                    for (var i = pages.length - 1; i >= 0; i--) {
                        if (pages[i].type === 'V') {
                            oFirstViewPage = pages[i];
                            break;
                        }
                    }
                    if (oFirstViewPage) {
                        oSchema.next = oFirstViewPage.name;
                    } else {
                        var oFirstListPage;
                        for (var i = pages.length - 1; i >= 0; i--) {
                            if (pages[i].type === 'L') {
                                oFirstListPage = pages[i];
                                break;
                            }
                        }
                        if (oFirstListPage) {
                            oSchema.next = oFirstListPage.name;
                        }
                    }
                    break;
                case 'addRecord':
                case 'editRecord':
                    var oFirstInputPage;
                    for (var i = pages.length - 1; i >= 0; i--) {
                        if (pages[i].type === 'I') {
                            oFirstInputPage = pages[i];
                            break;
                        }
                    }
                    if (oFirstInputPage) {
                        oSchema.next = oFirstInputPage.name;
                    }
                    break;
                default:
                    oSchema.next = '';
            }
            domWrap = editorProxy.appendButton(oSchema);
            $scope.setActiveWrap(domWrap);
        };
        $scope.newList = function() {
            var domWrap;
            domWrap = editorProxy.appendRecordList($scope.app);
            $scope.setActiveWrap(domWrap);
        };
        $scope.enrolleeList = function() {
            var enrolleeWrap;
            if($scope.app.entry_rule.scope=='member') {
                enrolleeWrap = editorProxy.appendEnrollee($scope.app, $scope.memberSchemas);
            } else {
                enrolleeWrap = editorProxy.appendEnrollee($scope.app);
            }
            $scope.setActiveWrap(enrolleeWrap);
        }
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
            } else if (/records|enrollees/.test(wrapType)) {
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
                    $scope.ep.$$modified = true;
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
            for (var i = $scope.app.dataSchemas.length - 1; i >= 0; i--) {
                if (schemaId === $scope.app.dataSchemas[i].id) {
                    schema = $scope.app.dataSchemas[i];
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
        $scope.$watch('ep', function(oNewPage) {
            if (!oNewPage) return;
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
                editorProxy.load(tinymceEditor, oNewPage);
            }
            // page's buttons
            var buttons = {},
                button, btnName;
            for (btnName in schemaLib.buttons) {
                button = schemaLib.buttons[btnName];
                if (button.scope && button.scope.indexOf(oNewPage.type) !== -1) {
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
    ngMod.controller('ctrlAppSchemas4IV', ['$scope', function($scope) {
        var oChooseState;
        $scope.choose = function(schema) {
            if (oChooseState[schema.id]) {
                var ia, sibling, domNewWrap;
                ia = $scope.app.dataSchemas.indexOf(schema);
                if (ia === 0) {
                    sibling = $scope.app.dataSchemas[++ia];
                    while (ia < $scope.app.dataSchemas.length && !oChooseState[sibling.id]) {
                        sibling = $scope.app.dataSchemas[++ia];
                    }
                    domNewWrap = editorProxy.appendSchema(schema, sibling, true);
                } else {
                    sibling = $scope.app.dataSchemas[--ia];
                    while (ia > 0 && !oChooseState[sibling.id]) {
                        sibling = $scope.app.dataSchemas[--ia];
                    }
                    if (oChooseState[sibling.id]) {
                        domNewWrap = editorProxy.appendSchema(schema, sibling);
                    } else {
                        ia = $scope.app.dataSchemas.indexOf(schema);
                        sibling = $scope.app.dataSchemas[++ia];
                        while (ia < $scope.app.dataSchemas.length && !oChooseState[sibling.id]) {
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
                oChooseState[removedSchema.id] = false;
            }
        });
        $scope.$watch('ep', function(oPage) {
            if (oPage) {
                oChooseState = {};
                if(!$scope.app) return;
                $scope.app.dataSchemas.forEach(function(schema) {
                    oChooseState[schema.id] = false;
                });
                if ($scope.ep.type === 'I') {
                    $scope.ep.data_schemas.forEach(function(dataWrap) {
                        if (dataWrap.schema) {
                            oChooseState[dataWrap.schema.id] = true;
                        }
                    });
                } else if ($scope.ep.type === 'V') {
                    $scope.otherSchemas = [{
                        id: 'enrollAt',
                        type: '_enrollAt',
                        title: '填写时间'
                    }];
                    $scope.ep.data_schemas.forEach(function(config) {
                        config.schema && config.schema.id && (oChooseState[config.schema.id] = true);
                    });
                    oChooseState['enrollAt'] === undefined && (oChooseState['enrollAt'] = false);
                }
                $scope.chooseState = oChooseState;
            }
        });
    }]);
    /**
     * 登记项编辑
     */
    ngMod.controller('ctrlInputWrap', ['$scope', '$timeout', function($scope, $timeout) {
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
     * value wrap
     */
    ngMod.controller('ctrlValueWrap', ['$scope', function($scope) {
        $scope.updWrap = function() {
            editorProxy.modifySchema($scope.activeWrap);
        };
    }]);
    /**
     * record list wrap
     */
    ngMod.controller('ctrlRecordListWrap', ['$scope', '$timeout', function($scope, $timeout) {
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
    /*
       enrollee list wrap
     */
    ngMod.controller('ctrlEnrolleeListWrap', ['$scope', '$timeout', function($scope, $timeout) {
        var listSchemas = $scope.activeWrap.schemas,
            memberSchemas = $scope.memberSchemas,
            config = $scope.activeWrap.config,
            chooseState = {};
        $scope.otherMschemas = [{
            id: 'group.l',
            title: '所属分组',
            type: 'enrollee'
        }];
        if($scope.app.entry_rule.scope=='sns') {
            $scope.mschemas = [{
                id: 'nickname',
                title: '昵称',
                type: 'sns'
            },{
                id: 'headimgurl',
                title: '头像',
                type: 'sns'
            }]
        }else{
            for(var i=$scope.otherMschemas.length-1; i>=0; i--) {
                if($scope.otherMschemas[i].id == 'schema_title') {
                    break;
                }else {
                    $scope.otherMschemas.push({
                        id: 'schema_title',
                        title: '所属通讯录',
                        type: 'address'
                    });
                    break;
                }
            }
            if($scope.activeWrap.config.mschemaId!==''){
                $scope.mschemas = [];
                memberSchemas.forEach(function(item) {
                    if(item.id == $scope.activeWrap.config.mschemaId) {
                        $scope.mschemas = item._mschemas;
                    }
                });
            }
        }
        $scope.doFilter = function(id) {
            memberSchemas.forEach(function(item) {
                if(item.id == id) {
                    config.mschemaId = id;
                    $scope.activeWrap.schemas = $scope.otherMschemas;
                    item._mschemas.forEach(function(ms) {
                        $scope.activeWrap.schemas.unshift(ms);
                        $scope.activeWrap.dom.classList.remove('active');
                    });
                }
            });
            $scope.updWrap();
        }
        listSchemas.forEach(function(schema) {
            chooseState[schema.id] = true;
        });
        $scope.chooseState = chooseState;
        /* 在处理activeSchema中提交 */
        $scope.choose = function(schema) {
            if (chooseState[schema.id]) {
                var ia, ibl, brother, domNewWrap;
                ia = $scope.mschemas.indexOf(schema);
                if (ia === 0) {
                    listSchemas.splice(0, 0, schema);
                } else {
                    brother = $scope.mschemas[--ia];
                    while (ia > 0 && !chooseState[brother.id]) {
                        brother = $scope.mschemas[--ia];
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
     * button wrap
     */
    ngMod.controller('ctrlButtonWrap', ['$scope', function($scope) {
        var oActiveSchema, appPages, nextPages;

        oActiveSchema = $scope.activeWrap.schema;
        appPages = $scope.app.pages;
        $scope.nextPages = nextPages = [];
        if ($scope.buttons[oActiveSchema.name].next) {
            appPages.forEach(function(oPage) {
                if ($scope.buttons[oActiveSchema.name].next.indexOf(oPage.type) !== -1) {
                    nextPages.push({ name: oPage.name, title: oPage.title });
                }
            });
        } else {
            appPages.forEach(function(oPage) {
                nextPages.push({ name: oPage.name, title: oPage.title });
            });
        }
        $scope.chooseType = function() {
            oActiveSchema.label = $scope.buttons[oActiveSchema.name].l;
            oActiveSchema.next = '';
            if (['addRecord', 'editRecord', 'removeRecord'].indexOf(oActiveSchema.name) !== -1) {
                for (var i = 0, ii = appPages.length; i < ii; i++) {
                    if (appPages[i].type === 'I') {
                        oActiveSchema.next = appPages[i].name;
                        break;
                    }
                }
                if (i === ii) alert('没有类型为“填写页”的页面');
            }
            editorProxy.modifyButton($scope.activeWrap);
        };
        $scope.updWrap = function() {
            editorProxy.modifyButton($scope.activeWrap);
        };
    }]);
});
