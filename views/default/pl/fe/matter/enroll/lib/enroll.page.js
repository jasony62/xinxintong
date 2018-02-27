define(['require', 'page', 'schema', 'wrap', 'editor'], function(require, pageLib, schemaLib, wrapLib, editorProxy) {
    'use strict';
    var ngMod = angular.module('page.enroll', []);
    /**
     * app's pages
     */
    ngMod.provider('srvEnrollPage', function() {
        var _siteId, _appId, _matterType, _baseUrl;

        _matterType = window.MATTER_TYPE.toLowerCase();
        _baseUrl = '/rest/pl/fe/matter/' + _matterType + '/page/';
        this.config = function(siteId, appId) {
            _siteId = siteId;
            _appId = appId;
        };
        this.$get = ['$uibModal', '$q', 'http2', 'noticebox', 'srv' + window.MATTER_TYPE + 'App', function($uibModal, $q, http2, noticebox, srvApp) {
            var _self;
            _self = {
                create: function() {
                    var deferred = $q.defer();
                    srvApp.get().then(function(app) {
                        $uibModal.open({
                            templateUrl: '/views/default/pl/fe/matter/enroll/component/createPage.html?_=3',
                            backdrop: 'static',
                            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                                $scope.options = {};
                                $scope.ok = function() {
                                    $mi.close($scope.options);
                                };
                                $scope.cancel = function() {
                                    $mi.dismiss();
                                };
                            }],
                        }).result.then(function(options) {
                            http2.post(_baseUrl + 'add?site=' + _siteId + '&app=' + _appId, options, function(rsp) {
                                var page = rsp.data;
                                pageLib.enhance(page);
                                app.pages.push(page);
                                deferred.resolve(page);
                            });
                        });
                    });
                    return deferred.promise;
                },
                update: function(page, names) {
                    var defer = $q.defer(),
                        updated = {},
                        url;

                    angular.isString(names) && (names = [names]);
                    names.forEach(function(name) {
                        if (name === 'html') {
                            updated.html = encodeURIComponent(page.html);
                        } else {
                            updated[name] = page[name];
                        }
                    });
                    url = _baseUrl + '/update';
                    url += '?site=' + _siteId;
                    url += '&app=' + _appId;
                    url += '&page=' + page.id;
                    url += '&cname=' + page.code_name;
                    http2.post(url, updated, function(rsp) {
                        page.$$modified = false;
                        defer.resolve();
                        noticebox.success('完成保存');
                    });

                    return defer.promise;
                },
                clean: function(page) {
                    page.html = '';
                    page.data_schemas = [];
                    page.act_schemas = [];
                    page.user_schemas = [];
                    return _self.update(page, ['data_schemas', 'act_schemas', 'user_schemas', 'html']);
                },
                remove: function(page) {
                    var defer = $q.defer();
                    srvApp.get().then(function(app) {
                        var url = _baseUrl + 'remove';
                        url += '?site=' + _siteId;
                        url += '&app=' + _appId;
                        url += '&pid=' + page.id;
                        url += '&cname=' + page.code_name;
                        http2.get(url, function(rsp) {
                            app.pages.splice(app.pages.indexOf(page), 1);
                            defer.resolve(app.pages);
                            noticebox.success('完成删除');
                        });
                    });
                    return defer.promise;
                },
                repair: function(aCheckResult, oPage) {
                    return $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/repair.html',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            $scope2.reason = aCheckResult[1];
                            $scope2.ok = function() {
                                $mi.close();
                            };
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                        }],
                        backdrop: 'static'
                    }).result.then(function() {
                        var aRepairResult;
                        aRepairResult = oPage.repair(aCheckResult);
                        if (aRepairResult[0] === true) {
                            if (aRepairResult[1] && aRepairResult[1].length) {
                                aRepairResult[1].forEach(function(changedProp) {
                                    switch (changedProp) {
                                        case 'data_schemas':
                                            // do nothing
                                            break;
                                        case 'html':
                                            if (oPage === editorProxy.getPage()) {
                                                editorProxy.refresh();
                                            }
                                            break;
                                    }
                                });
                            }
                        }
                    });
                }
            };
            return _self;
        }];
    });
    /**
     * page editor
     */
    ngMod.controller('ctrlPageEdit', ['$scope', 'cstApp', 'mediagallery', 'srvSite', function($scope, cstApp, mediagallery, srvSite) {
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
            enrolleeWrap = editorProxy.appendEnrollee($scope.app);
            $scope.setActiveWrap(enrolleeWrap);
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
            srvSite.openGallery(options).then(function(result) {
                var dom = tinymceEditor.dom,
                    style = "cursor:pointer",
                    fn, domMatter, sibling;

                if ($scope.activeWrap) {
                    sibling = $scope.activeWrap.dom;
                    while (sibling.parentNode !== tinymceEditor.getBody()) {
                        sibling = sibling.parentNode;
                    }
                    // 加到当前选中元素的后面
                    result.matters.forEach(function(matter) {
                        fn = "openMatter('" + matter.id + "','" + result.type + "')";
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
                    result.matters.forEach(function(matter) {
                        fn = "openMatter('" + matter.id + "','" + result.type + "')";
                        domMatter = dom.add(tinymceEditor.getBody(), 'div', {
                            'wrap': 'matter',
                            'class': 'form-group',
                        }, dom.createHTML('span', {
                            "style": style,
                            "ng-click": fn
                        }, dom.encode(matter.title)));
                    });
                }
            })
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
        var _oChooseState;
        $scope.choose = function(schema) {
            if (_oChooseState[schema.id]) {
                var ia, sibling, domNewWrap;
                ia = $scope.app.dataSchemas.indexOf(schema);
                if (ia === 0) {
                    sibling = $scope.app.dataSchemas[++ia];
                    while (ia < $scope.app.dataSchemas.length && !_oChooseState[sibling.id]) {
                        sibling = $scope.app.dataSchemas[++ia];
                    }
                    domNewWrap = editorProxy.appendSchema(schema, sibling, true);
                } else {
                    sibling = $scope.app.dataSchemas[--ia];
                    while (ia > 0 && !_oChooseState[sibling.id]) {
                        sibling = $scope.app.dataSchemas[--ia];
                    }
                    if (sibling) {
                        if (_oChooseState[sibling.id]) {
                            domNewWrap = editorProxy.appendSchema(schema, sibling);
                        } else {
                            ia = $scope.app.dataSchemas.indexOf(schema);
                            sibling = $scope.app.dataSchemas[++ia];
                            while (ia < $scope.app.dataSchemas.length && !_oChooseState[sibling.id]) {
                                sibling = $scope.app.dataSchemas[++ia];
                            }
                            domNewWrap = editorProxy.appendSchema(schema, sibling, true);
                        }
                    } else {
                        domNewWrap = editorProxy.appendSchema(schema);
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
                _oChooseState[removedSchema.id] = false;
            }
        });
        $scope.$watch('ep', function(oPage) {
            if (oPage) {
                _oChooseState = {};
                if (!$scope.app) return;
                $scope.app.dataSchemas.forEach(function(schema) {
                    _oChooseState[schema.id] = false;
                });
                if (oPage.type === 'I') {
                    oPage.data_schemas.forEach(function(dataWrap) {
                        if (dataWrap.schema) {
                            _oChooseState[dataWrap.schema.id] = true;
                        }
                    });
                } else if (oPage.type === 'V') {
                    $scope.otherSchemas = [{
                        id: 'enrollAt',
                        type: '_enrollAt',
                        title: '填写时间'
                    }, {
                        id: 'roundTitle',
                        type: '_roundTitle',
                        title: '填写轮次'
                    }];
                    oPage.data_schemas.forEach(function(config) {
                        config.schema && config.schema.id && (_oChooseState[config.schema.id] = true);
                    });
                    _oChooseState['enrollAt'] === undefined && (_oChooseState['enrollAt'] = false);
                    _oChooseState['roundTitle'] === undefined && (_oChooseState['roundTitle'] = false);
                }
                $scope.chooseState = _oChooseState;
            }
            $scope.$watchCollection('ep.data_schemas', function(newVal) {
                var aUncheckedSchemaIds;
                if (/I|V/.test($scope.ep.type)) {
                    if (newVal) {
                        aUncheckedSchemaIds = Object.keys(_oChooseState);
                        newVal.forEach(function(oWrap) {
                            var i;
                            _oChooseState[oWrap.schema.id] = true;
                            i = aUncheckedSchemaIds.indexOf(oWrap.schema.id);
                            if (i !== -1) {
                                aUncheckedSchemaIds.splice(i, 1);
                            }
                        });
                        aUncheckedSchemaIds.forEach(function(schemaId) {
                            _oChooseState[schemaId] = false;
                        });
                    }
                }
            });
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
        }, {
            id: 'roundTitle',
            type: '_roundTitle',
            title: '填写轮次'
        }];
        $scope.app.dataSchemas.forEach(function(schema) {
            chooseState[schema.id] = false;
        });
        $scope.otherSchemas.forEach(function(schema) {
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
                    if (brother) {
                        for (var ibl = listSchemas.length - 1; ibl >= 0; ibl--) {
                            if (listSchemas[ibl].id === brother.id) {
                                break;
                            }
                        }
                        listSchemas.splice(ibl + 1, 0, schema);
                    } else {
                        listSchemas.push(schema);
                    }
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
        if ($scope.app.entryRule.scope.sns == 'Y') {
            $scope.mschemas = [{
                id: 'nickname',
                title: '昵称',
                type: 'sns'
            }, {
                id: 'headimgurl',
                title: '头像',
                type: 'sns'
            }]
        } else {
            for (var i = $scope.otherMschemas.length - 1; i >= 0; i--) {
                if ($scope.otherMschemas[i].id == 'schema_title') {
                    break;
                } else {
                    $scope.otherMschemas.push({
                        id: 'schema_title',
                        title: '所属通讯录',
                        type: 'address'
                    });
                    break;
                }
            }
            if ($scope.activeWrap.config.mschemaId !== '') {
                $scope.mschemas = [];
                memberSchemas.forEach(function(item) {
                    if (item.id == $scope.activeWrap.config.mschemaId) {
                        $scope.mschemas = item._mschemas;
                    }
                });
            }
        }
        $scope.doFilter = function(id) {
            memberSchemas.forEach(function(item) {
                if (item.id == id) {
                    $scope.activeWrap.schemas = [];
                    config.mschemaId = id;
                    $scope.activeWrap.schemas = $scope.otherMschemas;
                    for (var i = item._mschemas.length - 1; i >= 0; i--) {
                        $scope.activeWrap.schemas.splice(0, 0, item._mschemas[i]);
                    }
                    $scope.mschemas = listSchemas = [];
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