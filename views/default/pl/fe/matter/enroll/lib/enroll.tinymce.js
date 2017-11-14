'use strict';
angular.module('tinymce.enroll', ['ui.bootstrap']).
directive('tinymce', function($timeout) {
    return {
        restrict: 'EA',
        scope: {
            id: '@',
            height: '=',
            contenteditable: '=',
            onsavecallback: '&',
            toolbar: '@',
        },
        replace: true,
        template: '<textarea></textarea>',
        link: function(scope, elem, attrs) {
            // 点击Button时选中文本节点
            function _clickButton(editor, target) {
                var selection = editor.selection,
                    spanNode, textNode;
                if (target.children.length) {
                    spanNode = target.children[0]; // first span

                    if (spanNode.tagName === 'SPAN') {
                        textNode = spanNode.childNodes[0];
                        if (textNode) {
                            selection.select(textNode, false);
                            selection.setCursorLocation(textNode, textNode.length);
                        } else {
                            selection.select(spanNode, false);
                            selection.setCursorLocation(spanNode, 0);
                        }
                    }
                }
            };
            // 点击Input时，设置它的wrap为编辑状态，如果显示label，将label设置为编辑状态
            function _clickInput(editor, target) {
                var selection = editor.selection,
                    wrap = target.parentNode,
                    labelNode, textNode;

                if (wrap && wrap.hasAttribute('wrap')) {
                    if (wrap.children[0].tagName === 'LABEL') {
                        labelNode = wrap.children[0];
                        textNode = labelNode.childNodes[0];
                        if (textNode) {
                            selection.select(textNode, false);
                            selection.setCursorLocation(textNode, textNode.length);
                        } else {
                            selection.select(labelNode, false);
                            selection.setCursorLocation(labelNode, 0);
                        }
                    }
                }
            };
            // tinymce setup
            function _setup(editor) {
                editor.on('change', function(e) {
                    var phase;
                    phase = scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        scope.$emit('tinymce.content.change', false);
                    } else {
                        scope.$apply(function() {
                            scope.$emit('tinymce.content.change', false);
                        });
                    }
                });
                editor.on('click', function(e) {
                    // 点击Button，Input不触发NodeChange事件，需要自己处理
                    if (/button/i.test(e.target.tagName)) {
                        _clickButton(editor, e.target);
                    } else if (/input|textarea/i.test(e.target.tagName)) {
                        _clickInput(editor, e.target);
                    }
                });
                editor.on('NodeChange', function(e) {
                    if (true === scope.initialized) {
                        var wrap;
                        if (e.selectionChange === true) {
                            /*选择节点*/
                            wrap = e.element;
                            if (wrap.tagName && wrap.tagName !== 'HTML') {
                                if (!wrap.hasAttribute('wrap') && wrap !== editor.getBody()) {
                                    while (wrap.parentNode !== editor.getBody()) {
                                        if (wrap.hasAttribute('wrap') || wrap.parentNode === null) break;
                                        wrap = wrap.parentNode;
                                    }
                                }
                                scope.$emit('tinymce.wrap.select', wrap);
                            } else {
                                scope.$emit('tinymce.wrap.select', editor.getBody());
                            }
                        }
                    }
                });
                /*编辑节点*/
                (function() {
                    var _lastNodeContent;
                    editor.on('keydown', function(evt) {
                        var selection = editor.selection,
                            node = selection.getNode();

                        _lastNodeContent = node.innerHTML;
                        if (evt.keyCode == 13) {
                            /* 检查组件元素，如果是，在结尾回车时不进行元素的复制，而是添加空行 */
                            var dom, wrap;
                            dom = editor.dom;
                            if (selection && selection.getNode()) {
                                wrap = selection.getNode();
                                if (wrap !== editor.getBody()) {
                                    while (wrap.parentNode !== editor.getBody()) {
                                        wrap = wrap.parentNode;
                                        if (wrap.hasAttribute('wrap')) {
                                            if (/radio|checkbox|score/.test(wrap.getAttribute('wrap'))) {
                                                scope.$emit('tinymce.option.add', wrap);
                                                return;
                                            }
                                        }
                                    }
                                    if (wrap.hasAttribute('wrap')) {
                                        evt.preventDefault();
                                        var newWrap = dom.create('div', {
                                            wrap: 'text',
                                            class: 'form-group'
                                        }, '&nbsp;');
                                        dom.insertAfter(newWrap, wrap);
                                        selection.setCursorLocation(newWrap, 0);
                                        editor.focus();
                                        scope.$emit('tinymce.wrap.add', newWrap);
                                    }
                                }
                            }
                        } else if (evt.keyCode === 8 || evt.keyCode === 46) {
                            if (_lastNodeContent.length === 1) {
                                // 模拟删除字符操作，避免节点被删掉
                                if (node.tagName === 'LABEL' && node.parentNode.hasAttribute('wrap')) {
                                    node.innerHTML = ' ';
                                    evt.preventDefault();
                                    evt.stopPropagation();
                                } else if (node.tagName === 'SPAN' && node.parentNode.tagName === 'BUTTON') {
                                    node.innerHTML = ' ';
                                    evt.preventDefault();
                                    evt.stopPropagation();
                                } else if (node.tagName === 'SPAN' && node.parentNode.parentNode && /checkbox|radio/.test(node.parentNode.parentNode.getAttribute('wrap'))) {
                                    node.innerHTML = ' ';
                                    evt.preventDefault();
                                    evt.stopPropagation();
                                }
                            }
                        } else {
                            if (node.hasAttribute('wrap') && node.getAttribute('wrap') !== 'text') {
                                /* wrap不允许直接被编辑 */
                                evt.preventDefault();
                                evt.stopPropagation();
                            }
                        }
                    });
                    editor.on('keyup', function(evt) {
                        var node, nodeContent, phase;

                        /* 粘贴操作后，selection会发生改变 */
                        if (evt.keyCode === 17 || evt.keyCode === 91) {
                            return;
                        }

                        node = editor.selection.getNode();
                        nodeContent = node.innerHTML;
                        if (_lastNodeContent && _lastNodeContent !== nodeContent) {
                            // 通知发生变化
                            phase = scope.$root.$$phase;
                            if (phase === '$digest' || phase === '$apply') {
                                scope.$emit('tinymce.content.change', {
                                    node: node
                                });
                            } else {
                                scope.$apply(function() {
                                    scope.$emit('tinymce.content.change', {
                                        node: node
                                    });
                                });
                            }
                        }
                    });
                })();
                (function() {
                    var _lastPasteNode, _lastPasteContent;
                    editor.on('PastePreProcess', function(e) {
                        /* 将粘贴的内容转换为纯文本 */
                        e.content = _lastPasteContent = e.content.replace(/<[^>]*>/g, '').replace(/\n|\r/g, '');
                        _lastPasteNode = editor.selection.getNode();
                    });
                    editor.on('PastePostProcess', function(e) {
                        /* 此时还没有把粘贴的内容写到node中，手动写入并阻止后续处理 */
                        var phase = scope.$root.$$phase;
                        _lastPasteNode.innerHTML = _lastPasteContent;
                        if (phase === '$digest' || phase === '$apply') {
                            scope.$emit('tinymce.content.change', {
                                node: _lastPasteNode
                            });
                        } else {
                            scope.$apply(function() {
                                scope.$emit('tinymce.content.change', {
                                    node: _lastPasteNode
                                });
                            });
                        }
                        e.preventDefault();
                    });
                })();
                editor.on('ExecCommand', function(e) {
                    switch (e.command) {
                        case 'mceTableDelete':
                            var c = this.getContent(),
                                patt = /<div class="table">&nbsp;<\/div>/;
                            if (patt.test(c)) {
                                c = c.replace(patt, '');
                                this.setContent(c);
                            }
                            break;
                    }
                });
                editor.addButton('multipleimage', {
                    tooltip: '插入图片',
                    icon: 'image',
                    onclick: function() {
                        var selectedNode, selectedId, tmpId = false;
                        selectedNode = editor.selection.getNode();
                        selectedId = editor.dom.getAttrib(selectedNode, 'id');
                        if (!selectedId) {
                            tmpId = true;
                            selectedId = '__mcenew' + (new Date() * 1);
                            editor.dom.setAttrib(selectedNode, 'id', selectedId);
                        }
                        scope.$emit('tinymce.multipleimage.open', function(urls, isShowName) {
                            var i, t, url, data, dom, pElm;
                            t = (new Date() * 1);
                            dom = editor.dom;
                            for (i in urls) {
                                url = urls[i] + '?_=' + t,
                                    data = {
                                        src: url
                                    },
                                    pElm = dom.add(selectedId, 'p');
                                dom.add(pElm, 'img', data);
                                if (isShowName === 'Y') {
                                    var picname = decodeURI(urls[i]).split('/').pop();
                                    picname = picname.split('.').shift();
                                    dom.add(pElm, 'span', {
                                        style: 'display:block'
                                    }, picname);
                                }
                            }
                            if (tmpId) {
                                selectedNode = dom.get(selectedId);
                                dom.setAttrib(selectedNode, 'id', null);
                            }
                            scope.$emit('tinymce.content.change', false);
                        });
                    }
                });
            };
            /**
             * 通知编辑的内容发生变化
             */
            var tinymceConfig = {
                selector: '#' + scope.id,
                language: 'zh_CN',
                theme: 'modern',
                skin: 'light',
                menubar: false,
                statusbar: false,
                plugins: ['save textcolor code table paste fullscreen visualblocks'],
                toolbar: 'fontsizeselect styleselect forecolor backcolor bullist numlist outdent indent table multipleimage',
                forced_root_block: 'div',
                valid_elements: "*[*]",
                relative_urls: false,
                content_css: '/static/css/bootstrap.min.css,/static/css/tinymce.css?v=' + (new Date * 1),
                setup: _setup,
                save_onsavecallback: function() {
                    $timeout(function() {
                        scope.onsavecallback && scope.onsavecallback();
                    });
                },
                init_instance_callback: function() {
                    var editor = tinymce.get(scope.id);
                    if (scope.contenteditable !== undefined) {
                        $(editor.getBody()).attr('contenteditable', scope.contenteditable);
                    }
                    scope.initialized = true;
                    scope.$emit('tinymce.instance.init', editor);
                }
            };
            if (scope.toolbar) {
                tinymceConfig.toolbar += ' ' + scope.toolbar;
            }
            scope.$on('$destroy', function() {
                var editor;
                if (editor = tinymce.get(scope.id)) {
                    editor.remove();
                    editor = null;
                }
            });
            tinymce.init(tinymceConfig);
        }
    }
});