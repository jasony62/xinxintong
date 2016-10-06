define(['frame', 'schema'], function(ngApp, schemaLib) {
	'use strict';
	/**
	 * 登记项管理
	 */
	ngApp.provider.controller('ctrlSchema', ['$scope', '$q', 'srvPage', '$uibModal', function($scope, $q, srvPage, $uibModal) {
		$scope.updPage = function(page, names) {
			return srvPage.update(page, names);
		};
		$scope.newSchema = function(type) {
			var newSchema;

			newSchema = schemaLib.newSchema(type, $scope.app);
			for (var i = $scope.app.data_schemas.length - 1; i >= 0; i--) {
				if (newSchema.id === $scope.app.data_schemas[i].id) {
					alert('不允许重复添加登记项');
					return;
				}
			}
			$scope.app.data_schemas.push(newSchema);
			$scope.update('data_schemas').then(function() {
				$scope.app.pages.forEach(function(page) {
					page.appendSchema(newSchema);
					$scope.updPage(page, ['data_schemas', 'html']);
				});
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
				$scope.app.pages.forEach(function(page) {
					page.appendSchema(newSchema);
					$scope.updPage(page, ['data_schemas', 'html']);
				});
			});
		};
		$scope.newByEnroll = function(schema) {
			var newSchema;

			for (var i = $scope.app.data_schemas.length - 1; i >= 0; i--) {
				if (schema.id === $scope.app.data_schemas[i].id) {
					alert('不允许重复添加登记项');
					return;
				}
			}
			newSchema = schemaLib.newSchema(schema.type, $scope.app);
			newSchema.type === 'member' && (newSchema.schema_id = schema.schema_id);
			newSchema.id = schema.id;
			newSchema.title = schema.title;
			newSchema.requireCheck = 'Y';
			newSchema.fromApp = $scope.app.enrollApp.id;

			$scope.app.data_schemas.push(newSchema);
			$scope.update('data_schemas').then(function() {
				$scope.app.pages.forEach(function(page) {
					page.appendSchema(newSchema);
					$scope.updPage(page, ['data_schemas', 'html']);
				});
			});
		};
		$scope.copySchema = function(schema) {
			var newSchema = angular.copy(schema),
				afterIndex;

			newSchema.id = 'c' + (new Date() * 1);
			afterIndex = $scope.app.data_schemas.indexOf(schema);
			$scope.app.data_schemas.splice(afterIndex + 1, 0, newSchema);

			$scope.update('data_schemas').then(function() {
				$scope.app.pages.forEach(function(page) {
					page.appendSchema(newSchema, schema);
					$scope.updPage(page, ['data_schemas', 'html']);
				});
			});
		};

		function removeSchema(removedSchema) {
			var deferred = $q.defer();

			//从应用的定义中删除
			$scope.app.data_schemas.splice($scope.app.data_schemas.indexOf(removedSchema), 1);
			$scope.update('data_schemas').then(function() {
				$scope.app.pages.forEach(function(page) {
					if (page.removeSchema(removedSchema)) {
						$scope.updPage(page, ['data_schemas', 'html']);
					}
				});
				deferred.resolve(removedSchema);
			});

			return deferred.promise;
		};
		$scope.removeSchema = function(removedSchema) {
			var deferred = $q.defer();
			if (window.confirm('确定从所有页面上删除登记项［' + removedSchema.title + '］？')) {
				removeSchema(removedSchema).then(function() {
					deferred.resolve();
				});
			}
			return deferred.promise;
		};
	}]);
	/**
	 * app setting controller
	 */
	ngApp.provider.controller('ctrlApp', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
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
				$scope.update('enroll_app_id').then(function(rsp) {
					var app = $scope.app,
						url = '/rest/pl/fe/matter/enroll/get?site=' + $scope.siteId + '&id=' + app.enroll_app_id;
					http2.get(url, function(rsp) {
						rsp.data.data_schemas = JSON.parse(rsp.data.data_schemas);
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
			$scope.update('enroll_app_id').then(function() {
				angular.forEach(app.data_schemas, function(dataSchema) {
					delete dataSchema.requireCheck;
				});
				$scope.update('data_schemas');
			});
		};
	}]);
	/**
	 * 应用的所有登记项
	 */
	ngApp.provider.controller('ctrlList', ['$scope', '$timeout', function($scope, $timeout) {
		function changeSchemaOrder(moved) {
			$scope.update('data_schemas').then(function() {
				var app = $scope.app;
				if (app.__schemasOrderConsistent === 'Y') {
					var i = app.data_schemas.indexOf(moved),
						prevSchema;
					if (i > 0) prevSchema = app.data_schemas[i - 1];
					app.pages.forEach(function(page) {
						page.moveSchema(moved, prevSchema);
						$scope.updPage(page, ['data_schemas', 'html']);
					});
				}
			});
		};

		var mapOfSchemas = {};

		$scope.popover = {};
		$scope.schemaHtml = function(schema) {
			var bust = (new Date()).getMinutes();
			return '/views/default/pl/fe/matter/enroll/schema/' + schema.type + '.html?_=' + bust;
		};
		$scope.schemaPopoverHtml = function() {
			var bust = (new Date()).getMinutes();
			return '/views/default/pl/fe/matter/enroll/schema/main.html?_=' + bust;
		};
		$scope.closePopover = function() {
			$($scope.popover.target).trigger('hide');
			$scope.popover = {};
		};
		$scope.upSchema = function(schema) {
			var index = $scope.appSchemas.indexOf(schema);
			if (index > 0) {
				$scope.appSchemas.splice(index, 1);
				$scope.appSchemas.splice(index - 1, 0, schema);
				changeSchemaOrder(schema);
			}
		};
		$scope.downSchema = function(schema) {
			var index = $scope.appSchemas.indexOf(schema);
			if (index < $scope.appSchemas.length - 1) {
				$scope.appSchemas.splice(index, 1);
				$scope.appSchemas.splice(index + 1, 0, schema);
				changeSchemaOrder(schema);
			}
		};
		$scope.chooseSchema = function(event, schema) {
			var target = event.currentTarget,
				page, wrap;

			$scope.activeSchema = schema;
			$scope.activeConfig = false;
			$scope.inputPage = false;
			for (var i = $scope.app.pages.length - 1; i >= 0; i--) {
				page = $scope.app.pages[i];
				if (page.type === 'I' && (wrap = page.wrapBySchema(schema))) {
					$scope.inputPage = page;
					$scope.activeConfig = wrap.config;
					break;
				}
			}
			if ($scope.popover.target !== target) {
				if ($scope.popover.target) {
					$($scope.popover.target).trigger('hide');
				}
				$(target).trigger('show');
				$scope.popover = {
					target: target,
					schema: schema,
					index: target.dataset.schemaIndex
				};
			}
		};
		$scope.addOption = function(schema, afterIndex) {
			var maxSeq = 0,
				newOp = {
					l: ''
				};

			if (schema.ops === undefined) {
				schema.ops = [];
			}
			schema.ops.forEach(function(op) {
				var opSeq = parseInt(op.v.substr(1));
				opSeq > maxSeq && (maxSeq = opSeq);
			});
			newOp.v = 'v' + (++maxSeq);
			if (afterIndex === undefined) {
				schema.ops.push(newOp);
			} else {
				schema.ops.splice(afterIndex + 1, 0, newOp);
			}
			$timeout(function() {
				$scope.$broadcast('xxt.editable.add', newOp);
			});
		};
		$scope.removeOption = function(schema, op) {
			var i = schema.ops.indexOf(op);

			schema.ops.splice(i, 1);
			$scope.updSchema(schema, 'ops');
		};
		var timerOfUpdate = null;
		$scope.updSchema = function(schema, prop) {
			if (timerOfUpdate !== null) {
				$timeout.cancel(timerOfUpdate);
			}
			timerOfUpdate = $timeout(function() {
				// 更新应用的定义
				$scope.update('data_schemas').then(function() {
					// 更新页面
					$scope.app.pages.forEach(function(page) {
						page.updateSchema(schema);
						$scope.updPage(page, ['data_schemas', 'html']);
					});
				});
			}, 1000);
			timerOfUpdate.then(function() {
				timerOfUpdate = null;
			});
		};
		$scope.updConfig = function(prop) {
			$scope.inputPage.updateSchema($scope.activeSchema);
			$scope.updPage($scope.inputPage, ['data_schemas', 'html']);
		};
		$scope.$on('schemas.orderChanged', function(e, moved) {
			changeSchemaOrder(moved);
		});
		$scope.$on('title.xxt.editable.changed', function(e, schema) {
			$scope.updSchema(schema, 'title');
		});
		// 回车添加选项
		$('body').on('keyup', function(evt) {
			if (event.keyCode === 13) {
				var schemaId, opNode, opIndex;
				opNode = evt.target.parentNode;
				if (opNode && opNode.getAttribute('evt-prefix') === 'option') {
					schemaId = opNode.getAttribute('state');
					opIndex = parseInt(opNode.dataset.index);
					$scope.$apply(function() {
						$scope.addOption(mapOfSchemas[schemaId], opIndex);
					});
				}
			}
		});
		$scope.$on('options.orderChanged', function(e, moved, schemaId) {
			$scope.updSchema(mapOfSchemas[schemaId], 'ops');
		});
		$scope.$on('option.xxt.editable.changed', function(e, op, schemaId) {
			$scope.updSchema(mapOfSchemas[schemaId], 'ops');
		});
		$scope.$watch('app', function(app) {
			if (app) {
				$scope.appSchemas = $scope.app.data_schemas;
				$scope.appSchemas.forEach(function(schema) {
					mapOfSchemas[schema.id] = schema;
				});
			}
		});
	}]);
	/**
	 * 登记项编辑
	 */
	ngApp.provider.controller('ctrlSchemaEdit', ['$scope', function($scope) {
		if ($scope.activeSchema.type === 'member') {
			if ($scope.activeSchema.schema_id) {
				(function() {
					var i, j, memberSchema, schema;
					/*自定义用户*/
					for (i = $scope.memberSchemas.length - 1; i >= 0; i--) {
						memberSchema = $scope.memberSchemas[i];
						if ($scope.activeSchema.schema_id === memberSchema.id) {
							for (j = memberSchema._schemas.length - 1; j >= 0; j--) {
								schema = memberSchema._schemas[j];
								if ($scope.activeSchema.id === schema.id) {
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
});