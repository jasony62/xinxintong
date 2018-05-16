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
            config: {},
            schema: schema,
        };
        if (/single|multiple/.test(schema.type)) {
            oWrap.config.align = 'V';
            if (/single/.test(schema.type)) {
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
        html += '>' + op.l + '</span></label>';
        if (op.desc && op.desc.length) html += '<div class="desc">' + op.desc + '</div>';
        html += '</li>';

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
        html += '>' + op.l + '</span></label>';
        if (op.desc && op.desc.length) html += '<div class="desc">' + op.desc + '</div>';
        html += '</li>';

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
        html += '</div>';
        if (op.desc && op.desc.length) html += '<div class="desc">' + op.desc + '</div>';
        html += '</li>';

        return html;
    }

    function _htmlSupplement($dom, schema) {
        var $supplement, html;
        if (schema.supplement === 'Y') {
            $supplement = $dom.find('.supplement');
            if ($supplement.length === 0) {
                html = '<div class="list-group-item supplement text-muted">';
                html += '<div class="top-bar tms-flex-row">';
                html += '<div class="tms-flex-grow" ng-if="!supplement.' + schema.id + '">请填写补充说明</div>';
                html += '<div class="tms-flex-grow" ng-if="supplement.' + schema.id + '" dynamic-html="supplement.' + schema.id + '"></div>';
                html += '<div class="btn-group" uib-dropdown>';
                html += '<button class="btn btn-default btn-xs dropdown-toggle" uib-dropdown-toggle><span class="glyphicon glyphicon-option-vertical"></span></button>';
                html += '<ul class="dropdown-menu dropdown-menu-right" uib-dropdown-menu>';
                html += '<li><a href ng-click="editSupplement(\'' + schema.id + '\')"><span class="glyphicon glyphicon-edit"></span> 编辑</a></li>';
                html += '</ul>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                $dom.append(html);
            }
        } else {
            $supplement = $dom.find('.supplement');
            if ($supplement.length) {
                $supplement.remove();
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
        var oSchema = oWrap.schema,
            oConfig = oWrap.config,
            inpAttrs = {
                wrap: 'input',
                class: 'form-group form-group-lg',
                schema: oSchema.id,
                'schema-type': oSchema.type,
            },
            html = '<label' + (oConfig.hidename ? ' class="hide"' : '') + (forEdit ? ' contenteditable="true">' : '>') + oSchema.title + '</label>';

        forEdit && (inpAttrs.contenteditable = 'false');

        switch (oSchema.type) {
            case 'multitext':
                html += '<ul class="list-group multitext">';
                html += '<li class="list-group-item" ng-repeat="item in data.' + oSchema.id + ' track by $index">';
                html += '<div wrap="multitext-history" class="top-bar tms-flex-row"><div class="tms-flex-grow" dynamic-html="item.value"';
                oSchema.placeholder && (html += ' placeholder="' + oSchema.title + '"');
                oSchema.required === 'Y' && (html += 'required=""');
                forEdit && (html += ' readonly');
                html += '></div>';
                html += '<div class="btn-group" uib-dropdown>';
                html += '<button class="btn btn-default btn-xs dropdown-toggle" uib-dropdown-toggle><span class="glyphicon glyphicon-option-vertical"></span></button>';
                html += '<ul class="dropdown-menu dropdown-menu-right" uib-dropdown-menu>';
                html += '<li><a href ng-click="removeItem(data.' + oSchema.id + ', $index)"><span class="glyphicon glyphicon-trash"></span> 删除</a></li>';
                html += '<li><a href ng-click="editItem(data.' + oSchema.id + ', $index)"><span class="glyphicon glyphicon-edit"></span> 编辑</a></li>';
                oSchema.history === 'Y' && (html += '<li><a href ng-click="' + 'dataBySchema(\'' + oSchema.id + '\', $index)' + '"><span class="glyphicon glyphicon-search"></span> 查找</a></li>');
                html += '</ul>';
                html += '</div>';
                html += '</div>';
                html += '</li>';
                html += '<li class="list-group-item"><button class="btn btn-success"  ng-click="addItem(\'' + oSchema.id + '\')">添加</button></li>';
                html += '</ul>';
                break;
            case 'shorttext':
                if (oSchema.history === 'Y') {
                    html += '<div wrap="shorttext-history" class="input-group input-group-lg">';
                    html += '<input type="text" ng-model="data.' + oSchema.id + '"';
                    html += ' title="' + oSchema.title + '"';
                    oSchema.placeholder && (html += ' placeholder="' + oSchema.title + '"');
                    oSchema.required === 'Y' && (html += 'required=""');
                    html += ' class="form-control">';
                    html += '<span class="input-group-btn">';
                    html += '<button class="btn btn-default" ng-click="' + 'dataBySchema(\'' + oSchema.id + '\')' + '">查找</button>';
                    html += '</span>';
                    html += '</div>';
                } else {
                    html += '<input type="text" ng-model="data.' + oSchema.id + '" title="' + oSchema.title + '"';
                    oSchema.placeholder && (html += ' placeholder="' + oSchema.title + '"');
                    oSchema.required === 'Y' && (html += 'required=""');
                    oSchema.type === 'member' && (html += 'ng-init="data.member.schema_id=' + oSchema.schema_id + '"');
                    html += ' class="form-control input-lg"';
                    forEdit && (html += ' readonly');
                    html += '>';
                }
                break;
            case 'date':
                inpAttrs['tms-date'] = 'Y';
                inpAttrs['tms-date-value'] = 'data.' + oSchema.id;
                html += '<div wrap="date" ng-bind="data.' + oSchema.id + '*1000|date:\'yy-MM-dd HH:mm\'"';
                html += ' title="' + oSchema.title + '"';
                oSchema.required === 'Y' && (html += 'required=""');
                html += ' class="form-control input-lg"></div>';
                break;
            case 'url':
                html += '<div wrap="url">';
                html += '<div ng-bind-html="data.' + oSchema.id + '._text"';
                html += ' title="' + oSchema.title + '"';
                html += ' class="form-control"></div>';
                html += '<div class="text-right">';
                html += '<button class="btn btn-default" ng-click="pasteUrl(\'' + oSchema.id + '\')">指定链接</button>';
                html += '</div>';
                html += '</div>';
                break;
            case 'location':
                html += '<div wrap="location" class="input-group input-group-lg">';
                html += '<input type="text" ng-model="data.' + oSchema.id + '"';
                html += ' title="' + oSchema.title + '"';
                html += ' placeholder="' + oSchema.title + '"';
                oSchema.required === 'Y' && (html += 'required=""');
                html += ' class="form-control">';
                html += '<div class="input-group-btn">';
                html += '<button class="btn btn-default" ng-click="' + 'getMyLocation(\'' + oSchema.id + '\')' + '">定位</button>';
                html += '</div>';
                html += '</div>';
                break;
            case 'longtext':
                html += '<textarea style="height:auto" ng-model="data.' + oSchema.id + '" title="' + oSchema.title + '"';
                oSchema.placeholder && (html += ' placeholder="' + oSchema.title + '"');
                oSchema.required === 'Y' && (html += 'required=""');
                html += ' class="form-control" rows="3"';
                forEdit && (html += ' readonly');
                html += '></textarea>';
                break;
            case 'single':
                if (oSchema.ops && oSchema.ops.length > 0) {
                    if (oSchema.component === 'S') {
                        html += this._htmlSingleSelect(oWrap, forEdit);
                    } else {
                        html += this._htmlSingleRadio(oWrap, forEdit);
                    }
                }
                break;
            case 'multiple':
                if (oSchema.ops && oSchema.ops.length > 0) {
                    html += this._htmlMultiple(oWrap, forEdit);
                }
                break;
            case 'image':
                inpAttrs['tms-image-input'] = 'Y';
                html += '<ul class="img-tiles clearfix" name="' + oSchema.id + '">';
                html += '<li wrap="img" ng-repeat="img in data.' + oSchema.id + '" class="img-thumbnail">';
                html += '<img flex-img>';
                html += '<button class="btn btn-default btn-xs" ng-click="removeImage(data.' + oSchema.id + ',$index)"><span class="glyphicon glyphicon-remove"></span></button>';
                html += '</li>';
                html += '<li class="img-picker">';
                html += '<button class="btn btn-default" ng-click="chooseImage(\'' + oSchema.id + '\',' + (oSchema.count || 1) + ')"><span class="glyphicon glyphicon-picture"></span><br>上传图片</button>';
                html += '</li>';
                html += '<li class="img-picker img-edit" ng-hide="isSmallLayout">';
                html += '<button class="btn btn-default" ng-click="pasteImage(\'' + oSchema.id + '\',$event,' + (oSchema.count || 1) + ')"><span class="glyphicon glyphicon-picture"></span><br>粘贴截图</button>';
                html += '</li>';
                html += '</ul>';
                break;
            case 'file':
                inpAttrs['tms-file-input'] = 'Y';
                html += '<ul class="list-group file" name="' + oSchema.id + '">';
                html += '<li class="list-group-item" ng-show="progressOfUploadFile"><div class="progressOfUploadFile" ng-bind="progressOfUploadFile"></li>';
                html += '<li ng-repeat="file in data.' + oSchema.id + '" class="list-group-item">';
                html += '<div wrap="file" class="top-bar tms-flex-row">';
                html += '<div class="tms-flex-grow" ng-bind="file.name"></div>';
                html += '<div class="btn-group" uib-dropdown><button class="btn btn-default btn-xs dropdown-toggle" uib-dropdown-toggle><span class="glyphicon glyphicon-option-vertical"></span></button>';
                html += '<ul class="dropdown-menu dropdown-menu-right" uib-dropdown-menu>';
                html += '<li><a href ng-click="clickFile(\'' + oSchema.id + '\',$index)"><span class="glyphicon glyphicon-trash"></span> 删除</a></li>';
                html += '</ul></div>';
                html += '</div>';
                html += '<li>';
                html += '<li class="list-group-item file-picker">';
                html += '<button class="btn btn-success" ng-click="chooseFile(\'' + oSchema.id + '\',' + (oSchema.count || 1) + ')">上传文件</button>';
                html += '</li>';
                html += '</ul>';
                break;
            case 'voice':
                inpAttrs['tms-voice-input'] = 'Y';
                html += '<ul class="list-group voice" name="' + oSchema.id + '">';
                html += '<li ng-repeat="voice in data.' + oSchema.id + '" class="list-group-item">';
                html += '<div wrap="voice" class="top-bar tms-flex-row">';
                html += '<div class="tms-flex-grow voice-name" ng-bind="voice.name"></div>';
                html += '<div class="btn-group" uib-dropdown><button class="btn btn-default btn-xs dropdown-toggle" uib-dropdown-toggle><span class="glyphicon glyphicon-option-vertical"></span></button>';
                html += '<ul class="dropdown-menu dropdown-menu-right" uib-dropdown-menu>';
                html += '<li><a href ng-click="clickFile(\'' + oSchema.id + '\',$index)"><span class="glyphicon glyphicon-trash"></span> 删除</a></li>';
                html += '</ul></div>';
                html += '</div>';
                html += '<li>';
                html += '<li class="list-group-item voice-picker">';
                html += '<button class="btn btn-success" ng-click="startVoice(\'' + oSchema.id + '\')">开始录音</button>';
                html += '</li>';
                html += '</ul>';
                break;
            case 'score':
                if (oSchema.ops && oSchema.ops.length > 0) {
                    html += this._htmlScoreItem(oWrap, forEdit);
                }
                break;
            case 'html':
                if (/audio|vedio/.test(oSchema.mediaType)) {
                    html += oSchema.content;
                } else {
                    html = oSchema.content;
                }
                return {
                    tag: 'div',
                    attrs: {
                        wrap: 'html',
                        class: 'form-group',
                        schema: oSchema.id,
                        'schema-type': 'html',
                    },
                    html: html
                };
        }

        return {
            tag: 'div',
            attrs: inpAttrs,
            html: html
        };
    };
    InputWrap.prototype.modify = function(domWrap, dataWrap, oBeforeSchema) {
        var $dom, $label, $input, oConfig = dataWrap.config,
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
                if (/shorttext|longtext|member|date|location|url/.test(oSchema.type)) {
                    $input = $dom.find('input,textarea');
                    if (oConfig.placeholder) {
                        $input.attr('placeholder', oSchema.title);
                    } else {
                        $input.removeAttr('placeholder');
                    }
                    if (oSchema.required === 'Y') {
                        $input.attr('required', '');
                    } else {
                        $input.removeAttr('required');
                    }
                } else if (/multitext/.test(oSchema.type)) {
                    (function(lib) {
                        var multitextEmbed;
                        multitextEmbed = lib.embed(dataWrap);
                        $dom.attr(multitextEmbed.attrs);
                        $dom.html(multitextEmbed.html);
                    })(this);
                } else if (/single/.test(oSchema.type)) {
                    (function(lib) {
                        var html;
                        if (oSchema.ops) {
                            if (oConfig.component === 'R') {
                                if ($dom.children('ul').length) {
                                    html = lib._htmlSingleRadio(dataWrap, false, true);
                                    $dom.children('ul').html(html);
                                } else if ($dom.children('select').length) {
                                    html = lib._htmlSingleRadio(dataWrap, false, false);
                                    $dom.children('select').replaceWith(html);
                                }
                            } else if (oConfig.component === 'S') {
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
                            sNgClick, imageEmbed;

                        sNgClick = 'chooseImage(' + "'" + oSchema.id + "'," + (oSchema.count || 1) + ')';
                        $button.attr('ng-click', sNgClick);

                        imageEmbed = lib.embed(dataWrap);
                        $dom.attr(imageEmbed.attrs);
                        $dom.html(imageEmbed.html);

                        _htmlSupplement($dom, oSchema);
                    })(this);
                } else if (/file/.test(oSchema.type)) {
                    (function(lib) {
                        var $button = $dom.find('li.file-picker button'),
                            sNgClick, fileEmbed;

                        sNgClick = 'chooseFile(' + "'" + oSchema.id + "'," + (oSchema.count || 1) + ')';
                        $button.attr('ng-click', sNgClick).html(oSchema.title);

                        fileEmbed = lib.embed(dataWrap);
                        $dom.attr(fileEmbed.attrs);
                        $dom.html(fileEmbed.html);

                        _htmlSupplement($dom, oSchema);
                    })(this);
                } else if (/voice/.test(oSchema.type)) {
                    (function(lib) {
                        var $button = $dom.find('li.voice-picker button'),
                            sNgClick, voiceEmbed;

                        sNgClick = 'startVoice(' + "'" + oSchema.id + "')";
                        $button.attr('ng-click', sNgClick).html(oSchema.title);

                        voiceEmbed = lib.embed(dataWrap);
                        $dom.attr(voiceEmbed.attrs);
                        $dom.html(voiceEmbed.html);

                        _htmlSupplement($dom, oSchema);
                    })(this);
                }
                $label.toggleClass('hide', !!oConfig.hidename);
                if (oSchema.description && oSchema.description.length) {
                    if (!$dom.find('[class="description"]').length) {
                        $('<div class="description">' + oSchema.description + '</div>').insertAfter($dom.find('label')[0])
                    } else {
                        $dom.find('[class="description"]').html(oSchema.description);
                    }
                } else {
                    $dom.find('[class="description"]').remove();
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
                var html;
                if (/audio|vedio/.test(oSchema.mediaType)) {
                    html = '<label' + (oConfig.hidename ? ' class="hide"' : '') + '>' + oSchema.title + '</label>';
                    html += oSchema.content;
                } else {
                    html = oSchema.content;
                }
                $dom.html(html);
            }
        }
    };
    InputWrap.prototype.dataByDom = function(domWrap, oPage) {
        var $wrap, oWrap, $label, oSchema = {};
        $wrap = $(domWrap);
        if (oPage) {
            oSchema.id = $wrap.attr('schema');
            oWrap = oPage.wrapBySchema(oSchema);
        }

        if (oWrap) return oWrap;
        /*返回模拟的*/
        if ($wrap.attr('wrap') === 'input') {
            oSchema.id = $wrap.attr('schema');
            oSchema.type = $wrap.attr('schema-type');
            $label = $wrap.children('label');
            oSchema.title = $label.html();
        } else if (/radio|checkbox/.test($wrap.attr('wrap'))) {
            var $label = $wrap.children('label'),
                $input = $label.children('input'),
                $span = $label.children('span'),
                op = {};

            oSchema.id = $input.attr('name');
            op.l = $span.html();
            if ($input.attr('type') === 'radio') {
                op.v = $input.val();
            } else {
                op.v = $input.attr('ng-model').split('.').pop();
            }
            oSchema.ops = [op];
        } else if ('score' === $wrap.attr('wrap')) {
            var $label = $wrap.find('label'),
                op = {};

            oSchema.id = $wrap.parent().parent().attr('schema');
            op.l = $label.html();
            op.v = $wrap.attr('opvalue');
            oSchema.ops = [op];
        } else if ('html' === $wrap.attr('wrap')) {
            var $label = $wrap.find('label');
            oSchema.id = $wrap.parent().parent().attr('schema');
            oSchema.type = 'html';
            oSchema.title = $label.html();
        } else {
            oSchema = false;
        }
        return {
            config: {},
            schema: oSchema
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
    var HtmlWrap = function() {};
    HtmlWrap.prototype = Object.create(Wrap.prototype);
    HtmlWrap.prototype.dataByDom = function(domWrap, oPage) {
        var $wrap = $(domWrap),
            $label = $wrap.find('label'),
            oSchema = {},
            dataWrap;

        oSchema.id = $wrap.attr('schema');
        dataWrap = oPage.wrapBySchema(oSchema);

        return angular.copy(dataWrap);
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
            case 'member':
            case 'location':
                html = '<div>{{Record.current.data.' + schema.id + '}}</div>';
                break;
            case 'longtext':
                html = '<div ng-bind-html="Record.current.data.' + schema.id + '"></div>';
                break;
            case 'url':
                html = '<div ng-bind-html="Record.current.data.' + schema.id + '._text"></div>';
                break;
            case 'single':
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
            case 'voice':
                html = '<ul><li ng-repeat="voice in Record.current.data.' + schema.id + '"><span ng-bind="voice.name"></span></li></ul>';
                break;
            case 'multitext':
                html = '<ul><li ng-repeat="item in Record.current.data.' + schema.id + '"><span ng-bind-html="item.value"></span></li></ul>';
                break;
            case '_enrollAt':
                html = "<div>{{Record.current.enroll_at*1000|date:'yy-MM-dd HH:mm'}}</div>";
                break;
            case '_roundTitle':
                html = "<div>{{Record.current.round.title}}</div>";
                break;
        }

        return html;
    };
    ValueWrap.prototype.embed = function(oWrap) {
        var wrapAttrs, label, html, config = oWrap.config,
            oSchema = oWrap.schema;

        wrapAttrs = this.wrapAttrs(oWrap);
        if (oSchema.type === 'html') {
            html = oSchema.content;
            if (oSchema.mediaType) {
                html = '<label>' + oSchema.title + '</label>' + html;
            }
            return {
                tag: 'div',
                attrs: {
                    id: config.id,
                    wrap: 'value',
                    schema: oSchema.id,
                    'schema-type': oSchema.type,
                    'class': 'form-group'
                },
                html: html
            }
        } else {
            label = '<label>' + oSchema.title + '</label>'
            html = label + this.htmlValue(oSchema);
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
            if (oSchema.type !== 'html' || oSchema.mediaType) {
                $dom.find('label').html(oSchema.title);
                if (oSchema.type === 'longtext') {
                    var embeded = this.embed(oWrap);
                    $dom.html(embeded.html);
                }
            } else {
                $dom.html(oSchema.content);
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
        /* 为了清除老版本的数据 */
        $tags = $dom.find('.tags');
        $tags.length && $tags.remove();
        if (oSchema.supplement === 'Y') {
            $supplement = $dom.find('.supplement');
            if ($supplement.length === 0) {
                $dom.append('<p class="supplement" ng-bind-html="Record.current.supplement.' + oSchema.id + '"></p>');
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
                    if (data && data.config === undefined) {
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
            case 'enrollee':
                html += '<div>{{r.' + oSchema.id + '}}</div>';
                break;
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
            case 'location':
            case 'member':
            case 'sns':
                html += '<div>{{r.data.' + oSchema.id + '}}</div>';
                break;
            case 'longtext':
                html += '<div ng-bind-html="r.data.' + oSchema.id + '"></div>';
                break;
            case 'url':
                html += '<div ng-bind-html="r.data.' + oSchema.id + '.title"></div>';
                break;
            case 'date':
                html += '<div><span ng-if="r.data.' + oSchema.id + '">{{r.data.' + oSchema.id + '*1000|date:"yy-MM-dd HH:mm"}}</span></div>';
                break;
            case 'single':
            case 'multiple':
                html += '<div ng-bind-html="' + "value2Label(r,'" + oSchema.id + "')" + '"></div>';
                break;
            case 'score':
                html += '<div ng-bind-html="' + "score2Html(r,'" + oSchema.id + "')" + '"></div>';
                break;
            case 'image':
                html += '<ul><li ng-repeat="img in r.data.' + oSchema.id + '.split(\',\')"><img ng-src="{{img}}"></li></ul>';
                break;
            case 'file':
                html += '<ul><li ng-repeat="file in r.data.' + oSchema.id + '"><span ng-bind="file.name"></span></li></ul>';
                break;
            case 'voice':
                html += '<ul><li ng-repeat="voice in r.data.' + oSchema.id + '"><span ng-bind="voice.name"></span></li></ul>';
                break;
            case 'multitext':
                html += '<ul><li ng-repeat="item in r.data.' + oSchema.id + '"><span ng-bind-html="item.value"></span></li></ul>';
                break;
            case '_enrollAt':
                html += "<div>{{r.enroll_at*1000|date:'yy-MM-dd HH:mm'}}</div>";
                break;
            case '_roundTitle':
                html += "<div>{{r.round.title}}</div>";
                break;
        }
        if (oSchema.supplement && oSchema.supplement === 'Y') {
            html += '<p class="supplement" ng-bind-html="r.supplement.' + oSchema.id + '"></p>';
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
            'enroll-records-type': dataWrap.config.type == 'records' ? 'records' : 'enrollees',
            'enroll-records-mschema': mschemaId,
            wrap: dataWrap.config.type == 'records' ? 'records' : 'enrollees',
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
        if (Object.keys(oWrap.config).indexOf('mschemaId') !== -1) {
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
            if (['editRecord', 'removeRecord', 'remarkRecord'].indexOf(schema.name) !== -1) {
                attrs['ng-controller'] = 'ctrlRecord';
            }
            return {
                tag: 'div',
                attrs: attrs,
                html: tmplBtn(action, schema.label)
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
        html: new HtmlWrap(),
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
            if (wrapType == 'enrollees') { wrapType = 'records' };
            if (!this[wrapType]) {
                return false;
            }
            dataWrap = this[wrapType].dataByDom(domWrap, oPage);

            return dataWrap;
        }
    };
});