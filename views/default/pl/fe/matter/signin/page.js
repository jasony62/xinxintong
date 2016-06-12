define(['frame'], function(ngApp) {
    var _ctrlEmbedButton = ['$scope', '$uibModalInstance', 'app', 'def', function($scope, $mi, app, def) {
        var targetPages = {},
            inputPages = {};
        angular.forEach(app.pages, function(page) {
            targetPages[page.name] = {
                l: page.title
            };
            if (page.type === 'I') {
                inputPages[page.name] = {
                    l: page.title
                };
            }
        });
        targetPages.closeWindow = {
            l: '关闭页面'
        };
        $scope.buttons = {
            submit: {
                l: '提交信息'
            },
            addRecord: {
                l: '新增登记'
            },
            editRecord: {
                l: '修改登记'
            },
            sendInvite: {
                l: '发出邀请'
            },
            acceptInvite: {
                l: '接受邀请'
            },
            gotoPage: {
                l: '页面导航'
            },
            closeWindow: {
                l: '关闭页面'
            },
            signin: {
                l: '签到'
            },
        };
        app.can_like_record === 'Y' && ($scope.buttons.likeRecord = {
            l: '点赞'
        });
        app.can_remark_record === 'Y' && ($scope.buttons.remarkRecord = {
            l: '评论'
        });
        $scope.pages = targetPages;
        $scope.inputPages = inputPages;
        $scope.def = def;
        $scope.choose = function() {
            var names;
            def.label = $scope.buttons[def.name].l;
            def.next = '';
            if (['addRecord', 'editRecord'].indexOf(def.name) !== -1) {
                names = Object.keys(inputPages);
                if (names.length === 0) {
                    alert('没有类型为“登记页”的页面');
                } else {
                    def.next = names[0];
                }
            }
        };
        $scope.ok = function() {
            $mi.close($scope.def);
        };
        $scope.cancel = function() {
            $mi.dismiss();
        };
    }];
    ngApp.provider.controller('ctrlPage', ['$scope', '$location', 'http2', '$uibModal', '$timeout', '$q', function($scope, $location, http2, $uibModal, $timeout, $q) {
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
        $scope.onPageChange = function(page) {
            var i, old;
            for (i = $scope.persisted.pages.length - 1; i >= 0; i--) {
                old = $scope.persisted.pages[i];
                if (old.name === page.name)
                    break;
            }
            page.$$modified = page.html !== old.html;
        };
        $scope.updPage = function(page, name) {
            var editor, defer = $q.defer();
            if (!angular.equals($scope.app, $scope.persisted)) {
                if (name === 'html') {
                    editor = tinymce.get(page.name);
                    if ($(editor.getBody()).find('.active').length) {
                        $(editor.getBody()).find('.active').removeClass('active');
                        $scope.hasActiveWrap = false;
                        page.html = $(editor.getBody()).html();
                    }
                }
                $scope.$root.progmsg = '正在保存页面...';
                var url, p = {};
                p[name] = name === 'html' ? encodeURIComponent(page[name]) : page[name];
                url = '/rest/pl/fe/matter/signin/page/update';
                url += '?site=' + $scope.siteId;
                url += '&app=' + $scope.id;
                url += '&pid=' + page.id;
                url += '&pname=' + page.name;
                url += '&cid=' + page.code_id;
                http2.post(url, p, function(rsp) {
                    $scope.persisted = angular.copy($scope.app);
                    page.$$modified = false;
                    $scope.$root.progmsg = '';
                    defer.resolve();
                });
            }
            return defer.promise;
        };
        $scope.delPage = function() {
            if (window.confirm('确定删除？')) {
                var url = '/rest/pl/fe/matter/signin/page/remove';
                url += '?site=' + $scope.siteId;
                url += '&app=' + $scope.id;
                url += '&pid=' + $scope.ep.id;
                http2.get(url, function(rsp) {
                    $scope.app.pages.splice($scope.app.pages.indexOf($scope.ep), 1);
                    history.back();
                });
            }
        };
        window.onbeforeunload = function(e) {
            var i, p, message, modified;
            modified = false;
            for (i in $scope.app.pages) {
                p = $scope.app.pages[i];
                if (p.$$modified) {
                    modified = true;
                    break;
                }
            }
            if (modified) {
                message = '已经修改的页面还没有保存',
                    e = e || window.event;
                if (e) {
                    e.returnValue = message;
                }
                return message;
            }
        };
        $scope.$watch('app.pages', function(pages) {
            var current = $location.search().page,
                dataSchemas, others = [];
            if (!pages || pages.length === 0) return;
            angular.forEach(pages, function(p) {
                if (p.name === current) {
                    $scope.ep = p;
                    if (angular.isString($scope.ep.data_schemas)) {
                        dataSchemas = $scope.ep.data_schemas;
                        $scope.ep.data_schemas = dataSchemas && dataSchemas.length ? JSON.parse(dataSchemas) : [];
                    }
                    if (angular.isString($scope.ep.act_schemas)) {
                        actSchemas = $scope.ep.act_schemas;
                        $scope.ep.act_schemas = actSchemas && actSchemas.length ? JSON.parse(actSchemas) : [];
                    }
                    if (angular.isString($scope.ep.user_schemas)) {
                        userSchemas = $scope.ep.user_schemas;
                        $scope.ep.user_schemas = userSchemas && userSchemas.length ? JSON.parse(userSchemas) : [];
                    }
                } else {
                    p !== $scope.ep && others.push(p);
                }
            });
            $scope.others = others;
        });
    }]);
    ngApp.provider.controller('ctrlPageSchema', ['$scope', '$uibModal', function($scope, $uibModal) {
        $scope.chooseUser = function() {
            $uibModal.open({
                templateUrl: 'chooseUserSchema.html',
                backdrop: 'static',
                controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                    var choosed = [];
                    $scope.schemas = [{
                        name: 'nickname',
                        label: '昵称'
                    }, {
                        name: 'headpic',
                        label: '头像'
                    }];
                    $scope.choose = function(schema) {
                        schema._selected ? choosed.push(schema) : choosed.splice(choosed.indexOf(schema), 1);
                    };
                    $scope.ok = function() {
                        $mi.close(choosed);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                }],
            }).result.then(function(choosed) {
                angular.forEach(choosed, function(schema) {
                    var userSchemas = $scope.ep.user_schemas,
                        i = 0,
                        l = userSchemas.length;
                    while (i < l && schema.name !== userSchemas[i++].name) {};
                    if (i === l) {
                        delete schema._selected;
                        userSchemas.push(schema);
                    }
                });
                $scope.updPage($scope.ep, 'user_schemas');
            });
        };
        $scope.removeUser = function(schema) {
            var user_schemas = $scope.ep.user_schemas;
            user_schemas.splice(user_schemas.indexOf(schema), 1);
        };
        $scope.removeAct = function(def) {
            $scope.ep.act_schemas.splice($scope.ep.act_schemas.indexOf(def), 1);
            $scope.updPage($scope.ep, 'act_schemas');
        };
        $scope.emptyPage = function() {
            var activeEditor = tinymce.get($scope.ep.name);
            activeEditor.setContent('');
            activeEditor.save();
            $scope.ep.html = '';
            $scope.onPageChange($scope.ep);
        };
    }]);
    ngApp.provider.controller('ctrlInputSchema', ['$scope', '$uibModal', function($scope, $uibModal) {
        $scope.chooseSchema = function() {
            $uibModal.open({
                templateUrl: 'chooseDataSchema.html',
                backdrop: 'static',
                resolve: {
                    schemas: function() {
                        return $scope.app.data_schemas;
                    }
                },
                controller: ['$scope', '$uibModalInstance', 'schemas', function($scope, $mi, schemas) {
                    var choosed = [];
                    $scope.schemas = angular.copy(schemas);
                    $scope.choose = function(schema) {
                        schema._selected ? choosed.push(schema) : choosed.splice(choosed.indexOf(schema), 1);
                    };
                    $scope.ok = function() {
                        $mi.close(choosed);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                }],
            }).result.then(function(choosed) {
                angular.forEach(choosed, function(schema) {
                    var dataSchemas = $scope.ep.data_schemas,
                        i = 0,
                        l = dataSchemas.length;
                    while (i < l && schema.id !== dataSchemas[i++].id) {};
                    if (i === l) {
                        delete schema._selected;
                        dataSchemas.push(schema);
                    }
                });
                $scope.updPage($scope.ep, 'data_schemas');
            });
        };
        $scope.removeSchema = function(schema) {
            var data_schemas = $scope.ep.data_schemas;
            data_schemas.splice(data_schemas.indexOf(schema), 1);
            $scope.updPage($scope.ep, 'data_schemas');
        };
        $scope.chooseAct = function() {
            $uibModal.open({
                templateUrl: 'chooseButton.html',
                backdrop: 'static',
                resolve: {
                    app: function() {
                        return $scope.app;
                    },
                    def: function() {
                        return {
                            name: '',
                            label: '',
                            next: ''
                        };
                    }
                },
                controller: _ctrlEmbedButton,
            }).result.then(function(def) {
                $scope.ep.act_schemas.push(def);
                $scope.updPage($scope.ep, 'act_schemas');
            });
        };
        $scope.makePage = function() {
            var activeEditor = tinymce.get($scope.ep.name);
            activeEditor.setContent('');
            angular.forEach($scope.ep.user_schemas, function(schema) {
                var def = {};
                def[schema.name] = true;
                window.wrapLib.embedUser($scope.ep, def);
            });
            angular.forEach($scope.ep.data_schemas, function(schema) {
                window.wrapLib.embedInput($scope.ep, schema);
            });
            angular.forEach($scope.ep.act_schemas, function(schema) {
                window.wrapLib.embedButton($scope.ep, schema);
            });
            activeEditor.save();
        };
    }]);
    ngApp.provider.controller('ctrlSigninSchema', ['$scope', '$uibModal', function($scope, $uibModal) {
        $scope.chooseSchema = function() {
            $uibModal.open({
                templateUrl: 'chooseDataSchema.html',
                backdrop: 'static',
                resolve: {
                    schemas: function() {
                        return $scope.app.data_schemas;
                    }
                },
                controller: ['$scope', '$uibModalInstance', 'schemas', function($scope, $mi, schemas) {
                    var choosed = [];
                    $scope.schemas = angular.copy(schemas);
                    $scope.choose = function(schema) {
                        schema._selected ? choosed.push(schema) : choosed.splice(choosed.indexOf(schema), 1);
                    };
                    $scope.ok = function() {
                        $mi.close(choosed);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                }],
            }).result.then(function(choosed) {
                angular.forEach(choosed, function(schema) {
                    var dataSchemas = $scope.ep.data_schemas,
                        i = 0,
                        l = dataSchemas.length;
                    while (i < l && schema.id !== dataSchemas[i++].id) {};
                    if (i === l) {
                        delete schema._selected;
                        dataSchemas.push(schema);
                    }
                });
                $scope.updPage($scope.ep, 'data_schemas');
            });
        };
        $scope.removeSchema = function(schema) {
            var data_schemas = $scope.ep.data_schemas;
            data_schemas.splice(data_schemas.indexOf(schema), 1);
            $scope.updPage($scope.ep, 'data_schemas');
        };
        $scope.chooseAct = function() {
            $uibModal.open({
                templateUrl: 'chooseButton.html',
                backdrop: 'static',
                resolve: {
                    app: function() {
                        return $scope.app;
                    },
                    def: function() {
                        return {
                            name: '',
                            label: '',
                            next: ''
                        };
                    }
                },
                controller: _ctrlEmbedButton,
            }).result.then(function(def) {
                $scope.ep.act_schemas.push(def);
                $scope.updPage($scope.ep, 'act_schemas');
            });
        };
        $scope.makePage = function() {
            var activeEditor = tinymce.get($scope.ep.name);
            activeEditor.setContent('');
            angular.forEach($scope.ep.user_schemas, function(schema) {
                var def = {};
                def[schema.name] = true;
                window.wrapLib.embedUser($scope.ep, def);
            });
            angular.forEach($scope.ep.data_schemas, function(schema) {
                window.wrapLib.embedInput($scope.ep, schema);
            });
            angular.forEach($scope.ep.act_schemas, function(schema) {
                window.wrapLib.embedButton($scope.ep, schema);
            });
            activeEditor.save();
        };
    }]);
    ngApp.provider.controller('ctrlViewSchema', ['$scope', '$uibModal', function($scope, $uibModal) {
        $scope.chooseSchema = function(dataSchemasCatalog) {
            $uibModal.open({
                templateUrl: 'chooseDataSchema.html',
                backdrop: 'static',
                resolve: {
                    schemas: function() {
                        var dataSchemas = angular.copy($scope.app.data_schemas);
                        dataSchemas.push({
                            id: 'enrollAt',
                            type: '_enrollAt',
                            title: '登记时间'
                        });
                        dataSchemas.push({
                            id: 'enrollerNickname',
                            type: '_enrollerNickname',
                            title: '用户昵称'
                        });
                        dataSchemas.push({
                            id: 'enrollerHeadpic',
                            type: '_enrollerHeadpic',
                            title: '用户头像'
                        });
                        return dataSchemas;
                    }
                },
                controller: ['$scope', '$uibModalInstance', 'schemas', function($scope, $mi, schemas) {
                    var choosed = [];
                    $scope.schemas = schemas;
                    $scope.choose = function(schema) {
                        schema._selected ? choosed.push(schema) : choosed.splice(choosed.indexOf(schema), 1);
                    };
                    $scope.ok = function() {
                        $mi.close(choosed);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                }],
            }).result.then(function(choosed) {
                var schemas = dataSchemasCatalog.schemas;
                angular.forEach(choosed, function(schema) {
                    var i = 0,
                        l = schemas.length;
                    while (i < l && schema.id !== schemas[i++].id) {};
                    if (i === l) {
                        delete schema._selected;
                        schemas.push(schema);
                    }
                });
                $scope.ep.data_schemas = angular.copy($scope.dataSchemas);
                $scope.updPage($scope.ep, 'data_schemas');
            });
        };
        $scope.removeSchema = function(schemas, schema) {
            schemas.splice(schemas.indexOf(schema), 1);
            $scope.updPage($scope.ep, 'data_schemas');
        };
        $scope.chooseAct = function() {
            $uibModal.open({
                templateUrl: 'chooseButton.html',
                backdrop: 'static',
                resolve: {
                    app: function() {
                        return $scope.app;
                    },
                    def: function() {
                        return {
                            name: '',
                            label: '',
                            next: ''
                        };
                    }
                },
                controller: _ctrlEmbedButton,
            }).result.then(function(schema) {
                $scope.actSchemas.push(schema);
                $scope.updPage($scope.ep, 'act_schemas');
            });
        };
        $scope.makePage = function() {
            var activeEditor = tinymce.get($scope.ep.name);
            activeEditor.setContent('');
            angular.forEach($scope.dataSchemas, function(schema, catelog) {
                if (schema.enabled === 'Y') {
                    wrapLib.embedShow($scope.ep, schema, catelog);
                }
            });
            angular.forEach($scope.actSchemas, function(schema) {
                window.wrapLib.embedButton($scope.ep, schema);
            });
            activeEditor.save();
        };
        $scope.$watch('ep', function(ep) {
            if (!ep) return;
            if (ep.data_schemas.record === undefined) {
                $scope.dataSchemas = {
                    record: {
                        enabled: 'N',
                        inline: 'Y',
                        splitLine: 'Y',
                        schemas: []
                    },
                    list: {
                        enabled: 'N',
                        inline: 'Y',
                        splitLine: 'Y',
                        dataScope: 'U',
                        canLike: 'N',
                        autoload: 'N',
                        onclick: '',
                        schemas: []
                    }
                };
            } else {
                $scope.dataSchemas = ep.data_schemas;
            }
            $scope.actSchemas = ep.act_schemas;
        });
    }]);
    ngApp.provider.controller('ctrlPageEditor', ['$scope', '$uibModal', '$q', 'mattersgallery', 'mediagallery', function($scope, $uibModal, $q, mattersgallery, mediagallery) {
        $scope.activeWrap = false;
        var setActiveWrap = function(wrap) {
            var wrapType;
            if (wrap) {
                wrapType = $(wrap).attr('wrap');
                wrap.classList.add('active');
                $scope.hasActiveWrap = true;
                $scope.activeWrap = {
                    type: wrapType,
                    editable: !/list/.test(wrapType),
                    upmost: /body/i.test(wrap.parentNode.tagName),
                    downmost: /button|static|radio|checkbox/.test(wrapType),
                };
            } else {
                $scope.hasActiveWrap = false;
                $scope.activeWrap = false;
            }
        };
        $scope.$on('tinymce.wrap.select', function(event, wrap) {
            $scope.$apply(function() {
                var root = wrap,
                    selectableWrap = wrap,
                    wrapType;
                while (root.parentNode) root = root.parentNode;
                $(root).find('.active').removeClass('active');
                $scope.hasActiveWrap = false;
                $scope.activeWrap = false;
                wrapType = $(selectableWrap).attr('wrap');
                while (!/input|radio|checkbox|static|button|list/.test(wrapType) && selectableWrap.parentNode) {
                    selectableWrap = selectableWrap.parentNode;
                    wrapType = $(selectableWrap).attr('wrap');
                }
                if (/input|radio|checkbox|static|button|list/.test(wrapType)) {
                    setActiveWrap(selectableWrap);
                }
            });
        });
        $scope.chooseSchema = function(page) {
            $uibModal.open({
                templateUrl: 'chooseDataSchema.html',
                backdrop: 'static',
                resolve: {
                    schemas: function() {
                        return $scope.app.data_schemas;
                    }
                },
                controller: ['$scope', '$uibModalInstance', 'schemas', function($scope, $mi, schemas) {
                    var choosed = [];
                    $scope.schemas = angular.copy(schemas);
                    $scope.choose = function(schema) {
                        schema._selected ? choosed.push(schema) : choosed.splice(choosed.indexOf(schema), 1);
                    };
                    $scope.ok = function() {
                        $mi.close(choosed);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                }],
            }).result.then(function(choosed) {
                var activeEditor = tinymce.get(page.name);
                angular.forEach(choosed, function(schema) {
                    var dataSchemas = page.data_schemas,
                        i = 0,
                        l = dataSchemas.length;
                    while (i < l && schema.id !== dataSchemas[i++].id) {};
                    if (i === l) {
                        delete schema._selected;
                        dataSchemas.push(schema);
                    }
                    window.wrapLib.embedInput(page, schema);
                });
                activeEditor.save();
                $scope.updPage(page, 'data_schemas');
            });
        };
        $scope.embedInput = function(page) {
            $uibModal.open({
                templateUrl: 'embedInputLib.html',
                resolve: {
                    memberSchemas: function() {
                        return $scope.memberSchemas;
                    }
                },
                backdrop: 'static',
                controller: ['$scope', '$uibModalInstance', '$timeout', 'memberSchemas', function($scope, $mi, $timeout, memberSchemas) {
                    var id;
                    id = 'c' + (new Date()).getTime();
                    $scope.memberSchemas = memberSchemas;
                    $scope.def = {
                        id: 'name',
                        type: 'name',
                        title: '姓名',
                        showname: 'placeholder',
                        component: 'R',
                        align: 'V',
                        count: 1,
                        setUpper: 'N'
                    };
                    $scope.addOption = function() {
                        if ($scope.def.ops === undefined)
                            $scope.def.ops = [];
                        var maxSeq = 0,
                            newOp = {
                                l: ''
                            };
                        angular.forEach($scope.def.ops, function(op) {
                            var opSeq = parseInt(op.v.substr(1));
                            opSeq > maxSeq && (maxSeq = opSeq);
                        });
                        newOp.v = 'v' + (++maxSeq);
                        $scope.def.ops.push(newOp);
                        $timeout(function() {
                            $scope.$broadcast('xxt.editable.add', newOp);
                        });
                    };
                    $scope.$on('xxt.editable.remove', function(e, op) {
                        var i = $scope.def.ops.indexOf(op);
                        $scope.def.ops.splice(i, 1);
                    });
                    $scope.changeType = function() {
                        var map = {
                            'name': {
                                title: '姓名',
                                id: 'name'
                            },
                            'mobile': {
                                title: '手机',
                                id: 'mobile'
                            },
                            'email': {
                                title: '邮箱',
                                id: 'email'
                            }
                        };
                        if (map[$scope.def.type]) {
                            $scope.def.title = map[$scope.def.type].title;
                            $scope.def.id = map[$scope.def.type].id;
                        } else if ($scope.def.type === 'member') {
                            $scope.def.title = '';
                            $scope.def.id = '';
                        } else {
                            $scope.def.title = '';
                            $scope.def.id = id;
                        }
                    };
                    $scope.selectedMemberSchema = {
                        schema: null,
                        attrs: null,
                        attr: null
                    };
                    $scope.shiftMemberSchema = function() {
                        var schema = $scope.selectedMemberSchema.schema,
                            schemaAttrs = [];
                        $scope.def.schema_id = schema.id;
                        schema.attr_name[0] === '0' && (schemaAttrs.push({
                            id: 'name',
                            label: '姓名'
                        }));
                        schema.attr_mobile[0] === '0' && (schemaAttrs.push({
                            id: 'mobile',
                            label: '手机'
                        }));
                        schema.attr_email[0] === '0' && (schemaAttrs.push({
                            id: 'email',
                            label: '邮箱'
                        }));
                        if (schema.extattr && schema.extattr.length) {
                            var i, l, ea;
                            for (i = 0, l = schema.extattr.length; i < l; i++) {
                                ea = schema.extattr[i];
                                schemaAttrs.push({
                                    id: 'extattr.' + ea.id,
                                    label: ea.label
                                });
                            }
                        }
                        $scope.selectedMemberSchema.attrs = schemaAttrs;
                    };
                    $scope.shiftMemberSchemaAttr = function() {
                        var attr = $scope.selectedMemberSchema.attr;
                        selectedMemberSchema = attr.label;
                        $scope.def.id = 'member.' + attr.id;
                    };
                    $scope.ok = function() {
                        if ($scope.def.title.length === 0) {
                            alert('必须指定登记项的名称');
                            return;
                        }
                        $mi.close($scope.def);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope.$watch('def.setUpper', function(nv) {
                        if (nv === 'Y') {
                            $scope.def.upper = $scope.def.ops ? $scope.def.ops.length : 0;
                        }
                    });
                }],
            }).result.then(function(def) {
                $scope.app.data_schemas.push(def);
                $scope.$parent.modified = true;
                $scope.update('data_schemas');
                $scope.submit().then(function() {
                    page.data_schemas.push(def);
                    $scope.updPage(page, 'data_schemas').then(function() {
                        var activeEditor = tinymce.get(page.name);
                        wrapLib.embedInput(page, def);
                        activeEditor.save();
                    });
                });
            });
        };
        $scope.editWrap = function(page) {
            var editor, $active, def;
            editor = tinymce.get(page.name);
            $active = $(editor.getBody()).find('.active');
            if (/button/.test($active.attr('wrap'))) {
                def = wrapLib.extractButtonSchema($active[0]);
                if (def.name === 'remarkRecord') {
                    $scope.$root.errmsg = '不支持修改该类型组件';
                    return;
                }
                $uibModal.open({
                    templateUrl: 'embedButtonLib.html',
                    backdrop: 'static',
                    resolve: {
                        app: function() {
                            return $scope.app;
                        },
                        def: function() {
                            return def;
                        }
                    },
                    controller: _ctrlEmbedButton,
                }).result.then(function(def) {
                    wrapLib.changeEmbedButton(page, $active[0], def);
                    $scope.ep.$$modified = true;
                });
            } else if (/input/.test($active.attr('wrap'))) {
                def = wrapLib.extractInputSchema($active[0]);
                $uibModal.open({
                    templateUrl: 'embedInputEditor.html',
                    backdrop: 'static',
                    controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                        $scope.def = def;
                        $scope.ok = function() {
                            $mi.close($scope.def);
                        };
                        $scope.cancel = function() {
                            $mi.dismiss();
                        };
                    }],
                }).result.then(function(def) {
                    wrapLib.changeEmbedInput(page, $active[0], def);
                });
            } else if (/static/.test($active.attr('wrap'))) {
                def = wrapLib.extractStaticSchema($active[0]);
                $uibModal.open({
                    templateUrl: 'embedStaticEditor.html',
                    backdrop: 'static',
                    controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                        $scope.def = def;
                        $scope.ok = function() {
                            $mi.close($scope.def);
                        };
                        $scope.cancel = function() {
                            $mi.dismiss();
                        };
                    }]
                }).result.then(function(def) {
                    wrapLib.changeEmbedStatic(page, $active[0], def);
                });
            }
        };
        $scope.removeWrap = function(page) {
            var editor;
            editor = tinymce.get(page.name);
            $(editor.getBody()).find('.active').remove();
            editor.save();
            setActiveWrap(null);
        };
        $scope.upWrap = function(page) {
            var editor, active;
            editor = tinymce.get(page.name);
            active = $(editor.getBody()).find('.active');
            active.prev().before(active);
            editor.save();
        };
        $scope.downWrap = function(page) {
            var editor, active;
            editor = tinymce.get(page.name);
            active = $(editor.getBody()).find('.active');
            active.next().after(active);
            editor.save();
        };
        $scope.upLevel = function(page) {
            var editor, $active, $parent;
            editor = tinymce.get(page.name);
            $active = $(editor.getBody()).find('.active');
            $parent = $active.parents('[wrap]');
            if ($parent.length) {
                $active.removeClass('active');
                setActiveWrap($parent[0]);
            }
        };
        $scope.downLevel = function(page) {
            var editor, $active, $children;
            editor = tinymce.get(page.name);
            $active = $(editor.getBody()).find('.active');
            $children = $active.find('[wrap]');
            if ($children.length) {
                $active.removeClass('active');
                setActiveWrap($children[0]);
            }
        };
        $scope.embedMatter = function(page) {
            mattersgallery.open($scope.siteId, function(matters, type) {
                var editor, dom, i, matter, mtype, fn;
                editor = tinymce.get(page.name);
                dom = editor.dom;
                for (i = 0; i < matters.length; i++) {
                    matter = matters[i];
                    mtype = matter.type ? matter.type : type;
                    fn = "openMatter(" + matter.id + ",'" + mtype + "')";
                    editor.insertContent(dom.createHTML('div', {
                        'wrap': 'link',
                        'class': 'matter-link'
                    }, dom.createHTML('a', {
                        href: 'javascript:void(0)',
                        "ng-click": fn,
                    }, dom.encode(matter.title))));
                }
            }, {
                matterTypes: $scope.innerlinkTypes,
                hasParent: false,
                singleMatter: true
            });
        };
        $scope.gotoCode = function(codeid) {
            //window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + codeid, '_self');
        };
        $scope.$on('tinymce.multipleimage.open', function(event, callback) {
            var options = {
                callback: callback,
                multiple: true,
                setshowname: true
            };
            mediagallery.open($scope.siteId, options);
        });
    }]);
});