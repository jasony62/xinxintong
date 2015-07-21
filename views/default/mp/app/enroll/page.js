(function () {
    xxtApp.register.controller('pageCtrl', ['$scope', 'http2', '$modal', '$timeout', 'Mp', function ($scope, http2, $modal, $timeout, Mp) {
        $scope.$parent.subView = 'page';
        var addWrap = function (page, name, attrs, html) {
            var dom, body, wrap, newWrap, selection, activeEditor;
            activeEditor = tinymce.get(page.name);
            dom = activeEditor.dom;
            body = activeEditor.getBody();
            selection = activeEditor.selection;
            wrap = selection.getNode();
            if (wrap === body) {
                newWrap = dom.add(body, name, attrs, html);
            } else {
                while (wrap.parentNode !== body)
                    wrap = wrap.parentNode;
                newWrap = dom.create(name, attrs, html);
                dom.insertAfter(newWrap, wrap);
            }
            selection.setCursorLocation(newWrap, 0);
            activeEditor.save();
        };
        var extractSchema = function (html) {
            var extractModelId = function (model) {
                var id;
                if (id = model.match(/ng-model=\"data\.(.+?)\"/)) {
                    id = id.pop().replace('ng-model="data.', '').replace('"', '');
                    return id;
                }
                return false;
            };
            var extractRadioModelOp = function (model) {
                var v, l;
                if (v = schema.match(/value=\"(.+?)\"/))
                    v = v.pop().replace('value=', '').replace(/\"/g, '');
                if (l = schema.match(/data-label=\"(.+?)\"/))
                    l = l.pop().replace('data-label=', '').replace(/\"/g, '');
                return { v: v, l: l };
            };
            var extractCheckboxModelOp = function (model) {
                var v, l;
                if (v = schema.match(/ng-model=\"(.+?)\"/))
                    v = v.pop().replace('ng-model=', '').replace(/\"/g, '').split('.').pop();
                if (l = schema.match(/data-label=\"(.+?)\"/))
                    l = l.pop().replace('data-label=', '').replace(/\"/g, '');
                return { v: v, l: l };
            };
            var extractSelectModelId = function (model) {
                var id;
                if (id = model.match(/name=\"data\.(.+?)\"/)) {
                    id = id.pop().replace('name="data.', '').replace('"', '');
                    return id;
                }
                return false;
            };
            var extractSelectModelOp = function (model) {
                var v, l;
                if (v = schema.match(/value=\"(.+?)\"/))
                    v = v.pop().replace('value=', '').replace(/\"/g, '');
                if (l = schema.match(/data-label=\"(.+?)\"/))
                    l = l.pop().replace('data-label=', '').replace(/\"/g, '');
                return { v: v, l: l };
            };
            var defs = {}, i, schemas, schema, type, title, modelId;
            schemas = html.match(/<(div|li|option).+?wrap=(.+?)>.+?<\/(div|li|option)>/gi);
            for (i in schemas) {
                schema = schemas[i];
                type = schema.match(/wrap=\".+?\"/).pop().replace('wrap=', '').replace(/\"/g, '');
                switch (type) {
                    case 'input':
                    case 'radio':
                    case 'checkbox':
                        title = schema.match(/\btitle=\".*?\"/);
                        try {
                            title = title.pop().replace('title=', '').replace(/\"/g, '');
                        } catch (e) {
                            alert('登记项数据格式错误，请检查');
                            console.log('eee:' + schema, e);
                        }
                        if (schema.match(/(<textarea|type=\"text\")/)) {
                            if (modelId = extractModelId(schema))
                                defs[modelId] = { id: modelId, title: title, type: type };
                        } else if (schema.match(/type=\"radio\"/)) {
                            if (modelId = extractModelId(schema)) {
                                if (defs[modelId] === undefined)
                                    defs[modelId] = { id: modelId, title: title, type: type, op: [] };
                                defs[modelId].op.push(extractRadioModelOp(schema));
                            }
                        } else if (schema.match(/type=\"checkbox\"/)) {
                            if (modelId = extractModelId(schema)) {
                                modelId = modelId.split('.')[0];
                                if (defs[modelId] === undefined)
                                    defs[modelId] = { id: modelId, title: title, type: type, op: [] };
                                defs[modelId].op.push(extractCheckboxModelOp(schema));
                            }
                        }
                        break;
                    case 'option':
                        title = schema.match(/\btitle=\".*?\"/).pop().replace('title=', '').replace(/\"/g, '');
                        if (modelId = extractSelectModelId(schema)) {
                            if (defs[modelId] === undefined)
                                defs[modelId] = { id: modelId, title: title, type: type, op: [] };
                            defs[modelId].op.push(extractSelectModelOp(schema));
                        }
                        break;
                    case 'img':
                        title = schema.match(/title=\".*?\"/).pop().replace('title=', '').replace(/\"/g, '');
                        if (title.length === 0) title = '（没有指定字段标题）';
                        if (modelId = schema.match(/ng-repeat=\"img in data\.(.+?)\"/)) {
                            modelId = modelId.pop().replace(/ng-repeat=\"img in data\./, '').replace(/\"/g, '');
                            defs[modelId] = { id: modelId, title: title, type: type };
                        }
                        break;
                }
            }
            return defs;
        };
        var CusdataCtrl = function ($scope, $modalInstance) {
            var key;
            key = 'c' + (new Date()).getTime();
            $scope.def = { key: key, type: '0', name: '', showname: '1', component: 'R', align: 'V', count: 1 };
            $scope.addOption = function () {
                if ($scope.def.ops === undefined)
                    $scope.def.ops = [];
                var newOp = { text: '' };
                $scope.def.ops.push(newOp);
                $timeout(function () { $scope.$broadcast('xxt.editable.add', newOp); });
            };
            $scope.addAttr = function () {
                $scope.def.attrs === undefined && ($scope.def.attrs = []);
                var newAttr = { name: '', value: '' };
                $scope.def.attrs.push(newAttr);
            };
            $scope.$on('xxt.editable.remove', function (e, op) {
                var i = $scope.def.ops.indexOf(op);
                $scope.def.ops.splice(i, 1);
            });
            $scope.ok = function () {
                $modalInstance.close($scope.def);
            };
            $scope.cancel = function () {
                $modalInstance.dismiss();
            };
        };
        var embedRecord = function (page, def) {
            if (def.schema === undefined) return;
            var i, s, html;
            for (i in def.schema) {
                s = def.schema[i];
                if (!s.checked) continue;
                switch (s.type) {
                    case 'input':
                        addWrap(page, 'div', { wrap: 'text', class: 'form-group' }, '<label>' + s.title + '</label><p class="form-control-static">{{Record.current.data.' + s.id + '}}</p>');
                        break;
                    case 'radio':
                    case 'checkbox':
                    case 'option':
                        addWrap(page, 'div', { wrap: 'text', class: 'form-group' }, '<label>' + s.title + '</label><p class="form-control-static">{{Record.current.data.' + s.id + '}}</p>');
                        break;
                    case 'img':
                        addWrap(page, 'div', { wrap: 'text', class: 'form-group' }, '<label>' + s.title + '</label><ul><li ng-repeat="img in Record.current.data.' + s.id + '.split(\',\')"><img ng-src="{{img}}"></li></ul>');
                        break;
                }
            }
            if (def.addEnrollAt) {
                html = "<label>登记时间</label><p>{{(Record.current.enroll_at*1000)|date:'yyyy-MM-dd HH:mm'}}</p>";
                addWrap(page, 'div', { wrap: 'text', class: 'form-group' }, html);
            }
            if (def.addNickname) {
                html = "<label>昵称</label><p>{{Record.current.enroller.nickname}}</p>";
                addWrap(page, 'div', { wrap: 'text', class: 'form-group' }, html);
            }
        };
        var embedList = function (page, def) {
            var dataApi, onclick, html;
            dataApi = def.dataScope === 'A' ? "Record.nextPage()" : "Record.nextPage('user')";
            onclick = def.onclick.length ? " ng-click=\"gotoPage($event,'" + def.onclick + "',r.enroll_key)\"" : '';
            html = '<ul class="list-group" infinite-scroll="' + dataApi + '" infinite-scroll-disabled="Record.busy" infinite-scroll-distance="1">';
            html += '<li class="list-group-item" ng-repeat="r in Record.list"' + onclick + '>';
            if (def.addEnrollAt) {
                html += "<div><label>登记时间</label><div>{{(r.enroll_at*1000)|date:'yyyy-MM-dd HH:mm'}}</div></div>";
            }
            if (def.addNickname) {
                html += "<div><label>昵称</label><div>{{r.nickname}}</div></div>";
            }
            if (def.schema) {
                var i, s;
                for (i in def.schema) {
                    s = def.schema[i];
                    if (!s.checked) continue;
                    switch (s.type) {
                        case 'input':
                            html += '<div class="form-group"><label>' + s.title + '</label><p class="form-control-static">{{r.data.' + s.id + '}}</p></div>';
                            break;
                        case 'radio':
                        case 'checkbox':
                        case 'option':
                            html += '<div class="form-group"><label>' + s.title + '</label><p class="form-control-static">{{r.data.' + s.id + '}}</p></div>';
                            break;
                        case 'img':
                            html += '<div class="form-group"><label>' + s.title + '</label><ul><li ng-repeat="img in r.data.' + s.id + '.split(\',\')"><img ng-src="{{img}}"></li></ul></div>';
                            break;
                    }
                }
            }
            if (def.canLike === 'Y') {
                html += '<div title="总赞数">{{r.score}}</div>';
                html += "<div ng-if='!r.myscore'><a href='javascript:void(0)' ng-click='Record.like($event,r)'>赞</a></div>";
                html += "<div ng-if='r.myscore==1'>已赞</div>";
            }
            html += "</li></ul>";
            addWrap(page, 'div', { wrap: 'list', class: 'form-group' }, html);
        };
        var embedRounds = function (page, def) {
            var onclick, html;
            onclick = def.onclick.length ? " ng-click=\"gotoPage($event,'" + def.onclick + "',null,r.rid)\"" : '';
            html = "<ul class='list-group' tms-init='Round.nextPage()'><li class='list-group-item' ng-repeat='r in Round.list'" + onclick + "><div>{{r.title}}</div></li></ul>";
            addWrap(page, 'div', { wrap: 'list', class: 'form-group' }, html);
        };
        var embedRemarks = function (page, def) {
            var html;
            html = "<ul class='list-group'><li class='list-group-item' ng-repeat='r in Record.current.remarks'><div>{{r.remark}}</div><div>{{r.nickname}}</div><div>{{(r.create_at*1000)|date:'yyyy-MM-dd HH:mm'}}</div></li></ul>";
            addWrap(page, 'div', { wrap: 'list', class: 'form-group' }, html);
        };
        $scope.innerlinkTypes = [
            { value: 'article', title: '单图文', url: '/rest/mp/matter' },
            { value: 'news', title: '多图文', url: '/rest/mp/matter' },
            { value: 'channel', title: '频道', url: '/rest/mp/matter' }
        ];
        $scope.embedInput = function (page) {
            $modal.open({
                templateUrl: 'embedInputLib.html',
                controller: CusdataCtrl,
                backdrop: 'static',
            }).result.then(function (def) {
                var key, inpAttrs, html, fn;
                key = def.key;
                inpAttrs = { wrap: 'input', class: 'form-group' };
                (def.showname == 1 && def.name && def.name.length) && addWrap(page, 'div', { wrap: 'text', class: 'form-group' }, def.name);
                switch (def.type) {
                    case '0':
                        addWrap(page, 'div', inpAttrs, '<input type="text" ng-model="data.name" title="姓名" placeholder="姓名" class="form-control input-lg">');
                        break;
                    case '1':
                        addWrap(page, 'div', inpAttrs, '<input type="text" ng-model="data.mobile" title="手机" placeholder="手机" class="form-control input-lg">');
                        break;
                    case '2':
                        addWrap(page, 'div', inpAttrs, '<input type="text" ng-model="data.email" title="邮箱" placeholder="邮箱" class="form-control input-lg">');
                        break;
                    case '3':
                        html = '<input type="text" ng-model="data.' + key + '"';
                        html += ' title="' + def.name + '"';
                        html += ' placeholder="' + def.name + '"';
                        def.required == 1 && (html += 'required=""');
                        html += ' class="form-control input-lg">';
                        addWrap(page, 'div', inpAttrs, html);
                        break;
                    case '4':
                        html = '<textarea ng-model="data.' + key + '"';
                        html += ' title="' + def.name + '"';
                        html += ' placeholder="' + def.name + '"';
                        def.required == 1 && (html += 'required=""');
                        html += ' class="form-control input-lg" rows="3">' + def.name + '</textarea>';
                        addWrap(page, 'div', inpAttrs, html);
                        break;
                    case '5':
                        if (def.ops && def.ops.length > 0) {
                            if (def.component === 'R') {
                                html = '', cls = 'radio';
                                if (def.align === 'H') cls += '-inline'
                                for (var i in def.ops) {
                                    html += '<li class="' + cls + '" wrap="radio"><label';
                                    if (def.align === 'H') html += ' class="radio-inline"';
                                    html += '><input type="radio" name="' + key + '"';
                                    html += ' value="' + i + '"';
                                    html += ' ng-model="data.' + key + '"';
                                    def.required == 1 && (html += 'required=""');
                                    html += ' title="' + def.name + '"';
                                    for (var a in def.attrs) {
                                        html += 'data-' + def.attrs[a].name + '="' + def.attrs[a].value + '"';
                                    }
                                    html += ' data-label="' + def.ops[i].text + '"><span>' + def.ops[i].text + '</span></label></li>';
                                }
                                addWrap(page, 'ul', { class: 'form-group' }, html);
                            } else if (def.component === 'S') {
                                html = '<select class="form-control input-lg" ng-model="data.' + key + '"';
                                def.required == 1 && (html += 'required=""');
                                html += ' title="' + def.name + '">';
                                for (var i in def.ops) {
                                    html += '<option wrap="option" name="data.' + key + '" value="' + i + '"' + 'data-label="' + def.ops[i].text + '"' + 'title="' + def.name + '"' + '>' + def.ops[i].text + '</option>';
                                }
                                html += '</select>';
                                addWrap(page, 'div', { class: 'form-group', wrap: 'select' }, html);
                            }
                        }
                        break;
                    case '6':
                        if (def.ops && def.ops.length > 0) {
                            var cls;
                            html = '';
                            cls = 'checkbox';
                            if (def.align === 'H') cls += '-inline';
                            for (var i in def.ops) {
                                html += '<li class="' + cls + '" wrap="checkbox"><label';
                                if (def.align === 'H') html += ' class="checkbox-inline"';
                                html += '><input type="checkbox" name="' + key + '"';
                                def.required == 1 && (html += 'required=""');
                                html += ' ng-model="data.' + key + '.' + i + '"';
                                html += ' title="' + def.name + '" data-label="' + def.ops[i].text + '"><span>' + def.ops[i].text + '</span></label></li>';
                            }
                            addWrap(page, 'ul', { class: 'form-group' }, html);
                        }
                        break;
                    case '7':
                        html = '';
                        html += '<li wrap="img" ng-repeat="img in data.' + key + '" class="img-thumbnail" title="' + def.name + '">';
                        html += '<img flex-img>';
                        html += '<button class="btn btn-default btn-xs" ng-click="removeImage(data.' + key + ',$index)"><span class="glyphicon glyphicon-remove"></span></button>';
                        html += '</li>';
                        html += '<li class="img-picker">';
                        html += '<button class="btn btn-default" ng-click="chooseImage(\'' + key + '\',' + def.count + ')"><span class="glyphicon glyphicon-picture"></span><br>上传图片</button>';
                        html += '</li>';
                        addWrap(page, 'ul', { class: 'form-group img-tiles clearfix', name: key }, html);
                        break;
                    case '8':
                        html = '<input type="text" ng-model="data.' + key + '"';
                        html += ' title="' + def.name + '"';
                        html += ' placeholder="' + def.name + '"';
                        def.required == 1 && (html += 'required=""');
                        html += ' class="form-control">';
                        html += '<span class="input-group-btn">';
                        fn = 'getMyLocation(\'' + key + '\')';
                        html += '<button class="btn btn-default" type="button" ng-click="' + fn + '">定位</button>';
                        html += '</span>';
                        addWrap(page, 'div', { wrap: 'input', class: 'form-group input-group input-group-lg' }, html);
                        break;
                }
            });
        };
        $scope.embedButton = function (page) {
            $modal.open({
                templateUrl: 'embedButtonLib.html',
                controller: ['$scope', '$modalInstance', 'pages', function ($scope, $mi, pages) {
                    $scope.buttons = [
                        ['submit', '提交信息'],
                        ['addRecord', '新增登记'],
                        ['editRecord', '修改登记'],
                        ['likeRecord', '点赞'],
                        ['remarkRecord', '评论'],
                        ['gotoPage', '页面导航'],
                        ['closeWindow', '关闭页面']
                    ];
                    $scope.pages = pages;
                    $scope.def = { type: '0', label: '', next: '' };
                    $scope.ok = function () { $mi.close($scope.def); };
                    $scope.cancel = function () { $mi.dismiss(); };
                }],
                backdrop: 'static',
                resolve: {
                    pages: function () { return $scope.editing.pages; }
                }
            }).result.then(function (def) {
                var attrs = { wrap: 'button', class: 'form-group' }
                    , tmplBtn = function (id, action, label) {
                        return '<button id="' + id + '" class="btn btn-primary btn-block btn-lg" ng-click="' + action + '"><span>' + label + '</span></button>';
                    }
                    , args = def.next ? "($event,'" + def.next + "')" : "($event)"
                    , button = def.type[0];
                switch (button) {
                    case 'submit':
                        addWrap(page, 'div', attrs, tmplBtn('btnSubmit', "submit" + args, def.label));
                        break;
                    case 'addRecord':
                        addWrap(page, 'div', attrs, tmplBtn('btnNewRecord', "addRecord" + args, def.label));
                        break;
                    case 'editRecord':
                        addWrap(page, 'div', attrs, tmplBtn('btnNewRecord', "gotoPage($event,'form',Record.current.enroll_key)", def.label));
                        break;
                    case 'likeRecord':
                        addWrap(page, 'div', attrs, tmplBtn('btnLikeRecord', "Record.like($event)", def.label));
                        break;
                    case 'remarkRecord':
                        var html = '<input type="text" class="form-control" placeholder="评论" ng-model="newRemark">';
                        html += '<span class="input-group-btn">';
                        html += '<button class="btn btn-success" type="button" ng-click="Record.remark($event,newRemark)">发送</button>';
                        html += '</span>';
                        addWrap(page, 'div', { class: 'form-group input-group input-group-lg' }, html);
                        break;
                    case 'gotoPage':
                        addWrap(page, 'div', attrs, tmplBtn('btnGotoPage_' + def.next, "gotoPage" + args, def.label));
                        break;
                    case 'closeWindow':
                        addWrap(page, 'div', attrs, tmplBtn('btnCloseWindow', 'closeWindow($event)', def.label));
                        break;
                }
            });
        };
        $scope.embedShow = function (page) {
            $modal.open({
                templateUrl: 'embedShowLib.html',
                backdrop: 'static',
                resolve: {
                    pages: function () { return $scope.editing.pages; },
                    schema: function () {
                        var i, page, s, s2;
                        s = extractSchema($scope.editing.pages.form.html);
                        for (i in $scope.editing.pages) {
                            page = $scope.editing.pages[i];
                            if (page.type && page.type === 'I') {
                                s2 = extractSchema(page.html);
                                s = angular.extend(s, s2);
                            }
                        }
                        return s;
                    }
                },
                controller: ['$scope', '$modalInstance', 'pages', 'schema', function ($scope, $mi, pages, schema) {
                    $scope.pages = pages;
                    $scope.def = { type: 'record', dataScope: 'U', canLike: 'N', onclick: '', addEnrollAt: 0, addNickname: 0 };
                    $scope.def.schema = schema;
                    $scope.ok = function () { $mi.close($scope.def); };
                    $scope.cancel = function () { $mi.dismiss(); };
                }]
            }).result.then(function (def) {
                switch (def.type) {
                    case 'record':
                        embedRecord(page, def);
                        break;
                    case 'list':
                        embedList(page, def);
                        break;
                    case 'rounds':
                        embedRounds(page, def);
                        break;
                    case 'remarks':
                        embedRemarks(page, def);
                        break;
                }
            });
        };
        $scope.embedMatter = function (page) {
            $scope.$broadcast('mattersgallery.open', function (matters, type) {
                var editor, dom, i, matter, mtype, fn;
                editor = tinymce.get(page.name);
                dom = editor.dom;
                for (i = 0; i < matters.length; i++) {
                    matter = matters[i];
                    mtype = matter.type ? matter.type : type;
                    fn = "openMatter(" + matter.id + ",'" + mtype + "')";
                    console.log('fn', fn);
                    editor.insertContent(dom.createHTML('div', { 'wrap': 'link', 'class': 'matter-link' }, dom.createHTML('a', {
                        href: '#',
                        "ng-click": fn,
                    }, dom.encode(matter.title))));
                }
            });
        };
        $scope.embedMember = function (page) {
            $modal.open({
                templateUrl: 'embedMemberLib.html',
                backdrop: 'static',
                controller: ['$modalInstance', '$scope', 'Mp', function ($mi, $scope, Mp) {
                    (new Mp()).getAuthapis().then(function (data) {
                        $scope.authapis = data;
                    });
                    $scope.selected = {};
                    $scope.authAttrs = [];
                    $scope.ok = function () {
                        $scope.selected.attrs = [];
                        for (var i = 0, l = $scope.authAttrs.length; i < l; i++) {
                            $scope.authAttrs[i].checked && $scope.selected.attrs.push($scope.authAttrs[i]);
                        }
                        $mi.close($scope.selected);
                    };
                    $scope.cancel = function () { $mi.dismiss(); };
                    $scope.shiftAuthapi = function () {
                        var auth = $scope.selected.authapi, authAttrs = [];
                        auth.attr_name[0] === '0' && (authAttrs.push({ id: 'name', label: '姓名' }));
                        auth.attr_mobile[0] === '0' && (authAttrs.push({ id: 'mobile', label: '手机' }));
                        auth.attr_email[0] === '0' && (authAttrs.push({ id: 'email', label: '邮箱' }));
                        auth.extattr && auth.extattr.length && (authAttrs = authAttrs.concat(auth.extattr));
                        $scope.authAttrs = authAttrs;
                    };
                }],
            }).result.then(function (data) {
                var inpAttrs = { wrap: 'input', class: 'form-group member' }, tpl, html, attr;
                tpl = '<input type="text" ng-init="data.member.authid=' + data.authapi.authid + '" ng-model="data.member.%id%" title="%label%" placeholder="%label%" class="form-control input-lg">';
                for (var i = 0, l = data.attrs.length; i < l; i++) {
                    attr = data.attrs[i];
                    html = tpl.replace(/%\w+%/g, function (pl) {
                        return attr[pl.replace(/%/g, '')];
                    });
                    addWrap(page, 'div', inpAttrs, html);
                }
            });
        };
        $scope.$on('tinymce.wrap.select', function (event, wrap) {
            $scope.$apply(function () {
                var root = wrap;
                while (root.parentNode) root = root.parentNode;
                $(root).find('.active').removeClass('active');
                $scope.hasActiveWrap = false;
                if (wrap.hasAttribute('wrap')) {
                    wrap.classList.add('active');
                    $scope.hasActiveWrap = true;
                }
            });
        });
        $scope.editWrap = function (page) {
        };
        $scope.removeWrap = function (page) {
            var editor;
            editor = tinymce.get(page.name);
            $(editor.getBody()).find('.active').remove();
            $scope.hasActiveWrap = false;
            editor.save();
        };
        $scope.upWrap = function (page) {
            var editor, active;
            editor = tinymce.get(page.name);
            active = $(editor.getBody()).find('.active');
            active.prev().before(active);
            editor.save();
        };
        $scope.downWrap = function (page) {
            var editor, active;
            editor = tinymce.get(page.name);
            active = $(editor.getBody()).find('.active');
            active.next().after(active);
            editor.save();
        };
        $scope.$on('tinymce.multipleimage.open', function (event, callback) {
            $scope.$broadcast('picgallery.open', callback, true, true);
        });
        $scope.addPage = function () {
            http2.get('/rest/mp/app/enroll/addPage?aid=' + $scope.aid, function (rsp) {
                var page = rsp.data;
                $scope.editing.pages[page.name] = page;
                $scope.extraPages[page.name] = page;
                $timeout(function () {
                    $('a[href="#tab_' + page.name + '"]').tab('show');
                });
            });
        };
        window.onbeforeunload = function (e) {
            var i, p, message, modified;
            modified = false;
            for (i in $scope.editing.pages) {
                p = $scope.editing.pages[i];
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
        $scope.onPageChange = function (page) {
            page.$$modified = page.html !== $scope.persisted.pages[page.name].html;
        };
        $scope.updPage = function (page, name) {
            if (!angular.equals($scope.editing, $scope.persisted)) {
                if (name === 'html') {
                    var editor;
                    editor = tinymce.get(page.name);
                    if ($(editor.getBody()).children('.active').length) {
                        $(editor.getBody()).children('.active').removeClass('active');
                        $scope.hasActiveWrap = false;
                        page.html = $(editor.getBody()).html();
                    }
                }
                $scope.$root.progmsg = '正在保存页面...';
                var url, p = {};
                p[name] = name === 'html' ? encodeURIComponent(page[name]) : page[name];
                url = '/rest/mp/app/enroll/updPage';
                url += '?aid=' + $scope.aid;
                url += '&pid=' + page.id;
                url += '&pname=' + page.name;
                url += '&cid=' + page.code_id;
                http2.post(url, p, function (rsp) {
                    $scope.persisted = angular.copy($scope.editing);
                    page.$$modified = false;
                    $scope.$root.progmsg = '';
                });
            }
        };
        $scope.delPage = function (page) {
            var url = '/rest/mp/app/enroll/delPage';
            url += '?aid=' + $scope.aid;
            url += '&pid=' + page.id;
            http2.get(url, function (rsp) {
                tinymce.remove('#' + page.name);
                delete $scope.editing.pages[page.name];
                delete $scope.extraPages[page.name];
                $timeout(function () {
                    $('a[href="#tab_form"]').tab('show');
                });
            });
        };
        $scope.shiftPage = function (event) {
            event.preventDefault();
            $(event.target).tab('show');
        };
        $scope.gotoCode = function (codeid) {
            window.open('/rest/code?pid=' + codeid, '_self');
        };
        $scope.$watch('editing', function (nv) {
            if (!nv) return;
            /* extra pages */
            var extraPages = {};
            angular.forEach($scope.editing.pages, function (value, key) {
                key !== 'form' && (extraPages[key] = value);
            });
            $scope.extraPages = extraPages;
            /* schema */
            var i, page, s, s2;
            s = extractSchema($scope.editing.pages.form.html);
            for (i in $scope.editing.pages) {
                page = $scope.editing.pages[i];
                if (page.type && page.type === 'I') {
                    s2 = extractSchema(page.html);
                    s = angular.extend(s, s2);
                }
            }
            $scope.schema = s;
        });
    }]);
})();
