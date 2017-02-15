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
            try {
                this.user_schemas = userSchemas && userSchemas.length ? JSON.parse(userSchemas) : [];
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
        moveSchema: function(moved, prev) {
            var movedWrap = this.wrapBySchema(moved),
                prevWrap, $html, $movedHtml, $prevHtml;

            this.data_schemas.splice(this.data_schemas.indexOf(movedWrap), 1);
            $html = $('<div>' + this.html + '</div>');
            $movedHtml = $html.find('[schema=' + moved.id + ']');
            if (prev) {
                prevWrap = this.wrapBySchema(prev);
                this.data_schemas.splice(this.data_schemas.indexOf(prevWrap), 0, movedWrap);
                $prevHtml = $html.find("[schema='" + prev.id + "']");
                $prevHtml.after($movedHtml);
            } else {
                this.data_schemas.splice(0, 0, movedWrap);
                $($html.find('[schema]').get(0)).before($movedHtml);
            }
            this.html = $html.html();
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
         * 添加题目的html
         */
        _appendWrap: function(tag, attrs, html, afterWrap) {
            var newDomWrap, $html, $lastInputWrap;

            $html = $('<div>' + this.html + '</div>');
            newDomWrap = $(document.createElement(tag)).attr(attrs).html(html);
            if (afterWrap === undefined) {
                $lastInputWrap = $html.find("[wrap='input']:last");
            } else {
                $lastInputWrap = $html.find("[schema='" + afterWrap.schema.id + "']");
            }

            if ($lastInputWrap.length) {
                // 加到最后一个题目后面
                $lastInputWrap.after(newDomWrap);
            } else {
                // 加在文档的最后
                $html.append(newDomWrap);
            }

            this.html = $html.html();

            return newDomWrap;
        },
        /**
         * 页面中添加题目
         */
        appendSchema: function(schema, afterSchema) {
            var newWrap, wrapParam, domNewWrap, afterWrap;

            newWrap = SchemaWrap.input.newWrap(schema);

            if (afterSchema === undefined) {
                this.data_schemas.push(newWrap);
            } else {
                afterWrap = this.wrapBySchema(afterSchema);
                var afterIndex = this.data_schemas.indexOf(afterWrap);
                if (afterIndex === -1) {
                    this.data_schemas.push(newWrap);
                } else {
                    this.data_schemas.splice(afterIndex + 1, 0, newWrap);
                }
            }

            wrapParam = SchemaWrap.input.embed(newWrap);

            domNewWrap = this._appendWrap(wrapParam.tag, wrapParam.attrs, wrapParam.html, afterWrap);

            return domNewWrap;
        },
        /**
         * 更新题目
         */
        updateSchema: function(schema, beforeState) {
            var $html, $dom, wrap;

            $html = $('<div>' + this.html + '</div>');
            if ($dom = $html.find("[schema='" + schema.id + "']")) {
                if (wrap = this.wrapBySchema(schema)) {
                    wrap.type = $dom.attr('wrap');
                    if (beforeState && schema.type !== beforeState.type) {
                        wrap.config = SchemaWrap.input.newWrap(schema).config;
                    }
                    SchemaWrap.input.modify($dom, wrap, beforeState);
                    this.html = $html.html();
                    return true;
                }
            }
            return false;
        },
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
         * 从页面中删除题目
         */
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
         * 添加题目的html
         */
        _appendWrap: function(tag, attrs, html, afterWrap) {
            var $html, domNewWrap, $lastInputWrap;

            $html = $('<div>' + this.html + '</div>');
            domNewWrap = $(document.createElement(tag)).attr(attrs).html(html);
            if (afterWrap === undefined) {
                $lastInputWrap = $html.find("[wrap='value']:last");
            } else {
                $lastInputWrap = $html.find("[schema='" + afterWrap.schema.id + "']");
            }

            if ($lastInputWrap.length) {
                $lastInputWrap.after(domNewWrap);
            } else {
                $html.append(domNewWrap);
            }

            this.html = $html.html();

            return domNewWrap;
        },
        /**
         * 页面中添加题目
         */
        appendSchema: function(schema, afterSchema) {
            var oNewWrap, domNewWrap, wrapParam, afterWrap;

            oNewWrap = SchemaWrap.value.newWrap(schema);

            if (afterSchema === undefined) {
                this.data_schemas.push(oNewWrap);
            } else {
                afterWrap = this.wrapBySchema(afterSchema);
                var afterIndex = this.data_schemas.indexOf(afterWrap);
                if (afterIndex === -1) {
                    this.data_schemas.push(oNewWrap);
                } else {
                    this.data_schemas.splice(afterIndex + 1, 0, oNewWrap);
                }
            }

            wrapParam = SchemaWrap.value.embed(oNewWrap);

            domNewWrap = this._appendWrap(wrapParam.tag, wrapParam.attrs, wrapParam.html, afterWrap);

            return domNewWrap;
        },
        updateSchema: function(schema, beforeState) {
            var $html, $dom, wrap;

            $html = $('<div>' + this.html + '</div>');
            if ($dom = $html.find("[schema='" + schema.id + "']")) {
                if (wrap = this.wrapBySchema(schema)) {
                    SchemaWrap.value.modify($dom, wrap, beforeState);
                    this.html = $html.html();
                    return true;
                }
            }

            return false;
        },
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
        wrapById: function(wrapId) {
            for (var i = this.data_schemas.length - 1; i >= 0; i--) {
                if (this.data_schemas[i].config.id === wrapId) {
                    return this.data_schemas[i];
                }
            }
            return false;
        },
        removeValue: function(config) {
            // 从查看页中删除题目
            for (var i = this.data_schemas.length - 1; i >= 0; i--) {
                if (this.data_schemas[i].id === config.id) {
                    return this.data_schemas.splice(i, 1);
                }
            }

            return false;
        },
        /**
         * 从页面中删除题目
         */
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
    protoViewPage = angular.extend({}, protoPage, protoViewPage);
    /**
     * 列表页处理逻辑基类
     */
    var protoListPage = {
        _arrange: function(mapOfAppSchemas) {
            if (this.data_schemas.length) {
                angular.forEach(this.data_schemas, function(item) {
                    if (item.config && item.config.pattern === 'records') {
                        var listSchemas = [];
                        angular.forEach(item.schemas, function(schema) {
                            listSchemas.push(mapOfAppSchemas[schema.id] ? mapOfAppSchemas[schema.id] : schema);
                        });
                        item.schemas = listSchemas;
                    }
                });
            } else if (angular.isObject(this.data_schemas)) {
                this.data_schemas = [];
            }
        },
        appendSchema: function(schema, afterSchema) {
            return false;
        },
        appendRecordList: function(app) {
            var dataWrap = {
                config: {
                    id: 'L' + (new Date() * 1),
                    pattern: 'records',
                    dataScope: 'U',
                    onclick: '',
                },
                schemas: angular.copy(app.data_schemas)
            };

            dataWrap.schemas.push({
                id: 'enrollAt',
                type: '_enrollAt',
                title: '登记时间'
            });

            this.data_schemas.push(dataWrap);

            return SchemaWrap.records.embed(dataWrap);
        },
        appendRoundList: function(app) {
            var dataWrap = {
                config: {
                    id: 'L' + (new Date() * 1),
                    pattern: 'rounds',
                    onclick: ''
                }
            };
            this.data_schemas.push(dataWrap);

            return SchemaWrap.rounds.embed(dataWrap);
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
                            schema: schemaInList
                        };
                    }
                }
            }

            return false;
        },
        removeValue: function(config, schema) {
            // 从列表中删除题目
            var i, j, list;
            for (i = this.data_schemas.length - 1; i >= 0; i--) {
                list = this.data_schemas[i];
                if (list.config.id === config.id) {
                    for (j = list.schemas.length - 1; j >= 0; j--) {
                        if (list.schemas[j].id === schema.id) {
                            return list.schemas.splice(j, 1);
                        }
                    }
                }
            }

            return false;
        },
        /**
         * 从页面中删除题目
         */
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
        updateSchema: function(schema, beforeState) {
            var $html;

            $html = $('<div>' + this.html + '</div>');
            $html.find("[schema='" + schema.id + "']").find('label').html(schema.title);

            this.html = $html.html();

            return true;
        },
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
