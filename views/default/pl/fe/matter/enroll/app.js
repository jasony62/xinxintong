(function() {
	/**
	 * app
	 */
	ngApp.provider.controller('ctrlApp', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
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
		$scope.$watch('app', function(app) {
			if (!app) return;
			$scope.ep = app.pages[0];
		});
		$scope.choosePage = function(page) {
			if (angular.isString(page)) {
				for (var i = $scope.app.pages.length - 1; i >= 0; i--) {
					if ($scope.app.pages[i].name === page) {
						page = $scope.app.pages[i];
						break;
					}
				}
			}
			$scope.ep = page;
			tinymce.activeEditor.setContent(page.html);
		};
		var base = {
				title: '',
				type: '',
				comment: '',
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
			createSchema = function(type) {
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
		$scope.newSchema = function(type) {
			var newSchema = createSchema(type);
			$scope.app.data_schemas.push(newSchema);
			$scope.update('data_schemas').then(function() {
				$scope.$broadcast('xxt.matter.enroll.app.data_schemas.created', newSchema);
			});
		};
		$scope.newMember = function() {
			var newSchema = createSchema('member');
			$modal.open({
				templateUrl: 'memberSchema.html',
				resolve: {
					memberSchemas: function() {
						return $scope.memberSchemas;
					}
				},
				controller: ['$scope', '$modalInstance', 'memberSchemas', function($scope2, $mi, memberSchemas) {
					$scope2.memberSchemas = memberSchemas;
					$scope2.schema = newSchema;
					$scope2.data = {};
					$scope2.shiftSchema = function() {
						var memberSchema = $scope2.data.memberSchema,
							attrs = [];
						$scope2.schema.schema_id = memberSchema.id;
						memberSchema.attr_name[0] === '0' && (attrs.push({
							id: 'name',
							label: '姓名'
						}));
						memberSchema.attr_mobile[0] === '0' && (attrs.push({
							id: 'mobile',
							label: '手机'
						}));
						memberSchema.attr_email[0] === '0' && (attrs.push({
							id: 'email',
							label: '邮箱'
						}));
						if (memberSchema.extattr && memberSchema.extattr.length) {
							angular.forEach(memberSchema.extattr, function(ea) {
								attrs.push({
									id: 'extattr.' + ea.id,
									label: ea.label
								});
							});
						}
						$scope2.data.attrs = attrs;
						if (attrs.length) {
							$scope2.data.attr = attrs[0];
							$scope2.shiftAttr();
						}
					};
					$scope2.shiftAttr = function() {
						var attr = $scope2.data.attr;
						$scope2.schema.title = attr.label;
						$scope2.schema.id = 'member.' + attr.id;
					};
					$scope2.cancel = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.close($scope2.schema);
					};
				}],
				backdrop: 'static'
			}).result.then(function(newSchema) {
				$scope.app.data_schemas.push(newSchema);
				$scope.$broadcast('xxt.matter.enroll.app.data_schemas.created', newSchema);
			});
		};
	}]);
	/**
	 * 在当前编辑页面中选择应用的登记项
	 */
	ngApp.provider.controller('ctrlChooseAppSchema', ['$scope', function($scope) {
		var pageSchemas = $scope.ep.data_schemas,
			appSchemas = $scope.app.data_schemas,
			chooseState = {};
		angular.forEach(pageSchemas, function(schema) {
			chooseState[schema.id] = true;
		});
		$scope.appSchemas = appSchemas;
		$scope.chooseState = chooseState;
		$scope.choose = function(schema) {
			if (chooseState[schema.id]) {
				$scope.$emit('xxt.matter.enroll.page.data_schemas.requestAdd', schema);
			} else {
				$scope.$emit('xxt.matter.enroll.page.data_schemas.requestRemove', schema);
			}
		};
		$scope.remove = function(removedSchema) {
			if (window.confirm('确定删除所有页面上的登记项？')) {
				$scope.$emit('xxt.matter.enroll.app.data_schemas.requestRemove', removedSchema);
			}
		};
		$scope.$on('xxt.matter.enroll.page.data_schemas.add', function(event, newSchema) {
			chooseState[newSchema.id] = true;
		});
		$scope.$on('xxt.matter.enroll.page.data_schemas.removed', function(event, removedSchema, target) {
			chooseState[removedSchema.id] = false;
			if (target === 'app') {
				/*从应用的定义中删除*/
				appSchemas.splice(appSchemas.indexOf(removedSchema), 1);
				$scope.update('data_schemas').then(function() {});
			}
		});
		$scope.$on('xxt.matter.enroll.page.data_schemas.added', function(event, addedSchema, target) {
			chooseState[addedSchema.id] = true;
		});
	}]);
	/**
	 * input wrap
	 */
	ngApp.provider.controller('ctrlInputWrap', ['$scope', '$timeout', function($scope, $timeout) {
		$scope.schema = $scope.activeSchema;
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
		$scope.shiftMemberSchema = function() {
			var memberSchema = $scope.selectedMemberSchema.schema,
				schemaAttrs = [];
			$scope.schema.schema_id = memberSchema.id;
			/*自定义用户属性列表*/
			memberSchema.attr_name[0] === '0' && (schemaAttrs.push({
				id: 'name',
				label: '姓名'
			}));
			memberSchema.attr_mobile[0] === '0' && (schemaAttrs.push({
				id: 'mobile',
				label: '手机'
			}));
			memberSchema.attr_email[0] === '0' && (schemaAttrs.push({
				id: 'email',
				label: '邮箱'
			}));
			if (memberSchema.extattr && memberSchema.extattr.length) {
				var i, l, ea;
				for (i = 0, l = memberSchema.extattr.length; i < l; i++) {
					ea = memberSchema.extattr[i];
					schemaAttrs.push({
						id: 'extattr.' + ea.id,
						label: ea.label
					});
				}
			}
			$scope.selectedMemberSchema.attrs = schemaAttrs;
			$scope.selectedMemberSchema.attr = null;
		};
		$scope.shiftMemberSchemaAttr = function() {
			var attr = $scope.selectedMemberSchema.attr;
			selectedMemberSchema = attr.label;
			$scope.schema.id = 'member.' + attr.id;
			$scope.schema.title = attr.label;
		};
		$scope.$watch('schema.setUpper', function(nv) {
			if (nv === 'Y') {
				$scope.schema.upper = $scope.schema.ops ? $scope.schema.ops.length : 0;
			}
		});
		if ($scope.schema.type === 'member') {
			if ($scope.schema.schema_id) {
				/*自定义用户*/
				for (var i = $scope.memberSchemas.length - 1; i >= 0; i--) {
					if ($scope.schema.schema_id === $scope.memberSchemas[i].id) {
						$scope.selectedMemberSchema = {
							schema: $scope.memberSchemas[i]
						};
						break;
					}
				}
				$scope.selectedMemberSchema.schema && $scope.shiftMemberSchema();
				/*自定义用户属性*/
				var id = $scope.schema.id.substr(7);
				for (var i = $scope.selectedMemberSchema.attrs.length - 1; i >= 0; i--) {
					if (id === $scope.selectedMemberSchema.attrs[i].id) {
						$scope.selectedMemberSchema.attr = $scope.selectedMemberSchema.attrs[i];
						break;
					}
				}
			} else {
				$scope.selectedMemberSchema = {
					schema: null,
					attrs: null,
					attr: null
				};
			}
		}
	}]);
	/**
	 * static wrap
	 */
	ngApp.provider.controller('ctrlStaticWrap', ['$scope', function($scope) {
		var pageSchemas = $scope.ep.data_schemas,
			chooseState = {};
		$scope.appSchemas = $scope.app.data_schemas;
		$scope.otherSchemas = [{
			id: 'enrollAt',
			type: '_enrollAt',
			title: '登记时间'
		}];
		angular.forEach(pageSchemas, function(config) {
			chooseState[config.schema.id] = true;
		});
		$scope.chooseState = chooseState;
		$scope.choose = function(schema) {
			var editor = tinymce.get('tinymce-page');
			if (chooseState[schema.id]) {
				var newConfig = {
					id: 'V' + (new Date()).getTime(),
					inline: 'Y',
					splitLine: 'Y',
					pattern: 'record'
				};
				newConfig.schema = schema;
				wrapLib.record.embed(editor, newConfig);
				pageSchemas.push(newConfig);
				$scope.updPage($scope.ep, ['data_schemas', 'html']);
			} else {
				for (var i = pageSchemas.length - 1; i >= 0; i--) {
					if (schema.id === pageSchemas[i].schema.id) {
						$(editor.getBody()).find('#' + pageSchemas[i].id).remove();
						pageSchemas.splice(i, 1);
						$scope.updPage($scope.ep, ['data_schemas', 'html']);
						break;
					}
				}
			}
		};
	}]);
	/**
	 * record list wrap
	 */
	ngApp.provider.controller('ctrlRecordListWrap', ['$scope', function($scope) {
		var listSchemas = $scope.activeSchema.schemas,
			chooseState = {};
		$scope.appSchemas = $scope.app.data_schemas;
		$scope.otherSchemas = [{
			id: 'enrollAt',
			type: '_enrollAt',
			title: '登记时间'
		}];
		angular.forEach(listSchemas, function(schema) {
			chooseState[schema.id] = true;
		});
		$scope.chooseState = chooseState;
		/* 在处理activeSchema中提交 */
		$scope.choose = function(schema) {
			if (chooseState[schema.id]) {
				listSchemas.push(schema);
			} else {
				for (var i = listSchemas.length - 1; i >= 0; i--) {
					if (schema.id === listSchemas[i].id) {
						listSchemas.splice(i, 1);
						break;
					}
				}
			}
		};
	}]);
	/**
	 * round list wrap
	 */
	ngApp.provider.controller('ctrlRoundListWrap', ['$scope', function($scope) {
		$scope.app = app;
	}]);
	/**
	 * button wrap
	 */
	ngApp.provider.controller('ctrlButtonWrap', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
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
		$scope.newPage = function(prop) {
			$modal.open({
				templateUrl: '/views/default/pl/fe/matter/enroll/component/createPage.html?_=2',
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
					schema[prop] = page.name;
					$scope.app.pages.push(page);
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
		};
	}]);
	/**
	 * page
	 */
	ngApp.provider.controller('ctrlPage', ['$scope', '$q', '$timeout', '$modal', 'http2', 'mediagallery', 'mattersgallery', function($scope, $q, $timeout, $modal, http2, mediagallery, mattersgallery) {
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
			var wrapType, editor;
			if ($scope.activeWrap) {
				editor = tinymce.get('tinymce-page');
				$(editor.getBody()).find('.active').removeClass('active');
			}
			if (wrap) {
				wrapType = $(wrap).attr('wrap');
				$scope.hasActiveWrap = true;
				$scope.activeWrap = {
					type: wrapType,
					editable: true,
					upmost: /body/i.test(wrap.parentNode.tagName),
					downmost: /button|static|radio|checkbox/.test(wrapType),
				};
				wrap.classList.add('active');
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
						var schema = wrapLib.input.extract(wrap),
							pageSchema;
						for (var i = $scope.ep.data_schemas.length - 1; i >= 0; i--) {
							pageSchema = $scope.ep.data_schemas[i];
							if (schema.id === pageSchema.id) {
								schema = pageSchema;
								break;
							}
						}
						return schema;
					})();
				} else if (/static/.test(wrapType)) {
					$scope.activeSchema = (function() {
						var config = wrapLib.extractStaticSchema(wrap),
							config2;
						if (config2 = $scope.ep.containStatic(config)) {
							return config2;
						}
						return config;
					})();
				} else if (/record-list/.test(wrapType)) {
					$scope.activeSchema = (function() {
						var config = wrapLib.extractStaticSchema(wrap),
							config2;
						if (config2 = $scope.ep.containList(config)) {
							return config2;
						}
						return config;
					})();
				} else if (/round-list/.test(wrapType)) {
					var config = (function() {
						var config = wrapLib.extractStaticSchema(wrap),
							config2;
						if (config2 = $scope.ep.containList(config)) {
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
		$scope.$on('tinymce.wrap.add', function(event, wrap) {
			var root = wrap;
			while (root.parentNode) root = root.parentNode;
			$(root).find('.active').removeClass('active');
			setActiveWrap(wrap);
		});
		$scope.$on('tinymce.wrap.select', function(event, wrap) {
			$scope.$apply(function() {
				var root = wrap,
					selectableWrap = wrap,
					wrapType;
				while (root.parentNode) root = root.parentNode;
				$(root).find('.active').removeClass('active');
				setActiveWrap(null);
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
		var timerOfUpdate = null;
		$scope.$watch('activeSchema', function(nv, ov) {
			var editor, newWrap, current;
			if (ov && ov.id === nv.id) {
				editor = tinymce.get('tinymce-page');
				$active = $(editor.getBody()).find('.active');
				$active = $active[0];
				if (/input/.test($scope.activeWrap.type)) {
					newWrap = wrapLib.input.modify(editor, $active, nv);
					setActiveWrap(newWrap);
					if (timerOfUpdate !== null) {
						$timeout.cancel(timerOfUpdate);
					}
					timerOfUpdate = $timeout(function() {
						/* 更新应用的定义 */
						$scope.update('data_schemas').then(function() {
							/* 更新当前页面 */
							$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
								setActiveWrap(newWrap);
							});
							/* 更新其它页面 */
							angular.forEach($scope.app.pages, function(page) {
								if (page !== $scope.ep) {
									page.updateBySchema(nv);
									$scope.updPage(page, ['data_schemas', 'html']);
								}
							});
						});
					}, 1000);
					timerOfUpdate.then(function() {
						timerOfUpdate = null;
					});
				} else if (/button/.test($scope.activeWrap.type)) {
					wrapLib.button.modify($active, nv);
					if (timerOfUpdate !== null) {
						$timeout.cancel(timerOfUpdate);
					}
					timerOfUpdate = $timeout(function() {
						/* 更新当前页面 */
						$scope.updPage($scope.ep, ['act_schemas', 'html']).then(function() {
							setActiveWrap($active);
						});
					}, 1000);
					timerOfUpdate.then(function() {
						timerOfUpdate = null;
					});
				} else if (/static/.test($scope.activeWrap.type)) {
					wrapLib.record.modify(editor, $active, nv);
					$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
						setActiveWrap($active);
					});
				} else if (/record-list/.test($scope.activeWrap.type)) {
					newWrap = wrapLib.embedList(editor, nv);
					$active.remove();
					setActiveWrap(newWrap);
					if (timerOfUpdate !== null) {
						$timeout.cancel(timerOfUpdate);
					}
					timerOfUpdate = $timeout(function() {
						/* 更新当前页面 */
						$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
							setActiveWrap($active);
						});
					}, 1000);
					timerOfUpdate.then(function() {
						timerOfUpdate = null;
					});
				} else if (/round-list/.test($scope.activeWrap.type)) {
					newWrap = wrapLib.embedRounds(editor, nv);
					$active.remove();
					setActiveWrap(newWrap);
				}
			}
		}, true);
		$scope.wrapEditorHtml = function() {
			var url = '/views/default/pl/fe/matter/enroll/wrap/' + $scope.activeWrap.type + '.html?_=5';
			return url;
		};
		var addInputSchema = function(addedSchema) {
			var deferred = $q.defer(),
				pageSchemas = $scope.ep.data_schemas,
				editor = tinymce.get('tinymce-page'),
				newWrap;

			/* 在当前页面上添加新登记项 */
			newWrap = wrapLib.input.embed(editor, addedSchema);
			pageSchemas.push(addedSchema);
			/* 更新后台数据 */
			$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
				setActiveWrap(newWrap);
				deferred.resolve();
			});

			return deferred.promise;
		};
		$scope.$on('xxt.matter.enroll.app.data_schemas.created', function(event, newSchema) {
			var editor, newWrap, viewPages = [];
			if ($scope.ep.type === 'I') {
				addInputSchema(newSchema).then(function() {
					$scope.$broadcast('xxt.matter.enroll.page.data_schemas.added', newSchema, 'app');
				});
			}
			angular.forEach($scope.app.pages, function(page) {
				if (page.type === 'V') {
					viewPages.push(page);
				}
			});
			if (viewPages.length === 1) {
				var page = viewPages[0],
					pageSchemas = page.data_schemas,
					newConfig = {
						id: 'V' + (new Date()).getTime(),
						inline: 'Y',
						splitLine: 'Y',
						pattern: 'record',
						schema: newSchema
					};
				/* 更新内存的数据 */
				page.appendRecord(newConfig);
				pageSchemas.push(newConfig);
				/* 更新后台数据 */
				$scope.updPage(page, ['data_schemas', 'html']).then(function() {});
			}
		});
		$scope.$on('xxt.matter.enroll.page.data_schemas.requestAdd', function(event, addedSchema) {
			addInputSchema(addedSchema).then(function() {
				$scope.$broadcast('xxt.matter.enroll.page.data_schemas.added', addedSchema, 'page');
			});
		});
		var removeSchema = function(removedSchema) {
			var deferred = $q.defer(),
				pageSchemas = $scope.ep.data_schemas,
				i, editor, $input;

			for (i = pageSchemas.length - 1; i >= 0; i--) {
				if (removedSchema.id === pageSchemas[i].id) {
					editor = tinymce.get('tinymce-page');
					$input = $(editor.getBody()).find("[ng-model='data." + removedSchema.id + "']");
					$input.parents('[wrap=input]').remove();
					pageSchemas.splice(i, 1);
					$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
						if (removedSchema.id === $scope.activeSchema.id) {
							setActiveWrap(null);
						}
						deferred.resolve(removedSchema);
					});
					break;
				}
			}
			/*页面上没有要删除的登记项*/
			if (i === -1) {
				deferred.resolve(removedSchema);
			}

			return deferred.promise;
		};
		$scope.$on('xxt.matter.enroll.app.data_schemas.requestRemove', function(event, removedSchema) {
			removeSchema(removedSchema).then(function() {
				/*更新其它页面。*/
				angular.forEach($scope.app.pages, function(page) {
					if (page !== $scope.ep) {
						page.removeBySchema(removedSchema);
						$scope.updPage(page, ['data_schemas', 'html']);
					}
				});
				/* 通知应用删除登记项 */
				$scope.$broadcast('xxt.matter.enroll.page.data_schemas.removed', removedSchema, 'app');
			});
		});
		$scope.$on('xxt.matter.enroll.page.data_schemas.requestRemove', function(event, removedSchema) {
			removeSchema(removedSchema).then(function() {
				$scope.$broadcast('xxt.matter.enroll.page.data_schemas.removed', removedSchema, 'page');
			});
		});
		$scope.addButton = function() {
			var editor = tinymce.get('tinymce-page'),
				schema = {
					id: 'act' + (new Date()).getTime(),
					name: 'closeWindow',
					label: '关闭页面',
					next: ''
				},
				newWrap;
			schema.id = 'act' + (new Date()).getTime();
			newWrap = wrapLib.button.embed(editor, schema);
			$scope.ep.act_schemas.push(schema);
			$scope.updPage($scope.ep, ['act_schemas', 'html']).then(function() {
				setActiveWrap(newWrap);
			});
		};
		$scope.addRecordList = function() {
			var editor = tinymce.get('tinymce-page'),
				dataSchemas = $scope.ep.data_schemas,
				configs = {
					id: 'L' + (new Date()).getTime(),
					pattern: 'record-list',
					inline: 'Y',
					splitLine: 'Y',
					dataScope: 'U',
					autoload: 'N',
					onclick: '',
					schemas: angular.copy($scope.app.data_schemas)
				},
				newWrap;
			configs.schemas.push({
				id: 'enrollAt',
				type: '_enrollAt',
				title: '登记时间'
			});
			dataSchemas.push(configs);
			newWrap = wrapLib.embedList(editor, configs);
			$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
				setActiveWrap(newWrap);
			});
		};
		$scope.addRoundList = function() {
			var editor = tinymce.get('tinymce-page'),
				dataSchemas = $scope.ep.data_schemas,
				configs = {
					id: 'L' + (new Date()).getTime(),
					pattern: 'round-list',
					onclick: ''
				},
				newWrap;
			dataSchemas.push(configs);
			newWrap = wrapLib.embedRounds(editor, configs);
			$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
				setActiveWrap(newWrap);
			});
		};
		$scope.removeWrap = function() {
			var schema, config,
				editor = tinymce.get('tinymce-page'),
				$active = $(editor.getBody()).find('.active'),
				wrapType = $active.attr('wrap');
			if (/input/.test(wrapType)) {
				removeSchema($scope.activeSchema).then(function(removedSchema) {
					$scope.$broadcast('xxt.matter.enroll.page.data_schemas.removed', removedSchema, 'page');
				});
			} else if (/button/.test(wrapType)) {
				$scope.ep.removeAct($scope.activeSchema);
				$active.remove();
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
					$scope.updPage($scope.ep, ['data_schemas', 'html']);
				}
			} else if (/record-list|round-list/.test(wrapType)) {
				config = wrapLib.extractStaticSchema($active[0]);
				$scope.ep.removeStatic(config);
				$active.remove();
				editor.save();
				$scope.updPage($scope.ep, ['data_schemas', 'html']);
			} else if (/text/.test(wrapType)) {
				$active.remove();
				setActiveWrap(null);
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
			$scope.createPage().then(function(page) {
				$scope.choosePage(page);
			});
		};
		$scope.updPage = function(page, names) {
			var editor, defer = $q.defer(),
				url, p = {};
			angular.isString(names) && (names = [names]);
			if (page === $scope.ep && names.indexOf('html') !== -1) {
				setActiveWrap(null);
				editor = tinymce.get('tinymce-page');
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
		$scope.$watch('ep', function() {
			setActiveWrap(null);
		});
	}]);
})();