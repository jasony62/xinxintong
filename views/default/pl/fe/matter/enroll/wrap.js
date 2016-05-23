(function() {
    'use strict';
    var WrapLib = function() {};
    WrapLib.prototype.addWrap = function(editor, name, attrs, html) {
        var dom, body, wrap, newWrap, selection, activeEditor, $activeWrap, $upmost;
        dom = editor.dom;
        body = editor.getBody();
        $activeWrap = $(body).find('[wrap].active');
        if ($activeWrap.length) {
            $upmost = $activeWrap.parents('[wrap]');
            $upmost = $upmost.length === 0 ? $activeWrap : $($upmost.get($upmost.length - 1));
            newWrap = dom.create(name, attrs, html);
            dom.insertAfter(newWrap, $upmost[0]);
        } else {
            newWrap = dom.add(body, name, attrs, html);
        }
        return newWrap;
    };
    WrapLib.prototype.extractInputSchema = function(wrap) {
        var $label, schema = {},
            $input, model;
        $label = $($(wrap).find('label').get(0));
        schema.title = $label.html();
        schema.showname = $label.hasClass('sr-only') ? 'placeholder' : 'label';
        $input = $(wrap).find('input,select,textarea');
        model = $input.attr('ng-model') || $input.attr('ng-bind');
        schema.id = model.split('.')[1];
        return schema;
    };
    WrapLib.prototype.modifyEmbedInput = function(editor, wrap, schema) {
        var newWrap = this.embedInput(editor, schema);
        $(editor.getBody()).find('.active').remove();
        return newWrap;
    };
    WrapLib.prototype.embedInput = function(editor, schema) {
        var newWrap, inpAttrs = {
                wrap: 'input',
                class: 'form-group form-group-lg'
            },
            html = '<label' + (schema.showname === 'label' ? '' : ' class="sr-only"') + '>' + schema.title + '</label>';
        switch (schema.type) {
            case 'name':
            case 'mobile':
            case 'email':
            case 'shorttext':
            case 'member':
                html += '<input type="text" ng-model="data.' + schema.id + '" title="' + schema.title + '"';
                schema.showname === 'placeholder' && (html += ' placeholder="' + schema.title + '"');
                schema.required == 1 && (html += 'required=""');
                schema.type === 'member' && (html += 'ng-init="data.member.schema_id=' + schema.schema_id + '"');
                html += ' class="form-control input-lg">';
                break;
            case 'date':
                inpAttrs['tms-date'] = 'Y';
                inpAttrs['tms-date-value'] = 'data.' + schema.id;
                html += '<div wrap="datet" ng-bind="data.' + schema.id + '|date:\'yy-MM-dd HH:mm\'"';
                html += ' title="' + schema.title + '"';
                html += ' placeholder="' + schema.title + '"';
                schema.required == 1 && (html += 'required=""');
                html += ' class="form-control input-lg"></div>';
                break;
            case 'longtext':
                html += '<textarea style="height:auto" ng-model="data.' + schema.id + '" title="' + schema.title + '"';
                schema.showname === 'placeholder' && (html += ' placeholder="' + schema.title + '"');
                schema.required == 1 && (html += 'required=""');
                html += ' class="form-control" rows="3"></textarea>';
                break;
            case 'single':
                if (schema.ops && schema.ops.length > 0) {
                    if (schema.component === 'R') {
                        html += '<ul>', cls = 'radio';
                        if (schema.align === 'H') cls += '-inline';
                        angular.forEach(schema.ops, function(op) {
                            html += '<li class="' + cls + '" wrap="radio"><label';
                            if (schema.align === 'H') html += ' class="radio-inline"';
                            html += '><input type="radio" name="' + schema.id + '"';
                            html += ' value="' + op.v + '"';
                            html += ' ng-model="data.' + schema.id + '"';
                            schema.required == 1 && (html += 'required=""');
                            html += ' title="' + schema.title + '"';
                            angular.forEach(schema.attrs, function(attr) {
                                html += 'data-' + attr.name + '="' + attr.value + '"';
                            });
                            html += ' data-label="' + op.l + '"><span>' + op.l + '</span></label></li>';
                        });
                        html += '</ul>';
                    } else if (schema.component === 'S') {
                        html += '<select class="form-control" ng-model="data.' + schema.id + '"';
                        schema.required == 1 && (html += 'required=""');
                        html += ' title="' + schema.title + '">\r\n';
                        angular.forEach(schema.ops, function(op) {
                            html += '<option wrap="option" name="data.' + schema.id + '" value="' + op.v + '"' + 'data-label="' + op.l + '"' + 'title="' + schema.title + '"' + '>' + op.l + '</option>';
                        });
                        html += '\r\n</select>';
                    }
                }
                break;
            case 'multiple':
                if (schema.ops && schema.ops.length > 0) {
                    var cls;
                    html += '<ul';
                    if (schema.setUpper === 'Y') {
                        html += ' tms-checkbox-group="' + schema.id + '" tms-checkbox-group-model="data" tms-checkbox-group-upper="' + schema.upper + '"';
                    }
                    html += '>';
                    cls = 'checkbox';
                    if (schema.align === 'H') cls += '-inline';
                    angular.forEach(schema.ops, function(op) {
                        html += '<li class="' + cls + '" wrap="checkbox"><label';
                        if (schema.align === 'H') html += ' class="checkbox-inline"';
                        html += '><input type="checkbox" name="' + schema.id + '"';
                        schema.required == 1 && (html += 'required=""');
                        html += ' ng-model="data.' + schema.id + '.' + op.v + '"';
                        html += ' title="' + schema.title + '" data-label="' + op.l + '"><span>' + op.l + '</span></label></li>';
                    });
                    html += '</ul>';
                }
                break;
            case 'image':
                inpAttrs['tms-image-input'] = 'Y';
                html += '<ul class="img-tiles clearfix" name="' + schema.id + '">';
                html += '<li wrap="img" ng-repeat="img in data.' + schema.id + '" class="img-thumbnail" title="' + schema.title + '">';
                html += '<img flex-img>';
                html += '<button class="btn btn-default btn-xs" ng-click="removeImage(data.' + schema.id + ',$index)"><span class="glyphicon glyphicon-remove"></span></button>';
                html += '</li>';
                html += '<li class="img-picker">';
                html += '<button class="btn btn-default" ng-click="chooseImage(\'' + schema.id + '\',' + schema.count + ')"><span class="glyphicon glyphicon-picture"></span><br>上传图片</button>';
                html += '</li>';
                html += '</ul>';
                break;
            case 'file':
                inpAttrs['tms-file-input'] = 'Y';
                html += '<ul class="list-group file" name="' + schema.id + '">';
                html += '<li class="list-group-item" ng-show="progressOfUploadFile"><div class="progressOfUploadFile" ng-bind="progressOfUploadFile"></li>';
                html += '<li wrap="file" ng-repeat="file in data.' + schema.id + '" class="list-group-item" title="' + schema.title + '">';
                html += '<span class="file-name" ng-bind="file.name"></span>';
                html += '</li>';
                html += '<li class="list-group-item file-picker">';
                html += '<button class="btn btn-success" ng-click="chooseFile(\'' + schema.id + '\',' + schema.count + ')">' + schema.title + '</button>';
                html += '</li>';
                html += '</ul>';
                break;
            case 'location':
                html += '<div wrap="location" class="input-group input-group-lg">';
                html += '<input type="text" ng-model="data.' + schema.id + '"';
                html += ' title="' + schema.title + '"';
                html += ' placeholder="' + schema.title + '"';
                schema.required == 1 && (html += 'required=""');
                html += ' class="form-control">';
                html += '<span class="input-group-btn">';
                fn = 'getMyLocation(\'' + schema.id + '\')';
                html += '<button class="btn btn-default" type="button" ng-click="' + fn + '">定位</button>';
                html += '</span>';
                html += '</div>';
                break;
        }
        newWrap = this.addWrap(editor, 'div', inpAttrs, html);
        return newWrap;
    };
    WrapLib.prototype.embedRecord = function(editor, def) {
        if (def.schema === undefined) return;
        var c = 'form-group',
            html = '',
            attrs = {
                'ng-controller': 'ctrlRecord',
                wrap: 'static'
            };
        def.inline === 'Y' && (c += ' wrap-inline');
        def.splitLine === 'Y' && (c += ' wrap-splitline');
        attrs.class = c;
        def.id && (attrs.id = def.id);
        switch (def.schema.type) {
            case 'name':
            case 'mobile':
            case 'email':
            case 'shorttext':
            case 'longtext':
            case 'member':
                html = '<label>' + def.schema.title + '</label><div>{{Record.current.data.' + def.schema.id + '}}</div>';
                break;
            case 'single':
            case 'multiple':
                html = '<label>' + def.schema.title + '</label><div>{{value2Label("' + def.schema.id + '")}}</div>';
                break;
            case 'datetime':
                html = "<label>" + def.schema.title + "</label><div>{{Record.current.data." + def.schema.id + "|date:'yy-MM-dd HH:mm'}}</div>";
                break;
            case 'image':
                html = '<label>' + def.schema.title + '</label><ul><li ng-repeat="img in Record.current.data.' + def.schema.id + '.split(\',\')"><img ng-src="{{img}}"></li></ul>';
                break;
            case '_enrollAt':
                html = "<label>" + def.schema.title + "</label><div>{{Record.current.enroll_at*1000|date:'yy-MM-dd HH:mm'}}</div>";
                break;
            case '_enrollerNickname':
                html = "<label>" + def.schema.title + "</label><div>{{Record.current.enroller.nickname}}</div>";
                break;
            case '_enrollerHeadpic':
                html = "<label>" + def.schema.title + "</label><div><img ng-src='{{Record.current.enroller.headimgurl}}'></div>";
                break;
        }
        this.addWrap(editor, 'div', attrs, html);
    };
    WrapLib.prototype.embedList = function(editor, config) {
        if (!config.schemas && config.schemas.length === 0) return false;;
        var onclick, html, attrs;
        onclick = config.onclick.length ? " ng-click=\"gotoPage($event,'" + config.onclick + "',r.enroll_key)\"" : '';
        html = '<ul class="list-group">';
        html += '<li class="list-group-item" ng-repeat="r in records"' + onclick + '>';
        angular.forEach(config.schemas, function(s) {
            switch (s.type) {
                case 'name':
                case 'email':
                case 'mobile':
                case 'shorttext':
                case 'longtext':
                case 'location':
                case 'member':
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
        html += "</li></ul>";
        attrs = {
            'ng-controller': 'ctrlRecords',
            'enroll-records': 'Y',
            'enroll-records-owner': config.dataScope,
            wrap: 'record-list',
            class: 'form-group'
        };
        config.id && (attrs.id = config.id);
        var newWrap = this.addWrap(editor, 'div', attrs, html);
        return newWrap;
    };
    WrapLib.prototype.embedRounds = function(page, config) {
        var onclick, html, attrs = {
            'ng-controller': 'ctrlRounds',
            wrap: 'round-list',
            class: 'form-group'
        };
        config.id && (attrs.id = config.id);
        onclick = config.onclick.length ? " ng-click=\"gotoPage($event,'" + config.onclick + "',null,r.rid)\"" : '';
        html = "<ul class='list-group'><li class='list-group-item' ng-repeat='r in rounds'" + onclick + "><div>{{r.title}}</div></li></ul>";
        this.addWrap(page, 'div', attrs, html);
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
    WrapLib.prototype.changeEmbedStatic = function(editor, wrap, config) {
        config.inline === 'Y' ? $(wrap).addClass('wrap-inline') : $(wrap).removeClass('wrap-inline');
        config.splitLine === 'Y' ? $(wrap).addClass('wrap-splitline') : $(wrap).removeClass('wrap-splitline');
    };
    WrapLib.prototype.extractStaticSchema = function(wrap) {
        var config = {},
            html;
        config.id = $(wrap).attr('id');
        config.inline = $(wrap).hasClass('wrap-inline');
        config.splitLine = $(wrap).hasClass('wrap-splitline');
        if (!config.id) {
            html = $(wrap).html();
            html = html.match(/\{\{(.+)\}\}/);
            if (html.length === 2) {
                config.schema = {
                    id: html[1].split('.').pop()
                };
            }
        }
        return config;
    };
    var PrefabActSchema = {
        _args: function(schema) {
            return schema.next ? "($event,'" + schema.next + "')" : "($event)"
        },
        addRecord: {
            act: function(schema) {
                return 'addRecord' + PrefabActSchema._args(schema);
            }
        },
        editRecord: {
            act: function(schema) {
                return 'editRecord' + PrefabActSchema._args(schema);
            }
        },
        removeRecord: {
            act: function(schema) {
                return 'removeRecord' + PrefabActSchema._args(schema);
            }
        },
        submit: {
            act: function(schema) {
                return 'submit' + PrefabActSchema._args(schema);
            }
        },
        acceptInvite: {
            act: function(schema) {
                return 'accept' + PrefabActSchema._args(schema);
            }
        },
        gotoPage: {
            act: function(schema) {
                return 'gotoPage' + PrefabActSchema._args(schema);
            }
        },
        closeWindow: {
            act: 'closeWindow($event)'
        }
    };
    WrapLib.prototype.button = {
        embed: function(editor, schema) {
            var attrs = {
                    wrap: 'button',
                    class: 'form-group'
                },
                tmplBtn = function(action, label) {
                    return '<button class="btn btn-primary btn-block btn-lg" ng-click="' + action + '"><span>' + label + '</span></button>';
                },
                prefab, action;
            schema.id && (attrs.id = schema.id);
            if (prefab = PrefabActSchema[schema.name]) {
                action = prefab.act;
                angular.isFunction(action) && (action = action(schema));
                if (schema.name === 'acceptInvite') {
                    attrs['ng-controller'] = 'ctrlInvite';
                } else if (['editRecord', 'removeRecord'].indexOf(schema.name) !== -1) {
                    attrs['ng-controller'] = 'ctrlRecord';
                }
                WrapLib.prototype.addWrap(editor, 'div', attrs, tmplBtn(action, schema.label));
            } else if (schema.name === 'sendInvite') {
                var html;
                action = "send($event,'" + schema.accept + "'";
                schema.next && (action += ",'" + schema.next + "'");
                action += ")";
                html = '<input type="text" class="form-control" placeholder="自定义用户标识" ng-model="invitee">';
                html += '<span class="input-group-btn">';
                html += '<button class="btn btn-success" type="button" ng-click="' + action + '"><span>' + label + '</span></button>';
                html += '</span>';
                attrs.class += "  input-group input-group-lg";
                attrs['ng-controller'] = 'ctrlInvite';
                WrapLib.prototype.addWrap(editor, 'div', attrs, html);
            }
        },
        extract: function(wrap) {
            var $button, action, arg, schema = {};
            $button = $(wrap).find('button');
            schema.id = $(wrap).attr('id');
            schema.label = $button.children('span').html();
            action = $button.attr('ng-click');
            action = action.match(/(.+?)\((.+?)\)/);
            schema.name = action[1];
            arg = action[2].split(',');
            arg.length === 2 && (schema.next = arg[1].replace(/'/g, ''));
            return schema;
        },
        modify: function(wrap, schema) {
            var prefab, action, $button;
            if (prefab = PrefabActSchema[schema.name]) {
                action = prefab.act;
                angular.isFunction(action) && (action = action(schema));
                $button = $(wrap).find('button');
                $button.children('span').html(schema.label);
                $button.attr('ng-click', action);
            }
        }
    }
    window.wrapLib = new WrapLib();
})();