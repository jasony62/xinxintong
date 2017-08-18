define([], function() {
    'use strict';
    /**/
    var _editor = null;
    /**/
    var _page = null;
    /**
     * wrap basic class
     */
    var Wrap = function() {};
    /*在指定的editor的文档中添加一个wrap*/
    Wrap.prototype.append = function(name, attrs, html) {
        var dom, body, wrap, newDomWrap, selection, $activeWrap, $upmost;

        if (_page) {
            var $html = $('<div>' + _page.html + '</div>');
            if (attrs.wrap && attrs.wrap === 'input') {
                var $inputWrap = $html.find("[wrap='input']");
                newDomWrap = $(document.createElement(name)).attr(attrs).html(html);
                if ($inputWrap.length) {
                    // 加到最后一个登记项后面
                    $inputWrap = $($inputWrap.get($inputWrap.length - 1));
                    $inputWrap.after(newDomWrap);
                } else {
                    // 加在文档的最后
                    $html.append(newDomWrap);
                }
            } else if (attrs.wrap && attrs.wrap === 'value') {
                var $valueWrap = $html.find("[wrap='value']");
                if ($valueWrap.length) {
                    // 加在最后一个static wrap的后面
                    $inputWrap.after(newDomWrap);
                } else {
                    // 加在页面的最后
                    $html.append(newDomWrap);
                }
            } else {
                // 加在页面的最后
                $html.append(newDomWrap);
            }
            _page.html = $html.html();
        } else if (_editor) {
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
        }

        return newDomWrap;
    };
    /**
     * input wrap class
     */
    var InputWrap = function() {};
    InputWrap.prototype = Object.create(Wrap.prototype);
    InputWrap.prototype.newWrap = function(schema) {
        var oWrap = {
            config: {
                showname: 'label'
            },
            schema: schema,
        };
        if (/single|multiple|phase/.test(schema.type)) {
            oWrap.config.align = 'V';
            if (/single|phase/.test(schema.type)) {
                oWrap.config.component = 'R';
            }
        }

        return oWrap;
    };

    function _htmlRadio(schema, op, config, forEdit) {
        var html, cls;

        cls = 'radio';
        config.align === 'H' && (cls += '-inline');

        html = '<li class="' + cls + '" wrap="radio"';
        forEdit && (html += ' contenteditable="false"');
        schema.required === 'Y' && (html += 'required');
        html += '><label';
        if (config.align === 'H') html += ' class="radio-inline"';
        html += '><input type="radio" name="' + schema.id + '"';
        html += ' value="' + op.v + '"';
        html += ' ng-model="data.' + schema.id + '"';
        schema.attrs && schema.attrs.forEach(function(attr) {
            html += 'data-' + attr.name + '="' + attr.value + '"';
        });
        forEdit && (html += ' disabled');
        html += '><span ';
        forEdit && (html += 'contenteditable="true"');
        html += '>' + op.l + '</span></label></li>';

        return html;
    }

    function _htmlCheckbox(schema, op, config, forEdit) {
        var html, cls;

        cls = 'checkbox';
        config.align === 'H' && (cls += '-inline');

        html = '<li class="' + cls + '" wrap="checkbox"';
        forEdit && (html += ' contenteditable="false"');
        schema.required === 'Y' && (html += 'required');
        html += '><label';
        if (config.align === 'H') html += ' class="checkbox-inline"';
        html += '><input type="checkbox" name="' + schema.id + '"';
        html += ' ng-model="data.' + schema.id + '.' + op.v + '"';
        forEdit && (html += ' disabled');
        html += '><span ';
        forEdit && (html += 'contenteditable="true"');
        html += '>' + op.l + '</span></label></li>';

        return html;
    }

    function _htmlNumber(schema, op, config, forEdit) {
        var html, index;

        index = schema.ops.indexOf(op);
        html = '<li class="score" wrap="score" opvalue="' + op.v + '"';
        forEdit && (html += ' contenteditable="false"');
        schema.required === 'Y' && (html += 'required');
        html += '><div><label'
        forEdit && (html += ' contenteditable="true"');
        html += '>' + op.l + '</label></div>';
        html += '<div class="number">';
        for (var num = schema.range[0]; num <= schema.range[1]; num++) {
            html += "<div ng-class=\"{'in':lessScore('" + schema.id + "'," + index + "," + num + ")}\" ng-click=\"score('" + schema.id + "'," + index + "," + num + ")\">" + num + "</div>";
        }
        html += '</div></li>';

        return html;
    }

    function _htmlSupplement($dom, schema) {
        var $supplement;
        if (schema.supplement === 'Y') {
            $supplement = $dom.find('.supplement');
            if ($supplement.length === 0) {
                $dom.append('<textarea style="height: auto;" placeholder="补充说明" rows=3 class="form-control input-lg supplement" ng-model="supplement.' + schema.id + '"></textarea>');
            }
        } else {
            $supplement = $dom.find('.supplement');
            if ($supplement.length) {
                $supplement.remove();
            }
        }
    }

    function _htmlTag($dom, schema) {
        var $tags;
        if (schema.cantag === 'Y') {
            $tags = $dom.find('.tags');
            if ($tags.length === 0) {
                $dom.append('<p class="form-control tags" ng-click="tagRecordData(\'' + schema.id + '\')"><span class="tag" ng-repeat="t in tag[\'' + schema.id + '\']" ng-bind="t.label"></span></p>');
            }
        } else {
            $tags = $dom.find('.tags');
            if ($tags.length) {
                $tags.remove();
            }
        }
    }

    InputWrap.prototype.newRadio = function(schema, op, config, forEdit) {
        return _htmlRadio(schema, op, config, forEdit);
    };
    InputWrap.prototype.newCheckbox = function(schema, op, config, forEdit) {
        return _htmlCheckbox(schema, op, config, forEdit);
    };
    InputWrap.prototype.newNumber = function(schema, op, config, forEdit) {
        return _htmlNumber(schema, op, config, forEdit);
    };
    InputWrap.prototype._htmlSingleRadio = function(oWrap, forEdit, onlyChildren) {
        var config = oWrap.config,
            schema = oWrap.schema,
            html = '';

        schema.ops.forEach(function(op) {
            html += _htmlRadio(schema, op, config, forEdit);
        });
        if (!onlyChildren) {
            html = '<ul>' + html + '</ul>';
        }

        return html;
    };
    InputWrap.prototype._htmlSingleSelect = function(oWrap, onlyChildren) {
        var config = oWrap.config,
            schema = oWrap.schema,
            html = '',
            html2;

        schema.ops.forEach(function(op) {
            html += '<option wrap="option" name="data.' + schema.id + '" value="' + op.v + '"' + 'data-label="' + op.l + '"' + 'title="' + schema.title + '"' + '>' + op.l + '</option>';
        });

        if (!onlyChildren) {
            html2 = '<select class="form-control input-lg" ng-model="data.' + schema.id + '"';
            schema.required === 'Y' && (html2 += 'required=""');
            html2 += ' title="' + schema.title + '">\r\n';
            html = html2 + html + '\r\n</select>';
        }

        return html;
    };
    InputWrap.prototype._htmlMultiple = function(oWrap, forEdit, onlyChildren) {
        var config = oWrap.config,
            schema = oWrap.schema,
            html = '',
            html2;

        schema.ops.forEach(function(op) {
            html += _htmlCheckbox(schema, op, config, forEdit);
        });

        if (!onlyChildren) {
            html2 = '<ul';
            if (config.setUpper === 'Y') {
                html2 += ' tms-checkbox-group="' + schema.id + '" tms-checkbox-group-model="data" tms-checkbox-group-upper="' + config.upper + '"';
            }
            html2 += '>';
            html = html2 + html + '</ul>';
        }

        return html;
    };
    InputWrap.prototype._htmlScoreItem = function(oWrap, forEdit) {
        var config = oWrap.config,
            schema = oWrap.schema,
            html;

        html = '<ul>';
        schema.ops.forEach(function(op) {
            html += _htmlNumber(schema, op, config, forEdit);
        });
        html += '</ul>';

        return html;
    };
    InputWrap.prototype.embed = function(oWrap, forEdit) {
        var schema = oWrap.schema,
            config = oWrap.config,
            inpAttrs = {
                wrap: 'input',
                class: 'form-group form-group-lg',
                schema: schema.id,
                'schema-type': schema.type,
            },
            html = '<label' + (config.showname === 'label' ? '' : ' class="sr-only"') + (forEdit ? ' contenteditable="true">' : '>') + schema.title + '</label>';

        forEdit && (inpAttrs.contenteditable = 'false');

        switch (schema.type) {
            case 'shorttext':
            case 'member':
                if (schema.type === 'shorttext' && schema.history === 'Y') {
                    html += '<div wrap="shorttext-history" class="input-group input-group-lg">';
                    html += '<input type="text" ng-model="data.' + schema.id + '"';
                    html += ' title="' + schema.title + '"';
                    config.showname === 'placeholder' && (html += ' placeholder="' + schema.title + '"');
                    schema.required === 'Y' && (html += 'required=""');
                    html += ' class="form-control">';
                    html += '<span class="input-group-btn">';
                    html += '<button class="btn btn-default" ng-click="' + 'dataBySchema(\'' + schema.id + '\')' + '">查找</button>';
                    html += '</span>';
                    html += '</div>';
                } else {
                    html += '<input type="text" ng-model="data.' + schema.id + '" title="' + schema.title + '"';
                    config.showname === 'placeholder' && (html += ' placeholder="' + schema.title + '"');
                    schema.required === 'Y' && (html += 'required=""');
                    schema.type === 'member' && (html += 'ng-init="data.member.schema_id=' + schema.schema_id + '"');
                    html += ' class="form-control input-lg"';
                    forEdit && (html += ' readonly');
                    html += '>';
                }
                break;
            case 'date':
                inpAttrs['tms-date'] = 'Y';
                inpAttrs['tms-date-value'] = 'data.' + schema.id;
                html += '<div wrap="date" ng-bind="data.' + schema.id + '*1000|date:\'yy-MM-dd HH:mm\'"';
                html += ' title="' + schema.title + '"';
                html += ' placeholder="' + schema.title + '"';
                schema.required === 'Y' && (html += 'required=""');
                html += ' class="form-control input-lg"></div>';
                break;
            case 'longtext':
                html += '<textarea style="height:auto" ng-model="data.' + schema.id + '" title="' + schema.title + '"';
                config.showname === 'placeholder' && (html += ' placeholder="' + schema.title + '"');
                schema.required === 'Y' && (html += 'required=""');
                html += ' class="form-control" rows="3"';
                forEdit && (html += ' readonly');
                html += '></textarea>';
                break;
            case 'phase':
            case 'single':
                if (schema.ops && schema.ops.length > 0) {
                    if (schema.component === 'S') {
                        html += this._htmlSingleSelect(oWrap, forEdit);
                    } else {
                        html += this._htmlSingleRadio(oWrap, forEdit);
                    }
                }
                break;
            case 'multiple':
                if (schema.ops && schema.ops.length > 0) {
                    html += this._htmlMultiple(oWrap, forEdit);
                }
                break;
            case 'image':
                inpAttrs['tms-image-input'] = 'Y';
                html += '<ul class="img-tiles clearfix" name="' + schema.id + '">';
                html += '<li wrap="img" ng-repeat="img in data.' + schema.id + '" class="img-thumbnail">';
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
                html += '<li wrap="file" ng-repeat="file in data.' + schema.id + '" class="list-group-item">';
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
                schema.required === 'Y' && (html += 'required=""');
                html += ' class="form-control">';
                html += '<span class="input-group-btn">';
                html += '<button class="btn btn-default" ng-click="' + 'getMyLocation(\'' + schema.id + '\')' + '">定位</button>';
                html += '</span>';
                html += '</div>';
                break;
            case 'score':
                if (schema.ops && schema.ops.length > 0) {
                    html += this._htmlScoreItem(oWrap, forEdit);
                }
                break;
            case 'html':
                return {
                    tag: 'div',
                    attrs: {
                        wrap: 'html',
                        class: 'form-group',
                        schema: schema.id,
                        'schema-type': 'html',
                    },
                    html: schema.content
                };
        }

        return {
            tag: 'div',
            attrs: inpAttrs,
            html: html
        };
    };
    InputWrap.prototype.modify = function(domWrap, dataWrap, oBeforeSchema) {
        var $dom, $label, $input, config = dataWrap.config,
            oSchema = dataWrap.schema;

        $dom = $(domWrap);
        if (dataWrap.type === 'input') {
            if (oBeforeSchema && (oSchema.type !== oBeforeSchema.type || (oSchema.type === 'shorttext' && oSchema.history === 'Y'))) {
                var embeded = this.embed(dataWrap);
                $dom.attr(embeded.attrs);
                $dom.html(embeded.html);
            } else {
                $label = $dom.children('label');
                $label.html(oSchema.title);

                if (oSchema.description && oSchema.description.length) {
                    if (!$dom.find('[class="description"]').length) {
                        $('<div class="description">' + oSchema.description + '</div>').insertAfter($dom.find('label')[0])
                    } else {
                        $dom.find('[class="description"]').html(oSchema.description);
                    }
                } else {
                    $dom.find('[class="description"]').remove();
                }

                if (/shorttext|longtext|member|date|location/.test(oSchema.type)) {
                    $input = $dom.find('input,select,textarea');
                    if (config.showname === 'label') {
                        $label.removeClass('sr-only');
                        $input.removeAttr('placeholder');
                    } else {
                        $label.addClass('sr-only');
                        $input.attr('placeholder', oSchema.title);
                    }
                    if (oSchema.required === 'Y') {
                        $input.attr('required', '');
                    } else {
                        $input.removeAttr('required');
                    }
                    _htmlTag($dom, oSchema);
                } else if (/single|phase/.test(oSchema.type)) {
                    (function(lib) {
                        var html;
                        if (oSchema.ops) {
                            if (config.component === 'R') {
                                if ($dom.children('ul').length) {
                                    html = lib._htmlSingleRadio(dataWrap, false, true);
                                    $dom.children('ul').html(html);
                                } else if ($dom.children('select').length) {
                                    html = lib._htmlSingleRadio(dataWrap, false, false);
                                    $dom.children('select').replaceWith(html);
                                }
                            } else if (config.component === 'S') {
                                if ($dom.children('select').length) {
                                    html = lib._htmlSingleSelect(dataWrap, true);
                                    $dom.children('select').html(html);
                                } else if ($dom.children('ul').length) {
                                    html = lib._htmlSingleSelect(dataWrap, false);
                                    $dom.children('ul').replaceWith(html);
                                }
                            }
                        }
                        _htmlSupplement($dom, oSchema);
                    })(this);
                } else if ('multiple' === oSchema.type) {
                    (function(lib) {
                        var html;
                        if (oSchema.ops) {
                            html = lib._htmlMultiple(dataWrap, false, true);
                            $dom.children('ul').html(html);
                        }
                        _htmlSupplement($dom, oSchema);
                    })(this);
                } else if ('score' === oSchema.type) {
                    (function(lib) {
                        var html, wrapSchema;
                        if (oSchema.ops && oSchema.ops.length > 0) {
                            html = lib._htmlScoreItem(dataWrap);
                            $dom.children('ul').remove();
                            $dom.append(html);
                        }
                    })(this);
                } else if (/image/.test(oSchema.type)) {
                    (function(lib) {
                        var $button = $dom.find('li.img-picker button'),
                            sNgClick;

                        sNgClick = 'chooseImage(' + "'" + oSchema.id + "'," + oSchema.count + ')';
                        $button.attr('ng-click', sNgClick);
                        _htmlSupplement($dom, oSchema);
                        _htmlTag($dom, oSchema);
                    })(this);
                } else if (/file/.test(oSchema.type)) {
                    (function(lib) {
                        var $button = $dom.find('li.file-picker button'),
                            sNgClick;

                        sNgClick = 'chooseFile(' + "'" + oSchema.id + "'," + oSchema.count + ')';
                        $button.attr('ng-click', sNgClick).html(oSchema.title);
                        _htmlSupplement($dom, oSchema);
                        _htmlTag($dom, oSchema);
                    })(this);
                }
            }
        } else if (/radio|checkbox/.test(dataWrap.type)) {
            if (oSchema.ops && oSchema.ops.length === 1) {
                $label = $dom.find('label');
                $label.find('span').html(oSchema.ops[0].l);
            }
        } else if ('html' === dataWrap.type) {
            if (oBeforeSchema && oSchema.type !== oBeforeSchema.type) {
                $dom.html(this.embed(dataWrap));
            } else {
                $dom.html(oSchema.content);
            }
        }
    };
    InputWrap.prototype.dataByDom = function(domWrap, page) {
        var $wrap, oWrap, $label, schema = {};
        $wrap = $(domWrap);

        if (page) {
            schema.id = $wrap.attr('schema');
            oWrap = page.wrapBySchema(schema);
        }

        if (oWrap) return oWrap;

        /*返回模拟的*/
        if ($wrap.attr('wrap') === 'input') {
            schema.id = $wrap.attr('schema');
            schema.type = $wrap.attr('schema-type');
            $label = $wrap.children('label');
            schema.title = $label.html();
        } else if (/radio|checkbox/.test($wrap.attr('wrap'))) {
            var $label = $wrap.children('label'),
                $input = $label.children('input'),
                $span = $label.children('span'),
                op = {};

            schema.id = $input.attr('name');
            op.l = $span.html();
            if ($input.attr('type') === 'radio') {
                op.v = $input.val();
            } else {
                op.v = $input.attr('ng-model').split('.').pop();
            }
            schema.ops = [op];
        } else if ('score' === $wrap.attr('wrap')) {
            var $label = $wrap.find('label'),
                op = {};

            schema.id = $wrap.parent().parent().attr('schema');
            op.l = $label.html();
            op.v = $wrap.attr('opvalue');
            schema.ops = [op];
        } else {
            schema = false;
        }
        return {
            config: {},
            schema: schema
        };
    };
    /**
     * radio wrap class
     */
    var RadioWrap = function() {};
    RadioWrap.prototype = Object.create(Wrap.prototype);
    RadioWrap.prototype.dataByDom = function(domWrap, page) {
        var $wrap = $(domWrap),
            $label = $wrap.find('label'),
            $input = $label.children('input'),
            $span = $label.children('span'),
            schema = {},
            dataWrap, opVal, op;

        schema.id = $input.attr('name');
        dataWrap = page.wrapBySchema(schema);

        opVal = $input.val();
        for (var i = dataWrap.schema.ops.length - 1; i >= 0; i--) {
            if (dataWrap.schema.ops[i].v === opVal) {
                op = dataWrap.schema.ops[i];
                break;
            }
        }

        schema.ops = [op];

        return {
            config: {},
            schema: schema
        };
    };
    RadioWrap.prototype.modify = function(wrap, dataWrap) {
        var $wrap = $(wrap),
            $label = $wrap.find('label'),
            $span = $label.children('span');

        $span.html(dataWrap.schema.ops[0].l);
    };
    /**
     * checkbox wrap class
     */
    var CheckboxWrap = function() {};
    CheckboxWrap.prototype = Object.create(Wrap.prototype);
    CheckboxWrap.prototype.dataByDom = function(domWrap, page) {
        var $wrap = $(domWrap),
            $label = $wrap.find('label'),
            $input = $label.children('input'),
            $span = $label.children('span'),
            schema = {},
            dataWrap, opVal, op;

        schema.id = $input.attr('name');
        dataWrap = page.wrapBySchema(schema);

        opVal = $input.attr('ng-model').split('.').pop();
        for (var i = dataWrap.schema.ops.length - 1; i >= 0; i--) {
            if (dataWrap.schema.ops[i].v === opVal) {
                op = dataWrap.schema.ops[i];
                break;
            }
        }

        schema.ops = [op];

        return {
            config: {},
            schema: schema
        };
    };
    CheckboxWrap.prototype.modify = function(wrap, dataWrap) {
        var $wrap = $(wrap),
            $label = $wrap.find('label'),
            $span = $label.children('span');

        $span.html(dataWrap.schema.ops[0].l);
    };
    /**
     * value wrap class
     */
    var ValueWrap = function() {};
    ValueWrap.prototype = Object.create(Wrap.prototype);
    ValueWrap.prototype.newWrap = function(schema) {
        var oWrap = {
            config: {
                id: 'V' + (new Date() * 1),
                pattern: 'record',
                inline: 'N',
                splitLine: 'Y',
            },
            schema: schema
        };
        return oWrap;
    };
    ValueWrap.prototype.wrapAttrs = function(oWrap) {
        var config = oWrap.config,
            schema = oWrap.schema,
            wrapAttrs = {
                id: config.id,
                'ng-controller': 'ctrlRecord',
                wrap: 'value',
                schema: schema.id,
                'schema-type': schema.type
            },
            cls = 'form-group';

        config.inline === 'Y' && (cls += ' wrap-inline');
        config.splitLine === 'Y' && (cls += ' wrap-splitline');
        wrapAttrs.class = cls;

        return wrapAttrs;
    };
    ValueWrap.prototype.htmlValue = function(schema) {
        var html;
        switch (schema.type) {
            case 'shorttext':
            case 'longtext':
            case 'member':
            case 'location':
                html = '<div>{{Record.current.data.' + schema.id + '}}</div>';
                break;
            case 'single':
            case 'phase':
            case 'multiple':
                html = '<div ng-bind-html="' + "value2Label('" + schema.id + "')" + '"></div>';
                break;
            case 'score':
                html = '<div ng-bind-html="' + "score2Html('" + schema.id + "')" + '"></div>';
                break;
            case 'date':
                html = "<div>{{Record.current.data." + schema.id + "*1000|date:'yy-MM-dd HH:mm'}}</div>";
                break;
            case 'image':
                html = '<ul><li ng-repeat="img in Record.current.data.' + schema.id + '.split(\',\')"><img ng-src="{{img}}"></li></ul>';
                break;
            case 'file':
                html = '<ul><li ng-repeat="file in Record.current.data.' + schema.id + '"><span ng-bind="file.name"></span></li></ul>';
                break;
            case '_enrollAt':
                html = "<div>{{Record.current.enroll_at*1000|date:'yy-MM-dd HH:mm'}}</div>";
                break;
        }

        return html;
    };
    ValueWrap.prototype.embed = function(oWrap) {
        var wrapAttrs, label, html, config = oWrap.config,
            schema = oWrap.schema;

        wrapAttrs = this.wrapAttrs(oWrap);
        if (schema.type === 'html') {
            html = schema.content;
            return {
                tag: 'div',
                attrs: {
                    id: config.id,
                    wrap: 'value',
                    schema: schema.id,
                    'schema-type': schema.type,
                    'class': 'form-group'
                },
                html: html
            }
        } else {
            label = '<label>' + schema.title + '</label>'
            html = label + this.htmlValue(schema);
            return {
                tag: 'div',
                attrs: wrapAttrs,
                html: html
            }
        }
    };
    ValueWrap.prototype.modify = function(domWrap, oWrap, oBeforeSchema) {
        var config = oWrap.config,
            oSchema = oWrap.schema,
            $dom = $(domWrap),
            $tags, $supplement;

        if (oBeforeSchema && oSchema.type !== oBeforeSchema.type) {
            var embeded = this.embed(oWrap);
            $dom.attr(embeded.attrs);
            $dom.html(embeded.html);
        } else {
            if (oSchema.type === 'html') {
                $dom.html(oSchema.content);
            } else {
                $dom.find('label').html(oSchema.title);
            }
            config.inline === 'Y' ? $dom.addClass('wrap-inline') : $dom.removeClass('wrap-inline');
            config.splitLine === 'Y' ? $dom.addClass('wrap-splitline') : $dom.removeClass('wrap-splitline');
        }
        if (oSchema.description && oSchema.description.length) {
            if (!$dom.find('[class="description"]').length) {
                $('<div class="description">' + oSchema.description + '</div>').insertAfter($dom.find('label')[0])
            } else {
                $dom.find('[class="description"]').html(oSchema.description);
            }
        } else {
            $dom.find('[class="description"]').remove();
        }
        if (oSchema.cantag === 'Y') {
            $tags = $dom.find('.tags');
            if ($tags.length === 0) {
                $dom.append('<p class="tags"><span class="tag" ng-repeat="t in Record.current.tag.' + oSchema.id + '" ng-bind="t.label"></span></p>');
            }
        } else {
            $tags = $dom.find('.tags');
            if ($tags.length) {
                $tags.remove();
            }
        }
        if (oSchema.supplement === 'Y') {
            $supplement = $dom.find('.supplement');
            if ($supplement.length === 0) {
                $dom.append('<p class="supplement" ng-bind="Record.current.supplement.' + oSchema.id + '"></p>');
            }
        } else {
            $supplement = $dom.find('.supplement');
            if ($supplement.length) {
                $supplement.remove();
            }
        }
    };
    ValueWrap.prototype.dataByDom = function(domWrap, oPage) {
        var $wrap = $(domWrap),
            wrapId = $wrap.attr('id');

        if (oPage) {
            if (wrapId) {
                return oPage.wrapById(wrapId);
            } else {
                var schemaId, data;
                schemaId = $wrap.attr('schema');
                if (schemaId) {
                    data = oPage.wrapBySchema({ id: schemaId });
                    if (data.config === undefined) {
                        data.config = {
                            inline: $wrap.hasClass('wrap-inline') ? 'Y' : 'N',
                            splitLine: $wrap.hasClass('wrap-splitline') ? 'Y' : 'N'
                        };
                    }
                    return data;
                }
            }
        } else {
            return {
                config: {
                    id: wrapId,
                    pattern: 'record',
                    inline: $wrap.hasClass('wrap-inline') ? 'Y' : 'N',
                    splitLine: $wrap.hasClass('wrap-splitline') ? 'Y' : 'N'
                },
                schema: {
                    id: $wrap.attr('schema'),
                    type: $wrap.attr('schema-type'),
                    title: $wrap.children('label').html()
                }
            };
        }
    };
    /**
     * records wrap class
     */
    var RecordsWrap = function() {};
    RecordsWrap.prototype = Object.create(Wrap.prototype);
    RecordsWrap.prototype._htmlValue = function(oSchema) {
        var html;
        html = '<div wrap="value" class="wrap-inline wrap-splitline" schema="' + oSchema.id + '" schema-type="' + oSchema.type + '"><label>' + oSchema.title + '</label>';
        switch (oSchema.type) {
            case 'address':
                html += '<div>{{r.mschema.' + oSchema.id + '}}</div>';
                break;
            case 'sns':
                html += '<div>{{r.sns.' + oSchema.id + '}}</div>';
                break;
            case 'headimgurl':
                html += '<div><img ng-src="{{r.sns.oSchema.id}}"/></div>';
                break;
            case 'shorttext':
            case 'longtext':
            case 'location':
            case 'member':
            case 'sns':
                html += '<div>{{r.data.' + oSchema.id + '}}</div>';
                break;
            case 'date':
                html += '<div><span ng-if="r.data.' + oSchema.id + '">{{r.data.' + oSchema.id + '*1000|date:"yy-MM-dd HH:mm"}}</span></div>';
                break;
            case 'single':
            case 'phase':
            case 'multiple':
                html += '<div ng-bind-html="' + "value2Label(r,'" + oSchema.id + "')" + '"></div>'
                break;
            case 'score':
                html += '<div ng-bind-html="' + "score2Html(r,'" + oSchema.id + "')" + '"></div>';
                break;
            case 'image':
                html += '<ul><li ng-repeat="img in r.data.' + oSchema.id + '.split(\',\')"><img ng-src="{{img}}"></li></ul>';
                break;
            case '_enrollAt':
                html += "<div>{{r.enroll_at*1000|date:'yy-MM-dd HH:mm'}}</div>";
                break;
        }
        if (oSchema.supplement && oSchema.supplement === 'Y') {
            html += '<p class="supplement" ng-bind="r.supplement.' + oSchema.id + '"></p>';
        }
        html += '</div>';

        return html;
    };
    RecordsWrap.prototype._htmlRecords = function(dataWrap) {
        var _this = this,
            config = dataWrap.config,
            schemas = dataWrap.schemas,
            html, onclick;

        html = '<ul class="list-group">';
        onclick = config.onclick.length ? " ng-click=\"gotoPage($event,'" + config.onclick + "',r.enroll_key)\"" : '';
        html += '<li class="list-group-item text-center actions"><div class="btn-group"><button class="btn btn-default" ng-click="openFilter()">筛选</button><button class="btn btn-default" ng-click="resetFilter()"><span class="glyphicon glyphicon-remove"></span></button></div></li>';
        html += '<li class="list-group-item" ng-repeat="r in records"' + onclick + '>';
        schemas.forEach(function(oSchema) {
            html += _this._htmlValue(oSchema);
        });
        html += "</li>";
        html += '<li class="list-group-item text-center actions"><div class="btn-group"><button class="btn btn-default" ng-click="openFilter()">筛选</button><button class="btn btn-default" ng-click="resetFilter()"><span class="glyphicon glyphicon-remove"></span></button></div><button class="btn btn-default" ng-click="fetch()" ng-if="options.page.total>records.length">更多</button></li>';
        html += "</ul>";

        return html;
    };
    RecordsWrap.prototype.embed = function(dataWrap) {
        if (!dataWrap.schemas && dataWrap.schemas.length === 0) return false;
        var html, attrs, mschemaId;
        html = this._htmlRecords(dataWrap);
        mschemaId = Object.keys(dataWrap.config).indexOf('mschemaId') == -1 ? '' : dataWrap.config.mschemaId;
        attrs = {
            id: dataWrap.config.id,
            'ng-controller': 'ctrlRecords',
            'enroll-records': 'Y',
            'enroll-records-owner': dataWrap.config.dataScope,
            'enroll-records-type': dataWrap.config.type=='records'?'records':'enrollees',
            'enroll-records-mschema': mschemaId,
            wrap: dataWrap.config.type=='records'?'records':'enrollees',
            class: 'form-group'
        };
        return {
            tag: 'div',
            attrs: attrs,
            html: html
        };
    };
    RecordsWrap.prototype.modify = function(domWrap, oWrap) {
        var html, mschemaId, attrs = {},
            $wrap = $(domWrap),
            config = oWrap.config;

        attrs['enroll-records-owner'] = config.dataScope;
        if(Object.keys(oWrap.config).indexOf('mschemaId') !== -1) {
            attrs['enroll-records-mschema'] = config.mschemaId;
        }
        $wrap.attr(attrs);
        $wrap.children('ul').remove();
        html = this._htmlRecords(oWrap);
        $wrap.append(html);

        return true;
    };
    RecordsWrap.prototype.dataByDom = function(domWrap, page) {
        var $wrap = $(domWrap),
            config = {};

        config.id = $wrap.attr('id');
        if (page) {
            return page.wrapByList(config);
        } else {
            config.pattern = 'records';
            config.dataScope = $wrap.attr('enroll-records-owner');
            return {
                config: config
            };
        }
    };
    /**
     * button wrap class
     */
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
        save: {
            act: function(schema) {
                return 'save' + PrefabActSchema._args(schema);
            }
        },
        remarkRecord: {
            act: function(schema) {
                return 'remarkRecord' + PrefabActSchema._args(schema);
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
    var ButtonWrap = function() {};
    ButtonWrap.prototype = Object.create(Wrap.prototype);
    ButtonWrap.prototype.embed = function(schema) {
        var attrs = {
                id: schema.id,
                wrap: 'button',
                class: 'form-group',
                contenteditable: 'false'
            },
            tmplBtn = function(action, label) {
                return '<button class="btn btn-primary btn-block btn-lg" ng-click="' + action + '"><span contenteditable="true">' + label + '</span></button>';
            },
            prefab, action;
        if (prefab = PrefabActSchema[schema.name]) {
            action = prefab.act;
            angular.isFunction(action) && (action = action(schema));
            if (schema.name === 'acceptInvite') {
                attrs['ng-controller'] = 'ctrlInvite';
            } else if (['editRecord', 'removeRecord', 'remarkRecord'].indexOf(schema.name) !== -1) {
                attrs['ng-controller'] = 'ctrlRecord';
            }
            return {
                tag: 'div',
                attrs: attrs,
                html: tmplBtn(action, schema.label)
            };
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

            return {
                tag: 'div',
                attrs: attrs,
                html: html
            };
        }
    };
    ButtonWrap.prototype.modify = function(dowWrap, oWrap) {
        var prefab, action, $button, schema = oWrap.schema;
        if (prefab = PrefabActSchema[schema.name]) {
            action = prefab.act;
            angular.isFunction(action) && (action = action(schema));
            $button = $(dowWrap).find('button');
            $button.children('span').html(schema.label);
            $button.attr('ng-click', action);
        }
        return true;
    };
    ButtonWrap.prototype.dataByDom = function(domWrap, page) {
        var $button, action, arg, schema = {};

        schema.id = $(domWrap).attr('id');
        if (page) {
            return {
                schema: page.wrapByButton(schema)
            };
        } else {
            $button = $(domWrap).find('button');
            schema.label = $button.children('span').html();
            action = $button.attr('ng-click');
            action = action.match(/(.+?)\((.+?)\)/);
            schema.name = action[1];
            arg = action[2].split(',');
            arg.length === 2 && (schema.next = arg[1].replace(/'/g, ''));

            return {
                schema: schema
            };
        }
    };
    /**
     *
     */
    return {
        input: new InputWrap(),
        radio: new RadioWrap(),
        checkbox: new CheckboxWrap(),
        value: new ValueWrap(),
        records: new RecordsWrap(),
        button: new ButtonWrap(),
        setEditor: function(editor) {
            _editor = editor;
        },
        setPage: function(oPage) {
            _page = oPage;
        },
        dataByDom: function(domWrap, oPage) {
            var wrapType = $(domWrap).attr('wrap'),
                dataWrap;
            if(wrapType=='enrollees'){wrapType = 'records'};
            if (!this[wrapType]) {
                return false;
            }
            dataWrap = this[wrapType].dataByDom(domWrap, oPage);

            return dataWrap;
        }
    };
});
