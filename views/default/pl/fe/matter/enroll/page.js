(function() {
    var WrapLib = function() {};
    WrapLib.prototype.addWrap = function(page, name, attrs, html) {
        var dom, body, wrap, newWrap, selection, activeEditor, $activeWrap, $upmost;
        activeEditor = tinymce.get(page.name);
        dom = activeEditor.dom;
        body = activeEditor.getBody();
        $activeWrap = $(body).find('[wrap].active');
        if ($activeWrap.length) {
            $upmost = $activeWrap.parents('[wrap]');
            $upmost = $upmost.length === 0 ? $activeWrap : $($upmost.get($upmost.length - 1));
            newWrap = dom.create(name, attrs, html);
            dom.insertAfter(newWrap, $upmost[0]);
        } else {
            newWrap = dom.add(body, name, attrs, html);
        }
        //activeEditor.save();
    };
    WrapLib.prototype.extractInputSchema = function(wrap) {
        var $label, def = {},
            $input, model;
        $label = $($(wrap).find('label').get(0));
        def.name = $label.html();
        def.showname = $label.hasClass('sr-only') ? 'placeholder' : 'label';
        $input = $(wrap).find('input,select,textarea');
        model = $input.attr('ng-model') || $input.attr('ng-bind');
        def.key = model.split('.')[1];
        return def;
    };
    WrapLib.prototype.changeEmbedInput = function(page, wrap, def) {
        var $label, input;
        $label = $($(wrap).find('label').get(0));
        $label.html(def.name);
        input = $(wrap).find('input,textarea,select,option,[wrap=img]');
        input.attr('title', def.name);
        if (def.showname === 'placeholder') {
            $label.addClass('sr-only');
            input.filter('input,textarea,select').attr('placeholder', def.name);
        } else {
            $label.removeClass('sr-only');
            input.filter('input,textarea,select').attr('placeholder', '');
        }
        tinymce.get(page.name).save();
    };
    WrapLib.prototype.embedInput = function(page, def) {
        var key, inpAttrs, html, fn;
        html = '';
        key = def.id;
        inpAttrs = {
            wrap: 'input',
            class: 'form-group form-group-lg'
        };
        html += '<label' + (def.showname === 'label' ? '' : ' class="sr-only"') + '>' + def.title + '</label>';
        switch (def.type) {
            case 'name':
            case 'mobile':
            case 'email':
            case 'shorttext':
            case 'auth':
                html += '<input type="text" ng-model="data.' + key + '" title="' + def.title + '"';
                def.showname === 'placeholder' && (html += ' placeholder="' + def.title + '"');
                def.required == 1 && (html += 'required=""');
                def.type === 'auth' && (html += 'ng-init="data.member.authid=' + def.auth.authid + '"');
                html += ' class="form-control input-lg">';
                break;
            case 'date':
                inpAttrs['tms-date'] = 'Y';
                inpAttrs['tms-date-value'] = 'data.' + key;
                html += '<div wrap="datet" ng-bind="data.' + key + '|date:\'yy-MM-dd HH:mm\'"';
                html += ' title="' + def.title + '"';
                html += ' placeholder="' + def.title + '"';
                def.required == 1 && (html += 'required=""');
                html += ' class="form-control input-lg"></div>';
                break;
            case 'longtext':
                html += '<textarea style="height:auto" ng-model="data.' + key + '" title="' + def.title + '"';
                def.showname === 'placeholder' && (html += ' placeholder="' + def.title + '"');
                def.required == 1 && (html += 'required=""');
                html += ' class="form-control" rows="3"></textarea>';
                break;
            case 'single':
                if (def.ops && def.ops.length > 0) {
                    if (def.component === 'R') {
                        html += '<ul>', cls = 'radio';
                        if (def.align === 'H') cls += '-inline'
                        for (var i in def.ops) {
                            html += '<li class="' + cls + '" wrap="radio"><label';
                            if (def.align === 'H') html += ' class="radio-inline"';
                            html += '><input type="radio" name="' + key + '"';
                            html += ' value="' + def.ops[i].v + '"';
                            html += ' ng-model="data.' + key + '"';
                            def.required == 1 && (html += 'required=""');
                            html += ' title="' + def.title + '"';
                            for (var a in def.attrs) {
                                html += 'data-' + def.attrs[a].name + '="' + def.attrs[a].value + '"';
                            }
                            html += ' data-label="' + def.ops[i].l + '"><span>' + def.ops[i].l + '</span></label></li>';
                        }
                        html += '</ul>';
                    } else if (def.component === 'S') {
                        html += '<select class="form-control" ng-model="data.' + key + '"';
                        def.required == 1 && (html += 'required=""');
                        html += ' title="' + def.title + '">\r\n';
                        for (var i in def.ops) {
                            html += '<option wrap="option" name="data.' + key + '" value="' + def.ops[i].v + '"' + 'data-label="' + def.ops[i].l + '"' + 'title="' + def.title + '"' + '>' + def.ops[i].l + '</option>';
                        }
                        html += '\r\n</select>';
                    }
                }
                break;
            case 'multiple':
                if (def.ops && def.ops.length > 0) {
                    var cls;
                    html += '<ul';
                    if (def.setUpper === 'Y') {
                        html += ' tms-checkbox-group="' + key + '" tms-checkbox-group-model="data" tms-checkbox-group-upper="' + def.upper + '"';
                    }
                    html += '>';
                    cls = 'checkbox';
                    if (def.align === 'H') cls += '-inline';
                    for (var i in def.ops) {
                        html += '<li class="' + cls + '" wrap="checkbox"><label';
                        if (def.align === 'H') html += ' class="checkbox-inline"';
                        html += '><input type="checkbox" name="' + key + '"';
                        def.required == 1 && (html += 'required=""');
                        html += ' ng-model="data.' + key + '.' + def.ops[i].v + '"';
                        html += ' title="' + def.title + '" data-label="' + def.ops[i].l + '"><span>' + def.ops[i].l + '</span></label></li>';
                    }
                    html += '</ul>';
                }
                break;
            case 'image':
                inpAttrs['tms-image-input'] = 'Y';
                html += '<ul class="img-tiles clearfix" name="' + key + '">';
                html += '<li wrap="img" ng-repeat="img in data.' + key + '" class="img-thumbnail" title="' + def.title + '">';
                html += '<img flex-img>';
                html += '<button class="btn btn-default btn-xs" ng-click="removeImage(data.' + key + ',$index)"><span class="glyphicon glyphicon-remove"></span></button>';
                html += '</li>';
                html += '<li class="img-picker">';
                html += '<button class="btn btn-default" ng-click="chooseImage(\'' + key + '\',' + def.count + ')"><span class="glyphicon glyphicon-picture"></span><br>上传图片</button>';
                html += '</li>';
                html += '</ul>';
                break;
            case 'file':
                inpAttrs['tms-file-input'] = 'Y';
                html += '<ul class="list-group file" name="' + key + '">';
                html += '<li class="list-group-item" ng-show="progressOfUploadFile"><div class="progressOfUploadFile" ng-bind="progressOfUploadFile"></li>';
                html += '<li wrap="file" ng-repeat="file in data.' + key + '" class="list-group-item" title="' + def.title + '">';
                html += '<span class="file-name" ng-bind="file.name"></span>';
                html += '</li>';
                html += '<li class="list-group-item file-picker">';
                html += '<button class="btn btn-success" ng-click="chooseFile(\'' + key + '\',' + def.count + ')">' + def.title + '</button>';
                html += '</li>';
                html += '</ul>';
                break;
            case 'location':
                html += '<div wrap="location" class="input-group input-group-lg">';
                html += '<input type="text" ng-model="data.' + key + '"';
                html += ' title="' + def.title + '"';
                html += ' placeholder="' + def.title + '"';
                def.required == 1 && (html += 'required=""');
                html += ' class="form-control">';
                html += '<span class="input-group-btn">';
                fn = 'getMyLocation(\'' + key + '\')';
                html += '<button class="btn btn-default" type="button" ng-click="' + fn + '">定位</button>';
                html += '</span>';
                html += '</div>';
                break;
        }
        this.addWrap(page, 'div', inpAttrs, html);
    };
    WrapLib.prototype.embedRecord = function(page, def) {
        if (def.schemas === undefined || def.schemas.length === 0) return;
        var c, html, htmls, _this, attrs;
        htmls = [];
        c = 'form-group';
        def.inline && (c += ' wrap-inline');
        def.splitLine && (c += ' wrap-splitline');
        attrs = {
            'ng-controller': 'ctrlRecord',
            wrap: 'static',
            class: c
        };
        angular.forEach(def.schemas, function(s) {
            html = '';
            switch (s.type) {
                case 'name':
                case 'mobile':
                case 'email':
                case 'shorttext':
                case 'longtext':
                    html = '<label>' + s.title + '</label><div>{{Record.current.data.' + s.id + '}}</div>';
                    break;
                case 'single':
                case 'multiple':
                    html = '<label>' + s.title + '</label><div>{{value2Label("' + s.id + '")}}</div>';
                    break;
                case 'datetime':
                    html = "<label>" + s.title + "</label><div>{{Record.current.data." + s.id + "|date:'yy-MM-dd HH:mm'}}</div>";
                    break;
                case 'image':
                    html = '<label>' + s.title + '</label><ul><li ng-repeat="img in Record.current.data.' + s.id + '.split(\',\')"><img ng-src="{{img}}"></li></ul>';
                    break;
                case '_enrollAt':
                    html = "<label>" + s.title + "</label><div>{{Record.current.enroll_at*1000|date:'yy-MM-dd HH:mm'}}</div>";
                    break;
                case '_enrollerNickname':
                    html = "<label>" + s.title + "</label><div>{{Record.current.enroller.nickname}}</div>";
                    break;
                case '_enrollerHeadpic':
                    html = "<label>" + s.title + "</label><div><img ng-src='{{Record.current.enroller.fan.headimgurl}}'></div>";
                    break;
            }
            html ? htmls.push(html) : console.log('embedRecord schema error', s);
        });
        _this = this;
        angular.forEach(htmls, function(h) {
            _this.addWrap(page, 'div', attrs, h);
        });
    };
    WrapLib.prototype.embedList = function(page, def) {
        if (!def.schemas && def.schemas.length === 0) return false;;
        var onclick, html;
        onclick = def.onclick.length ? " ng-click=\"gotoPage($event,'" + def.onclick + "',r.enroll_key)\"" : '';
        html = '<ul class="list-group">';
        html += '<li class="list-group-item" ng-repeat="r in records"' + onclick + '>';
        angular.forEach(def.schemas, function(s) {
            switch (s.type) {
                case 'name':
                case 'email':
                case 'mobile':
                case 'shorttext':
                case 'longtext':
                case 'location':
                    html += '<div wrap="static" class="wrap-inline"><label>' + s.title + '</label><div>{{r.data.' + s.id + '}}</div></div>';
                    break;
                case 'datetime':
                    html += '<div wrap="static" class="wrap-inline"><label>' + s.title + '</label><div>{{r.data.' + s.id + '|date:"yy-MM-dd HH:mm"}}</div></div>';
                    break;
                case 'single':
                case 'multiple':
                    html += '<div wrap="static" class="wrap-inline"><label>' + s.title + '</label><div>{{value2Label(r,"' + s.id + '")}}</div></div>';
                    break;
                case 'img':
                    html += '<div wrap="static" class="wrap-inline"><label>' + s.title + '</label><ul><li ng-repeat="img in r.data.' + s.id + '.split(\',\')"><img ng-src="{{img}}"></li></ul></div>';
                    break;
                case '_enrollAt':
                    html += "<div wrap='static' class='wrap-inline'><label>" + s.title + "</label><div>{{r.enroll_at*1000|date:'yy-MM-dd HH:mm'}}</div></div>";
                    break;
                case '_enrollerNickname':
                    html += "<div wrap='static' class='wrap-inline'><label>" + s.title + "</label><div>{{r.nickname}}</div></div>";
                    break;
                case '_enrollerHeadpic':
                    html += "<div wrap='static' class='wrap-inline'><label>" + s.title + "</label><div><img ng-src='{{r.headimgurl}}'></div></div>";
                    break;
            }
        });
        if (def.canLike === 'Y') {
            html += '<div wrap="static" class="wrap-inline"><label>总赞数</label><div>{{r.score}}</div></div>';
            html += "<div wrap='static' ng-if='!r.myscore'><a href='javascript:void(0)' ng-click='like($event,r)'>赞</a></div>";
            html += "<div wrap='static' ng-if='r.myscore==1'>已赞</div>";
        }
        html += "</li></ul>";
        this.addWrap(page, 'div', {
            'ng-controller': 'ctrlRecords',
            'enroll-records': 'Y',
            'enroll-records-owner': def.dataScope,
            wrap: 'list',
            class: 'form-group'
        }, html);
        return true;
    };
    WrapLib.prototype.embedRounds = function(page, def) {
        var onclick, html;
        onclick = def.onclick.length ? " ng-click=\"gotoPage($event,'" + def.onclick + "',null,r.rid)\"" : '';
        html = "<ul class='list-group'><li class='list-group-item' ng-repeat='r in rounds'" + onclick + "><div>{{r.title}}</div></li></ul>";
        this.addWrap(page, 'div', {
            'ng-controller': 'ctrlRounds',
            wrap: 'list',
            class: 'form-group'
        }, html);
    };
    WrapLib.prototype.embedRemarks = function(page, def) {
        var html;
        html = "<ul class='list-group'>";
        html += "<li class='list-group-item' ng-repeat='r in Record.current.remarks'>";
        html += "<div wrap='static'>{{r.remark}}</div>";
        html += "<div wrap='static'>{{r.nickname}}</div>";
        html += "<div wrap='static'>{{(r.create_at*1000)|date:'yy-MM-dd HH:mm'}}</div>";
        html += "</li>";
        html += "</ul>";
        this.addWrap(page, 'div', {
            'ng-controller': 'ctrlRemark',
            wrap: 'list',
            class: 'form-group'
        }, html);
    };
    WrapLib.prototype.embedLikers = function(page, def) {
        var html;
        html = "<ul class='list-group'><li class='list-group-item' ng-repeat='l in Record.current.likers'><div>{{l.nickname}}</div></li></ul>";
        this.addWrap(page, 'div', {
            'ng-controller': 'ctrlRecord',
            wrap: 'list',
            class: 'form-group'
        }, html);
    };
    WrapLib.prototype.embedShow = function(page, def, catelog) {
        switch (catelog) {
            case 'record':
                this.embedRecord(page, def);
                break;
            case 'list':
                this.embedList(page, def);
                break;
            case 'rounds':
                this.embedRounds(page, def);
                break;
            case 'remarks':
                this.embedRemarks(page, def);
                break;
            case 'likers':
                this.embedLikers(page, def);
                break;
        }
    };
    WrapLib.prototype.embedUser = function(page, def) {
        if (def.nickname === true) {
            html = "<label>昵称</label><div>{{User.nickname}}</div>";
            this.addWrap(page, 'div', {
                wrap: 'static',
                class: 'form-group'
            }, html);
        }
        if (def.headpic === true) {
            html = '<label>头像</label><div><img ng-src="{{User.headimgurl}}"></div>';
            this.addWrap(page, 'div', {
                wrap: 'static',
                class: 'form-group'
            }, html);
        }
        if (def.rankByFollower === true) {
            html = '<label>邀请用户排名</label><div tms-exec="onReady(\'Statistic.rankByFollower()\')">{{Statistic.result.rankByFollower.rank}}</div>';
            this.addWrap(page, 'div', {
                wrap: 'static',
                class: 'form-group'
            }, html);
        }
    };
    WrapLib.prototype.changeEmbedStatic = function(page, wrap, def) {
        def.inline ? $(wrap).addClass('wrap-inline') : $(wrap).removeClass('wrap-inline');
        def.splitLine ? $(wrap).addClass('wrap-splitline') : $(wrap).removeClass('wrap-splitline');
    };
    WrapLib.prototype.extractStaticSchema = function(wrap) {
        var def = {};
        def.inline = $(wrap).hasClass('wrap-inline');
        def.splitLine = $(wrap).hasClass('wrap-splitline');
        return def;
    };
    WrapLib.prototype.extractButtonSchema = function(wrap) {
        var $button, action, arg, def = {};
        $button = $(wrap).find('button');
        def.label = $button.children('span').html();
        action = $button.attr('ng-click');
        action = action.match(/(.+?)\((.+?)\)/);
        def.type = action[1];
        arg = action[2].split(',');
        arg.length === 2 && (def.next = arg[1].replace(/'/g, ''));
        return def;
    };
    var EmbedButtonSchema = {
        _args: function(def) {
            return def.next ? "($event,'" + def.next + "')" : "($event)"
        },
        addRecord: {
            id: function(def) {
                return 'btnNewRecord_' + def.next;
            },
            act: function(def) {
                return 'addRecord' + EmbedButtonSchema._args(def);
            }
        },
        editRecord: {
            id: function(def) {
                return 'btnEditRecord_' + def.next;
            },
            act: function(def) {
                return 'editRecord' + EmbedButtonSchema._args(def);
            }
        },
        submit: {
            id: 'btnSubmit',
            act: function(def) {
                return 'submit' + EmbedButtonSchema._args(def);
            }
        },
        acceptInvite: {
            id: function(def) {
                return 'btnAcceptInvite_' + def.next;
            },
            act: function(def) {
                return 'accept' + EmbedButtonSchema._args(def);
            }
        },
        gotoPage: {
            id: function(def) {
                return 'btnGotoPage_' + def.next;
            },
            act: function(def) {
                return 'gotoPage' + EmbedButtonSchema._args(def);
            }
        },
        likeRecord: {
            id: function(def) {
                return 'btnLikeRecord_' + def.next;
            },
            act: function(def) {
                return 'like' + EmbedButtonSchema._args(def);
            }
        },
        closeWindow: {
            id: 'btnCloseWindow',
            act: 'closeWindow($event)'
        },
        signin: {
            id: 'btnSignin',
            act: function(def) {
                return 'signin' + EmbedButtonSchema._args(def);
            }
        },
    };
    WrapLib.prototype.changeEmbedButton = function(page, wrap, def) {
        var schema, id, action, $button;
        if (schema = EmbedButtonSchema[def.type]) {
            action = schema.act;
            angular.isFunction(action) && (action = action(def));
            $button = $(wrap).find('button');
            $button.children('span').html(def.label);
            $button.attr('ng-click', action);
        } else if (button === 'remarkRecord') {
            // not support
        }
    };
    WrapLib.prototype.embedButton = function(page, def) {
        var attrs, tmplBtn, schema, id, action;
        attrs = {
            wrap: 'button',
            class: 'form-group'
        };
        tmplBtn = function(id, action, label) {
            return '<button id="' + id + '" class="btn btn-primary btn-block btn-lg" ng-click="' + action + '"><span>' + label + '</span></button>';
        };
        if (schema = EmbedButtonSchema[def.name]) {
            id = schema.id;
            angular.isFunction(id) && (id = id(def));
            action = schema.act;
            angular.isFunction(action) && (action = action(def));
            if (def.name === 'acceptInvite') {
                attrs['ng-controller'] = 'ctrlInvite';
            } else if (def.name === 'editRecord' || def.name === 'likeRecord') {
                attrs['ng-controller'] = 'ctrlRecord';
            }
            this.addWrap(page, 'div', attrs, tmplBtn(id, action, def.label));
        } else if (def.name === 'sendInvite') {
            var html, action;
            action = "send($event,'" + def.accept + "'";
            def.next && (action += ",'" + def.next + "'");
            action += ")";
            html = '<input type="text" class="form-control" placeholder="认证用户标识" ng-model="invitee">';
            html += '<span class="input-group-btn">';
            html += '<button class="btn btn-success" type="button" ng-click="' + action + '"><span>' + label + '</span></button>';
            html += '</span>';
            this.addWrap(page, 'div', {
                'ng-controller': 'ctrlInvite',
                wrap: 'button',
                class: 'form-group input-group input-group-lg'
            }, html);
        } else if (def.name === 'remarkRecord') {
            var html = '<input type="text" class="form-control" placeholder="评论" ng-model="newRemark">';
            html += '<span class="input-group-btn">';
            html += '<button class="btn btn-success" type="button" ng-click="remark($event)"><span>发送</span></button>';
            html += '</span>';
            this.addWrap(page, 'div', {
                'ng-controller': 'ctrlRemark',
                wrap: 'button',
                class: 'form-group input-group input-group-lg'
            }, html);
        }
    };
    window.wrapLib = new WrapLib();
})();
(function() {
    var _ctrlEmbedButton = ['$scope', '$modalInstance', 'app', 'def', function($scope, $mi, app, def) {
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
    ngApp.provider.controller('ctrlPage', ['$scope', '$location', 'http2', '$modal', '$timeout', function($scope, $location, http2, $modal, $timeout) {
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
            var editor;
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
                url = '/rest/pl/fe/matter/enroll/page/update';
                url += '?site=' + $scope.siteId;
                url += '&app=' + $scope.id;
                url += '&pid=' + page.id;
                url += '&pname=' + page.name;
                url += '&cid=' + page.code_id;
                http2.post(url, p, function(rsp) {
                    $scope.persisted = angular.copy($scope.app);
                    page.$$modified = false;
                    $scope.$root.progmsg = '';
                });
            }
        };
        $scope.delPage = function() {
            if (window.confirm('确定删除？')) {
                var url = '/rest/pl/fe/matter/enroll/page/remove';
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
            if (pages) {
                angular.forEach(pages, function(p) {
                    if (p.name === current) {
                        $scope.ep = p;
                        dataSchemas = $scope.ep.data_schemas;
                        $scope.ep.data_schemas = dataSchemas && dataSchemas.length ? JSON.parse(dataSchemas) : [];
                        actSchemas = $scope.ep.act_schemas;
                        $scope.ep.act_schemas = actSchemas && actSchemas.length ? JSON.parse(actSchemas) : [];
                        userSchemas = $scope.ep.user_schemas;
                        $scope.ep.user_schemas = userSchemas && userSchemas.length ? JSON.parse(userSchemas) : [];
                    } else {
                        p !== $scope.ep && others.push(p);
                    }
                });
            };
            $scope.others = others;
        });
    }]);
    ngApp.provider.controller('ctrlPageSchema', ['$scope', '$modal', function($scope, $modal) {
        $scope.chooseUser = function() {
            $modal.open({
                templateUrl: 'chooseUserSchema.html',
                backdrop: 'static',
                controller: ['$scope', '$modalInstance', function($scope, $mi) {
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
    ngApp.provider.controller('ctrlInputSchema', ['$scope', '$modal', function($scope, $modal) {
        $scope.chooseSchema = function() {
            $modal.open({
                templateUrl: 'chooseDataSchema.html',
                backdrop: 'static',
                resolve: {
                    schemas: function() {
                        return $scope.app.data_schemas;
                    }
                },
                controller: ['$scope', '$modalInstance', 'schemas', function($scope, $mi, schemas) {
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
            $modal.open({
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
    ngApp.provider.controller('ctrlSigninSchema', ['$scope', '$modal', function($scope, $modal) {
        $scope.chooseSchema = function() {
            $modal.open({
                templateUrl: 'chooseDataSchema.html',
                backdrop: 'static',
                resolve: {
                    schemas: function() {
                        return $scope.app.data_schemas;
                    }
                },
                controller: ['$scope', '$modalInstance', 'schemas', function($scope, $mi, schemas) {
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
            $modal.open({
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
    ngApp.provider.controller('ctrlViewSchema', ['$scope', '$modal', function($scope, $modal) {
        $scope.chooseSchema = function(dataSchemasCatalog) {
            $modal.open({
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
                controller: ['$scope', '$modalInstance', 'schemas', function($scope, $mi, schemas) {
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
            $modal.open({
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
                    console.log('schema', schema);
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
    ngApp.provider.controller('ctrlPageEditor', ['$scope', '$modal', 'mattersgallery', 'mediagallery', function($scope, $modal, mattersgallery, mediagallery) {
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
                $modal.open({
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
                $modal.open({
                    templateUrl: 'embedInputEditor.html',
                    backdrop: 'static',
                    controller: function($scope, $modalInstance) {
                        $scope.def = def;
                        $scope.ok = function() {
                            $modalInstance.close($scope.def);
                        };
                        $scope.cancel = function() {
                            $modalInstance.dismiss();
                        };
                    },
                }).result.then(function(def) {
                    wrapLib.changeEmbedInput(page, $active[0], def);
                });
            } else if (/static/.test($active.attr('wrap'))) {
                def = wrapLib.extractStaticSchema($active[0]);
                $modal.open({
                    templateUrl: 'embedStaticEditor.html',
                    backdrop: 'static',
                    controller: ['$scope', '$modalInstance', function($scope, $mi) {
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
            window.open('/rest/code?pid=' + codeid, '_self');
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
})();