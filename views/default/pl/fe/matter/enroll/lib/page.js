/**
 * base class of page
 */
define(['wrap'], function(SchemaWrap) {
    'use strict';
    /**
     * 页面处理逻辑基类
     */
    var oProtoPage = {
        wrapBySchema: function(schema) {
            var dataWrap, i;
            for (i = this.dataSchemas.length - 1; i >= 0; i--) {
                dataWrap = this.dataSchemas[i];
                if (schema.id === dataWrap.schema.id) {
                    return dataWrap;
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
                this.dataSchemas.splice(this.dataSchemas.indexOf(movedWrap), 1);
                $html = $('<div>' + this.html + '</div>');
                $movedHtml = $html.find('[schema="' + oMovedSchema.id + '"]');
                if (oPrevSchema) {
                    prevWrap = this.wrapBySchema(oPrevSchema);
                    this.dataSchemas.splice(this.dataSchemas.indexOf(prevWrap), 0, movedWrap);
                    $prevHtml = $html.find("[schema='" + oPrevSchema.id + "']");
                    $prevHtml.after($movedHtml);
                } else {
                    this.dataSchemas.splice(0, 0, movedWrap);
                    $($html.find('[schema]').get(0)).before($movedHtml);
                }
                this.html = $html.html();
            }
        },
        check: function() {
            var $html, schemasById, oSchema, $schemas, $schema;
            $html = $('<div>' + this.html + '</div>');
            schemasById = {};
            for (var i = this.dataSchemas.length - 1; i >= 0; i--) {
                oSchema = this.dataSchemas[i].schema;
                if ($html.find("[schema='" + oSchema.id + "']").length === 0) {
                    return ['s01', '题目【' + oSchema.title + '】在页面【' + this.title + '】中不存在，可通过（显示/隐藏）操作在页面中添加该题目', oSchema];
                }
                schemasById[oSchema.id] = oSchema;
            }
            $schemas = $html.find("[schema]");
            if ($schemas.length !== this.dataSchemas.length) {
                for (var i = $schemas.length - 1; i >= 0; i--) {
                    $schema = $($schemas[i]);
                    if (!schemasById[$schema.attr('schema')]) {
                        return ['p01', '页面【' + this.title + '】中的题目【' + $schema.text() + '】没有定义，可通过删除页面元素清除', $schema];
                    }
                }
            }

            return [true];
        },
        repair: function(aCheckResult) {
            var code, aChangedProps;

            code = aCheckResult[0];
            aChangedProps = [];
            switch (code) {
                case 's01':
                    if (aCheckResult[2]) {
                        for (var i = this.dataSchemas.length - 1; i >= 0; i--) {
                            if (this.dataSchemas[i].schema.id === aCheckResult[2].id) {
                                this.dataSchemas.splice(i, 1);
                                aChangedProps.push('dataSchemas');
                                break;
                            }
                        }
                    }
                    break;
                case 'p01':
                    if (aCheckResult[2]) {
                        var $html;
                        $html = $('<div>' + this.html + '</div>');
                        $html.find("[schema='" + aCheckResult[2].attr('schema') + "']").remove();
                        this.html = $html.html();
                        aChangedProps.push('html');
                    }
                    break;
            }

            return [true, aChangedProps];
        }
    };
    /**
     * 输入页处理逻辑基类
     */
    var oProtoInputPage = {
        /**
         * 整理题目，使得页面中的schema和应用中的schema是同一个对象
         */
        _arrange: function(mapOfAppSchemas) {
            var _this = this;

            if (this.dataSchemas.length) {
                var dataSchemas = [];
                this.dataSchemas.forEach(function(item) {
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
                this.dataSchemas = dataSchemas;
            }
        },
        /**
         * 添加题目的html
         */
        _appendWrap: function(tag, attrs, html, afterWrap) {
            var newDomWrap, $html, $siblingInputWrap, bInsertAfter, $btnWrap;

            bInsertAfter = true;
            $html = $('<div>' + this.html + '</div>');
            newDomWrap = $(document.createElement(tag)).attr(attrs).html(html);
            if (afterWrap) {
                $siblingInputWrap = $html.find("[schema='" + afterWrap.schema.id + "']");
            } else if (afterWrap === false) {
                $siblingInputWrap = $html.find("[wrap='input']:first");
                bInsertAfter = false;
            } else {
                $siblingInputWrap = $html.find("[wrap='input']:last");
            }

            if ($siblingInputWrap.length) {
                // 加到指定题目后面
                if (bInsertAfter) {
                    $siblingInputWrap.after(newDomWrap);
                } else {
                    $siblingInputWrap.before(newDomWrap);
                }
            } else {
                // 加在文档的最后
                $html.append(newDomWrap);
            }

            this.html = $html.html();

            return newDomWrap;
        },
        appendSchema: function(schema, afterSchema) {
            var newWrap, wrapParam, domNewWrap, afterWrap;

            newWrap = SchemaWrap.input.newWrap(schema);

            if (afterSchema) {
                afterWrap = this.wrapBySchema(afterSchema);
                var afterIndex = this.dataSchemas.indexOf(afterWrap);
                if (afterIndex === -1) {
                    this.dataSchemas.push(newWrap);
                } else {
                    this.dataSchemas.splice(afterIndex + 1, 0, newWrap);
                }
            } else if (afterSchema === false) {
                afterWrap = false;
                this.dataSchemas.splice(0, 0, newWrap);
            } else {
                this.dataSchemas.push(newWrap);
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
            for (var i = this.dataSchemas.length - 1; i >= 0; i--) {
                if (this.dataSchemas[i].schema.id === schema.id) {
                    this.dataSchemas.splice(i, 1);
                    found = true;
                    break;
                }
            }
            return found;
        }
    };
    oProtoInputPage = angular.extend({}, oProtoPage, oProtoInputPage);
    /**
     * 查看页处理逻辑基类
     */
    var oProtoViewPage = {
        /**
         * 整理题目，使得页面中的schema和应用中的schema是同一个对象
         */
        _arrange: function(mapOfAppSchemas) {
            var _this = this;

            if (this.dataSchemas.length) {
                var dataSchemas = [];
                angular.forEach(this.dataSchemas, function(item) {
                    var config = item.config,
                        schema = item.schema,
                        matched = false;
                    if (config && config.pattern === 'record') {
                        if (schema && schema.id) {
                            if (/enrollAt|roundTitle/.test(schema.id)) {
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
                this.dataSchemas = dataSchemas;
            }
        },
        /**
         * 添加题目的html
         */
        _appendWrap: function(tag, attrs, html, afterWrap) {
            var $html, domNewWrap, $siblingInputWrap, bInsertAfter, $btnWrap;

            bInsertAfter = true;
            $html = $('<div>' + this.html + '</div>');
            domNewWrap = $(document.createElement(tag)).attr(attrs).html(html);
            if (afterWrap) {
                $siblingInputWrap = $html.find("[schema='" + afterWrap.schema.id + "']");
            } else if (afterWrap === false) {
                $siblingInputWrap = $html.find("[wrap='value']:first");
                bInsertAfter = false;
            } else {
                $siblingInputWrap = $html.find("[wrap='value']:last");
            }

            if ($siblingInputWrap.length) {
                if (bInsertAfter) {
                    $siblingInputWrap.after(domNewWrap);
                } else {
                    $siblingInputWrap.before(domNewWrap);
                }
            } else {
                // 加在文档的最后
                $html.append(domNewWrap);
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
            } else if (afterSchema === false) {
                afterWrap = false;
            } else {
                if (this.dataSchemas.length) {
                    lastWrap = this.dataSchemas[this.dataSchemas.length - 1];
                    if (lastWrap.schema.type === '_enrollAt') {
                        if (this.dataSchemas.length > 1) {
                            afterWrap = this.dataSchemas[this.dataSchemas.length - 2];
                        }
                    } else {
                        afterWrap = lastWrap;
                    }
                }
            }

            oNewWrap = SchemaWrap.value.newWrap(schema);
            if (afterWrap) {
                afterIndex = this.dataSchemas.indexOf(afterWrap);
                if (afterIndex === -1) {
                    this.dataSchemas.push(oNewWrap);
                } else {
                    this.dataSchemas.splice(afterIndex + 1, 0, oNewWrap);
                }
            } else if (afterWrap === false) {
                this.dataSchemas.splice(0, 0, oNewWrap);
            } else {
                this.dataSchemas.push(oNewWrap);
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
            for (var i = this.dataSchemas.length - 1; i >= 0; i--) {
                if (this.dataSchemas[i].schema.id === schema.id) {
                    this.dataSchemas.splice(i, 1);
                    found = true;
                    break;
                }
            }
            return found;
        },
        wrapById: function(wrapId) {
            for (var i = this.dataSchemas.length - 1; i >= 0; i--) {
                if (this.dataSchemas[i].config.id === wrapId) {
                    return this.dataSchemas[i];
                }
            }
            return false;
        }
    };
    oProtoViewPage = angular.extend({}, oProtoPage, oProtoViewPage);

    return {
        enhance: function(oPage, mapOfAppSchemas) {
            switch (oPage.type) {
                case 'I':
                    angular.merge(oPage, oProtoInputPage);
                    break;
                case 'V':
                    angular.merge(oPage, oProtoViewPage);
                    break;
                default:
                    console.error('unknown page type', oPage);
            }
            if (mapOfAppSchemas) {
                oPage._arrange(mapOfAppSchemas);
            }
        }
    };
});