define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
	'use strict';
	/**
	 *
	 */
	ngApp.provider.controller('ctrlSchema', ['$scope', '$q', 'srvPage', '$uibModal', function($scope, $q, srvPage, $uibModal) {
		$scope.updPage = function(page, names) {
			return srvPage.update(page, names);
		};
		$scope.newSchema = function(type) {
			var newSchema, mission;
			if (type === 'phase') {
				mission = $scope.app.mission;
				if (!mission || !mission.phases || mission.phases.length === 0) {
					alert('请先指定项目的阶段');
					return;
				}
			}
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
		$scope.copySchema = function(schema) {
			var newSchema = angular.copy(schema);
			newSchema.id = 'c' + (new Date() * 1);
			$scope.app.data_schemas.push(newSchema);
			$scope.update('data_schemas').then(function() {
				$scope.app.pages.forEach(function(page) {
					page.appendSchema(newSchema);
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
					if (page.removeBySchema(removedSchema)) {
						$scope.updPage(page, ['data_schemas', 'html']).then(function() {
							deferred.resolve(removedSchema);
						});
					} else {
						deferred.resolve(removedSchema);
					}
				});
			});

			return deferred.promise;
		};
		$scope.removeSchema = function(removedSchema) {
			var deferred = $q.defer();
			if (window.confirm('确定删除所有页面上的登记项？')) {
				removeSchema(removedSchema).then(function() {
					deferred.resolve();
				});
			}
			return deferred.promise;
		};
		$scope.batchSingleScore = function() {
			$uibModal.open({
				templateUrl: '/views/default/pl/fe/matter/enroll/component/batchSingleScore.html?_=1',
				backdrop: 'static',
				resolve: {
					app: function() {
						return $scope.app;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'app', function($scope2, $mi, app) {
					var maxOpNum = 0,
						opScores = [];

					app.data_schemas.forEach(function(schema) {
						if (schema.type === 'single') {
							schema.ops.length > maxOpNum && (maxOpNum = schema.ops.length);
						}
					});
					while (opScores.length < maxOpNum) {
						opScores.push(maxOpNum - opScores.length);
					}

					$scope2.opScores = opScores;
					$scope2.close = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.close(opScores);
					};
				}]
			}).result.then(function(result) {
				$scope.app.data_schemas.forEach(function(schema) {
					if (schema.type === 'single') {
						schema.ops.forEach(function(op, index) {
							op.score = result[index];
						});
					}
				});
				$scope.update('data_schemas');
			});
		};
	}]);
	/**
	 * 应用的所有登记项
	 */
	ngApp.provider.controller('ctrlList', ['$scope', '$timeout', function($scope, $timeout) {
		$scope.popover = {};
		$scope.$on('schemas.orderChanged', function(e, moved) {
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
		});
		$scope.schemaHtml = function(schema) {
			var bust = (new Date()).getMinutes();
			return '/views/default/pl/fe/matter/enroll/schema/' + schema.type + '.html?_=' + bust;
		};
		$scope.schemaPopoverHtml = function() {
			var bust = (new Date()).getMinutes();
			return '/views/default/pl/fe/matter/enroll/schema/main.html?_=' + bust;
		};
		$scope.removePopover = function() {
			$scope.removeSchema($scope.popover.schema).then(function() {
				$scope.closePopover();
			});
		};
		$scope.closePopover = function() {
			$($scope.popover.target).trigger('hide');
			$scope.popover = {};
		};
		$scope.upPopover = function() {
			var index = $scope.popover.index;
			if (index > 0) {
				$scope.appSchemas.splice(index, 1);
				$scope.appSchemas.splice(index - 1, 0, $scope.popover.schema);
				$scope.popover.index--;
				$scope.popover.modified = true;
			}
		};
		$scope.downPopover = function() {
			var index = $scope.popover.index;
			if (index < $scope.appSchemas.length - 1) {
				$scope.appSchemas.splice(index, 1);
				$scope.appSchemas.splice(index + 1, 0, $scope.popover.schema);
				$scope.popover.index++;
				$scope.popover.modified = true;
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
				if (wrap = page.wrapBySchema(schema)) {
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
		$scope.addOption = function(schema) {
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
			schema.ops.push(newOp);
			$timeout(function() {
				$scope.$broadcast('xxt.editable.add', newOp);
			});
		};
		var timerOfUpdate = null;
		$scope.updSchema = function(prop) {
			if (timerOfUpdate !== null) {
				$timeout.cancel(timerOfUpdate);
			}
			timerOfUpdate = $timeout(function() {
				// 更新应用的定义
				$scope.update('data_schemas').then(function() {
					// 更新页面
					$scope.app.pages.forEach(function(page) {
						page.updateBySchema($scope.activeSchema);
						$scope.updPage(page, ['data_schemas', 'html']);
					});
				});
			}, 1000);
			timerOfUpdate.then(function() {
				timerOfUpdate = null;
			});
		};
		$scope.updConfig = function(prop) {
			$scope.inputPage.updateBySchema($scope.activeSchema);
			$scope.updPage($scope.inputPage, ['data_schemas', 'html']);
		};
		$scope.$on('title.xxt.editable.changed', function(e, op) {
			$scope.updSchema('title');
		});
		$scope.$on('option.xxt.editable.changed', function(e, op) {
			$scope.updSchema('ops');
		});
		$scope.$on('option.xxt.editable.remove', function(e, op) {
			var schema = $scope.activeSchema,
				i = schema.ops.indexOf(op);

			schema.ops.splice(i, 1);
			$scope.updSchema('ops');
		});
		$scope.$watch('app', function(app) {
			if (app) {
				$scope.appSchemas = $scope.app.data_schemas;
			}
		});
	}]);
	/**
	 * 登记项编辑
	 */
	ngApp.provider.controller('ctrlEdit', ['$scope', '$timeout', function($scope, $timeout) {
		$scope.onOptionKeyup = function(event) {
			// 回车时自动添加选项
			if (event.keyCode === 13) {
				$scope.addOption();
			}
		};
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