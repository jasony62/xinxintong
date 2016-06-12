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
        activeEditor.save();
    };
    WrapLib.prototype.extractInputSchema = function(wrap) {
        var $label, def = {},
            $input, model;
        $label = $($(wrap).find('label').get(0));
        def.name = $label.html();
        def.showname = $label.hasClass('sr-only') ? 'placeholder' : 'label';
        $input = $(wrap).find('input,select');
        model = $input.attr('ng-model') || $input.attr('ng-bind');
        def.key = model.split('.')[1];
        return def;
    };
    WrapLib.prototype.extractSchema = function(html) {
        var extractModelId = function(model) {
            var id;
            if (id = model.match(/ng-model=\"data\.(.+?)\"/)) {
                id = id.pop().replace('ng-model="data.', '').replace('"', '');
                return id;
            }
            return false;
        };
        var extractRadioModelOp = function(model) {
            var v, l;
            if (v = schema.match(/value=\"(.+?)\"/))
                v = v.pop().replace('value=', '').replace(/\"/g, '');
            if (l = schema.match(/data-label=\"(.+?)\"/))
                l = l.pop().replace('data-label=', '').replace(/\"/g, '');
            return {
                v: v,
                l: l
            };
        };
        var extractCheckboxModelOp = function(model) {
            var v, l;
            if (v = schema.match(/ng-model=\"(.+?)\"/))
                v = v.pop().replace('ng-model=', '').replace(/\"/g, '').split('.').pop();
            if (l = schema.match(/data-label=\"(.+?)\"/))
                l = l.pop().replace('data-label=', '').replace(/\"/g, '');
            return {
                v: v,
                l: l
            };
        };
        var extractSelectModelId = function(model) {
            var id;
            if (id = model.match(/name=\"data\.(.+?)\"/)) {
                id = id.pop().replace('name="data.', '').replace('"', '');
                return id;
            }
            return false;
        };
        var extractSelectModelOp = function(model) {
            var v, l;
            if (v = schema.match(/value=\"(.+?)\"/))
                v = v.pop().replace('value=', '').replace(/\"/g, '');
            if (l = schema.match(/data-label=\"(.+?)\"/))
                l = l.pop().replace('data-label=', '').replace(/\"/g, '');
            return {
                v: v,
                l: l
            };
        };
        var defs, i, schemas, schema, type, title, modelId;
        defs = {};
        schemas = html.match(/<(div|li|option).+?wrap=(.+?)>.*?<\/(div|li|option)>/ig);
        for (i in schemas) {
            schema = schemas[i];
            type = schema.match(/wrap=\".+?\"/).pop().replace('wrap=', '').replace(/\"/g, '');
            switch (type) {
                case 'input':
                case 'location':
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
                            defs[modelId] = {
                                id: modelId,
                                title: title,
                                type: 'input'
                            };
                    } else if (schema.match(/type=\"radio\"/)) {
                        if (modelId = extractModelId(schema)) {
                            if (defs[modelId] === undefined)
                                defs[modelId] = {
                                    id: modelId,
                                    title: title,
                                    type: 'radio',
                                    op: []
                                };
                            defs[modelId].op.push(extractRadioModelOp(schema));
                        }
                    } else if (schema.match(/type=\"checkbox\"/)) {
                        if (modelId = extractModelId(schema)) {
                            modelId = modelId.split('.')[0];
                            if (defs[modelId] === undefined)
                                defs[modelId] = {
                                    id: modelId,
                                    title: title,
                                    type: 'checkbox',
                                    op: []
                                };
                            defs[modelId].op.push(extractCheckboxModelOp(schema));
                        }
                    }
                    break;
                case 'option':
                    title = schema.match(/\btitle=\".*?\"/).pop().replace('title=', '').replace(/\"/g, '');
                    if (modelId = extractSelectModelId(schema)) {
                        if (defs[modelId] === undefined)
                            defs[modelId] = {
                                id: modelId,
                                title: title,
                                type: 'option',
                                op: []
                            };
                        defs[modelId].op.push(extractSelectModelOp(schema));
                    }
                    break;
                case 'datetime':
                    title = schema.match(/title=\".*?\"/).pop().replace('title=', '').replace(/\"/g, '');
                    if (modelId = schema.match(/ng-bind=\"data\.(.+?)\|/)) {
                        modelId = modelId[1];
                        defs[modelId] = {
                            id: modelId,
                            title: title,
                            type: 'datetime'
                        };
                    }
                    break;
                case 'img':
                    title = schema.match(/title=\".*?\"/).pop().replace('title=', '').replace(/\"/g, '');
                    if (modelId = schema.match(/ng-repeat=\"img in data\.(.+?)\"/)) {
                        modelId = modelId.pop().replace(/ng-repeat=\"img in data\./, '').replace(/\"/g, '');
                        defs[modelId] = {
                            id: modelId,
                            title: title,
                            type: 'img'
                        };
                    }
                    break;
                case 'file':
                    title = schema.match(/title=\".*?\"/).pop().replace('title=', '').replace(/\"/g, '');
                    if (modelId = schema.match(/ng-repeat=\"file in data\.(.+?)\"/)) {
                        modelId = modelId.pop().replace(/ng-repeat=\"file in data\./, '').replace(/\"/g, '');
                        defs[modelId] = {
                            id: modelId,
                            title: title,
                            type: 'file'
                        };
                    }
                    break;
            }
        }
        return defs;
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
        key = def.key;
        inpAttrs = {
            wrap: 'input',
            class: 'form-group form-group-lg'
        };
        html += '<label' + (def.showname === 'label' ? '' : ' class="sr-only"') + '>' + def.name + '</label>';
        switch (def.type) {
            case 'name':
            case 'mobile':
            case 'email':
            case 'shorttext':
            case 'member':
                html += '<input type="text" ng-model="data.' + key + '" title="' + def.name + '"';
                def.showname === 'placeholder' && (html += ' placeholder="' + def.name + '"');
                def.required == 1 && (html += 'required=""');
                def.type === 'auth' && (html += 'ng-init="data.member.authid=' + def.auth.authid + '"');
                html += ' class="form-control input-lg">';
                break;
            case 'date':
                inpAttrs['tms-date'] = 'Y';
                inpAttrs['tms-date-value'] = 'data.' + key;
                html += '<div wrap="datet" ng-bind="data.' + key + '|date:\'yy-MM-dd HH:mm\'"';
                html += ' title="' + def.name + '"';
                html += ' placeholder="' + def.name + '"';
                def.required == 1 && (html += 'required=""');
                html += ' class="form-control input-lg"></div>';
                break;
            case 'longtext':
                html += '<textarea style="height:auto" ng-model="data.' + key + '" title="' + def.name + '"';
                def.showname === 'placeholder' && (html += ' placeholder="' + def.name + '"');
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
                            html += ' value="v' + i + '"';
                            html += ' ng-model="data.' + key + '"';
                            def.required == 1 && (html += 'required=""');
                            html += ' title="' + def.name + '"';
                            for (var a in def.attrs) {
                                html += 'data-' + def.attrs[a].name + '="' + def.attrs[a].value + '"';
                            }
                            html += ' data-label="' + def.ops[i].text + '"><span>' + def.ops[i].text + '</span></label></li>';
                        }
                        html += '</ul>';
                    } else if (def.component === 'S') {
                        html += '<select class="form-control" ng-model="data.' + key + '"';
                        def.required == 1 && (html += 'required=""');
                        html += ' title="' + def.name + '">\r\n';
                        for (var i in def.ops) {
                            html += '<option wrap="option" name="data.' + key + '" value="v' + i + '"' + 'data-label="' + def.ops[i].text + '"' + 'title="' + def.name + '"' + '>' + def.ops[i].text + '</option>';
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
                        html += ' ng-model="data.' + key + '.v' + i + '"';
                        html += ' title="' + def.name + '" data-label="' + def.ops[i].text + '"><span>' + def.ops[i].text + '</span></label></li>';
                    }
                    html += '</ul>';
                }
                break;
            case 'image':
                inpAttrs['tms-image-input'] = 'Y';
                html += '<ul class="img-tiles clearfix" name="' + key + '">';
                html += '<li wrap="img" ng-repeat="img in data.' + key + '" class="img-thumbnail" title="' + def.name + '">';
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
                html += '<li wrap="file" ng-repeat="file in data.' + key + '" class="list-group-item" title="' + def.name + '">';
                html += '<span class="file-name" ng-bind="file.name"></span>';
                html += '</li>';
                html += '<li class="list-group-item file-picker">';
                html += '<button class="btn btn-success" ng-click="chooseFile(\'' + key + '\',' + def.count + ')">' + def.name + '</button>';
                html += '</li>';
                html += '</ul>';
                break;
            case 'location':
                html += '<div wrap="location" class="input-group input-group-lg">';
                html += '<input type="text" ng-model="data.' + key + '"';
                html += ' title="' + def.name + '"';
                html += ' placeholder="' + def.name + '"';
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
        if (def.schema === undefined) return;
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
        angular.forEach(def.schema, function(s) {
            if (s.checked) {
                switch (s.type) {
                    case 'input':
                        html = '<label>' + s.title + '</label><div>{{Record.current.data.' + s.id + '}}</div>';
                        break;
                    case 'radio':
                    case 'checkbox':
                    case 'option':
                        html = '<label>' + s.title + '</label><div>{{value2Label("' + s.id + '")}}</div>';
                        attrs['enroll-schema'] = 'fromCache=Y;interval=600';
                        break;
                    case 'datetime':
                        html = "<label>" + s.title + "</label><div>{{Record.current.data." + s.id + "|date:'yy-MM-dd HH:mm'}}</div>";
                        break;
                    case 'img':
                        html = '<label>' + s.title + '</label><ul><li ng-repeat="img in Record.current.data.' + s.id + '.split(\',\')"><img ng-src="{{img}}"></li></ul>';
                        break;
                }
                html ? htmls.push(html) : console.log('embedRecord schema error', s);
            }
        });
        if (def.addEnrollAt) {
            html = "<label>登记时间</label><div>{{Record.current.enroll_at*1000|date:'yy-MM-dd HH:mm'}}</div>";
            htmls.push(html);
        }
        if (def.addNickname) {
            html = "<label>昵称</label><div>{{Record.current.enroller.nickname}}</div>";
            htmls.push(html);
        }
        if (def.addHeadpic) {
            html = "<label>头像</label><div><img ng-src='{{Record.current.enroller.fan.headimgurl}}'></div>";
            htmls.push(html);
        }
        _this = this;
        angular.forEach(htmls, function(h) {
            _this.addWrap(page, 'div', attrs, h);
        });
    };
    WrapLib.prototype.embedList = function(page, def) {
        var onclick, html;
        onclick = def.onclick.length ? " ng-click=\"gotoPage($event,'" + def.onclick + "',r.enroll_key)\"" : '';
        html = '<ul class="list-group">';
        console.log('hhh', html);
        html += '<li class="list-group-item" ng-repeat="r in records"' + onclick + '>';
        if (def.addEnrollAt)
            html += "<div wrap='static' class='wrap-inline'><label>登记时间</label><div>{{r.enroll_at*1000|date:'yy-MM-dd HH:mm'}}</div></div>";
        if (def.addNickname)
            html += "<div wrap='static' class='wrap-inline'><label>昵称</label><div>{{r.nickname}}</div></div>";
        if (def.addHeadpic)
            html += "<div wrap='static' class='wrap-inline'><label>头像</label><div><img ng-src='{{r.headimgurl}}'></div></div>";
        if (def.schema) {
            var i, s;
            for (i in def.schema) {
                s = def.schema[i];
                if (!s.checked) continue;
                switch (s.type) {
                    case 'input':
                    case 'location':
                        html += '<div wrap="static" class="wrap-inline"><label>' + s.title + '</label><div>{{r.data.' + s.id + '}}</div></div>';
                        break;
                    case 'datetime':
                        html += '<div wrap="static" class="wrap-inline"><label>' + s.title + '</label><div>{{r.data.' + s.id + '|date:"yy-MM-dd HH:mm"}}</div></div>';
                        break;
                    case 'radio':
                    case 'checkbox':
                    case 'option':
                        html += '<div wrap="static" class="wrap-inline"><label>' + s.title + '</label><div>{{r.data.' + s.id + '|value2Label:"' + s.id + '"}}</div></div>';
                        break;
                    case 'img':
                        html += '<div wrap="static" class="wrap-inline"><label>' + s.title + '</label><ul><li ng-repeat="img in r.data.' + s.id + '.split(\',\')"><img ng-src="{{img}}"></li></ul></div>';
                        break;
                }
            }
        }
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
    WrapLib.prototype.embedShow = function(page, def) {
        switch (def.type) {
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
            html = "<label>昵称</label><div>{{User.fan.nickname}}</div>";
            this.addWrap(page, 'div', {
                wrap: 'static',
                class: 'form-group'
            }, html);
        }
        if (def.headpic === true) {
            html = '<label>头像</label><div><img ng-src="{{User.fan.headimgurl}}"></div>';
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
        if (schema = EmbedButtonSchema[def.type]) {
            id = schema.id;
            angular.isFunction(id) && (id = id(def));
            action = schema.act;
            angular.isFunction(action) && (action = action(def));
            if (def.type === 'acceptInvite') {
                attrs['ng-controller'] = 'ctrlInvite';
            } else if (def.type === 'editRecord' || def.type === 'likeRecord') {
                attrs['ng-controller'] = 'ctrlRecord';
            }
            this.addWrap(page, 'div', attrs, tmplBtn(id, action, def.label));
        } else if (def.type === 'sendInvite') {
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
        } else if (def.type === 'remarkRecord') {
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
    xxtApp.register.controller('pageCtrl', ['$scope', 'http2', '$uibModal', '$timeout', 'Mp', function($scope, http2, $uibModal, $timeout, Mp) {
        $scope.$parent.subView = 'page';
        var extractSchema = function() {
            var i, pages, page, s, s2;
            pages = $scope.editing.pages;
            s = {};
            for (i in pages) {
                page = pages[i];
                if (page.type && page.type === 'I') {
                    s2 = wrapLib.extractSchema(page.html);
                    s = angular.extend(s, s2);
                }
            }
            return s;
        };
        $scope.innerlinkTypes = [{
            value: 'article',
            title: '单图文',
            url: '/rest/mp/matter'
        }, {
            value: 'news',
            title: '多图文',
            url: '/rest/mp/matter'
        }, {
            value: 'channel',
            title: '频道',
            url: '/rest/mp/matter'
        }];
        $scope.embedInput = function(page) {
            $uibModal.open({
                templateUrl: 'embedInputLib.html',
                backdrop: 'static',
                controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                    var key;
                    key = 'c' + (new Date()).getTime();
                    (new Mp()).getAuthapis().then(function(data) {
                        $scope.authapis = data;
                    });
                    $scope.def = {
                        key: 'name',
                        type: '0',
                        name: '姓名',
                        showname: 'placeholder',
                        component: 'R',
                        align: 'V',
                        count: 1,
                        setUpper: 'N'
                    };
                    $scope.addOption = function() {
                        if ($scope.def.ops === undefined)
                            $scope.def.ops = [];
                        var newOp = {
                            text: ''
                        };
                        $scope.def.ops.push(newOp);
                        $timeout(function() {
                            $scope.$broadcast('xxt.editable.add', newOp);
                        });
                    };
                    $scope.addExtAttr = function() {
                        $scope.def.attrs === undefined && ($scope.def.attrs = []);
                        var newAttr = {
                            name: '',
                            value: ''
                        };
                        $scope.def.attrs.push(newAttr);
                    };
                    $scope.$on('xxt.editable.remove', function(e, op) {
                        var i = $scope.def.ops.indexOf(op);
                        $scope.def.ops.splice(i, 1);
                    });
                    $scope.changeType = function() {
                        var map = {
                            '0': {
                                name: '姓名',
                                key: 'name'
                            },
                            '1': {
                                name: '手机',
                                key: 'mobile'
                            },
                            '2': {
                                name: '邮箱',
                                key: 'email'
                            }
                        };
                        if (map[$scope.def.type]) {
                            $scope.def.name = map[$scope.def.type].name;
                            $scope.def.key = map[$scope.def.type].key;
                        } else if ($scope.def.type === 'auth') {
                            $scope.def.name = '';
                            $scope.def.key = '';
                            $scope.def.auth = {};
                        } else {
                            $scope.def.name = '';
                            $scope.def.key = key;
                        }
                    };
                    $scope.selectedAuth = {
                        api: null,
                        attrs: null,
                        attr: null
                    };
                    $scope.shiftAuthapi = function() {
                        var auth = $scope.selectedAuth.api,
                            authAttrs = [];
                        $scope.def.auth.authid = auth.authid;
                        auth.attr_name[0] === '0' && (authAttrs.push({
                            id: 'name',
                            label: '姓名'
                        }));
                        auth.attr_mobile[0] === '0' && (authAttrs.push({
                            id: 'mobile',
                            label: '手机'
                        }));
                        auth.attr_email[0] === '0' && (authAttrs.push({
                            id: 'email',
                            label: '邮箱'
                        }));
                        if (auth.extattr && auth.extattr.length) {
                            var i, l, ea;
                            for (i = 0, l = auth.extattr.length; i < l; i++) {
                                ea = auth.extattr[i];
                                authAttrs.push({
                                    id: 'extattr.' + ea.id,
                                    label: ea.label
                                });
                            }
                        }
                        $scope.selectedAuth.attrs = authAttrs;
                    };
                    $scope.shiftAuthAttr = function() {
                        var attr = $scope.selectedAuth.attr;
                        $scope.def.name = attr.label;
                        $scope.def.key = 'member.' + attr.id;
                    };
                    $scope.ok = function() {
                        if ($scope.def.name.length === 0) {
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
                wrapLib.embedInput(page, def);
            });
        };
        var embedButtonCtrl = ['$scope', '$uibModalInstance', 'enroll', 'def', function($scope, $mi, enroll, def) {
            var page, targetPages, inputPages;
            targetPages = {};
            inputPages = {};
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
            };
            enroll.can_like_record === 'Y' && ($scope.buttons.likeRecord = {
                l: '点赞'
            });
            enroll.can_remark_record === 'Y' && ($scope.buttons.remarkRecord = {
                l: '评论'
            });
            for (var p in enroll.pages) {
                page = enroll.pages[p];
                targetPages[page.name] = {
                    l: page.title
                };
                if (page.type === 'I') {
                    inputPages[page.name] = {
                        l: page.title
                    };
                }
            }
            targetPages.closeWindow = {
                l: '关闭页面'
            };
            $scope.pages = targetPages;
            $scope.inputPages = inputPages;
            $scope.def = def;
            $scope.selectButton = function() {
                var names;
                def.label = $scope.buttons[def.type].l;
                def.next = '';
                if (['addRecord', 'editRecord'].indexOf(def.type) !== -1) {
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
        $scope.embedButton = function(page) {
            $uibModal.open({
                templateUrl: 'embedButtonLib.html',
                backdrop: 'static',
                resolve: {
                    enroll: function() {
                        return $scope.editing;
                    },
                    def: function() {
                        return {
                            type: '',
                            label: '',
                            next: ''
                        };
                    }
                },
                controller: embedButtonCtrl,
            }).result.then(function(def) {
                wrapLib.embedButton(page, def);
            });
        };
        $scope.embedShow = function(page) {
            $uibModal.open({
                templateUrl: 'embedShowLib.html',
                backdrop: 'static',
                resolve: {
                    enroll: function() {
                        return $scope.editing;
                    },
                    schema: function() {
                        return extractSchema();
                    }
                },
                controller: ['$scope', '$uibModalInstance', 'enroll', 'schema', function($scope, $mi, enroll, schema) {
                    $scope.options = {
                        record: {
                            l: '登记项'
                        },
                        list: {
                            l: '登记清单'
                        },
                    };
                    enroll.multi_rounds === 'Y' && ($scope.options.rounds = {
                        l: '轮次清单'
                    });
                    enroll.can_remark_record === 'Y' && ($scope.options.remarks = {
                        l: '评论清单'
                    });
                    enroll.can_like_record === 'Y' && ($scope.options.likers = {
                        l: '点赞人清单'
                    });
                    $scope.pages = enroll.pages;
                    $scope.def = {
                        type: 'record',
                        inline: true,
                        splitLine: true,
                        dataScope: 'U',
                        canLike: 'N',
                        autoload: 'N',
                        onclick: '',
                        addEnrollAt: 0,
                        addNickname: 0
                    };
                    $scope.def.schema = schema;
                    $scope.ok = function() {
                        $mi.close($scope.def);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                }]
            }).result.then(function(def) {
                wrapLib.embedShow(page, def);
            });
        };
        $scope.embedUser = function(page) {
            $uibModal.open({
                templateUrl: 'embedUserLib.html',
                backdrop: 'static',
                resolve: {
                    enroll: function() {
                        return $scope.editing;
                    }
                },
                controller: ['$scope', '$uibModalInstance', 'enroll', function($scope, $mi, enroll) {
                    $scope.pages = enroll.pages;
                    $scope.def = {
                        nickname: 0
                    };
                    $scope.ok = function() {
                        $mi.close($scope.def);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                }]
            }).result.then(function(def) {
                wrapLib.embedUser(page, def);
            });
        };
        $scope.embedMatter = function(page) {
            $scope.$broadcast('mattersgallery.open', function(matters, type) {
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
            });
        };
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
                if (def.type === 'remarkRecord') {
                    $scope.$root.errmsg = '不支持修改该类型组件';
                    return;
                }
                $uibModal.open({
                    templateUrl: 'embedButtonLib.html',
                    backdrop: 'static',
                    resolve: {
                        enroll: function() {
                            return $scope.editing;
                        },
                        def: function() {
                            return def;
                        }
                    },
                    controller: embedButtonCtrl,
                }).result.then(function(def) {
                    wrapLib.changeEmbedButton(page, $active[0], def);
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
        $scope.$on('tinymce.multipleimage.open', function(event, callback) {
            var options = {
                callback: callback,
                multiple: true,
                setshowname: true
            }
            $scope.$broadcast('mediagallery.open', options);
        });
        $scope.addPage = function() {
            http2.get('/rest/mp/app/enroll/page/add?aid=' + $scope.aid, function(rsp) {
                var page = rsp.data;
                $scope.editing.pages.push(page);
                $timeout(function() {
                    $('a[href="#tab_' + page.name + '"]').tab('show');
                });
            });
        };
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
            if (!angular.equals($scope.editing, $scope.persisted)) {
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
                url = '/rest/mp/app/enroll/page/update';
                url += '?aid=' + $scope.aid;
                url += '&pid=' + page.id;
                url += '&pname=' + page.name;
                url += '&cid=' + page.code_id;
                http2.post(url, p, function(rsp) {
                    $scope.persisted = angular.copy($scope.editing);
                    page.$$modified = false;
                    $scope.$root.progmsg = '';
                });
            }
        };
        $scope.delPage = function(index, page) {
            if (window.confirm('确定删除？')) {
                var url = '/rest/mp/app/enroll/page/remove';
                url += '?aid=' + $scope.aid;
                url += '&pid=' + page.id;
                http2.get(url, function(rsp) {
                    tinymce.remove('#' + page.name);
                    $scope.editing.pages.splice(index, 1);
                    $timeout(function() {
                        $($('a[href^=#tab_]')[0]).tab('show');
                    });
                });
            }
        };
        $scope.shiftPage = function(event) {
            event.preventDefault();
            $(event.target).tab('show');
        };
        $scope.gotoCode = function(codeid) {
            window.open('/rest/code?pid=' + codeid, '_self');
        };
        window.onbeforeunload = function(e) {
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
        $scope.$watch('editing', function(nv) {
            if (!nv) return;
            $scope.schema = extractSchema();
            $timeout(function() {
                $($('a[href^=#tab_]')[0]).tab('show');
            });
        });
    }]);
})();