/**
 * base class of page
 */
define(['wrap'], function(wrapLib) {
	'use strict';
	/**
	 * 页面处理逻辑基类
	 */
	var protoPage = {
		_arrangeSchemas: function() {
			var dataSchemas = this.data_schemas,
				actSchemas = this.act_schemas,
				userSchemas = this.user_schemas;

			try {
				this.data_schemas = dataSchemas && dataSchemas.length ? JSON.parse(dataSchemas) : [];
			} catch (e) {
				console.error(e);
				this.data_schemas = [];
			}
			try {
				this.act_schemas = actSchemas && actSchemas.length ? JSON.parse(actSchemas) : [];
			} catch (e) {
				console.error(e);
				this.act_schemas = [];
			}
			try {
				this.user_schemas = userSchemas && userSchemas.length ? JSON.parse(userSchemas) : [];
			} catch (e) {
				console.error(e);
				this.user_schemas = [];
			}
		},
		/**
		 * 从页面中删除登记项
		 */
		removeSchema: function(schema) {
			// 清除页面内容
			var $html = $('<div>' + this.html + '</div>');

			$html.find("[schema='" + schema.id + "']").remove();
			this.html = $html.html();
			// 清除数据定义中的项
			for (var i = this.data_schemas.length - 1; i >= 0; i--) {
				if (this.data_schemas[i].schema.id === schema.id) {
					this.data_schemas.splice(i, 1);
					break;
				}
			}
			return true;
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
		 * 调整登记项在页面中的位置
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
		 * 添加登记项的html
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
				// 加到最后一个登记项后面
				$lastInputWrap.after(newDomWrap);
			} else {
				// 加在文档的最后
				$html.append(newDomWrap);
			}

			this.html = $html.html();

			return newDomWrap;
		},
		/**
		 * 页面中添加登记项
		 */
		appendSchema: function(schema, afterSchema) {
			var newWrap, wrapParam, domNewWrap, afterWrap;

			newWrap = wrapLib.input.newWrap(schema);

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

			wrapParam = wrapLib.input.embed(newWrap);

			domNewWrap = this._appendWrap(wrapParam.tag, wrapParam.attrs, wrapParam.html, afterWrap);

			return domNewWrap;
		},
		/**
		 * 更新登记项
		 */
		updateSchema: function(schema) {
			var $html, $wrap, $label, $input, oPage = this;

			$html = $('<div>' + this.html + '</div>');
			$wrap = $html.find("[schema='" + schema.id + "']");
			$label = $wrap.find('label').html(schema.title);
			if (/name|email|mobile|shorttext|longtext|member/.test(schema.type)) {
				$input = $wrap.find('input,select,textarea');
				$input.attr('title', schema.title);
				if ($input.attr('placeholder')) {
					$input.attr('placeholder', schema.title);
				}
			} else if (/single|phase/.test(schema.type)) {
				(function(lib) {
					var html, wrapSchema;
					if (schema.ops && schema.ops.length > 0) {
						wrapSchema = oPage.wrapBySchema(schema);
						$wrap.children('ul,select').remove();
						if (wrapSchema.config) {
							if (wrapSchema.config.component === 'R') {
								html = lib.input._htmlSingleRadio(wrapSchema);
								$wrap.append(html);
							} else if (wrapSchema.config.component === 'S') {
								html = lib.input._htmlSingleSelect(wrapSchema);
								$wrap.append(html);
							}
						}
					}
				})(wrapLib);
			} else if ('multiple' === schema.type) {
				(function(lib) {
					var html, wrapSchema;
					if (schema.ops && schema.ops.length > 0) {
						wrapSchema = oPage.wrapBySchema(schema);
						html = lib.input._htmlMultiple(wrapSchema);
						$wrap.children('ul').remove();
						$wrap.append(html);
					}
				})(wrapLib);
			} else if ('score' === schema.type) {
				(function(lib) {
					var html, wrapSchema;
					if (schema.ops && schema.ops.length > 0) {
						wrapSchema = oPage.wrapBySchema(schema);
						html = lib.input._htmlScoreItem(wrapSchema);
						$wrap.children('ul').remove();
						$wrap.append(html);
					}
				})(wrapLib);
			} else if (/image|file/.test(schema.type)) {
				(function(lib) {
					var $button = $wrap.find('li.img-picker button'),
						sNgClick;

					sNgClick = 'chooseImage(' + "'" + schema.id + "'," + schema.count + ')';
					$button.attr('ng-click', sNgClick);
				})(wrapLib);
			} else if ('html' === schema.type) {
				$wrap.html(schema.content);
			}

			this.html = $html.html();
		},
		/**
		 * 整理登记项，使得页面中的schema和应用中的schema是同一个对象
		 */
		arrange: function(mapOfAppSchemas) {
			this._arrangeSchemas();

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
					if (!matched) console.error('data invalid', item);
				});
				this.data_schemas = dataSchemas;
			} else if (angular.isObject(this.data_schemas)) {
				this.data_schemas = [];
			}
		},
		/**
		 * 根据登记项获得登记项的包裹对象
		 */
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
	};
	angular.extend(protoInputPage, protoPage);
	/**
	 * 查看页处理逻辑基类
	 */
	var protoViewPage = {
		/**
		 * 添加登记项的html
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
		},
		/**
		 * 页面中添加登记项
		 */
		appendSchema: function(schema, afterSchema) {
			var oNewWrap, domNewWrap, wrapParam, afterWrap;

			oNewWrap = wrapLib.value.newWrap(schema);

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

			wrapParam = wrapLib.value.embed(oNewWrap);

			domNewWrap = this._appendWrap(wrapParam.tag, wrapParam.attrs, wrapParam.html, afterWrap);

			return domNewWrap;
		},
		updateSchema: function(schema) {
			var $html;

			$html = $('<div>' + this.html + '</div>');
			if (schema.type === 'html') {
				$html.find("[schema='" + schema.id + "']").html(schema.content);
			} else {
				$html.find("[schema='" + schema.id + "']").find('label').html(schema.title);
			}

			this.html = $html.html();
		},
		/**
		 * 整理登记项，使得页面中的schema和应用中的schema是同一个对象
		 */
		arrange: function(mapOfAppSchemas) {
			this._arrangeSchemas();

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
					if (!matched) console.error('data invalid', item);
				});
				this.data_schemas = dataSchemas;
			} else if (angular.isObject(this.data_schemas)) {
				this.data_schemas = [];
			}
		},
		/**
		 * 根据登记项获得登记项的包裹对象
		 */
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
		wrapById: function(wrapId) {
			for (var i = this.data_schemas.length - 1; i >= 0; i--) {
				if (this.data_schemas[i].config.id === wrapId) {
					return this.data_schemas[i];
				}
			}
			return false;
		},
		removeValue: function(config) {
			// 从查看页中删除登记项
			for (var i = this.data_schemas.length - 1; i >= 0; i--) {
				if (this.data_schemas[i].id === config.id) {
					return this.data_schemas.splice(i, 1);
				}
			}

			return false;
		}
	};
	angular.extend(protoViewPage, protoPage);
	/**
	 * 列表页处理逻辑基类
	 */
	var protoListPage = {
		arrange: function(mapOfAppSchemas) {
			this._arrangeSchemas();

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

			return wrapLib.records.embed(dataWrap);
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

			return wrapLib.rounds.embed(dataWrap);
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
		 * 根据登记项获得登记项的包裹对象
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
			// 从列表中删除登记项
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
		updateSchema: function(schema) {
			var $html;

			$html = $('<div>' + this.html + '</div>');
			$html.find("[schema='" + schema.id + "']").find('label').html(schema.title);

			this.html = $html.html();
		},
	};
	angular.extend(protoListPage, protoPage);

	return {
		enhance: function(page) {
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
		},

	};
});