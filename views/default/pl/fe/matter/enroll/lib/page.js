/**
 * base class of page
 */
define(['wrap'], function(SchemaWrap) {
    'use strict';
    /**
     * 页面处理逻辑基类
     */
    var protoPage = {
        _parseSchemas: function() {
            var dataSchemas = this.data_schemas,
                actSchemas = this.act_schemas,
                userSchemas = this.user_schemas;

            try {
                this.data_schemas = dataSchemas && dataSchemas.length ? JSON.parse(dataSchemas) : [];
            } catch (e) {
                alert('应用程序错误！');
                console.error(e);
                return;
            }
            try {
                this.act_schemas = actSchemas && actSchemas.length ? JSON.parse(actSchemas) : [];
            } catch (e) {
                alert('应用程序错误！');
                console.error(e);
                return;
            }
        },
        wrapBySchema: function(schema) {
            var dataWrap, i;
            for (i = this.data_schemas.length - 1; i >= 0; i--) {
                dataWrap = this.data_schemas[i];
                if (schema.id === dataWrap.schema.id) {
                    return dataWrap;
                }
            }

            return false;
        },
        removeButton: function(schema) {
            for (var i = this.act_schemas.length - 1; i >= 0; i--) {
                if (this.act_schemas[i].id === schema.id) {
                    return this.act_schemas.splice(i, 1);
                }
            }
            return false;
        },
        /**
         * 调整题目在页面中的位置
         */
        moveSchema: function(oMovedSchema, oPrevSchema) {
            var movedWrap, prevWrap, $html, $movedHtml, $prevHtml;

            if (/I|V/.test(this.type)) {
                movedWrap = this.wrapBySchema(oMovedSchema);
                this.data_schemas.splice(this.data_schemas.indexOf(movedWrap), 1);
                $html = $('<div>' + this.html + '</div>');
                $movedHtml = $html.find('[schema="' + oMovedSchema.id + '"]');
                if (oPrevSchema) {
                    prevWrap = this.wrapBySchema(oPrevSchema);
                    this.data_schemas.splice(this.data_schemas.indexOf(prevWrap), 0, movedWrap);
                    $prevHtml = $html.find("[schema='" + oPrevSchema.id + "']");
                    $prevHtml.after($movedHtml);
                } else {
                    this.data_schemas.splice(0, 0, movedWrap);
                    $($html.find('[schema]').get(0)).before($movedHtml);
                }
                this.html = $html.html();
            }
        },
        /**
         * 根据按钮项获得按钮项的包裹对象
         */
        wrapByButton: function(schema) {
            for (var i = this.act_schemas.length - 1; i >= 0; i--) {
                if (this.act_schemas[i].id === schema.id) {
                    return this.act_schemas[i];
                }
            }

            return false;
        },
    };
    /**
     * 输入页处理逻辑基类
     */
    var protoInputPage = {
        /**
         * 整理题目，使得页面中的schema和应用中的schema是同一个对象
         */
        _arrange: function(mapOfAppSchemas) {
            var _this = this;

            if (this.data_schemas.length) {
                var dataSchemas = [];
                this.data_schemas.forEach(function(item) {
                    var matched = false;
                    if (item.schema && item.schema.id) {
                        if (mapOfAppSchemas[item.schema.id]) {
                            item.schema = mapOfAppSchemas[item.schema.id];
                            dataSchemas.push(item);
                            matched = true;
                        }
                    }
                    if (!matched) console.error("page[" + _this.name + "]'schema is invalid:", item);
                });
                this.data_schemas = dataSchemas;
            }
        },
        /**
         * 添加题目的html
         */
        _appendWrap: function(tag, attrs, html, afterWrap) {
            var newDomWrap, $html, $lastInputWrap, $btnWrap;

            $html = $('<div>' + this.html + '</div>');
            newDomWrap = $(document.createElement(tag)).attr(attrs).html(html);
            if (afterWrap) {
                $lastInputWrap = $html.find("[schema='" + afterWrap.schema.id + "']");
            } else {
                $lastInputWrap = $html.find("[wrap='input']:last");
            }

            if ($lastInputWrap.length) {
                // 加到最后一个题目后面
                $lastInputWrap.after(newDomWrap);
            } else {
                // 加在按钮的前面
                $btnWrap = $html.find("[wrap='button']:first");
                if ($btnWrap.length) {
                    $btnWrap.before(newDomWrap);
                } else {
                    // 加在文档的最后
                    $html.append(newDomWrap);
                }
            }

            this.html = $html.html();

            return newDomWrap;
        },
        appendSchema: function(schema, afterSchema) {
            var newWrap, wrapParam, domNewWrap, afterWrap;

            newWrap = SchemaWrap.input.newWrap(schema);

            if (afterSchema) {
                afterWrap = this.wrapBySchema(afterSchema);
                var afterIndex = this.data_schemas.indexOf(afterWrap);
                if (afterIndex === -1) {
                    this.data_schemas.push(newWrap);
                } else {
                    this.data_schemas.splice(afterIndex + 1, 0, newWrap);
                }
            } else {
                this.data_schemas.push(newWrap);
            }

            wrapParam = SchemaWrap.input.embed(newWrap);

            domNewWrap = this._appendWrap(wrapParam.tag, wrapParam.attrs, wrapParam.html, afterWrap);

            return domNewWrap;
        },
        updateSchema: function(oSchema, oBeforeState) {
            var $html, $dom, wrap;

            $html = $('<div>' + this.html + '</div>');
            if ($dom = $html.find("[schema='" + oSchema.id + "']")) {
                if (wrap = this.wrapBySchema(oSchema)) {
                    wrap.type = $dom.attr('wrap');
                    if (oBeforeState && oSchema.type !== oBeforeState.type) {
                        wrap.config = SchemaWrap.input.newWrap(oSchema).config;
                    }
                    SchemaWrap.input.modify($dom, wrap, oBeforeState);
                    this.html = $html.html();
                    return true;
                }
            }
            return false;
        },
        removeSchema: function(schema) {
            var found = false,
                $html = $('<div>' + this.html + '</div>');

            $html.find("[schema='" + schema.id + "']").remove();
            this.html = $html.html();
            for (var i = this.data_schemas.length - 1; i >= 0; i--) {
                if (this.data_schemas[i].schema.id === schema.id) {
                    this.data_schemas.splice(i, 1);
                    found = true;
                    break;
                }
            }
            return found;
        },
    };
    protoInputPage = angular.extend({}, protoPage, protoInputPage);
    /**
     * 查看页处理逻辑基类
     */
    var protoViewPage = {
        /**
         * 整理题目，使得页面中的schema和应用中的schema是同一个对象
         */
        _arrange: function(mapOfAppSchemas) {
            var _this = this;

            if (this.data_schemas.length) {
                var dataSchemas = [];
                angular.forEach(this.data_schemas, function(item) {
                    var config = item.config,
                        schema = item.schema,
                        matched = false;
                    if (config && config.pattern === 'record') {
                        if (schema && schema.id) {
                            if (schema.id === 'enrollAt') {
                                matched = true;
                                dataSchemas.push(item);
                            } else if (mapOfAppSchemas[schema.id]) {
                                item.schema = mapOfAppSchemas[schema.id];
                                dataSchemas.push(item);
                                matched = true;
                            }
                        }
                    }
                    if (!matched) console.error("page[" + _this.name + "]'schema is invalid:", item);
                });
                this.data_schemas = dataSchemas;
            }
        },
        /**
         * 添加题目的html
         */
        _appendWrap: function(tag, attrs, html, afterWrap) {
            var $html, domNewWrap, $lastInputWrap, $btnWrap;

            $html = $('<div>' + this.html + '</div>');
            domNewWrap = $(document.createElement(tag)).attr(attrs).html(html);
            if (afterWrap) {
                $lastInputWrap = $html.find("[schema='" + afterWrap.schema.id + "']");
            } else {
                $lastInputWrap = $html.find("[wrap='value']:last");
            }

            if ($lastInputWrap.length) {
                $lastInputWrap.after(domNewWrap);
            } else {
                // 加在按钮的前面
                $btnWrap = $html.find("[wrap='button']:first");
                if ($btnWrap.length) {
                    $btnWrap.before(domNewWrap);
                } else {
                    // 加在文档的最后
                    $html.append(domNewWrap);
                }
            }

            this.html = $html.html();

            return domNewWrap;
        },
        /**
         * 页面中添加题目
         * 如果【填写时间】在最后一位，新题目添加到填写时间前面
         */
        appendSchema: function(schema, afterSchema) {
            var oNewWrap, domNewWrap, wrapParam, afterWrap, lastWrap, afterIndex;

            if (afterSchema) {
                afterWrap = this.wrapBySchema(afterSchema);
            } else {
                if (this.data_schemas.length) {
                    lastWrap = this.data_schemas[this.data_schemas.length - 1];
                    if (lastWrap.schema.type === '_enrollAt') {
                        if (this.data_schemas.length > 1) {
                            afterWrap = this.data_schemas[this.data_schemas.length - 2];
                        }
                    } else {
                        afterWrap = lastWrap;
                    }
                }
            }

            oNewWrap = SchemaWrap.value.newWrap(schema);
            if (afterWrap) {
                afterIndex = this.data_schemas.indexOf(afterWrap);
                if (afterIndex === -1) {
                    this.data_schemas.push(oNewWrap);
                } else {
                    this.data_schemas.splice(afterIndex + 1, 0, oNewWrap);
                }
            } else {
                this.data_schemas.push(oNewWrap);
            }

            wrapParam = SchemaWrap.value.embed(oNewWrap);

            domNewWrap = this._appendWrap(wrapParam.tag, wrapParam.attrs, wrapParam.html, afterWrap);

            return domNewWrap;
        },
        updateSchema: function(oSchema, oBeforeState) {
            var $html, $dom, wrap;
            $html = $('<div>' + this.html + '</div>');
            if ($dom = $html.find("[schema='" + oSchema.id + "']")) {
                if (wrap = this.wrapBySchema(oSchema)) {
                    SchemaWrap.value.modify($dom, wrap, oBeforeState);
                    this.html = $html.html();
                    return true;
                }
            }

            return false;
        },
        removeSchema: function(schema) {
            var found = false,
                $html = $('<div>' + this.html + '</div>');

            $html.find("[schema='" + schema.id + "']").remove();
            this.html = $html.html();
            for (var i = this.data_schemas.length - 1; i >= 0; i--) {
                if (this.data_schemas[i].schema.id === schema.id) {
                    this.data_schemas.splice(i, 1);
                    found = true;
                    break;
                }
            }
            return found;
        },
        wrapById: function(wrapId) {
            for (var i = this.data_schemas.length - 1; i >= 0; i--) {
                if (this.data_schemas[i].config.id === wrapId) {
                    return this.data_schemas[i];
                }
            }
            return false;
        },
    };
    protoViewPage = angular.extend({}, protoPage, protoViewPage);
    /**
     * 列表页处理逻辑基类
     */
    var protoListPage = {
        _arrange: function(mapOfAppSchemas) {
            if (this.data_schemas.length) {
                this.data_schemas.forEach(function(item) {
                    // todo 处理异常数据的情况，应该在保存数据的时候做检查
                    if (item && item.schemas) {
                        var listSchemas = [];
                        item.schemas.forEach(function(schema) {
                            listSchemas.push(mapOfAppSchemas[schema.id] ? mapOfAppSchemas[schema.id] : schema);
                        });
                        item.schemas = listSchemas;
                    }
                });
            } else if (angular.isObject(this.data_schemas)) {
                this.data_schemas = [];
            }
        },
        wrapByList: function(config) {
            for (var i = this.data_schemas.length - 1; i >= 0; i--) {
                if (this.data_schemas[i].config.id === config.id) {
                    return this.data_schemas[i];
                }
            }
            return false;
        },
        /**
         * 根据题目获得题目的包裹对象
         */
        wrapBySchema: function(schema) {
            var listWrap, schemaInList, i;
            for (i = this.data_schemas.length - 1; i >= 0; i--) {
                listWrap = this.data_schemas[i];
                for (var j = listWrap.schemas.length - 1; j >= 0; j--) {
                    schemaInList = listWrap.schemas[j];
                    if (schema.id === schemaInList.id) {
                        return {
                            list: listWrap,
                            schema: schemaInList
                        };
                    }
                }
            }

            return false;
        },
        appendSchema: function(oSchema, oBeforeSchema) {
            var valueHtml, $pageHtml, listWrap, $listHtml;

            $pageHtml = $('<div>' + this.html + '</div>');
            valueHtml = SchemaWrap.records._htmlValue(oSchema);

            for (var i = this.data_schemas.length - 1; i >= 0; i--) {
                listWrap = this.data_schemas[i];
                $listHtml = $pageHtml.find("[id='" + listWrap.config.id + "']>ul>li[ng-repeat]");
                if ($listHtml.length) {
                    if (oBeforeSchema) {
                        for (var j = listWrap.schemas.length - 1; j >= 0; j--) {
                            if (listWrap.schemas[j].id === oBeforeSchema.id) {
                                var $beforeWrap = $listHtml.find('[schema=' + oBeforeSchema.id + ']');
                                $beforeWrap.after(valueHtml);
                                listWrap.schemas.splice(j + 1, 0, oSchema);
                                break;
                            }
                        }
                        if (j === -1) {
                            $listHtml.append(valueHtml);
                            listWrap.schemas.push(oSchema);
                        }
                    } else {
                        $listHtml.append(valueHtml);
                        listWrap.schemas.push(oSchema);
                    }
                }
            }
            this.html = $pageHtml.html();

            return true;
        },
        updateSchema: function(oSchema, oBeforeState) {
            var $html, $wrap, $tags, $supplement;

            $html = $('<div>' + this.html + '</div>');
            $wrap = $html.find("[schema='" + oSchema.id + "']");
            $wrap.find('label').html(oSchema.title);

            if (oSchema.cantag === 'Y') {
                $tags = $wrap.find('.tags');
                if ($tags.length === 0) {
                    $wrap.append('<p class="tags"><span ne-repeat="r in Record.current"><span class="tag" ng-repeat="t in r.tag.' + oSchema.id + '" ng-bind="t.label"></span></span></p>');
                }
            } else {
                $wrap.find('.tags').remove();
            }
            if (oSchema.supplement === 'Y') {
                $supplement = $wrap.find('.supplement');
                if ($supplement.length === 0) {
                    $wrap.append('<p class="supplement" ng-bind="r.supplement.' + oSchema.id + '"></p>');
                }
            } else {
                $wrap.find('.supplement').remove();
            }

            this.html = $html.html();

            return true;
        },
        removeSchema: function(schema) {
            // 清除页面内容
            var $html = $('<div>' + this.html + '</div>'),
                list;

            $html.find("[schema='" + schema.id + "']").remove();
            this.html = $html.html();
            // 清除数据定义中的项
            for (var i = this.data_schemas.length - 1; i >= 0; i--) {
                list = this.data_schemas[i];
                if (list.schemas) {
                    for (var j = list.schemas.length - 1; j >= 0; j--) {
                        if (list.schemas[j].id === schema.id) {
                            list.schemas.splice(j, 1);
                            break;
                        }
                    }
                }
            }
            return true;
        },
        moveSchema: function(oMovedSchema, oPrevSchema) {
            var $html, listSchemas, $moved, $list;
            $html = $('<div>' + this.html + '</div>');
            for (var i = this.data_schemas.length - 1; i >= 0; i--) {
                $list = $html.find("[id=" + this.data_schemas[i].config.id + "]");
                if ($list.length === 1) {
                    listSchemas = this.data_schemas[i].schemas;
                    for (var j = listSchemas.length - 1; j >= 0; j--) {
                        if (listSchemas[j].id === oMovedSchema.id) {
                            listSchemas.splice(j, 1);
                            $moved = $list.find("[schema='" + oMovedSchema.id + "']");
                            $moved.remove();
                            break;
                        }
                    }
                    if ($moved && oPrevSchema) {
                        for (var j = listSchemas.length - 1; j >= 0; j--) {
                            if (listSchemas[j].id === oPrevSchema.id) {
                                listSchemas.splice(j + 1, 0, oMovedSchema);
                                $list.find("[schema='" + oPrevSchema.id + "']").after($moved);
                                break;
                            }
                        }
                    } else {
                        listSchemas.splice(0, 0, oMovedSchema);
                        $list.find("[schema]:first").before($moved);
                    }
                }
            }
            if ($moved) {
                this.html = $html.html();
                return true;
            }
            return false;
        }
    };
    protoListPage = angular.extend({}, protoPage, protoListPage);

    return {
        enhance: function(page, mapOfAppSchemas) {
            switch (page.type) {
                case 'I':
                    angular.merge(page, protoInputPage);
                    break;
                case 'V':
                    angular.merge(page, protoViewPage);
                    break;
                case 'L':
                    angular.merge(page, protoListPage);
                    break;
                default:
                    console.error('unknown page', page);
            }
            page._parseSchemas();
            if (mapOfAppSchemas) {
                page._arrange(mapOfAppSchemas);
            }
        }
    };
});