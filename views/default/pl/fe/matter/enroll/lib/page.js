/**
 * base class of page
 */
define(['wrap'], function(wrapLib) {
	'use strict';
	var _activeWrap = false,
		_editor = null;

	return {
		setEditor: function(editor) {
			_editor = editor;
		},
		purifyHtml: function() {
			var html;
			html = $(_editor.getBody()).html();
			html = $('<div>' + html + '</div>');
			html.find('.active').removeClass('active');
			html.find('[readonly]').removeAttr('readonly');
			html.find('[disabled]').removeAttr('disabled');
			this.html = html.html();

			return this.html;
		},
		setActiveWrap: function(domWrap) {
			var wrapType;
			if (_activeWrap) {
				_activeWrap.dom.classList.remove('active');
			}
			if (domWrap) {
				wrapType = $(domWrap).attr('wrap');
				_activeWrap = {
					type: wrapType,
					dom: domWrap,
					upmost: /body/i.test(domWrap.parentNode.tagName),
					downmost: /button|value|radio|checkbox/.test(wrapType),
				};
				domWrap.classList.add('active');
				var dataWrap = wrapLib.dataByDom(domWrap, this);
				angular.extend(_activeWrap, dataWrap);
			} else {
				_activeWrap = false;
			}

			return _activeWrap;
		},
		selectWrap: function(domWrap) {
			var root = domWrap,
				selectableWrap = domWrap,
				wrapType;
			while (root.parentNode) root = root.parentNode;
			$(root).find('.active').removeClass('active');
			this.setActiveWrap(null);
			wrapType = $(selectableWrap).attr('wrap');
			while (!/text|input|radio|checkbox|value|button|records|rounds/.test(wrapType) && selectableWrap.parentNode) {
				selectableWrap = selectableWrap.parentNode;
				wrapType = $(selectableWrap).attr('wrap');
			}
			if (/text|input|radio|checkbox|value|button|records|rounds/.test(wrapType)) {
				this.setActiveWrap(selectableWrap);
			}

			return _activeWrap;
		},
		moveWrap: function(action) {
			var $active = $(_activeWrap.dom);
			if (action === 'up') {
				$active.prev().before($active);
			} else if (action === 'down') {
				$active.next().after($active);
			} else if (action === 'upLevel') {
				this.setActiveWrap($active.parents('[wrap]').get(0));
			} else if (action === 'downLevel') {
				this.setActiveWrap($active.find('[wrap]').get(0));
			}

			return _activeWrap;
		},
		wrapBySchema: function(schema) {
			if (this.type === 'I') {
				var dataWrap, i;
				for (i = this.data_schemas.length - 1; i >= 0; i--) {
					dataWrap = this.data_schemas[i];
					if (schema.id === dataWrap.schema.id) {
						return dataWrap;
					}
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
		arrange: function(mapOfAppSchemas) {
			var dataSchemas = this.data_schemas,
				actSchemas = this.act_schemas,
				userSchemas = this.user_schemas;
			this.data_schemas = dataSchemas && dataSchemas.length ? JSON.parse(dataSchemas) : [];
			this.act_schemas = actSchemas && actSchemas.length ? JSON.parse(actSchemas) : [];
			this.user_schemas = userSchemas && userSchemas.length ? JSON.parse(userSchemas) : [];
			if (this.data_schemas.length) {
				if (this.type === 'I') {
					angular.forEach(this.data_schemas, function(dataWrap) {
						mapOfAppSchemas[dataWrap.schema.id] && (dataWrap.schema = mapOfAppSchemas[dataWrap.schema.id]);
					});
				} else if (this.type === 'V') {
					angular.forEach(this.data_schemas, function(config) {
						if (config.pattern === 'record') {
							mapOfAppSchemas[config.schema.id] && (config.schema = mapOfAppSchemas[config.schema.id]);
						}
					});
				} else if (this.type === 'L') {
					angular.forEach(this.data_schemas, function(config) {
						if (config.pattern === 'records') {
							var listSchemas = [];
							angular.forEach(config.schemas, function(schema) {
								listSchemas.push(mapOfAppSchemas[schema.id] ? mapOfAppSchemas[schema.id] : schema);
							});
							config.schemas = listSchemas;
						}
					});
				}
			} else if (angular.isObject(this.data_schemas)) {
				this.data_schemas = [];
			}
		},
		containInput: function(schema) {
			var i, l;
			if (this.type === 'I') {
				for (i = 0, l = this.data_schemas.length; i < l; i++) {
					if (this.data_schemas[i].id === schema.id) {
						return this.data_schemas[i];
					}
				}
			} else if (this.type === 'V') {
				if (this.data_schemas.record) {
					for (i = 0, l = this.data_schemas.record.length; i < l; i++) {
						if (this.data_schemas.record[i].schema.id === schema.id) {
							return this.data_schemas.record[i].schema;
						}
					}
				}
				if (this.data_schemas.list) {
					var list, j, k;
					for (i = 0, l = this.data_schemas.list.length; i < l; i++) {
						list = this.data_schemas.list[i];
						for (j = 0, k = list.schemas.length; j < k; j++) {
							if (list.schemas[j].id === schema.id) {
								return list.schemas[j];
							}
						}
					}
				}
			}
			return false;
		},
		removeInput: function(schema) {
			if (this.type === 'I') {
				for (var i = this.data_schemas.length - 1; i >= 0; i--) {
					if (this.data_schemas[i].id === schema.id) {
						return this.data_schemas.splice(i, 1);
					}
				}
			}
			return false;
		},
		containAct: function(dataWrap) {
			for (var i = this.act_schemas.length - 1; i >= 0; i--) {
				if (this.act_schemas[i].id === dataWrap.id) {
					return this.act_schemas[i];
				}
			}
			return false;
		},
		containStatic: function(schema) {
			if (this.type === 'V') {
				for (i = 0, l = this.data_schemas.length; i < l; i++) {
					if (this.data_schemas[i].id === schema.id) {
						return this.data_schemas[i];
					}
				}
			}
			return false;
		},
		containList: function(config) {
			if (this.type === 'L') {
				for (var i = this.data_schemas.length - 1; i >= 0; i--) {
					if (this.data_schemas[i].config.id === config.id) {
						return this.data_schemas[i];
					}
				}
			}
			return false;
		},
		removeAct: function(schema) {
			for (var i = this.act_schemas.length - 1; i >= 0; i--) {
				if (this.act_schemas[i].id === schema.id) {
					return this.act_schemas.splice(i, 1);
				}
			}
			return false;
		},
		removeValue: function(config, schema) {
			if (this.type === 'V') {
				/*从查看页中删除登记项*/
				for (var i = this.data_schemas.length - 1; i >= 0; i--) {
					if (this.data_schemas[i].id === config.id) {
						return this.data_schemas.splice(i, 1);
					}
				}
			} else if (this.type === 'L' && config.id && schema) {
				/*从列表中删除登记项*/
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
			}
			return false;
		},
		updateBySchema: function(schema) {
			if (this.type === 'V' || this.type === 'L') {
				var $html = $('<div>' + this.html + '</div>');
				$html.find("[schema='" + schema.id + "']").find('label').html(schema.title);
				this.html = $html.html();
			}
		},
		removeBySchema: function(schema) {
			if (this.type === 'V' || this.type === 'L') {
				var $html = $('<div>' + this.html + '</div>');
				$html.find("[schema='" + schema.id + "']").remove();
				this.html = $html.html();
			}
		},
		appendBySchema: function(schema) {
			var newWrap, domNewWrap;
			if (this.type === 'I') {
				newWrap = wrapLib.input.newWrap(schema);
				domNewWrap = wrapLib.input.embed(newWrap);
				this.data_schemas.push(newWrap);
			}
			return domNewWrap;
		},
		appendRecord: function(schema) {
			var oNewWrap = wrapLib.value.newWrap(schema),
				wrapAttrs, wrapHtml, domNewWrap, $newHtml;
			/* make wrap */
			wrapAttrs = wrapLib.value.wrapAttrs(oNewWrap);
			wrapHtml = wrapLib.value.htmlValue(schema);
			domNewWrap = $('<div></div>').attr(wrapAttrs).append('<label>' + schema.title + '</label>').append(wrapHtml);
			/* update page */
			$newHtml = $('<div>' + this.html + '</div>');
			if ($newHtml.find("[wrap='value']").length) {
				$newHtml.find("[wrap='value']:last").after(domNewWrap);
			} else {
				$newHtml = $('<div></div>').append(domNewWrap);
			}
			this.html = $newHtml.html();

			this.data_schemas.push(oNewWrap);

			return domNewWrap;
		},
		appendRecord2: function(schema) {
			var dataWrap, domNewWrap;
			dataWrap = wrapLib.value.newWrap(schema);
			domNewWrap = wrapLib.value.embed(dataWrap);
			this.data_schemas.push(dataWrap);

			return domNewWrap;
		},
		appendButton: function(btn) {
			var oWrap = {
					id: 'act' + (new Date()).getTime(),
					name: btn.n,
					label: btn.l,
					next: ''
				},
				domNewWrap;

			domNewWrap = wrapLib.button.embed(oWrap);
			this.act_schemas.push(oWrap);

			return domNewWrap;
		},
		appendRecordList: function(app) {
			var dataWrap = {
				config: {
					id: 'L' + (new Date()).getTime(),
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
					id: 'L' + (new Date()).getTime(),
					pattern: 'rounds',
					onclick: ''
				}
			};
			this.data_schemas.push(dataWrap);

			return wrapLib.rounds.embed(dataWrap);
		},
		removeWrap: function(oWrap) {
			var wrapType = oWrap.type,
				$domRemoved = $(oWrap.dom);
			if (/input/.test(wrapType)) {
				this.removeInput(oWrap.schema);
			} else
			if (/button/.test(wrapType)) {
				this.removeAct(oWrap.schema);
			} else if (/value/.test(wrapType)) {
				var config = oWrap.config;
				if (config) {
					if (config.id === undefined) {
						/*列表中的值对象*/
						var $listWrap = $domRemoved.parents('[wrap]');
						if ($listWrap.length && $listWrap.attr('wrap') === 'records') {
							config.id = $listWrap.attr('id');
						}
						this.removeValue(config, oWrap.schema);
					} else {
						this.removeValue(config);
					}
				}
			} else if (/records|rounds/.test(wrapType)) {
				(function removeList() {
					var listId = $domRemoved.attr('id');
					for (var i = this.data_schemas.length - 1; i >= 0; i--) {
						list = this.data_schemas[i];
						if (list.id === listId) {
							this.data_schemas.splice(i, 1);
							break;
						}
					}
				})();
			}

			$domRemoved.remove();
			_editor.save();
		},
		removeSchema2: function(removedSchema) {
			var pageSchemas = this.data_schemas,
				i, $domRemoved;

			for (i = pageSchemas.length - 1; i >= 0; i--) {
				if (removedSchema.id === pageSchemas[i].schema.id) {
					$domRemoved = $(_editor.getBody()).find("[schema='" + removedSchema.id + "']");
					$domRemoved.remove();
					pageSchemas.splice(i, 1);
					return $domRemoved[0];
				}
			}

			return false;
		}
	};
});