angular.module('tinymce.ui.xxt', ['ui.bootstrap']).
directive('tinymce', function($timeout) {
    return {
        restrict: 'EA',
        scope: {
            id: '@',
            height: '=',
            content: '=',
            contenteditable: '=',
            onsavecallback: '&',
            update: '&',
            change: '&',
            toolbar: '@',
        },
        replace: true,
        template: '<textarea></textarea>',
        link: function(scope, elem, attrs) {
            var tinymceConfig = {
                selector: '#' + scope.id,
                language: 'zh_CN',
                theme: 'modern',
                skin: 'light',
                menubar: false,
                statusbar: false,
                plugins: ['save textcolor code table paste fullscreen visualblocks'],
                toolbar: 'fontsizeselect styleselect forecolor backcolor bullist numlist outdent indent table multipleimage',
                content_css: '/static/css/bootstrap.min.css,/static/css/tinymce.css?v=' + (new Date() * 1),
                forced_root_block: 'div',
                height: scope.height ? scope.height : 300,
                valid_elements: "*[*]",
                relative_urls: false,
                save_onsavecallback: function() {
                    $timeout(function() {
                        scope.onsavecallback && scope.onsavecallback();
                    });
                },
                setup: function(editor) {
                    var _lastContent;
                    editor.on('keydown', function(evt) {
                        var selection = editor.selection,
                            _lastContent = selection.getNode().innerHTML;
                        if (evt.keyCode == 13) {
                            /**
                             * 检查组件元素，如果是，在结尾回车时不进行元素的复制，而是添加空行
                             */
                            var dom, wrap;
                            dom = editor.dom;
                            if (selection && selection.getNode()) {
                                wrap = selection.getNode();
                                if (wrap !== editor.getBody()) {
                                    while (wrap.parentNode !== editor.getBody()) {
                                        wrap = wrap.parentNode;
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
                        } else {
                            if (selection.getNode().hasAttribute('wrap') && selection.getNode().getAttribute('wrap') !== 'text') {
                                /*
                                 * wrap不允许直接被编辑
                                 */
                                evt.preventDefault();
                                evt.stopPropagation();
                            }
                        }
                    });
                    editor.on('keyup', function(evt) {
                        var content = editor.selection.getNode().innerHTML;
                        if (_lastContent !== content) {
                            scope.$emit('tinymce.content.editing', editor.selection.getNode());
                        }
                    });
                    editor.on('click', function(e) {
                        var wrap;
                        wrap = e.target;
                        if (wrap.tagName !== 'HTML') {
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
                    });
                    editor.on('change', function(e) {
                        var content, phase, node;
                        content = tinymce.get(scope.id).getContent();
                        if (scope.content !== content) {
                            phase = scope.$root.$$phase;
                            if (phase === '$digest' || phase === '$apply') {
                                scope.content = content;
                            } else {
                                scope.$apply(function() {
                                    scope.content = content;
                                });
                            }
                            $timeout(function() {
                                scope.change && scope.change();
                            });
                        }
                    });
                    editor.on('blur', function(e) {
                        var content = tinymce.get(scope.id).getContent();
                        if (scope.content !== content) {
                            var phase = scope.$root.$$phase;
                            if (phase === '$digest' || phase === '$apply') {
                                scope.content = content;
                            } else {
                                scope.$apply(function() {
                                    scope.content = content;
                                });
                            }
                            $timeout(function() {
                                scope.update && scope.update();
                            });
                        }
                    });
                    editor.on('BeforeSetContent', function(e) {
                        var c;
                        if (e.content && e.content.length) {
                            c = e.content;
                            c = c.replace(/\n|\r/g, '').replace(/\s*/, ''); // trim
                            if (/^<table.*<\/table>$/i.test(c)) {
                                c = $('<div>' + c + '</div>');
                                c.find('td').html('&nbsp;');
                                e.content = '<p>&nbsp;</p><div wrap="table">' + c.html() + '</div><p>&nbsp;</p>';
                            }
                        }
                    });
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
                                selectedId = '__mcenew' + (new Date).getTime();
                                editor.dom.setAttrib(selectedNode, 'id', selectedId);
                            }
                            scope.$emit('tinymce.multipleimage.open', function(urls, isShowName) {
                                var i, t, url, data, dom, pElm;
                                t = (new Date()).getTime();
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
                                editor.save();
                            });
                        }
                    });
                },
                init_instance_callback: function() {
                    scope.initialized = true;
                    if (scope.content !== undefined) {
                        tinymce.get(scope.id).setContent(scope.content);
                        tinymce.get(scope.id).undoManager.clear();
                        scope.setContentDone = true;
                    }
                    if (scope.contenteditable !== undefined) {
                        $(tinymce.activeEditor.getBody()).attr('contenteditable', scope.contenteditable);
                    }
                    scope.$emit('tinymce.instance.init');
                }
            };
            if (scope.toolbar) {
                tinymceConfig.toolbar += ' ' + scope.toolbar;
            }
            setTimeout(function() {
                tinymce.init(tinymceConfig);
            }, 0);
            scope.setContentDone = false;
            scope.$watch('content', function(nv) {
                if (!scope.setContentDone && nv && nv.length && scope.initialized) {
                    tinymce.get(scope.id).setContent(nv);
                    tinymce.get(scope.id).undoManager.clear();
                    scope.setContentDone = true;
                }
            });
            scope.$on('$destroy', function() {
                var tinyInstance;
                if (tinyInstance = tinymce.get(scope.id)) {
                    tinyInstance.remove();
                    tinyInstance = null;
                }
            });
        }
    }
});