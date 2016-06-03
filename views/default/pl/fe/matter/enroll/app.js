(function() {
	ngApp.provider.controller('ctrlApp', ['$scope', 'http2', function($scope, http2) {
		$scope.publish = function() {
			$scope.app.state = 2;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/enroll/publish?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
		$scope.remove = function() {
			if (window.confirm('确定删除？')) {
				http2.get('/rest/pl/fe/matter/enroll/remove?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
					location = '/rest/pl/fe/site/console?site=' + $scope.siteId;
				});
			}
		};
		$scope.$on('xxt.tms-datepicker.change', function(event, data) {
			$scope.app[data.state] = data.value;
			$scope.update(data.state);
		});
	}]);
	ngApp.provider.controller('ctrlButton', ['$scope', function($scope) {
		var targetPages = {},
			inputPages = {},
			schema = $scope.activeSchema;
		$scope.$watch('app', function(app) {
			if (!app) return;
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
			removeRecord: {
				l: '删除登记'
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
			}
		};
		$scope.pages = targetPages;
		$scope.inputPages = inputPages;
		$scope.choose = function() {
			var names;
			schema.label = $scope.buttons[schema.name].l;
			schema.next = '';
			if (['addRecord', 'editRecord', 'removeRecord'].indexOf(schema.name) !== -1) {
				names = Object.keys(inputPages);
				if (names.length === 0) {
					alert('没有类型为“填写页”的页面');
				} else {
					schema.next = names[0];
				}
			}
		};
	}]);
	ngApp.provider.controller('ctrlPageEditor', ['$scope', '$q', '$modal', 'http2', 'mediagallery', 'mattersgallery', function($scope, $q, $modal, http2, mediagallery, mattersgallery) {
		$scope.$watch('app', function(app) {
			if (!app) return;
			$scope.ep = app.pages[0];
		});
		$scope.choosePage = function(page) {
			$scope.ep = page;
			tinymce.activeEditor.setContent(page.html);
		};
		$scope.activeWrap = false;
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
		var setActiveWrap = function(wrap) {
			var wrapType;
			if (wrap) {
				wrapType = $(wrap).attr('wrap');
				wrap.classList.add('active');
				$scope.hasActiveWrap = true;
				$scope.activeWrap = {
					type: wrapType,
					editable: true,
					upmost: /body/i.test(wrap.parentNode.tagName),
					downmost: /button|static|radio|checkbox/.test(wrapType),
				};
				if (/button/.test(wrapType)) {
					$scope.activeSchema = (function() {
						var schema, schema2;
						schema = wrapLib.button.extract(wrap);
						if (schema2 = $scope.ep.containAct(schema)) {
							return schema2;
						}
						return schema;
					})();
				} else if (/input/.test(wrapType)) {
					$scope.activeSchema = (function() {
						var schema = wrapLib.extractInputSchema(wrap);
						for (var i = 0, l = $scope.ep.data_schemas.length; i < l; i++) {
							if (schema.id === $scope.ep.data_schemas[i].id) {
								schema = $scope.ep.data_schemas[i];
								break;
							}
						}
						return schema;
					})();
				} else if (/static/.test($active.attr('wrap'))) {
					var config = (function() {
						var config = wrapLib.extractStaticSchema($active[0]),
							config2;
						if (config2 = $scope.ep.containStatic(config)) {
							return config2;
						}
						return config;
					})();
				} else if (/record-list/.test($active.attr('wrap'))) {
					var config = (function() {
						var config = wrapLib.extractStaticSchema($active[0]),
							config2;
						if (config2 = $scope.ep.containStatic(config)) {
							return config2;
						}
						return config;
					})();
				} else if (/round-list/.test($active.attr('wrap'))) {
					var config = (function() {
						var config = wrapLib.extractStaticSchema($active[0]),
							config2;
						if (config2 = $scope.ep.containStatic(config)) {
							return config2;
						}
						return config;
					})();
				}
			} else {
				$scope.hasActiveWrap = false;
				$scope.activeWrap = false;
				$scope.activeSchema = false;
			}
		};
		var ctrlSchemaEditor = ['$scope', '$modalInstance', '$timeout', 'schema', 'memberSchemas', function($scope, $mi, $timeout, schema, memberSchemas) {
			var base = {
					title: '',
					type: '',
					required: 'N',
					showname: 'label'
				},
				prefab = {
					'name': {
						title: '姓名',
						id: 'name'
					},
					'mobile': {
						title: '手机',
						id: 'mobile'
					},
					'email': {
						title: '邮箱',
						id: 'email'
					}
				},
				newSchema = function(type) {
					var id = 'c' + (new Date()).getTime(),
						schema = angular.copy(base);
					schema.type = type;
					if (prefab[type]) {
						schema.id = prefab[type].id;
						schema.title = prefab[type].title;
					} else {
						schema.id = id;
						schema.title = '新登记项';
						if (type === 'single' || type === 'multiple') {
							schema.ops = [{
								l: '选项1',
								v: 'v1'
							}, {
								l: '选项2',
								v: 'v2'
							}];
							schema.align = 'V';
							if (type === 'single') {
								schema.component = 'R';
							}
						} else if (type === 'image' || type === 'file') {
							schema.count = 1;
						}
					}
					return schema;
				};
			$scope.schema = schema ? schema : newSchema('shorttext');
			$scope.memberSchemas = memberSchemas;
			$scope.schemaHtml = function(schema) {
				var url = '/views/default/pl/fe/matter/enroll/component/' + schema.type + '.html?_=1';
				return url;
			};
			$scope.addOption = function() {
				if ($scope.schema.ops === undefined)
					$scope.schema.ops = [];
				var maxSeq = 0,
					newOp = {
						l: ''
					};
				angular.forEach($scope.schema.ops, function(op) {
					var opSeq = parseInt(op.v.substr(1));
					opSeq > maxSeq && (maxSeq = opSeq);
				});
				newOp.v = 'v' + (++maxSeq);
				$scope.schema.ops.push(newOp);
				$timeout(function() {
					$scope.$broadcast('xxt.editable.add', newOp);
				});
			};
			$scope.$on('xxt.editable.remove', function(e, op) {
				var i = $scope.schema.ops.indexOf(op);
				$scope.schema.ops.splice(i, 1);
			});
			$scope.changeType = function() {
				$scope.schema = newSchema($scope.schema.type);
			};
			$scope.selectedMemberSchema = {
				schema: null,
				attrs: null,
				attr: null
			};
			$scope.shiftMemberSchema = function() {
				var schema = $scope.selectedMemberSchema.schema,
					schemaAttrs = [];
				$scope.schema.schema_id = schema.id;
				schema.attr_name[0] === '0' && (schemaAttrs.push({
					id: 'name',
					label: '姓名'
				}));
				schema.attr_mobile[0] === '0' && (schemaAttrs.push({
					id: 'mobile',
					label: '手机'
				}));
				schema.attr_email[0] === '0' && (schemaAttrs.push({
					id: 'email',
					label: '邮箱'
				}));
				if (schema.extattr && schema.extattr.length) {
					var i, l, ea;
					for (i = 0, l = schema.extattr.length; i < l; i++) {
						ea = schema.extattr[i];
						schemaAttrs.push({
							id: 'extattr.' + ea.id,
							label: ea.label
						});
					}
				}
				$scope.selectedMemberSchema.attrs = schemaAttrs;
			};
			$scope.shiftMemberSchemaAttr = function() {
				var attr = $scope.selectedMemberSchema.attr;
				selectedMemberSchema = attr.label;
				$scope.schema.id = 'member.' + attr.id;
			};
			$scope.ok = function() {
				if ($scope.schema.title.length === 0) {
					alert('必须指定登记项的名称');
					return;
				}
				$mi.close($scope.schema);
			};
			$scope.cancel = function() {
				$mi.dismiss();
			};
			$scope.$watch('schema.setUpper', function(nv) {
				if (nv === 'Y') {
					$scope.schema.upper = $scope.schema.ops ? $scope.schema.ops.length : 0;
				}
			});
		}];
		var ctrlEmbedButton = ['$scope', '$modalInstance', 'app', 'schema', function($scope, $mi, app, schema) {
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
				removeRecord: {
					l: '删除登记'
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
				}
			};
			$scope.pages = targetPages;
			$scope.inputPages = inputPages;
			$scope.schema = schema;
			$scope.choose = function() {
				var names;
				schema.label = $scope.buttons[schema.name].l;
				schema.next = '';
				if (['addRecord', 'editRecord', 'removeRecord'].indexOf(schema.name) !== -1) {
					names = Object.keys(inputPages);
					if (names.length === 0) {
						alert('没有类型为“填写页”的页面');
					} else {
						schema.next = names[0];
					}
				}
			};
			$scope.ok = function() {
				$mi.close($scope.schema);
			};
			$scope.cancel = function() {
				$mi.dismiss();
			};
		}];
		$scope.$on('tinymce.wrap.add', function(event, wrap) {
			var root = wrap;
			while (root.parentNode) root = root.parentNode;
			$(root).find('.active').removeClass('active');
			$scope.hasActiveWrap = false;
			$scope.activeWrap = false;
			setActiveWrap(wrap);
		});
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
				while (!/text|input|radio|checkbox|static|button|record-list|round-list/.test(wrapType) && selectableWrap.parentNode) {
					selectableWrap = selectableWrap.parentNode;
					wrapType = $(selectableWrap).attr('wrap');
				}
				if (/text|input|radio|checkbox|static|button|record-list|round-list/.test(wrapType)) {
					setActiveWrap(selectableWrap);
				}
			});
		});
		$scope.$watch('activeSchema', function(nv, ov) {
			var editor;
			if (ov !== undefined) {
				editor = tinymce.get('tinymce-page');
				$active = $(editor.getBody()).find('.active');
				$active = $active[0];
				if (/input/.test($scope.activeWrap.type)) {
					var newWrap = wrapLib.modifyEmbedInput(editor, $active, nv);
					editor.save();
					setActiveWrap(newWrap);
				} else if (/button/.test($scope.activeWrap.type)) {
					wrapLib.button.modify($active, nv);
					editor.save();
				}
			}
		}, true);
		var chooseInput = function() {
			$modal.open({
				templateUrl: '/views/default/pl/fe/matter/enroll/component/chooseInput.html?_=1',
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
				var editor = tinymce.get('tinymce-page');
				angular.forEach(choosed, function(schema) {
					var dataSchemas = $scope.ep.data_schemas,
						i = 0,
						l = dataSchemas.length;
					while (i < l && schema.id !== dataSchemas[i++].id) {};
					if (i === l) {
						delete schema._selected;
						dataSchemas.push(schema);
					}
					wrapLib.embedInput(editor, schema);
				});
				editor.save();
				$scope.updPage($scope.ep, ['data_schemas', 'html']);
			});
		};
		var chooseInput4View = function() {
			$modal.open({
				templateUrl: '/views/default/pl/fe/matter/enroll/component/chooseStatic.html?_=3',
				backdrop: 'static',
				size: 'lg',
				windowClass: 'auto-height',
				resolve: {
					app: function() {
						return $scope.app;
					},
					page: function() {
						return $scope.ep;
					}
				},
				controller: ['$scope', '$modalInstance', 'app', 'page', function($scope, $mi, app, page) {
					var choosedSchemas = [],
						prefab = {
							record: {
								inline: 'Y',
								splitLine: 'Y'
							},
							'record-list': {
								inline: 'Y',
								splitLine: 'Y',
								dataScope: 'U',
								autoload: 'N',
								onclick: ''
							},
							'round-list': {
								onclick: ''
							}
						};
					$scope.data = {
						pattern: 'record'
					};
					$scope.configs = {};
					$scope.app = app;
					$scope.schemas = angular.copy(app.data_schemas);
					$scope.schemas.push({
						id: 'enrollAt',
						type: '_enrollAt',
						title: '登记时间'
					});
					$scope.$watch('data.pattern', function(pattern) {
						if (!pattern) return;
						$scope.configs = angular.copy(prefab[pattern]);
						$scope.configs.id = 's' + (new Date()).getTime();
					});
					$scope.choose = function(schema) {
						schema._selected ? choosedSchemas.push(schema) : choosedSchemas.splice(choosedSchemas.indexOf(schema), 1);
					};
					$scope.ok = function() {
						$scope.configs.pattern = $scope.data.pattern;
						$scope.configs.schemas = choosedSchemas;
						$mi.close($scope.configs);
					};
					$scope.cancel = function() {
						$mi.dismiss();
					};
				}],
			}).result.then(function(configs) {
				var pattern = configs.pattern,
					dataSchemas = $scope.ep.data_schemas,
					editor = tinymce.get('tinymce-page');
				if (configs.pattern === 'record' && configs.schemas.length) {
					var baseConfig = configs,
						schemas = configs.schemas;
					delete baseConfig.schemas;
					angular.forEach(schemas, function(schema) {
						var recordConfig = angular.copy(baseConfig);
						recordConfig.schema = schema;
						wrapLib.embedRecord(editor, recordConfig);
						dataSchemas.push(recordConfig);
					});
					editor.save();
					$scope.updPage($scope.ep, ['data_schemas', 'html']);
				} else if (configs.pattern === 'record-list' || configs.pattern === 'round-list') {
					dataSchemas.push(configs);
					if (configs.pattern === 'record-list') {
						wrapLib.embedList(editor, configs);
					} else {
						wrapLib.embedRounds(editor, configs);
					}
					editor.save();
					$scope.updPage($scope.ep, ['data_schemas', 'html']);
				}
			});
		};
		$scope.chooseInput = function() {
			if ($scope.ep.type === 'I') {
				chooseInput();
			} else if ($scope.ep.type === 'V') {
				chooseInput4View();
			}
		};
		$scope.createInput = function() {
			$modal.open({
				templateUrl: '/views/default/pl/fe/matter/enroll/component/createInput.html?_=1',
				resolve: {
					schema: function() {
						return false;
					},
					memberSchemas: function() {
						return $scope.memberSchemas;
					}
				},
				backdrop: 'static',
				size: 'lg',
				windowClass: 'auto-height',
				controller: ctrlSchemaEditor,
			}).result.then(function(schema) {
				$scope.app.data_schemas.push(schema);
				$scope.$parent.modified = true;
				$scope.update('data_schemas');
				$scope.submit().then(function() {
					$scope.ep.data_schemas.push(schema);
					$scope.updPage($scope.ep, 'data_schemas').then(function() {
						var editor = tinymce.get('tinymce-page');
						wrapLib.embedInput(editor, schema);
						editor.save();
						$scope.updPage($scope.ep, 'html');
					});
				});
			});
		};
		$scope.createAct = function() {
			$modal.open({
				templateUrl: '/views/default/pl/fe/matter/enroll/component/configAct.html?_=1',
				backdrop: 'static',
				resolve: {
					app: function() {
						return $scope.app;
					},
					schema: function() {
						return {
							name: '',
							label: '',
							next: ''
						};
					}
				},
				controller: ctrlEmbedButton,
			}).result.then(function(schema) {
				var editor = tinymce.get('tinymce-page');
				schema.id = 'act' + (new Date()).getTime();
				wrapLib.button.embed(editor, schema);
				editor.save();
				$scope.ep.act_schemas.push(schema);
				$scope.updPage($scope.ep, ['act_schemas', 'html']);
			});
		};
		$scope.modifyWrap = function() {
			var editor = tinymce.get('tinymce-page'),
				$active = $(editor.getBody()).find('.active');
			if (/button/.test($active.attr('wrap'))) {
				$modal.open({
					templateUrl: '/views/default/pl/fe/matter/enroll/component/configAct.html?_=1',
					backdrop: 'static',
					resolve: {
						app: function() {
							return $scope.app;
						},
						schema: function() {
							var schema, schema2;
							schema = wrapLib.button.extract($active[0]);
							if (schema2 = $scope.ep.containAct(schema)) {
								return schema2;
							}
							return schema;
						}
					},
					controller: ctrlEmbedButton,
				}).result.then(function(schema) {
					wrapLib.button.modify($active[0], schema);
					editor.save();
					$scope.updPage($scope.ep, ['act_schemas', 'html']);
				});
			} else if (/input/.test($active.attr('wrap'))) {
				$modal.open({
					templateUrl: '/views/default/pl/fe/matter/enroll/component/modifyInput.html?_=1',
					backdrop: 'static',
					size: 'lg',
					windowClass: 'auto-height',
					resolve: {
						memberSchemas: function() {
							return $scope.memberSchemas;
						},
						schema: function() {
							var schema = wrapLib.extractInputSchema($active[0]);
							for (var i = 0, l = $scope.ep.data_schemas.length; i < l; i++) {
								if (schema.id === $scope.ep.data_schemas[i].id) {
									schema = $scope.ep.data_schemas[i];
									break;
								}
							}
							return schema;
						}
					},
					controller: ctrlSchemaEditor,
				}).result.then(function(schema) {
					$scope.$parent.modified = true;
					$scope.update('data_schemas');
					$scope.submit().then(function() {
						$scope.updPage($scope.ep, 'data_schemas').then(function() {
							var newWrap = wrapLib.modifyEmbedInput(editor, $active[0], schema);
							editor.save();
							setActiveWrap(newWrap);
						});
					});
				});
			} else if (/static/.test($active.attr('wrap'))) {
				$modal.open({
					templateUrl: '/views/default/pl/fe/matter/enroll/component/modifyStatic.html?_=1',
					backdrop: 'static',
					resolve: {
						app: function() {
							return $scope.app;
						},
						config: function() {
							var config = wrapLib.extractStaticSchema($active[0]),
								config2;
							if (config2 = $scope.ep.containStatic(config)) {
								return config2;
							}
							return config;
						}
					},
					controller: ['$scope', '$modalInstance', 'app', 'config', function($scope, $mi, app, config) {
						$scope.config = config;
						$scope.schemas = angular.copy(app.data_schemas);
						$scope.ok = function() {
							$mi.close($scope.config);
						};
						$scope.cancel = function() {
							$mi.dismiss();
						};
					}]
				}).result.then(function(config) {
					var editor = tinymce.get('tinymce-page'),
						$active = $(editor.getBody()).find('.active');
					wrapLib.changeEmbedStatic(editor, $active[0], config);
					editor.save();
					$scope.updPage($scope.ep, ['data_schemas', 'html']);
				});
			} else if (/record-list/.test($active.attr('wrap'))) {
				$modal.open({
					templateUrl: '/views/default/pl/fe/matter/enroll/component/modifyRecordList.html?_=1',
					backdrop: 'static',
					size: 'lg',
					resolve: {
						app: function() {
							return $scope.app;
						},
						config: function() {
							var config = wrapLib.extractStaticSchema($active[0]),
								config2;
							if (config2 = $scope.ep.containStatic(config)) {
								return config2;
							}
							return config;
						}
					},
					controller: ['$scope', '$modalInstance', 'app', 'config', function($scope, $mi, app, config) {
						var choosedSchemas = [];
						$scope.config = config;
						$scope.app = app;
						$scope.schemas = angular.copy(app.data_schemas);
						angular.forEach(config.schemas, function(schema) {
							for (var i = 0, l = $scope.schemas.length; i < l; i++) {
								if (schema.id === $scope.schemas[i].id) {
									$scope.schemas[i]._selected = true;
									choosedSchemas.push($scope.schemas[i]);
									break;
								}
							}
						});
						$scope.choose = function(schema) {
							schema._selected ? choosedSchemas.push(schema) : choosedSchemas.splice(choosedSchemas.indexOf(schema), 1);
						};
						$scope.ok = function() {
							angular.forEach(choosedSchemas, function(schema) {
								delete schema._selected;
							});
							$scope.config.schemas = choosedSchemas;
							$mi.close($scope.config);
						};
						$scope.cancel = function() {
							$mi.dismiss();
						};
					}]
				}).result.then(function(config) {
					var editor = tinymce.get('tinymce-page'),
						$active = $(editor.getBody()).find('.active'),
						newWrap;
					config.pattern = 'record-list';
					newWrap = wrapLib.embedList(editor, config);
					$active.remove();
					setActiveWrap(newWrap);
					editor.save();
					$scope.updPage($scope.ep, ['data_schemas', 'html']);
				});
			} else if (/round-list/.test($active.attr('wrap'))) {
				$modal.open({
					templateUrl: '/views/default/pl/fe/matter/enroll/component/modifyRoundList.html?_=1',
					backdrop: 'static',
					resolve: {
						app: function() {
							return $scope.app;
						},
						config: function() {
							var config = wrapLib.extractStaticSchema($active[0]),
								config2;
							if (config2 = $scope.ep.containStatic(config)) {
								return config2;
							}
							return config;
						}
					},
					controller: ['$scope', '$modalInstance', 'app', 'config', function($scope, $mi, app, config) {
						var choosedSchemas = [];
						$scope.config = config;
						$scope.app = app;
						$scope.ok = function() {
							$mi.close($scope.config);
						};
						$scope.cancel = function() {
							$mi.dismiss();
						};
					}]
				}).result.then(function(config) {
					var editor = tinymce.get('tinymce-page'),
						$active = $(editor.getBody()).find('.active'),
						newWrap;
					config.pattern = 'round-list';
					newWrap = wrapLib.embedRounds(editor, config);
					$active.remove();
					setActiveWrap(newWrap);
					editor.save();
					$scope.updPage($scope.ep, ['data_schemas', 'html']);
				});
			}
		};
		/*查找包含指定登记项的页面*/
		var pagesHasSchema = function(schema) {
			var pages = [];
			angular.forEach($scope.app.pages, function(page) {
				page.containInput(schema) && pages.push(page);
			});
			return pages;
		};
		$scope.removeWrap = function() {
			var schema, config,
				editor = tinymce.get('tinymce-page'),
				$active = $(editor.getBody()).find('.active'),
				wrapType = $active.attr('wrap');
			if (/input/.test(wrapType)) {
				schema = wrapLib.extractInputSchema($active[0]);
				/*从页面中删除，从页面的schema中删除*/
				$scope.ep.removeInput(schema);
				$active.remove();
				editor.save();
				setActiveWrap(null);
				$scope.updPage($scope.ep, ['data_schemas', 'html']);
				/*如果没有页面包含指定的登记项，从应用的schema中删除*/
				if (pagesHasSchema(schema) === false) {}
			} else if (/button/.test(wrapType)) {
				schema = wrapLib.button.extract($active[0]);
				$scope.ep.removeAct(schema);
				$active.remove();
				editor.save();
				setActiveWrap(null);
				$scope.updPage($scope.ep, ['act_schemas', 'html']);
			} else if (/static/.test(wrapType)) {
				config = wrapLib.extractStaticSchema($active[0]);
				if (config.id || config.schema) {
					if (config.id) {
						$scope.ep.removeStatic(config);
					} else {
						$parent = $active.parents('[wrap]');
						if ($parent.length) {
							var config2 = wrapLib.extractStaticSchema($parent[0]);
							config2.schema = config.schema;
							$scope.ep.removeStatic(config2);
						}
					}
					$active.remove();
					editor.save();
					setActiveWrap(null);
					$scope.updPage($scope.ep, ['data_schemas', 'html']);
				}
			} else if (/record-list|round-list/.test(wrapType)) {
				config = wrapLib.extractStaticSchema($active[0]);
				$scope.ep.removeStatic(config);
				$active.remove();
				editor.save();
				setActiveWrap(null);
				$scope.updPage($scope.ep, ['data_schemas', 'html']);
			} else if (/text/.test(wrapType)) {
				$active.remove();
				editor.save();
				setActiveWrap(null);
				$scope.updPage($scope.ep, ['html']);
			}
		};
		$scope.upWrap = function(page) {
			var editor = tinymce.get('tinymce-page'),
				active = $(editor.getBody()).find('.active');
			active.prev().before(active);
			editor.save();
		};
		$scope.downWrap = function(page) {
			var editor = tinymce.get('tinymce-page'),
				active = $(editor.getBody()).find('.active');
			active.next().after(active);
			editor.save();
		};
		$scope.upLevel = function(page) {
			var editor = tinymce.get('tinymce-page'),
				$active = $(editor.getBody()).find('.active'),
				$parent = $active.parents('[wrap]');
			if ($parent.length) {
				$active.removeClass('active');
				setActiveWrap($parent[0]);
			}
		};
		$scope.downLevel = function(page) {
			var editor = tinymce.get('tinymce-page'),
				$active = $(editor.getBody()).find('.active'),
				$children = $active.find('[wrap]');
			if ($children.length) {
				$active.removeClass('active');
				setActiveWrap($children[0]);
			}
		};
		$scope.embedMatter = function(page) {
			mattersgallery.open($scope.siteId, function(matters, type) {
				var editor = tinymce.get('tinymce-page'),
					dom, mtype, fn;
				dom = editor.dom;
				angular.forEach(matters, function(matter) {
					fn = "openMatter(" + matter.id + ",'" + mtype + "')";
					editor.insertContent(dom.createHTML('div', {
						'wrap': 'link',
						'class': 'matter-link'
					}, dom.createHTML('a', {
						href: 'javascript:void(0)',
						"ng-click": fn,
					}, dom.encode(matter.title))));
				});
			}, {
				matterTypes: $scope.innerlinkTypes,
				hasParent: false,
				singleMatter: true
			});
		};
		$scope.gotoCode = function() {
			window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + $scope.ep.code_name, '_self');
		};
		$scope.onPageChange = function() {
			$scope.ep.$$modified = true;
		};
		$scope.addPage = function() {
			$modal.open({
				templateUrl: '/views/default/pl/fe/matter/enroll/component/createPage.html?_=1',
				backdrop: 'static',
				controller: ['$scope', '$modalInstance', function($scope, $mi) {
					$scope.options = {};
					$scope.ok = function() {
						$mi.close($scope.options);
					};
					$scope.cancel = function() {
						$mi.dismiss();
					};
				}],
			}).result.then(function(options) {
				http2.post('/rest/pl/fe/matter/enroll/page/add?site=' + $scope.siteId + '&app=' + $scope.id, options, function(rsp) {
					var page = rsp.data;
					$scope.app.pages.push(page);
					$scope.choosePage(page);
				});
			});
		};
		$scope.updPage = function(page, names) {
			var editor, defer = $q.defer(),
				url, p = {};
			angular.isString(names) && (names = [names]);
			if (names.indexOf('html') !== -1) {
				editor = tinymce.get('tinymce-page');
				if ($(editor.getBody()).find('.active').length) {
					$(editor.getBody()).find('.active').removeClass('active');
					$scope.hasActiveWrap = false;
				}
				page.html = $(editor.getBody()).html();
			}
			$scope.$root.progmsg = '正在保存页面...';
			angular.forEach(names, function(name) {
				p[name] = name === 'html' ? encodeURIComponent(page[name]) : page[name];
			});
			url = '/rest/pl/fe/matter/enroll/page/update';
			url += '?site=' + $scope.siteId;
			url += '&app=' + $scope.id;
			url += '&pid=' + page.id;
			url += '&cname=' + page.code_name;
			http2.post(url, p, function(rsp) {
				page.$$modified = false;
				$scope.$root.progmsg = '';
				defer.resolve();
			});
			return defer.promise;
		};
		$scope.delPage = function() {
			if (window.confirm('确定删除？')) {
				var url = '/rest/pl/fe/matter/enroll/page/remove';
				url += '?site=' + $scope.siteId;
				url += '&app=' + $scope.id;
				url += '&pid=' + $scope.ep.id;
				url += '&cname=' + $scope.ep.code_name;
				http2.get(url, function(rsp) {
					$scope.app.pages.splice($scope.app.pages.indexOf($scope.ep), 1);
					if ($scope.app.pages.length) {
						$scope.choosePage($scope.app.pages[0]);
					} else {
						$scope.ep = null;
					}
				});
			}
		};
		$scope.gotoPageConfig = function() {
			location = '/rest/pl/fe/matter/enroll/page?site=' + $scope.siteId + '&id=' + $scope.id + '&page=' + $scope.ep.name;
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