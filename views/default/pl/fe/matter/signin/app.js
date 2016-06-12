define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
	/**
	 * app setting controller
	 */
	ngApp.provider.controller('ctrlApp', ['$scope', '$uibModal', '$q', 'http2', function($scope, $uibModal, $q, http2) {
		$scope.publish = function() {
			$scope.app.state = 2;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/signin/publish?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
		$scope.remove = function() {
			if (window.confirm('确定删除？')) {
				http2.get('/rest/pl/fe/matter/signin/remove?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
					if ($scope.app.mission) {
						location = "/rest/pl/fe/matter/mission?site=" + $scope.siteId + "&id=" + $scope.app.mission.id;
					} else {
						location = '/rest/pl/fe/site/console?site=' + $scope.siteId;
					}
				});
			}
		};
		$scope.assignEnrollApp = function() {
			$uibModal.open({
				templateUrl: 'assignEnrollApp.html',
				resolve: {
					app: function() {
						return $scope.app;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'app', function($scope2, $mi, app) {
					$scope2.app = app;
					$scope2.data = {
						filter: {},
						source: ''
					};
					app.mission && ($scope2.data.sameMission = 'Y');
					$scope2.cancel = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.close($scope2.data);
					};
					var url = '/rest/pl/fe/matter/enroll/list?site=' + $scope.siteId + '&scenario=registration&size=999';
					app.mission && (url += '&mission=' + app.mission.id);
					http2.get(url, function(rsp) {
						$scope2.apps = rsp.data.apps;
					});
				}],
				backdrop: 'static'
			}).result.then(function(data) {
				$scope.app.enroll_app_id = data.source;
				$scope.update('enroll_app_id');
				$scope.submit().then(function(rsp) {
					var app = $scope.app,
						url = '/rest/pl/fe/matter/enroll/get?site=' + $scope.siteId + '&id=' + app.enroll_app_id;
					http2.get(url, function(rsp) {
						app.enrollApp = rsp.data;
					});
					for (var i = app.data_schemas.length - 1; i > 0; i--) {
						if (app.data_schemas[i].id === 'mobile') {
							app.data_schemas[i].requireCheck = 'Y';
							break;
						}
					}
					$scope.update('data_schemas');
				});
			});
		};
		$scope.cancelEnrollApp = function() {
			var app = $scope.app;
			app.enroll_app_id = '';
			$scope.update('enroll_app_id');
			$scope.submit().then(function() {
				angular.forEach(app.data_schemas, function(dataSchema) {
					delete dataSchema.requireCheck;
				});
				$scope.update('data_schemas');
			});
		};
		$scope.addPage = function() {
			$scope.createPage().then(function(page) {
				$scope.choosePage(page);
			});
		};
		$scope.updPage = function(page, names) {
			var defer = $q.defer(),
				url, p = {};
			angular.isString(names) && (names = [names]);
			if (page === $scope.ep && names.indexOf('html') !== -1) {
				$scope.ep.purifyHtml();
			}
			$scope.$root.progmsg = '正在保存页面...';
			angular.forEach(names, function(name) {
				p[name] = name === 'html' ? encodeURIComponent(page[name]) : page[name];
			});
			url = '/rest/pl/fe/matter/signin/page/update';
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
			if (window.confirm('确定删除页面？')) {
				var url = '/rest/pl/fe/matter/signin/page/remove';
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
			location = '/rest/pl/fe/matter/signin/page?site=' + $scope.siteId + '&id=' + $scope.id + '&page=' + $scope.ep.name;
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
				if (i === -1) return;
			}
			$scope.ep = page;
			tinymce.activeEditor.setContent(page.html);
		};
		$scope.newSchema = function(type) {
			var newSchema = schemaLib.newSchema(type);
			$scope.app.data_schemas.push(newSchema);
			$scope.update('data_schemas').then(function() {
				$scope.$broadcast('xxt.matter.signin.app.data_schemas.created', newSchema);
			});
		};
		$scope.newMember = function() {
			if (!$scope.memberSchemas || $scope.memberSchemas.length === 0) return;

			var memberSchema = $scope.memberSchemas[0],
				newSchema = schemaLib.newInput('member');

			newSchema.schema_id = memberSchema.id;
			if (memberSchema.attr_name[0] === '0') {
				newSchema.title = '姓名';
				newSchema.id = 'member.name';
			} else if (memberSchema.attr_mobile[0] === '0') {
				newSchema.title = '手机';
				newSchema.id = 'member.mobile';
			} else if (memberSchema.attr_email[0] === '0') {
				newSchema.title = '邮箱';
				newSchema.id = 'member.email';
			}
			$scope.app.data_schemas.push(newSchema);
			$scope.update('data_schemas').then(function() {
				$scope.$broadcast('xxt.matter.signin.app.data_schemas.created', newSchema);
			});
		};
	}]);
	/**
	 * 在当前编辑页面中选择应用的登记项
	 */
	ngApp.provider.controller('ctrlAppSchemas4Input', ['$scope', function($scope) {
		var pageSchemas = $scope.ep.data_schemas,
			appSchemas = $scope.app.data_schemas,
			chooseState = {};
		angular.forEach(pageSchemas, function(dataWrap) {
			chooseState[dataWrap.schema.id] = true;
		});
		$scope.appSchemas = appSchemas;
		$scope.chooseState = chooseState;
		$scope.choose = function(schema) {
			if (chooseState[schema.id]) {
				$scope.$emit('xxt.matter.signin.page.data_schemas.requestAdd', schema);
			} else {
				$scope.$emit('xxt.matter.signin.page.data_schemas.requestRemove', schema);
			}
		};
		$scope.remove = function(removedSchema) {
			if (window.confirm('确定删除所有页面上的登记项？')) {
				$scope.$emit('xxt.matter.signin.app.data_schemas.requestRemove', removedSchema);
			}
		};
		$scope.$on('xxt.matter.signin.page.data_schemas.add', function(event, newSchema) {
			chooseState[newSchema.id] = true;
		});
		$scope.$on('xxt.matter.signin.page.data_schemas.removed', function(event, removedSchema, target) {
			chooseState[removedSchema.id] = false;
			if (target === 'app') {
				/*从应用的定义中删除*/
				appSchemas.splice(appSchemas.indexOf(removedSchema), 1);
				$scope.update('data_schemas');
			}
		});
		$scope.$on('xxt.matter.signin.page.data_schemas.added', function(event, addedSchema, target) {
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
			chooseState[config.schema.id] = true;
		});
		$scope.chooseState = chooseState;
		$scope.choose = function(schema) {
			if (chooseState[schema.id]) {
				$scope.ep.appendRecord2(schema);
				$scope.updPage($scope.ep, ['data_schemas', 'html']);
			} else {
				$scope.$emit('xxt.matter.signin.page.data_schemas.requestRemove', schema);
			}
		};
		$scope.$on('xxt.matter.signin.page.data_schemas.removed', function(event, removedSchema) {
			chooseState[removedSchema.id] = false;
		});
	}]);
	/**
	 * input wrap
	 */
	ngApp.provider.controller('ctrlInputWrap', ['$scope', '$timeout', function($scope, $timeout) {
		$scope.schema = $scope.activeWrap.schema;
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
		var timerOfUpdate = null;
		$scope.updWrap = function(obj, name) {
			wrapLib.input.modify($scope.activeWrap.dom, $scope.activeWrap);
			if (timerOfUpdate !== null) {
				$timeout.cancel(timerOfUpdate);
			}
			timerOfUpdate = $timeout(function() {
				/* 更新应用的定义 */
				$scope.update('data_schemas').then(function() {
					/* 更新当前页面 */
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
				$scope.updPage($scope.ep, ['act_schemas', 'html']);
			}, 1000);
			timerOfUpdate.then(function() {
				timerOfUpdate = null;
			});
		};
	}]);
	/**
	 * page
	 */
	ngApp.provider.controller('ctrlPage', ['$scope', '$q', 'mediagallery', 'mattersgallery', function($scope, $q, mediagallery, mattersgallery) {
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
		$scope.wrapEditorHtml = function() {
			var url = '/views/default/pl/fe/matter/enroll/wrap/' + $scope.activeWrap.type + '.html?_=18';
			return url;
		};
		var addInputSchema = function(addedSchema) {
			var deferred = $q.defer(),
				domNewWrap;

			/* 在当前页面上添加新登记项 */
			domNewWrap = $scope.ep.appendBySchema(addedSchema);
			/* 更新后台数据 */
			$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
				$scope.setActiveWrap(domNewWrap);
				deferred.resolve();
			});

			return deferred.promise;
		};
		/*创建了新的schema*/
		$scope.$on('xxt.matter.signin.app.data_schemas.created', function(event, newSchema) {
			var newWrap, viewPages = [];
			if ($scope.ep.type === 'I') {
				addInputSchema(newSchema).then(function() {
					$scope.$broadcast('xxt.matter.signin.page.data_schemas.added', newSchema, 'app');
				});
			}
			angular.forEach($scope.app.pages, function(page) {
				if (page.type === 'V') {
					viewPages.push(page);
				}
			});
			/*如果只有1个查看页，在页面上自动添加登记项*/
			if (viewPages.length === 1) {
				var page = viewPages[0];
				/* 更新内存的数据 */
				page.appendRecord(newSchema);
				/* 更新后台数据 */
				$scope.updPage(page, ['data_schemas', 'html']);
			}
		});
		$scope.$on('xxt.matter.signin.page.data_schemas.requestAdd', function(event, addedSchema) {
			addInputSchema(addedSchema).then(function() {
				$scope.$broadcast('xxt.matter.signin.page.data_schemas.added', addedSchema, 'page');
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
		$scope.$on('xxt.matter.signin.app.data_schemas.requestRemove', function(event, removedSchema) {
			removeSchema(removedSchema).then(function() {
				/*更新其它页面。*/
				angular.forEach($scope.app.pages, function(page) {
					if (page !== $scope.ep) {
						page.removeBySchema(removedSchema);
						$scope.updPage(page, ['data_schemas', 'html']);
					}
				});
				/* 通知应用删除登记项 */
				$scope.$broadcast('xxt.matter.signin.page.data_schemas.removed', removedSchema, 'app');
			});
		});
		$scope.$on('xxt.matter.signin.page.data_schemas.requestRemove', function(event, removedSchema) {
			removeSchema(removedSchema).then(function() {
				$scope.$broadcast('xxt.matter.signin.page.data_schemas.removed', removedSchema, 'page');
			});
		});
		$scope.newButton = function(btn) {
			var domWrap = $scope.ep.appendButton(btn);
			$scope.updPage($scope.ep, ['act_schemas', 'html']).then(function() {
				$scope.setActiveWrap(domWrap);
			});
		};
		$scope.newList = function(pattern) {
			if (pattern === 'records') {
				var domWrap = $scope.ep.appendRecordList($scope.app);
			} else if (pattern === 'rounds') {
				var domWrap = $scope.ep.appendRoundList($scope.app);
			}
			$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
				$scope.setActiveWrap(domWrap);
			});
		};
		$scope.removeWrap = function() {
			var wrapType = $scope.activeWrap.type;
			$scope.ep.removeWrap($scope.activeWrap);
			if (wrapType === 'button') {
				$scope.updPage($scope.ep, ['act_schemas', 'html']);
			} else {
				$scope.updPage($scope.ep, ['data_schemas', 'html']).then(function() {
					if (/input/.test(wrapType)) {
						$scope.$broadcast('xxt.matter.signin.page.data_schemas.removed', $scope.activeWrap.schema, 'page');
					}
				});
			}
			$scope.setActiveWrap(null);
		};
		$scope.moveWrap = function(action) {
			$scope.activeWrap = $scope.ep.moveWrap(action);
			tinymceEditor.save();
		};
		$scope.embedMatter = function(page) {
			mattersgallery.open($scope.siteId, function(matters, type) {
				var dom, mtype, fn;
				dom = tinymceEditor.dom;
				angular.forEach(matters, function(matter) {
					fn = "openMatter(" + matter.id + ",'" + mtype + "')";
					tinymceEditor.insertContent(dom.createHTML('div', {
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
		$scope.$on('tinymce.multipleimage.open', function(event, callback) {
			var options = {
				callback: callback,
				multiple: true,
				setshowname: true
			};
			mediagallery.open($scope.siteId, options);
		});
		$scope.$on('tinymce.instance.init', function() {
			var $body;
			tinymceEditor = tinymce.get('tinymce-page');
			$body = $(tinymceEditor.getBody());
			$body.find('input[type=text],textarea').attr('readonly', true);
			$body.find('input[type=radio],input[type=checkbox]').attr('disabled', true);
			wrapLib.setEditor(tinymceEditor);
			$scope.ep && $scope.ep.setEditor(tinymceEditor);
		});
		$scope.$watch('ep', function(page) {
			if (page) {
				$scope.setActiveWrap(null);
				tinymceEditor && page.setEditor(tinymceEditor);
			}
		});
	}]);
});