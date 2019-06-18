angular.module('tinymce.ui.xxt', ['ui.bootstrap']).
directive('tinymce', function ($timeout) {
    return {
        restrict: 'EA',
        scope: {
            id: '@',
            height: '=',
            contenteditable: '=',
            toolbar: '@',
        },
        replace: true,
        template: '<textarea></textarea>',
        link: function (scope, elem, attrs) {
            var tinymceConfig = {
                selector: '#' + scope.id,
                language: 'zh_CN',
                theme: 'modern',
                skin: 'light',
                menubar: false,
                statusbar: false,
                plugins: ['save textcolor code table paste fullscreen visualblocks'],
                toolbar: 'fontsizeselect styleselect forecolor backcolor bullist numlist outdent indent table multipleimage',
                height: scope.height ? scope.height : 300,
                forced_root_block: 'p',
                valid_elements: "*[*]",
                relative_urls: false,
                content_css: '/static/css/bootstrap.min.css,/static/css/tinymce.css?v=' + (new Date() * 1),
                setup: function (editor) {
                    /*编辑节点*/
                    (function () {
                        var _lastContent;
                        editor.on('keydown', function (evt) {
                            var selection = editor.selection,
                                node = selection.getNode();
                            _lastContent = node.innerHTML;
                        });
                        editor.on('keyup', function (evt) {
                            var content = editor.selection.getNode().innerHTML,
                                phase;
                            if (_lastContent !== content) {
                                phase = scope.$root.$$phase;
                                if (phase === '$digest' || phase === '$apply') {
                                    scope.$emit('tinymce.content.change', {
                                        node: editor.selection.getNode(),
                                        content: content
                                    });
                                } else {
                                    scope.$apply(function () {
                                        scope.$emit('tinymce.content.change', {
                                            node: editor.selection.getNode(),
                                            content: content
                                        });
                                    });
                                }
                            }
                        });
                    })();
                    editor.on('change', function (e) {
                        var phase;
                        phase = scope.$root.$$phase;
                        if (phase === '$digest' || phase === '$apply') {
                            scope.$emit('tinymce.content.change', false);
                        } else {
                            scope.$apply(function () {
                                scope.$emit('tinymce.content.change', false);
                            });
                        }
                    });
                    editor.on('BeforeSetContent', function (e) {
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
                    editor.on('ExecCommand', function (e) {
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
                        onclick: function () {
                            var selectedNode, selectedId, tmpId = false;
                            selectedNode = editor.selection.getNode();
                            selectedId = editor.dom.getAttrib(selectedNode, 'id');
                            if (!selectedId) {
                                tmpId = true;
                                selectedId = '__mcenew' + (new Date() * 1);
                                editor.dom.setAttrib(selectedNode, 'id', selectedId);
                            }
                            scope.$emit('tinymce.multipleimage.open', function (urls, isShowName) {
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
                },
                init_instance_callback: function () {
                    var editor = tinymce.get(scope.id);
                    if (scope.content) {
                        editor.setContent(scope.content);
                        editor.undoManager.clear();
                    }
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
            scope.$on('$destroy', function () {
                var editor;
                if (editor = tinymce.get(scope.id)) {
                    editor.remove();
                    editor = null;
                }
            });
            tinymce.init(tinymceConfig);
        }
    };
});