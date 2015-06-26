xxtApp.controller('enrollCtrl', ['$rootScope', '$scope', 'http2', function ($rootScope, $scope, http2) {
    $scope.taskCodeEntryUrl = 'http://' + location.host + '/rest/q';
    $scope.roundState = ['新建', '启用', '停止'];
    $rootScope.floatToolbar = { matterShop: true };
    $scope.update = function (name) {
        if (!angular.equals($scope.editing, $scope.persisted)) {
            var p = {};
            p[name] = $scope.editing[name];
            http2.post('/rest/mp/app/enroll/update?aid=' + $scope.aid, p, function (rsp) {
                $scope.persisted = angular.copy($scope.editing);
            });
        }
    };
    $scope.$watch('aid', function (nv) {
        if (nv && nv.length)
            http2.get('/rest/mp/app/enroll/get?aid=' + nv, function (rsp) {
                $scope.editing = rsp.data;
                $scope.editing.type = 'enroll';
                $scope.editing.pages.form.title = '登记信息页';
                $scope.editing.canSetReceiver = 'Y';
                $scope.persisted = angular.copy($scope.editing);
                $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + $scope.editing.mpid;
            });
    });
    $scope.$on('xxt.float-toolbar.shop.open', function (event) {
        $scope.$emit('mattershop.new', $scope.mpid, $scope.editing);
    });
    http2.get('/rest/mp/mpaccount/get', function (rsp) {
        $scope.mpaccount = rsp.data;
        $scope.hasParent = $scope.mpaccount.parent_mpid && $scope.mpaccount.parent_mpid.length;
    });
    http2.get('/rest/mp/mpaccount/feature?fields=matter_visible_to_creater', function (rsp) {
        $scope.features = rsp.data;
    });
}]);
xxtApp.controller('settingCtrl', ['$scope', 'http2', 'matterTypes', '$modal', function ($scope, http2, matterTypes, $modal) {
    $scope.matterTypes = matterTypes;
    $scope.setPic = function () {
        $scope.$broadcast('picgallery.open', function (url) {
            var t = (new Date()).getTime(), url = url + '?_=' + t, nv = { pic: url };
            http2.post('/rest/mp/app/enroll/update?aid=' + $scope.aid, nv, function () {
                $scope.editing.pic = url;
            });
        }, false);
    };
    $scope.removePic = function () {
        var nv = { pic: '' };
        http2.post('/rest/mp/app/enroll/update?aid=' + $scope.aid, nv, function () {
            $scope.editing.pic = '';
        });
    };
    $scope.setSuccessReply = function () {
        $scope.$broadcast('mattersgallery.open', function (aSelected, matterType) {
            if (aSelected.length === 1) {
                var p = { mt: matterType, mid: aSelected[0].id };
                http2.post('/rest/mp/app/enroll/setSuccessReply?aid=' + $scope.aid, p, function (rsp) {
                    $scope.editing.successMatter = aSelected[0];
                });
            }
        });
    };
    $scope.setFailureReply = function () {
        $scope.$broadcast('mattersgallery.open', function (aSelected, matterType) {
            if (aSelected.length === 1) {
                var p = { mt: matterType, mid: aSelected[0].id };
                http2.post('/rest/mp/app/enroll/setFailureReply?aid=' + $scope.aid, p, function (rsp) {
                    $scope.editing.failureMatter = aSelected[0];
                });
            }
        });
    };
    $scope.removeSuccessReply = function () {
        var p = { mt: '', mid: '' };
        http2.post('/rest/mp/app/enroll/setSuccessReply?aid=' + $scope.aid, p, function (rsp) {
            $scope.editing.successMatter = null;
        });
    };
    $scope.removeFailureReply = function () {
        var p = { mt: '', mid: '' };
        http2.post('/rest/mp/app/enroll/setFailureReply?aid=' + $scope.aid, p, function (rsp) {
            $scope.editing.failureMatter = null;
        });
    };
    $scope.addRound = function () {
        $modal.open({
            templateUrl: 'roundEditor.html',
            backdrop: 'static',
            resolve: {
                roundState: function () { return $scope.roundState; }
            },
            controller: ['$scope', '$modalInstance', 'roundState', function ($scope, $modalInstance, roundState) {
                $scope.round = { state: 0 };
                $scope.roundState = roundState;
                $scope.close = function () { $modalInstance.dismiss(); };
                $scope.ok = function () { $modalInstance.close($scope.round); };
                $scope.start = function () {
                    $scope.round.state = 1;
                    $modalInstance.close($scope.round);
                };
            }]
        }).result.then(function (newRound) {
            http2.post('/rest/mp/app/enroll/addRound?aid=' + $scope.aid, newRound, function (rsp) {
                if ($scope.editing.rounds.length > 0 && rsp.data.state == 1)
                    $scope.editing.rounds[1].state = 2;
                $scope.editing.rounds.splice(0, 0, rsp.data);
            });
        });
    };
    $scope.openRound = function (round) {
        $modal.open({
            templateUrl: 'roundEditor.html',
            backdrop: 'static',
            resolve: {
                roundState: function () { return $scope.roundState; }
            },
            controller: ['$scope', '$modalInstance', 'roundState', function ($scope, $modalInstance, roundState) {
                $scope.round = angular.copy(round);
                $scope.roundState = roundState;
                $scope.close = function () { $modalInstance.dismiss(); };
                $scope.ok = function () { $modalInstance.close({ action: 'update', data: $scope.round }); };
                $scope.remove = function () { $modalInstance.close({ action: 'remove' }); };
                $scope.start = function () {
                    $scope.round.state = 1;
                    $modalInstance.close({ action: 'update', data: $scope.round });
                };
            }]
        }).result.then(function (rst) {
            if (rst.action === 'update') {
                var url = '/rest/mp/app/enroll/updateRound';
                url += '?aid=' + $scope.aid;
                url += '&rid=' + round.rid;
                http2.post(url, rst.data, function (rsp) {
                    if ($scope.editing.rounds.length > 1 && rst.data.state == 1)
                        $scope.editing.rounds[1].state = 2;
                    angular.extend(round, rst.data);
                });
            } else if (rst.action === 'remove') {
                var url = '/rest/mp/app/enroll/removeRound';
                url += '?aid=' + $scope.aid;
                url += '&rid=' + round.rid;
                http2.get(url, function (rsp) {
                    var i = $scope.editing.rounds.indexOf(round);
                    $scope.editing.rounds.splice(i, 1);
                });
            }
        });
    };
}]);
xxtApp.controller('pageCtrl', ['$rootScope', '$scope', 'http2', '$modal', '$timeout', function ($rootScope, $scope, http2, $modal, $timeout) {
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
        $scope.def = { type: '0', name: '', showname: '1', component: 'R', align: 'V', count: 1 };
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
    $scope.schema = function () {
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
    };
    $scope.embedInput = function (page) {
        $modal.open({
            templateUrl: 'embedInputLib.html',
            controller: CusdataCtrl,
            backdrop: 'static',
        }).result.then(function (def) {
            var key, inpAttrs, html, fn;
            key = 'c' + (new Date()).getTime();
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
    $scope.$on('tinymce.wrap.select', function (event, wrap) {
        $scope.$apply(function () {
            $scope.hasActiveWrap = false;
            if (wrap.hasAttribute('wrap')) {
                $(wrap.parentNode).children('.active').removeClass('active');
                wrap.classList.add('active');
                $scope.hasActiveWrap = true;
            } else {
                $(wrap).children('.active').removeClass('active');
            }
        });
    });
    $scope.removeWrap = function (page) {
        var editor;
        editor = tinymce.get(page.name);
        $(editor.getBody()).children('.active').remove();
        $scope.hasActiveWrap = false;
        editor.save();
    };
    $scope.$on('tinymce.multipleimage.open', function (event, callback) {
        $scope.$broadcast('picgallery.open', callback, true, true);
    });
    $scope.extraPages = function () {
        var result = {};
        angular.forEach($scope.editing.pages, function (value, key) {
            key !== 'form' && (result[key] = value);
        });
        return result;
    };
    $scope.addPage = function () {
        http2.get('/rest/mp/app/enroll/addPage?aid=' + $scope.aid, function (rsp) {
            var page = rsp.data;
            $scope.editing.pages[page.name] = page;
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
            $rootScope.progmsg = '正在保存页面...';
            var url, p = {};
            p[name] = encodeURIComponent(page[name]);
            url = '/rest/mp/app/enroll/updPage';
            url += '?aid=' + $scope.aid;
            url += '&pid=' + page.id;
            url += '&pname=' + page.name;
            url += '&cid=' + page.code_id;
            http2.post(url, p, function (rsp) {
                $scope.persisted = angular.copy($scope.editing);
                page.$$modified = false;
                $rootScope.progmsg = '';
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
            $timeout(function () {
                $('a[href="#tab_form"]').tab('show');
            });
        });
    };
    $scope.gotoCode = function (codeid) {
        window.open('/rest/code?pid=' + codeid, '_self');
    };
}]);
xxtApp.controller('rollCtrl', ['$scope', 'http2', '$modal', function ($scope, http2, $modal) {
    var t = (new Date()).getTime();
    $scope.doSearch = function (page) {
        page && ($scope.page.at = page);
        var url = '/rest/mp/app/enroll/records?aid=' + $scope.aid + '&contain=total' + $scope.page.joinParams();
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
        searchBy: 'nickname',
        joinParams: function () {
            var p;
            p = '&page=' + this.at + '&size=' + this.size;
            if (this.keyword !== '') {
                p += '&kw=' + this.keyword;
                p += '&by=' + this.searchBy;
            }
            p += '&rid=' + (this.byRound ? this.byRound : 'ALL');
            return p;
        }
    };
    $scope.searchBys = [
        { n: '昵称', v: 'nickname' },
        { n: '手机号', v: 'mobile' },
    ];
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
    $scope.importRoll = function () {
        http2.get('/rest/member/auth/userselector', function (rsp) {
            var url = rsp.data;
            $.getScript(url, function () {
                $modal.open(AddonParams).result.then(function (selected) {
                    if (selected.members && selected.members.length) {
                        var members = [];
                        for (var i in selected.members)
                            members.push(selected.members[i].data.mid);
                        http2.post('/rest/mp/app/importRoll?aid=' + $scope.aid, members, function (rsp) {
                            for (var i in rsp.data)
                                $scope.roll.splice(0, 0, rsp.data[i]);
                        });
                    }
                })
            });
        });
    };
    $scope.importRoll2 = function () {
        $modal.open({
            templateUrl: 'importActivityRoll.html',
            controller: 'importActivityRollCtrl',
            backdrop: 'static',
            size: 'lg'
        }).result.then(function (param) {
            http2.post('/rest/mp/app/enroll/importRoll2?aid=' + $scope.aid, param, function (rsp) {
                $scope.doSearch(1);
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
}]);
xxtApp.controller('importActivityRollCtrl', ['$scope', 'http2', '$modalInstance', function ($scope, http2, $modalInstance) {
    $scope.param = {
        checkedActs: [],
        checkedWalls: [],
        wallUserState: 'active',
        alg: 'inter'
    };
    $scope.changeAct = function (act) {
        var i = $scope.param.checkedActs.indexOf(act.aid);
        if (i === -1)
            $scope.param.checkedActs.push(act.aid);
        else
            $scope.param.checkedActs.splice(i, 1);
    };
    $scope.changeWall = function (wall) {
        var i = $scope.param.checkedWalls.indexOf(wall.wid);
        if (i === -1)
            $scope.param.checkedWalls.push(wall.wid);
        else
            $scope.param.checkedWalls.splice(i, 1);
    };
    $scope.cancel = function () {
        $modalInstance.dismiss();
    };
    $scope.ok = function () {
        $modalInstance.close($scope.param);
    };
    http2.get('/rest/mp/app/enroll?page=1&size=999', function (rsp) {
        $scope.activities = rsp.data[0];
    });
    http2.get('/rest/mp/app/wall', function (rsp) {
        $scope.walls = rsp.data;
    });
}]);
xxtApp.controller('editorCtrl', ['$scope', '$modalInstance', 'rollItem', 'tags', 'cols', function ($scope, $modalInstance, rollItem, tags, cols) {
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
    $scope.$on('tag.xxt.combox.done', function (event, aSelected) {
        var aNewTags = [];
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.item.aTags) {
                if (aSelected[i] === $scope.item.aTags[j]) {
                    existing = true;
                    break;
                }
            }
            !existing && aNewTags.push(aSelected[i]);
        }
        $scope.item.aTags = $scope.item.aTags.concat(aNewTags);
    });
    $scope.$on('tag.xxt.combox.add', function (event, newTag) {
        $scope.item.aTags.push(newTag);
        if ($scope.aTags.indexOf(newTag) === -1) {
            $scope.aTags.push(newTag);
        }
    });
    $scope.$on('tag.xxt.combox.del', function (event, removed) {
        $scope.item.aTags.splice($scope.item.aTags.indexOf(removed), 1);
    });
}]);
xxtApp.controller('StatCtrl', ['$scope', 'http2', function ($scope, http2) {
    http2.get('/rest/mp/app/enroll/stat?aid=' + $scope.aid, function (rsp) {
        $scope.stat = rsp.data;
    });
}]);
xxtApp.controller('lotteryCtrl', ['$scope', 'http2', function ($scope, http2) {
    var getWinners = function () {
        var url = '/rest/mp/app/enroll/lotteryWinners?aid=' + $scope.aid;
        if ($scope.editing)
            url += '&rid=' + $scope.editing.round_id;
        http2.get(url, function (rsp) {
            $scope.winners = rsp.data;
        });
    };
    $scope.aTargets = null;
    $scope.addRound = function () {
        http2.post('/rest/mp/app/enroll/addLotteryRound?aid=' + $scope.aid, null, function (rsp) {
            $scope.rounds.push(rsp.data);
        });
    };
    $scope.open = function (round) {
        $scope.editing = round;
        $scope.aTargets = $scope.editing.targets.length === 0 ? [] : eval($scope.editing.targets);
        getWinners();
    };
    $scope.updateLotteryRound = function (name) {
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/app/enroll/updateLotteryRound?aid=' + $scope.aid + '&rid=' + $scope.editing.round_id, nv, function (rsp) {
        });
    };
    $scope.removeLotteryRound = function () {
        http2.post('/rest/mp/app/enroll/removeLotteryRound?aid=' + $scope.aid + '&rid=' + $scope.editing.round_id, null, function (rsp) {
            var i = $scope.rounds.indexOf($scope.editing);
            $scope.rounds.splice(i, 1);
        });
    };
    $scope.addTarget = function () {
        var target = { tags: [] };
        $scope.aTargets.push(target);
    };
    $scope.removeTarget = function (i) {
        $scope.aTargets.splice(i, 1);
    };
    $scope.saveTargets = function () {
        var arr = [];
        for (var i in $scope.aTargets)
            arr.push({ tags: $scope.aTargets[i].tags });
        $scope.editing.targets = JSON.stringify(arr);
        $scope.updateLotteryRound('targets');
    };
    $scope.$on('tag.xxt.combox.done', function (event, aSelected, state) {
        var aNewTags = [];
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.aTargets[state].tags) {
                if (aSelected[i] === $scope.aTargets[state].tags[j]) {
                    existing = true;
                    break;
                }
            }
            !existing && aNewTags.push(aSelected[i]);
        }
        $scope.aTargets[state].tags = $scope.aTargets[state].tags.concat(aNewTags);
    });
    $scope.$on('tag.xxt.combox.add', function (event, newTag, state) {
        $scope.aTargets[state].tags.push(newTag);
        if ($scope.aTags.indexOf(newTag) === -1) {
            $scope.aTags.push(newTag);
            $scope.editing.tags = $scope.aTags.join(',');
            $scope.update('tags');
        }
    });
    $scope.$on('tag.xxt.combox.del', function (event, removed, state) {
        $scope.aTargets[state].tags.splice($scope.aTargets[state].tags.indexOf(removed), 1);
    });
    $scope.aTags = $scope.editing.tags.length === 0 ? [] : $scope.editing.tags.split(',');
    $scope.lotteryUrl = "http://" + location.host + "/rest/app/enroll/lottery2?aid=" + $scope.aid;
    http2.get('/rest/mp/app/enroll/lotteryRounds?aid=' + $scope.aid, function (rsp) {
        $scope.rounds = rsp.data;
    });
    getWinners();
}]);
