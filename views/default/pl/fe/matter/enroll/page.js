define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
	/**
	 * app setting controller
	 */
	ngApp.provider.controller('ctrlPage', ['$scope', 'srvPage', function($scope, srvPage) {
		window.onbeforeunload = function(e) {
			var message;
			if ($scope.ep.$$modified) {
				message = '已经修改的页面还没有保存，确定离开？';
				e = e || window.event;
				if (e) {
					e.returnValue = message;
				}
				return message;
			}
		};
		$scope.publish = function() {
			$scope.app.state = 2;
			if ($scope.ep.$$modified) {
				$scope.updPage($scope.ep, ['data_schemas', 'act_schemas', 'html']).then(function() {
					$scope.update('state').then(function() {
						location.href = '/rest/pl/fe/matter/enroll/publish?site=' + $scope.siteId + '&id=' + $scope.id;
					});
				});
			} else {
				$scope.update('state').then(function() {
					location.href = '/rest/pl/fe/matter/enroll/publish?site=' + $scope.siteId + '&id=' + $scope.id;
				});
			}
		};
		$scope.addPage = function() {
			$scope.createPage().then(function(page) {
				$scope.choosePage(page);
			});
		};
		$scope.updPage = function(page, names) {
			return srvPage.update(page, names);
		};
		$scope.delPage = function() {
			if (window.confirm('确定删除页面？')) {
				srvPage.remove($scope.ep).then(function() {
					$scope.app.pages.splice($scope.app.pages.indexOf($scope.ep), 1);
					if ($scope.app.pages.length) {
						$scope.choosePage($scope.app.pages[0]);
					} else {
						$scope.ep = null;
					}
				});
			}
		};
		$scope.choosePage = function(page) {
			if (angular.isString(page)) {
				for (var i = $scope.app.pages.length - 1; i >= 0; i--) {
					if ($scope.app.pages[i].name === page) {
						page = $scope.app.pages[i];
						break;
					}
				}
				if (i === -1) return;
			}
			$scope.ep = page;
		};
		$scope.cleanPage = function() {
			$scope.ep.html = '';
			$scope.ep.data_schemas = [];
			$scope.ep.act_schemas = [];
			srvPage.update($scope.ep, ['data_schemas', 'act_schemas', 'html']);
		};
		$scope.newSchema = function(type) {
			var i, newSchema, mission;
			if (type === 'phase') {
				mission = $scope.app.mission;
				if (!mission || !mission.phases || mission.phases.length === 0) {
					alert('请先指定项目的阶段');
					return;
				}
			}
			newSchema = schemaLib.newSchema(type, $scope.app);
			for (i = $scope.app.data_schemas.length - 1; i >= 0; i--) {
				if (newSchema.id === $scope.app.data_schemas[i].id) {
					alert('不允许重复添加登记项');
					return;
				}
			}
			$scope.app.data_schemas.push(newSchema);
			$scope.update('data_schemas').then(function() {
				$scope.$broadcast('xxt.matter.enroll.app.data_schemas.created', newSchema);
			});
		};
		$scope.newMember = function(ms, schema) {
			var newSchema = schemaLib.newSchema('member');

			newSchema.schema_id = ms.id;
			newSchema.id = schema.id;
			newSchema.title = schema.title;

			for (i = $scope.app.data_schemas.length - 1; i >= 0; i--) {
				if (newSchema.id === $scope.app.data_schemas[i].id) {
					alert('不允许重复添加登记项');
					return;
				}
			}

			$scope.app.data_schemas.push(newSchema);
			$scope.update('data_schemas').then(function() {
				$scope.$broadcast('xxt.matter.enroll.app.data_schemas.created', newSchema);
			});
		};
		$scope.copySchema = function(schema) {
			var newSchema = angular.copy(schema);
			newSchema.id = 'c' + (new Date() * 1);
			$scope.app.data_schemas.push(newSchema);
			$scope.update('data_schemas').then(function() {
				$scope.$broadcast('xxt.matter.enroll.app.data_schemas.created', newSchema);
			});
		};
		/*初始化页面数据*/
		$scope.$watch('app', function(app) {
			if (!app) return;
			$scope.ep = app.pages[0];
		});
	}]);
	/**
	 * page
	 */
	ngApp.provider.controller('ctrlEditor', ['$scope', '$q', '$timeout', 'mediagallery', 'mattersgallery', function($scope, $q, $timeout, mediagallery, mattersgallery) {
		var tinymceEditor;
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
		$scope.buttons = schemaLib.buttons;
		$scope.setActiveWrap = function(domWrap) {
			$scope.activeWrap = $scope.ep.setActiveWrap(domWrap);
		};
		$scope.wrapEditorHtml = function() {
			var url = '/views/default/pl/fe/matter/enroll/wrap/' + $scope.activeWrap.type + '.html?_=22';
			return url;
		};
		var addInputSchema = function(addedSchema) {
			var deferred = $q.defer(),
				domNewWrap;

			/* 在当前页面上添加新登记项 */
			domNewWrap = $scope.ep.appendBySchema(addedSchema);
			$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
				$scope.setActiveWrap(domNewWrap);
				/* 页面滚动到新元素 */
				$scope.ep.scroll(domNewWrap);
				deferred.resolve();
			});

			return deferred.promise;
		};
		/*创建了新的schema*/
		$scope.$on('xxt.matter.enroll.app.data_schemas.created', function(event, newSchema) {
			var newWrap;
			if ($scope.ep.type === 'I') {
				addInputSchema(newSchema).then(function() {
					$scope.$broadcast('xxt.matter.enroll.page.data_schemas.added', newSchema, 'app');
				});
			}
			angular.forEach($scope.app.pages, function(page) {
				if (page.type === 'V') {
					/* 更新内存的数据 */
					page.appendRecord(newSchema);
					/* 更新后台数据 */
					$scope.updPage(page, ['data_schemas', 'html']);
				}
			});
		});
		$scope.$on('xxt.matter.enroll.page.data_schemas.requestAdd', function(event, addedSchema) {
			addInputSchema(addedSchema).then(function() {
				$scope.$broadcast('xxt.matter.enroll.page.data_schemas.added', addedSchema, 'page');
			});
		});
		var removeSchema = function(removedSchema) {
			var deferred = $q.defer();

			if ($scope.ep.removeSchema2(removedSchema)) {
				$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
					if ($scope.activeWrap && removedSchema.id === $scope.activeWrap.schema.id) {
						$scope.setActiveWrap(null);
					}
					deferred.resolve(removedSchema);
				});
			} else {
				deferred.resolve(removedSchema);
			}

			return deferred.promise;
		};
		$scope.removeSchema = function(removedSchema) {
			if (window.confirm('确定删除所有页面上的登记项？')) {
				removeSchema(removedSchema).then(function() {
					/* 通知应用删除登记项 */
					$scope.$broadcast('xxt.matter.enroll.page.data_schemas.removed', removedSchema, 'app');
				});
			}
		};
		$scope.$on('xxt.matter.enroll.page.data_schemas.requestRemove', function(event, removedSchema) {
			removeSchema(removedSchema).then(function() {
				$scope.$broadcast('xxt.matter.enroll.page.data_schemas.removed', removedSchema, 'page');
			});
		});
		$scope.newButton = function(btn) {
			var domWrap = $scope.ep.appendButton(btn);
			$scope.updPage($scope.ep, ['act_schemas', 'html']).then(function() {
				$scope.setActiveWrap(domWrap);
			});
		};
		$scope.newList = function(pattern) {
			var domWrap;
			if (pattern === 'records') {
				domWrap = $scope.ep.appendRecordList($scope.app);
			} else if (pattern === 'rounds') {
				domWrap = $scope.ep.appendRoundList($scope.app);
			}
			$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
				$scope.setActiveWrap(domWrap);
			});
		};
		$scope.removeWrap = function() {
			var wrapType = $scope.activeWrap.type,
				schema;
			$scope.ep.removeWrap($scope.activeWrap);
			if (wrapType === 'button') {
				$scope.updPage($scope.ep, ['act_schemas', 'html']);
			} else {
				schema = $scope.activeWrap.schema;
				$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
					if (/input/.test(wrapType)) {
						$scope.$broadcast('xxt.matter.enroll.page.data_schemas.removed', schema, 'page');
					}
				});
			}
			$scope.setActiveWrap(null);
		};
		$scope.moveWrap = function(action) {
			$scope.activeWrap = $scope.ep.moveWrap(action);
			$scope.updPage($scope.ep, ['html']);
		};
		$scope.embedMatter = function(page) {
			var options = {
				matterTypes: $scope.innerlinkTypes,
				singleMatter: true
			};
			if ($scope.app.mission) {
				options.mission = $scope.app.mission;
			}
			mattersgallery.open($scope.siteId, function(matters, type) {
				var dom = tinymceEditor.dom,
					style = "cursor:pointer",
					fn, domMatter, sibling;
				if ($scope.activeWrap) {
					sibling = $scope.activeWrap.dom;
					while (sibling.parentNode !== tinymceEditor.getBody()) {
						sibling = sibling.parentNode;
					}
					/*加到当前选中元素的后面*/
					angular.forEach(matters, function(matter) {
						fn = "openMatter(" + matter.id + ",'" + type + "')";
						domMatter = dom.create('div', {
							'wrap': 'matter',
							'class': 'form-group',
						}, dom.createHTML('span', {
							"style": style,
							"ng-click": fn
						}, dom.encode(matter.title)));
						dom.insertAfter(domMatter, sibling);
					});
				} else {
					/*加到页面的结尾*/
					angular.forEach(matters, function(matter) {
						fn = "openMatter(" + matter.id + "','" + type + "')";
						domMatter = dom.add(tinymceEditor.getBody(), 'div', {
							'wrap': 'matter',
							'class': 'form-group',
						}, dom.createHTML('span', {
							"style": style,
							"ng-click": fn
						}, dom.encode(matter.title)));
					});
				}
			}, options);
		};
		$scope.gotoCode = function() {
			window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + $scope.ep.code_name, '_self');
		};
		var _timerOfPageUpdate = null;
		$scope.$on('tinymce.content.change', function(event, changed) {
			var status, html;
			if (changed) {
				// 文档中的节点发生变化
				status = $scope.ep.contentChange(changed.node, $scope.activeWrap, $timeout);
			} else {
				html = $scope.ep.purifyInput(tinymceEditor.getContent());
				if (html !== $scope.ep.html) {
					$scope.ep.html = html;
					status = {
						htmlChanged: true
					};
				}
			}
			/*提交页面内容的修改*/
			if (status && status.htmlChanged) {
				if (_timerOfPageUpdate !== null) {
					$timeout.cancel(_timerOfPageUpdate);
				}
				_timerOfPageUpdate = $timeout(function() {
					var updatedFields = ['html'];
					status.actionChanged && updatedFields.push('act_schemas');
					if (status.schemaChanged === true) {
						/* 更新应用的定义 */
						$scope.update('data_schemas').then(function() {
							/* 更新当前页面 */
							updatedFields.push('data_schemas');
							$scope.updPage($scope.ep, updatedFields);
							/* 更新其它页面 */
							if ($scope.activeWrap.schema) {
								angular.forEach($scope.app.pages, function(page) {
									if (page !== $scope.ep) {
										page.updateBySchema($scope.activeWrap.schema);
										$scope.updPage(page, ['data_schemas', 'html']);
									}
								});
							}
						});
					} else {
						$scope.updPage($scope.ep, updatedFields);
					}
				}, 1000);
				_timerOfPageUpdate.then(function() {
					_timerOfPageUpdate = null;
				});
			}
		});
		$scope.$on('tinymce.wrap.add', function(event, domWrap) {
			$scope.$apply(function() {
				$scope.activeWrap = $scope.ep.selectWrap(domWrap);
			});
		});
		$scope.$on('tinymce.wrap.select', function(event, domWrap) {
			$scope.$apply(function() {
				$scope.activeWrap = $scope.ep.selectWrap(domWrap);
			});
		});
		$scope.$on('tinymce.multipleimage.open', function(event, callback) {
			var options = {
				callback: callback,
				multiple: true,
				setshowname: true
			};
			mediagallery.open($scope.siteId, options);
		});
		/*切换编辑的页面*/
		$scope.$watch('ep', function(page) {
			var html;
			if (!page) return;
			$scope.setActiveWrap(null);
			if (tinymceEditor) {
				wrapLib.setEditor(tinymceEditor);
				page.setEditor(tinymceEditor);
				if (page.type === 'I') {
					html = page.disableInput();
				} else {
					html = page.html;
				}
				tinymceEditor.setContent(html);
			}

		});
		$scope.$on('tinymce.instance.init', function(event, editor) {
			var html;
			tinymceEditor = editor;
			if ($scope.ep) {
				wrapLib.setEditor(tinymceEditor);
				$scope.ep.setEditor(editor);
				if ($scope.ep.type === 'I') {
					html = $scope.ep.disableInput();
				} else {
					html = $scope.ep.html;
				}
				editor.setContent(html);
			}
		});
	}]);
	/**
	 * 在当前编辑页面中选择应用的登记项
	 */
	ngApp.provider.controller('ctrlAppSchemas4Input', ['$scope', function($scope) {
		var pageSchemas = $scope.ep.data_schemas,
			appSchemas = $scope.app.data_schemas,
			chooseState = {};
		angular.forEach(pageSchemas, function(dataWrap) {
			if (dataWrap.schema) {
				chooseState[dataWrap.schema.id] = true;
			} else {
				console.error('page[' + $scope.ep.name + '] schema not exist', dataWrap);
			}
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
		$scope.$on('xxt.matter.enroll.page.data_schemas.add', function(event, newSchema) {
			chooseState[newSchema.id] = true;
		});
		$scope.$on('xxt.matter.enroll.page.data_schemas.removed', function(event, removedSchema, target) {
			chooseState[removedSchema.id] = false;
			if (target === 'app') {
				/*从应用的定义中删除*/
				appSchemas.splice(appSchemas.indexOf(removedSchema), 1);
				$scope.update('data_schemas');
			}
			/* 输入项被删除，其它页面上也不应该再有这个输入项 */
			angular.forEach($scope.app.pages, function(page) {
				if (page !== $scope.ep) {
					page.removeBySchema(removedSchema);
					$scope.updPage(page, ['data_schemas', 'html']);
				}
			});
		});
		$scope.$on('xxt.matter.enroll.page.data_schemas.added', function(event, addedSchema, target) {
			chooseState[addedSchema.id] = true;
		});
	}]);
	/**
	 * view
	 */
	ngApp.provider.controller('ctrlAppSchemas4View', ['$scope', function($scope) {
		var pageSchemas = $scope.ep.data_schemas,
			chooseState = {};
		$scope.appSchemas = $scope.app.data_schemas;
		$scope.otherSchemas = [{
			id: 'enrollAt',
			type: '_enrollAt',
			title: '登记时间'
		}];
		angular.forEach(pageSchemas, function(config) {
			config.schema && config.schema.id && (chooseState[config.schema.id] = true);
		});
		$scope.chooseState = chooseState;
		$scope.choose = function(schema) {
			if (chooseState[schema.id]) {
				$scope.ep.appendRecord2(schema);
				$scope.updPage($scope.ep, ['data_schemas', 'html']);
			} else {
				$scope.$emit('xxt.matter.enroll.page.data_schemas.requestRemove', schema);
			}
		};
		$scope.$on('xxt.matter.enroll.page.data_schemas.removed', function(event, removedSchema) {
			chooseState[removedSchema.id] = false;
		});
	}]);
	/**
	 * input wrap
	 */
	ngApp.provider.controller('ctrlInputWrap', ['$scope', '$timeout', function($scope, $timeout) {
		$scope.schema = $scope.activeWrap.schema;
		$scope.upperOptions = [];
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
		$scope.onKeyup = function(event) {
			// 回车时自动添加选项
			if (event.keyCode === 13) {
				$scope.addOption();
			}
		};
		$scope.$on('xxt.editable.remove', function(e, op) {
			var i = $scope.schema.ops.indexOf(op);
			$scope.schema.ops.splice(i, 1);
		});
		$scope.$watch('schema.ops', function(nv, ov) {
			if (nv !== ov) {
				$scope.updWrap('schema', 'ops');
			}
		}, true);
		$scope.$watch('schema.setUpper', function(nv) {
			if (nv === 'Y') {
				$scope.schema.upper = $scope.schema.ops ? $scope.schema.ops.length : 0;
			}
		});
		var timerOfUpdate = null;
		$scope.updWrap = function(obj, names) {
			wrapLib.input.modify($scope.activeWrap.dom, $scope.activeWrap);
			if (timerOfUpdate !== null) {
				$timeout.cancel(timerOfUpdate);
			}
			timerOfUpdate = $timeout(function() {
				/* 更新应用的定义 */
				$scope.update('data_schemas').then(function() {
					/* 更新当前页面 */
					$scope.ep.purifyInput(tinymce.activeEditor.getContent(), true);
					$scope.updPage($scope.ep, ['data_schemas', 'html']);
					/* 更新其它页面 */
					angular.forEach($scope.app.pages, function(page) {
						if (page !== $scope.ep) {
							page.updateBySchema($scope.activeWrap.schema);
							$scope.updPage(page, ['data_schemas', 'html']);
						}
					});
				});
			}, 1000);
			timerOfUpdate.then(function() {
				timerOfUpdate = null;
			});
		};
		if ($scope.schema.type === 'member') {
			if ($scope.schema.schema_id) {
				(function() {
					var i, j, memberSchema, schema;
					/*自定义用户*/
					for (i = $scope.memberSchemas.length - 1; i >= 0; i--) {
						memberSchema = $scope.memberSchemas[i];
						if ($scope.schema.schema_id === memberSchema.id) {
							for (j = memberSchema._schemas.length - 1; j >= 0; j--) {
								schema = memberSchema._schemas[j];
								if ($scope.schema.id === schema.id) {
									break;
								}
							}
							$scope.selectedMemberSchema = {
								schema: memberSchema,
								attr: schema
							};
							break;
						}
					}
				})();
			}
		}
	}]);
	/**
	 * value wrap controller
	 */
	ngApp.provider.controller('ctrlValueWrap', ['$scope', function($scope) {
		$scope.updWrap = function(obj, prop) {
			wrapLib.value.modify($scope.activeWrap.dom, $scope.activeWrap);
			$scope.updPage($scope.ep, ['data_schemas', 'html']);
		};
	}]);
	/**
	 * record list wrap controller
	 */
	ngApp.provider.controller('ctrlRecordListWrap', ['$scope', '$timeout', function($scope, $timeout) {
		var listSchemas = $scope.activeWrap.schemas,
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
			$scope.updWrap('config', 'schemas');
		};
		/*通过编辑窗口更新定义*/
		var timerOfUpdate = null;
		$scope.updWrap = function(obj, prop) {
			wrapLib.records.modify($scope.activeWrap.dom, $scope.activeWrap);
			if (timerOfUpdate !== null) {
				$timeout.cancel(timerOfUpdate);
			}
			timerOfUpdate = $timeout(function() {
				$scope.updPage($scope.ep, ['data_schemas', 'html']);
			}, 1000);
			timerOfUpdate.then(function() {
				timerOfUpdate = null;
			});
		};
	}]);
	/**
	 * round list wrap
	 */
	ngApp.provider.controller('ctrlRoundListWrap', ['$scope', function($scope) {
		$scope.app = app;
		/*通过编辑窗口更新定义*/
		var timerOfUpdate = null;
		$scope.updWrap = function(nv, ov) {
			var editor, $active, newWrap;
			editor = tinymce.get('tinymce-page');
			$active = $(editor.getBody()).find('.active');
			$active = $active[0];
			newWrap = wrapLib.embedRounds(editor, nv);
			$active.remove();
			$scope.setActiveWrap(newWrap);
		};
	}]);
	/**
	 * button wrap controller
	 */
	ngApp.provider.controller('ctrlButtonWrap', ['$scope', '$timeout', function($scope, $timeout) {
		var targetPages = {},
			inputPages = {},
			schema = $scope.activeWrap.schema;
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
		/*直接给带有导航功能的按钮创建页面*/
		$scope.newPage = function(prop) {
			$scope.createPage().then(function(page) {
				targetPages[page.name] = {
					l: page.title
				};
				if (page.type === 'I') {
					inputPages[page.name] = {
						l: page.title
					};
				}
				schema[prop] = page.name;
			});
		};
		/*更新按钮定义*/
		var timerOfUpdate = null;
		$scope.updWrap = function(obj, prop) {
			wrapLib.button.modify($scope.activeWrap.dom, $scope.activeWrap);
			if (timerOfUpdate !== null) {
				$timeout.cancel(timerOfUpdate);
			}
			timerOfUpdate = $timeout(function() {
				$scope.ep.purifyInput(tinymce.activeEditor.getContent(), true);
				$scope.updPage($scope.ep, ['act_schemas', 'html']);
			}, 1000);
			timerOfUpdate.then(function() {
				timerOfUpdate = null;
			});
		};
	}]);
});