/**
 * tinymce
 */
define(['wrap'], function(wrapLib) {
    'use strict';
    var _activeWrap = false,
        _editor = null,
        _page = null;

    function _appendWrap(name, attrs, html) {
        var dom, body, newDomWrap, selection, $activeWrap, $upmost;

        dom = _editor.dom;
        body = _editor.getBody();
        $activeWrap = $(body).find('[wrap].active');
        if ($activeWrap && $activeWrap.length) {
            /*如果有活动状态的wrap，加在这个wrap之后*/
            $upmost = $activeWrap.parents('[wrap]');
            $upmost = $upmost.length === 0 ? $activeWrap : $($upmost.get($upmost.length - 1));
            newDomWrap = dom.create(name, attrs, html);
            dom.insertAfter(newDomWrap, $upmost[0]);
        } else {
            if (attrs.wrap && attrs.wrap === 'input') {
                var $inputWrap = $(body).find("[wrap='input']");
                if ($inputWrap.length) {
                    /*加在最后一个input wrap的后面*/
                    newDomWrap = dom.create(name, attrs, html);
                    dom.insertAfter(newDomWrap, $inputWrap[$inputWrap.length - 1]);
                } else {
                    /*加在文档的最后*/
                    newDomWrap = dom.add(body, name, attrs, html);
                }
            } else if (attrs.wrap && attrs.wrap === 'value') {
                var $valueWrap = $(body).find("[wrap='value']");
                if ($valueWrap.length) {
                    /*加在最后一个static wrap的后面*/
                    newDomWrap = dom.create(name, attrs, html);
                    dom.insertAfter(newDomWrap, $valueWrap[$valueWrap.length - 1]);
                } else {
                    /*加在文档的最后*/
                    newDomWrap = dom.add(body, name, attrs, html);
                }
            } else {
                /*加在文档的最后*/
                newDomWrap = dom.add(body, name, attrs, html);
            }
        }

        _editor.fire('change');

        return newDomWrap;
    };
    return {
        load: function(editor, page) {
            var html;
            this.setEditor(editor);
            html = this.setPage(page);
            _editor.setContent(html);
            _editor.undoManager.clear();
        },
        setEditor: function(editor) {
            _editor = editor;
            wrapLib.setEditor(editor);
        },
        getEditor: function() {
            return _editor;
        },
        setPage: function(page) {
            _page = page;
            return page ? this.disableInput() : '';
        },
        getPage: function() {
            return _page;
        },
        disableInput: function(refresh) {
            var html;

            html = _page.html;
            if (_page.type === 'I') {
                html = $('<div>' + html + '</div>');
                html.find('[wrap=input]').attr('contenteditable', 'false');
                html.find('[wrap=input]>label').attr('contenteditable', 'true');
                html.find('[wrap=button]').attr('contenteditable', 'false');
                html.find('[wrap=button]>button>span').attr('contenteditable', 'true');
                html.find('[wrap=checkbox]>label>span').attr('contenteditable', 'true');
                html.find('[wrap=radio]>label>span').attr('contenteditable', 'true');
                html.find('[wrap=score]>div>label').attr('contenteditable', 'true');
                html.find('input[type=text],textarea').attr('readonly', true);
                html.find('input[type=text],textarea').attr('disabled', true);
                html.find('input[type=radio],input[type=checkbox]').attr('readonly', true);
                html.find('input[type=radio],input[type=checkbox]').attr('disabled', true);
                html = html.html();
            }

            refresh === true && (_page.html = html);

            return html;
        },
        /**
         * 清理代码，去掉额外的页面状态
         */
        purifyPage: function(page, persist) {
            var html = page.html;

            html = $('<div>' + html + '</div>');
            if (page.type === 'I') {
                html.find('[readonly]').removeAttr('readonly');
                html.find('[disabled]').removeAttr('disabled');
                html.find('[contenteditable]').removeAttr('contenteditable');
            }
            html.find('.active').removeClass('active');
            html = html.html();

            persist === true && (page.html = html);

            return html;
        },
        appendSchema: function(newSchema) {
            var oNewWrap, domNewWrap;
            if (_page.type === 'I') {
                var wrapParam;

                oNewWrap = wrapLib.input.newWrap(newSchema);
                _page.data_schemas.push(oNewWrap);

                wrapParam = wrapLib.input.embed(oNewWrap, true);
                domNewWrap = _appendWrap(wrapParam.tag, wrapParam.attrs, wrapParam.html);
            } else if (_page.type === 'V') {
                var wrapParam;

                oNewWrap = wrapLib.value.newWrap(newSchema);
                _page.data_schemas.push(oNewWrap);

                wrapParam = wrapLib.value.embed(oNewWrap);
                domNewWrap = _appendWrap(wrapParam.tag, wrapParam.attrs, wrapParam.html);
            }

            return domNewWrap;
        },
        addOptionWrap: function(domWrap, schema, newOp) {
            var html, newOptionWrap, dom, elem, textNode, wrapType;

            dom = _editor.dom;
            wrapType = domWrap.getAttribute('wrap');
            if (/radio/.test(wrapType)) {
                html = wrapLib.input.newRadio(schema, newOp, {}, true);
                html = $(html);

                newOptionWrap = dom.create('li', {
                    wrap: 'radio',
                    contenteditable: 'false',
                    class: 'radio'
                }, html.html());
            } else if (/checkbox/.test(wrapType)) {
                html = wrapLib.input.newCheckbox(schema, newOp, {}, true);
                html = $(html);

                newOptionWrap = dom.create('li', {
                    wrap: 'checkbox',
                    contenteditable: 'false',
                    class: 'checkbox'
                }, html.html());
            } else if (/score/.test(wrapType)) {
                html = wrapLib.input.newNumber(schema, newOp, {}, true);
                html = $(html);

                newOptionWrap = dom.create('li', {
                    wrap: 'score',
                    contenteditable: 'false',
                    class: 'score'
                }, html.html());
            }

            elem = dom.insertAfter(newOptionWrap, domWrap);
            if (/radio|checkbox/.test(wrapType)) {
                textNode = elem.querySelector('label>span');
            } else {
                textNode = elem.querySelector('label');
            }
            _editor.selection.select(textNode, false);
            _editor.selection.setCursorLocation(textNode, 0);
        },
        /**
         * 修改登记项
         */
        modifySchema: function(wrap) {
            if (_page.type === 'I') {
                wrapLib.input.modify(wrap.dom, wrap);
            } else if (_page.type === 'V') {
                wrapLib.value.modify(wrap.dom, wrap);
            } else if (_page.type === 'L') {
                if (wrap.type === 'value') {
                    wrapLib.value.modify(wrap.dom, wrap);
                } else if (wrap.type === 'records') {
                    wrapLib.records.modify(wrap.dom, wrap);
                } else if (wrap.type === 'rounds') {
                    wrapLib.rounds.modify(wrap.dom, wrap);
                }
            }
        },
        modifyButton: function(wrap) {
            wrapLib.button.modify(wrap.dom, wrap);
        },
        /**
         * 页面编辑器内容发生变化
         */
        nodeChange: function(node) {
            var domNodeWrap = $(node).parents('[wrap]'),
                status = {
                    schemaChanged: false,
                    actionChanged: false
                };
            if (_page.type === 'I') {
                if (domNodeWrap.length === 1 && domNodeWrap[0].getAttribute('wrap') === 'input') {
                    // 编辑input's label
                    if (/label/i.test(node.nodeName)) {
                        (function freshSchemaByDom() {
                            var oWrap = wrapLib.dataByDom(domNodeWrap[0]),
                                pageWrap = _page.wrapBySchema(oWrap.schema);

                            if (oWrap) {
                                if (oWrap.schema.title !== pageWrap.schema.title) {
                                    pageWrap.schema.title = oWrap.schema.title;
                                    status.schemaChanged = true;
                                    status.schema = oWrap.schema;
                                }
                            }
                        })();
                    }
                } else if (domNodeWrap.length === 1 && domNodeWrap[0].getAttribute('wrap') === 'button') {
                    // 编辑button's span
                    if (/span/i.test(node.nodeName)) {
                        (function freshButtonByDom() {
                            var oWrap = wrapLib.dataByDom(domNodeWrap[0]),
                                pageWrap = _page.wrapByButton(oWrap.schema);

                            if (oWrap) {
                                if (oWrap.schema.label !== pageWrap.label) {
                                    pageWrap.label = oWrap.schema.label;
                                    status.actionChanged = true;
                                }
                            }
                        })();
                    }
                } else if (domNodeWrap.length === 2) {
                    // 编辑input's options
                    (function(page) {
                        var $domParentWrap = $(domNodeWrap[0]),
                            oOptionWrap, editingSchema;
                        if (/radio|checkbox|score/.test($domParentWrap.attr('wrap'))) {
                            oOptionWrap = wrapLib.input.dataByDom(domNodeWrap[0]);
                            if (oOptionWrap.schema && oOptionWrap.schema.ops && oOptionWrap.schema.ops.length === 1) {
                                for (var i = page.data_schemas.length - 1; i >= 0; i--) {
                                    editingSchema = page.data_schemas[i].schema;
                                    if (oOptionWrap.schema.id === editingSchema.id) {
                                        for (var j = editingSchema.ops.length - 1; j >= 0; j--) {
                                            if (oOptionWrap.schema.ops[0].v === editingSchema.ops[j].v) {
                                                editingSchema.ops[j].l = oOptionWrap.schema.ops[0].l;
                                                status.schemaChanged = true;
                                                status.schema = editingSchema;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    })(_page);
                }
                // 修改了页面内容
                var html = _editor.getContent();
                html = this.purifyPage({
                    type: 'I',
                    html: html
                });
                if (html !== _page.html) {
                    status.htmlChanged = true;
                    _page.$$modified = true;
                }
            } else if (_page.type === 'V') {
                if (domNodeWrap.length === 1 && domNodeWrap[0].getAttribute('wrap') === 'value') {
                    // 编辑input's label
                    if (/label/i.test(node.nodeName)) {
                        (function freshSchemaByDom() {
                            var oWrap = wrapLib.dataByDom(domNodeWrap[0]),
                                pageWrap = _page.wrapBySchema(oWrap.schema);

                            if (oWrap) {
                                if (oWrap.schema.title !== pageWrap.schema.title) {
                                    pageWrap.schema.title = oWrap.schema.title;
                                    status.schemaChanged = true;
                                    status.schema = oWrap.schema;
                                }
                            }
                        })();
                    }
                } else if (domNodeWrap.length === 1 && domNodeWrap[0].getAttribute('wrap') === 'button') {
                    // 编辑button's span
                    if (/span/i.test(node.nodeName)) {
                        (function freshButtonByDom() {
                            var oWrap = wrapLib.dataByDom(domNodeWrap[0]),
                                pageWrap = _page.wrapByButton(oWrap.schema);

                            if (oWrap) {
                                if (oWrap.schema.label !== pageWrap.label) {
                                    pageWrap.label = oWrap.schema.label;
                                    status.actionChanged = true;
                                }
                            }
                        })();
                    }
                }
                // 修改了页面内容
                var html = _editor.getContent();
                if (html !== _page.html) {
                    status.htmlChanged = true;
                    _page.$$modified = true;
                }
            } else if (_page.type === 'L') {
                if (domNodeWrap.length && domNodeWrap[0].getAttribute('wrap') === 'value') {
                    if (/label/i.test(node.nodeName)) {
                        (function freshSchemaByDom() {
                            var oWrap = wrapLib.dataByDom(domNodeWrap[0]),
                                pageWrap = _page.wrapBySchema(oWrap.schema);

                            if (oWrap) {
                                if (oWrap.schema.title !== pageWrap.schema.title) {
                                    pageWrap.schema.title = oWrap.schema.title;
                                    status.schemaChanged = true;
                                    status.schema = oWrap.schema;
                                }
                            }
                        })();
                    }
                } else if (domNodeWrap.length === 1 && domNodeWrap[0].getAttribute('wrap') === 'button') {
                    // 编辑button's span
                    if (/span/i.test(node.nodeName)) {
                        (function freshButtonByDom() {
                            var oWrap = wrapLib.dataByDom(domNodeWrap[0]),
                                pageWrap = _page.wrapByButton(oWrap.schema);

                            if (oWrap) {
                                if (oWrap.schema.label !== pageWrap.label) {
                                    pageWrap.label = oWrap.schema.label;
                                    status.actionChanged = true;
                                }
                            }
                        })();
                    }
                }
            }

            return status;
        },
        setActiveWrap: function(domWrap) {
            var wrapType;
            if (_activeWrap) {
                _activeWrap.dom.classList.remove('active');
            }
            if (domWrap) {
                wrapType = $(domWrap).attr('wrap');
                _activeWrap = {
                    type: wrapType,
                    dom: domWrap,
                    upmost: /body/i.test(domWrap.parentNode.tagName),
                    downmost: /button|value|radio|checkbox/.test(wrapType),
                };
                domWrap.classList.add('active');
                var dataWrap = wrapLib.dataByDom(domWrap, _page);
                angular.extend(_activeWrap, dataWrap);
            } else {
                _activeWrap = false;
            }

            return _activeWrap;
        },
        selectWrap: function(domWrap) {
            var selectableWrap = domWrap,
                wrapType;

            $(_editor.getBody()).find('.active').removeClass('active');
            this.setActiveWrap(null);
            if (selectableWrap) {
                wrapType = $(selectableWrap).attr('wrap');
                while (!/text|matter|input|radio|checkbox|value|button|records|rounds|score/.test(wrapType) && selectableWrap.parentNode) {
                    selectableWrap = selectableWrap.parentNode;
                    wrapType = $(selectableWrap).attr('wrap');
                }
                if (/text|matter|input|radio|checkbox|value|button|records|rounds|score/.test(wrapType)) {
                    this.setActiveWrap(selectableWrap);
                }
            }

            return _activeWrap;
        },
        moveWrap: function(action) {
            var $active = $(_activeWrap.dom);
            if (action === 'up') {
                $active.prev().before($active);
            } else if (action === 'down') {
                $active.next().after($active);
            } else if (action === 'upLevel') {
                this.setActiveWrap($active.parents('[wrap]').get(0));
            } else if (action === 'downLevel') {
                this.setActiveWrap($active.find('[wrap]').get(0));
            }

            return _activeWrap;
        },
        optionSchemaByDom: function(domWrap, app) {
            var optionDom, schemaOption, schemaOptionId, schemaId, schema, wrapType;

            wrapType = domWrap.getAttribute('wrap');
            if (/radio|checkbox/.test(wrapType)) {
                optionDom = domWrap.querySelector('input');
                schemaId = optionDom.getAttribute('name');
                if (/radio/.test(wrapType)) {
                    schemaOptionId = optionDom.getAttribute('value');
                } else if (/checkbox/.test(wrapType)) {
                    schemaOptionId = optionDom.getAttribute('ng-model');
                    schemaOptionId = schemaOptionId.split('.')[2];
                }
            } else if ('score' === wrapType) {
                schemaId = domWrap.parentNode.parentNode.getAttribute('schema');
                schemaOptionId = domWrap.getAttribute('opvalue');
            }

            for (var i = app.data_schemas.length - 1; i >= 0; i--) {
                if (schemaId === app.data_schemas[i].id) {
                    schema = app.data_schemas[i];
                    for (var j = schema.ops.length - 1; j >= 0; j--) {
                        if (schema.ops[j].v === schemaOptionId) {
                            schemaOption = schema.ops[j];
                            break;
                        }
                    }
                    break;
                }
            }

            return [schema, schemaOption];
        },
        appendButton: function(btn) {
            var oWrap = {
                    id: 'act' + (new Date() * 1),
                    name: btn.n,
                    label: btn.l,
                    next: ''
                },
                domNewWrap;

            domNewWrap = wrapLib.button.embed(oWrap);
            _page.act_schemas.push(oWrap);

            return domNewWrap;
        },
        appendRecordList: function(app) {
            var dataWrap = {
                config: {
                    id: 'L' + (new Date() * 1),
                    pattern: 'records',
                    dataScope: 'U',
                    onclick: '',
                },
                schemas: angular.copy(app.data_schemas)
            };

            dataWrap.schemas.push({
                id: 'enrollAt',
                type: '_enrollAt',
                title: '登记时间'
            });

            _page.data_schemas.push(dataWrap);

            return wrapLib.records.embed(dataWrap);
        },
        appendRoundList: function(app) {
            var dataWrap = {
                config: {
                    id: 'L' + (new Date() * 1),
                    pattern: 'rounds',
                    onclick: ''
                }
            };
            _page.data_schemas.push(dataWrap);

            return wrapLib.rounds.embed(dataWrap);
        },
        removeWrap: function(oWrap) {
            var wrapType = oWrap.type,
                $domRemoved = $(oWrap.dom);

            if (/input/.test(wrapType)) {
                _page.removeSchema(oWrap.schema);
            } else if (/button/.test(wrapType)) {
                _page.removeButton(oWrap.schema);
            } else if (/value/.test(wrapType)) {
                var config = oWrap.config;
                if (config) {
                    if (config.id === undefined) {
                        // 列表中的值对象
                        var $listWrap = $domRemoved.parents('[wrap]');
                        if ($listWrap.length && $listWrap.attr('wrap') === 'records') {
                            config.id = $listWrap.attr('id');
                        }
                        _page.removeValue(config, oWrap.schema);
                    } else {
                        _page.removeValue(config);
                    }
                }
            } else if (/records|rounds/.test(wrapType)) {
                (function removeList() {
                    var listId = $domRemoved.attr('id'),
                        list;

                    for (var i = _page.data_schemas.length - 1; i >= 0; i--) {
                        list = _page.data_schemas[i];
                        if (list.id === listId) {
                            _page.data_schemas.splice(i, 1);
                            break;
                        }
                    }
                })();
            }
            /* 删除editor中的dom */
            $domRemoved.remove();

            this.html = _editor.getContent();

            return $domRemoved[0];
        },
        /**
         * 从当前编辑页面上删除（不显示）登记项
         */
        removeSchema: function(removedSchema) {
            var pageSchemas = _page.data_schemas,
                $domRemoved;

            for (var i = pageSchemas.length - 1; i >= 0; i--) {
                if (removedSchema.id === pageSchemas[i].schema.id) {
                    $domRemoved = $(_editor.getBody()).find("[schema='" + removedSchema.id + "']");
                    $domRemoved.remove();
                    pageSchemas.splice(i, 1);
                    return $domRemoved[0];
                }
            }

            return false;
        },
        scroll: function(dom) {
            var domBody = _editor.getBody(),
                offsetTop = dom.offsetTop;
            domBody.scrollTop = offsetTop - 15;
        }
    };
});
