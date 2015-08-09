
xxtApp = angular.module('xxt', ['ui.tms', 'matters.xxt']);
xxtApp.config(['$locationProvider', function ($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
xxtApp.directive('headingPic', function () {
    return {
        restrict: 'A',
        link: function (scope, elem, attrs) {
            var w, h;
            w = $(elem).width();
            h = w / 9 * 5;
            $(elem).css('max-height', h);
        }
    };
});
xxtApp.controller('enrollCtrl', ['$rootScope', '$scope', '$location', 'http2', function ($rootScope, $scope, $location, http2) {
    var appid;
    appid = $location.search().id;
    $scope.mpid = $location.search().mpid;
    $scope.update = function (name) {
        if (!angular.equals($scope.editing, $scope.persisted)) {
            var p = {};
            p[name] = $scope.editing[name];
            http2.post('/rest/member/box/enroll/update?mpid=' + $scope.mpid + '&id=' + appid, p, function (rsp) {
                $scope.persisted = angular.copy($scope.editing);
            });
        }
    };
    $scope.preview = function () {
        location.href = '/rest/app/enroll?mpid=' + $scope.mpid + '&aid=' + appid + '&preview=Y';
    };
    http2.get('/rest/member/box/enroll/get?mpid=' + $scope.mpid + '&id=' + appid, function (rsp) {
        $scope.editing = rsp.data;
        $scope.editing.type = 'enroll';
        $scope.editing.pages.form.title = '登记信息页';
        $scope.editing.canSetReceiver = 'Y';
        $scope.persisted = angular.copy($scope.editing);
    });
    $scope.$on('xxt.float-toolbar.shop.open', function (event) {
        $scope.$emit('mattershop.new', $scope.mpid, $scope.editing);
    });
}]).controller('settingCtrl', ['$scope', 'http2', function ($scope, http2, $modal) {
    var openPickImageFrom = function () {
        var st = (document.body && document.body.scrollTop) ? document.body.scrollTop : document.documentElement.scrollTop;
        var ch = document.documentElement.clientHeight;
        var cw = document.documentElement.clientWidth;
        var $dlg = $('#pickImageFrom');
        $dlg.css({
            'display': 'block',
            'top': (st + (ch - $dlg.height() - 30) / 2) + 'px',
            'left': ((cw - $dlg.width() - 30) / 2) + 'px'
        });
    };
    $scope.chooseImage = function (from) {
        if (window.wx !== undefined) {
            wx.chooseImage({
                success: function (res) {
                    $scope.editing.pic = res.localIds[0];
                    $scope.$apply('editing.pic');
                    $scope.update('pic');
                }
            });
        } else if (window.YixinJSBridge) {
            if (from === undefined) {
                openPickImageFrom();
                return;
            }
            $('#pickImageFrom').hide();
            YixinJSBridge.invoke(
                'pickImage', {
                    type: from,
                    quality: 100
                }, function (result) {
                    if (result.data && result.data.length) {
                        $scope.editing.pic = 'data:' + result.mime + ';base64,' + result.data;
                        $scope.$apply('editing.pic');
                        $scope.update('pic');
                    }
                }
                );
        } else {
            var eleInp = document.createElement('input');
            eleInp.setAttribute('type', 'file');
            eleInp.addEventListener('change', function (evt) {
                var cnt, f, type;
                cnt = evt.target.files.length;
                f = evt.target.files[0];
                type = { ".jp": "image/jpeg", ".pn": "image/png", ".gi": "image/gif" }[f.name.match(/\.(\w){2}/g)[0] || ".jp"];
                f.type2 = f.type || type;
                var reader = new FileReader();
                reader.onload = (function (theFile) {
                    return function (e) {
                        $scope.editing.pic = e.target.result.replace(/^.+(,)/, "data:" + theFile.type2 + ";base64,");
                        $scope.$apply('editng.pic');
                        $scope.update('pic');
                    };
                })(f);
                reader.readAsDataURL(f);
            }, false);
            eleInp.click();
        }
    };
    $scope.removePic = function () {
        $scope.editing.pic = '';
        $scope.update('pic');
    };
}]).controller('pageCtrl', ['$scope', 'http2', '$modal', '$timeout', function ($scope, http2, $modal, $timeout) {
    var addWrap = function (page, name, attrs, html) {
        var dom, body, wrap, newWrap, selection, activeEditor;
        activeEditor = tinymce.get(page.name);
        dom = activeEditor.dom;
        body = activeEditor.getBody();
        selection = activeEditor.selection
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
        activeEditor.focus();
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
        var defs = {}, i, schemas, schema, type, title, modelId;
        schemas = html.match(/<(div|li).+?wrap=(.+?)>.+?<\/(div|li)>/gi);
        for (i in schemas) {
            schema = schemas[i];
            type = schema.match(/wrap=\".+?\"/).pop().replace('wrap=', '').replace(/\"/g, '');
            switch (type) {
                case 'input':
                case 'radio':
                case 'checkbox':
                    title = schema.match(/\btitle=\".*?\"/).pop().replace('title=', '').replace(/\"/g, '');
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
        $scope.def = { type: '0', name: '', showname: '1', align: 'V' };
        $scope.addOption = function () {
            if ($scope.def.ops === undefined)
                $scope.def.ops = [];
            var newOp = { text: '' };
            $scope.def.ops.push(newOp);
            $timeout(function () { $scope.$broadcast('xxt.editable.add', newOp); });
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
        var i, s;
        for (i in def.schema) {
            s = def.schema[i];
            if (!s.checked) continue;
            switch (s.type) {
                case 'input':
                    addWrap(page, 'div', { wrap: 'text', class: 'form-group' }, '<label>' + s.title + '</label><p class="form-control-static">{{Record.current.data.' + s.id + '}}</p>');
                    break;
                case 'radio':
                case 'checkbox':
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
        page.html = tinymce.get(page.name).getContent();
        $scope.updPage(page, 'html');
    };
    var embedRemarks = function (page, def) {
        var ctrl, dataApi, onclick, html, js;
        html = "<ul class='list-group'><li class='list-group-item' ng-repeat='r in Record.current.remarks'><div>{{r.remark}}</div><div>{{r.nickname}}</div><div>{{(r.create_at*1000)|date:'yyyy-MM-dd HH:mm'}}</div></li></ul>";
        addWrap(page, 'div', { wrap: 'list', class: 'form-group' }, html);
    };
    $scope.embedInput = function (page) {
        $modal.open({
            templateUrl: 'embedInputLib.html',
            controller: CusdataCtrl,
            backdrop: 'static',
        }).result.then(function (def) {
            var cus = '', key, inpAttrs;
            key = 'c' + (new Date()).getTime();
            inpAttrs = { wrap: 'input', class: 'form-group' };
            if (def.showname == 1)
                addWrap(page, 'div', { wrap: 'text', class: 'form-group' }, def.name);
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
                    addWrap(page, 'div', inpAttrs, '<input type="text" ng-model="data.' + key + '" title="' + def.name + '" placeholder="' + def.name + '" class="form-control input-lg">');
                    break;
                case '4':
                    addWrap(page, 'div', inpAttrs, '<textarea ng-model="data.' + key + '" title="' + def.name + '" placeholder="' + def.name + '" class="form-control input-lg" rows="3">' + def.name + '</textarea>');
                    break;
                case '5':
                    if (def.ops && def.ops.length > 0) {
                        var html = '', cls = 'radio';
                        if (def.align === 'H') cls += '-inline'
                        for (var i in def.ops) {
                            html += '<li class="' + cls + '" wrap="radio"><label';
                            if (def.align === 'H') html += ' class="radio-inline"';
                            html += '><input type="radio" name="' + key + '" value="' + i + '" ng-model="data.' + key + '" title="' + def.name + '" data-label="' + def.ops[i].text + '"><span>' + def.ops[i].text + '</span></label></li>';
                        }
                        addWrap(page, 'ul', { class: 'form-group' }, html);
                    }
                    break;
                case '6':
                    if (def.ops && def.ops.length > 0) {
                        var html = '', cls = 'checkbox';
                        if (def.align === 'H') cls += '-inline'
                        for (var i in def.ops) {
                            html += '<li class="' + cls + '" wrap="checkbox"><label';
                            if (def.align === 'H') html += ' class="checkbox-inline"';
                            html += '><input type="checkbox" name="' + key + '" ng-model="data.' + key + '.' + i + '" title="' + def.name + '" data-label="' + def.ops[i].text + '"><span>' + def.ops[i].text + '</span></label></li>';
                        }
                        addWrap(page, 'ul', { class: 'form-group' }, html);
                    }
                    break;
                case '7':
                    var html = '';
                    html += '<li wrap="img" ng-repeat="img in data.' + key + '" class="img-thumbnail" title="' + def.name + '">';
                    html += '<img flex-img>';
                    html += '<button class="btn btn-default btn-xs" ng-click="removeImage(data.' + key + ',$index)"><span class="glyphicon glyphicon-remove"></span></button>';
                    html += '</li>';
                    html += '<li class="img-picker">';
                    html += '<button class="btn btn-default" ng-click="chooseImage(\'' + key + '\')"><span class="glyphicon glyphicon-picture"></span><br>上传图片</button>';
                    html += '</li>';
                    addWrap(page, 'ul', { class: 'form-group img-tiles clearfix', name: key }, html);
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
                case 'remarks':
                    embedRemarks(page, def);
                    break;
            }
        });
    };
    $scope.$on('tinymce.multipleimage.open', function (event, callback) {
        var options = {
            callback: callback,
            multiple: true,
            setshowname: true
        }
        $scope.$broadcast('mediagallery.open', options);
    });
    $scope.extraPages = function () {
        var result = {};
        angular.forEach($scope.editing.pages, function (value, key) {
            if (key !== 'form' && key !== 'result')
                result[key] = value;
        });
        return result;
    };
    $scope.addPage = function () {
        http2.get('/rest/member/box/enroll/addPage?aid=' + $scope.aid, function (rsp) {
            var page = rsp.data;
            $scope.editing.pages[page.name] = page;
            $timeout(function () {
                $('a[href="#tab_' + page.name + '"]').tab('show');
            });
        });
    };
    $scope.updPage = function (page, name) {
        if (!angular.equals($scope.editing, $scope.persisted)) {
            var url, p = {};
            p[name] = page[name];
            url = '/rest/member/box/enroll/updPage';
            url += '?mpid=' + $scope.mpid;
            url += '&cid=' + page.code_id;
            http2.post(url, p, function (rsp) {
                $scope.persisted = angular.copy($scope.editing);
            });
        }
    };
    $scope.delPage = function (page) {
        var url = '/rest/member/box/enroll/delPage';
        url += '?aid=' + $scope.aid;
        url += '&pid=' + page.id;
        http2.get(url, function (rsp) {
            tinymce.remove('#' + page.name);
            delete $scope.editing.pages[page.name];
            $timeout(function () {
                $('a[href="#tab_form"]').tab('show');
            });
        });
    };
    $scope.gotoCode = function (codeid) {
        window.open('/rest/code?pid=' + codeid);
    };
}]).controller('rollCtrl', ['$scope', 'http2', '$modal', function ($scope, http2, $modal) {
    var t = (new Date()).getTime();
    $scope.doSearch = function (page) {
        page && ($scope.page.at = page);
        var url = '/rest/member/box/enroll/records?id=' + $scope.editing.id + '&contain=total' + $scope.page.joinParams();
        http2.get(url, function (rsp) {
            if (rsp.data) {
                $scope.roll = rsp.data[0] ? rsp.data[0] : [];
                rsp.data[1] && ($scope.page.total = rsp.data[1]);
                rsp.data[2] && ($scope.cols = rsp.data[2]);
            } else
                $scope.roll = [];
        });
    };
    $scope.page = {
        at: 1,
        size: 30,
        keyword: '',
        joinParams: function () {
            var p;
            p = '&page=' + this.at + '&size=' + this.size;
            if (this.keyword !== '') {
                p += '&kw=' + this.keyword;
            }
            return p;
        }
    };
    $scope.viewUser = function (fan) {
        location.href = '/rest/mp/user?fid=' + fan.fid;
        // todo 如果是认证用户???
    };
    $scope.keywordKeyup = function (evt) {
        if (evt.which === 13)
            $scope.doSearch();
    };
    $scope.editRoll = function (rollItem) {
        var ins = $modal.open({
            templateUrl: 'editor.html',
            controller: 'editorCtrl',
            resolve: {
                rollItem: function () {
                    rollItem.aid = $scope.aid;
                    return rollItem;
                },
                tags: function () {
                    return $scope.editing.tags;
                },
                cols: function () {
                    return $scope.cols;
                }
            }
        });
        ins.result.then(function (updated) {
            var p = updated[0], tags = updated[1].join(',');
            if ($scope.editing.tags.length !== tags.length) {
                $scope.editing.tags = tags;
                $scope.update('tags');
            }
            http2.post('/rest/mp/app/enroll/updateRoll?aid=' + $scope.aid + '&ek=' + rollItem.enroll_key, p);
        });
    };
    $scope.addRoll = function () {
        var ins = $modal.open({
            templateUrl: 'editor.html',
            controller: 'editorCtrl',
            resolve: {
                rollItem: function () {
                    return { aid: $scope.aid, tags: '' };
                },
                tags: function () {
                    return $scope.editing.tags;
                },
                cols: function () {
                    return $scope.cols;
                }
            }
        });
        ins.result.then(function (updated) {
            var p = updated[0], tags = updated[1].join(',');
            if ($scope.editing.tags.length !== tags.length) {
                $scope.editing.tags = tags;
                $scope.update('tags');
            }
            http2.post('/rest/mp/app/enroll/addRoll?aid=' + $scope.aid, p, function (rsp) {
                $scope.roll.splice(0, 0, rsp.data);
            });
        });
    };
    $scope.removeRoll = function (roll) {
        var vcode;
        vcode = prompt('是否要删除登记信息？，若是，请输入活动名称。');
        if (vcode === $scope.editing.title) {
            http2.get('/rest/mp/app/enroll/removeRoll?aid=' + $scope.aid + '&key=' + roll.enroll_key, function (rsp) {
                var i = $scope.roll.indexOf(roll);
                $scope.roll.splice(i, 1);
                $scope.page.total = $scope.page.total - 1;
            });
        }
    };
    $scope.cleanAll = function () {
        var vcode;
        vcode = prompt('是否要删除所有登记信息？，若是，请输入活动名称。');
        if (vcode === $scope.editing.title) {
            http2.get('/rest/mp/app/enroll/clean?aid=' + $scope.aid, function (rsp) {
                $scope.doSearch(1);
            });
        }
    };
    $scope.doSearch();
}]).controller('editorCtrl', ['$scope', '$modalInstance', 'rollItem', 'tags', 'cols', function ($scope, $modalInstance, rollItem, tags, cols) {
    $scope.item = rollItem;
    $scope.item.aTags = (!rollItem.tags || rollItem.tags.length === 0) ? [] : rollItem.tags.split(',');
    $scope.aTags = (!tags || tags.length === 0) ? [] : tags.split(',');
    $scope.cols = cols;
    $scope.signin = function () {
        $scope.item.signin_at = Math.round((new Date()).getTime() / 1000);
    };
    $scope.ok = function () {
        var p, col;
        p = { tags: $scope.item.aTags.join(','), data: {} };
        $scope.item.tags = p.tags;
        if ($scope.item.id)
            p.signin_at = $scope.item.signin_at;
        for (var c in $scope.cols) {
            col = $scope.cols[c];
            p.data[col.id] = $scope.item.data[col.id];
        }
        $modalInstance.close([p, $scope.aTags]);
    };
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
}]);
