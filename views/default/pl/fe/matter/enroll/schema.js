define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
	'use strict';
	/**
	 *
	 */
	ngApp.provider.controller('ctrlSchema', ['$scope', 'srvPage', '$uibModal', function($scope, srvPage, $uibModal) {
		$scope.updPage = function(page, names) {
			return srvPage.update(page, names);
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
			$scope.update('data_schemas').then(function() {});
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
			$scope.update('data_schemas').then(function() {});
		};
		$scope.copySchema = function(schema) {
			var newSchema = angular.copy(schema);
			newSchema.id = 'c' + (new Date() * 1);
			$scope.app.data_schemas.push(newSchema);
			$scope.update('data_schemas').then(function() {});
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
	ngApp.provider.controller('ctrlList', ['$scope', function($scope) {

		$scope.popover = {};
		$('body').on('click', function(event) {
			var target = event.target;
			if (event.target.tagName === 'SPAN' && target.parentNode && target.parentNode.tagName === 'BUTTON') {
				target = target.parentNode;
			}
			if (target.tagName === 'BUTTON' && target.classList.contains('popover-schema') && target.dataset.schemaIndex !== undefined) {
				var schema = $scope.appSchemas[target.dataset.schemaIndex];
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
				} else {
					$scope.popover = {};
					$(target).trigger('hide');
				}
			}
		});
		$scope.$on('orderChanged', function(e, moved) {
			$scope.update('data_schemas').then(function() {});
		});
		$scope.removePopover = function() {
			$scope.removeSchema($scope.popover.schema).then(function() {
				$($scope.popover.target).trigger('hide');
				$scope.popover = {};
			});
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
		$scope.closePopover = function() {
			if ($scope.popover.modified) {
				$scope.update('data_schemas').then(function() {
					$($scope.popover.target).trigger('hide');
					$scope.popover = {};
				});
			} else {
				$($scope.popover.target).trigger('hide');
				$scope.popover = {};
			}
		};
		$scope.chooseSchema = function(schema) {
			$scope.activeSchema = schema;
		};
		$scope.$watch('app', function(app) {
			if (app) {
				$scope.appSchemas = $scope.app.data_schemas;
			}
		});
	}]);
	/**
	 * 登记项编辑
	 */
	ngApp.provider.controller('ctrlOne', ['$scope', '$timeout', function($scope, $timeout) {
		$scope.addOption = function() {
			var schema = $scope.activeSchema,
				maxSeq = 0,
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
		$scope.onKeyup = function(event) {
			// 回车时自动添加选项
			if (event.keyCode === 13) {
				$scope.addOption();
			}
		};
		$scope.$on('xxt.editable.remove', function(e, op) {
			var schema = $scope.activeSchema,
				i = schema.ops.indexOf(op);

			schema.ops.splice(i, 1);
		});
		$scope.$watch('activeSchema.ops', function(nv, ov) {
			if (nv !== ov) {
				$scope.updWrap('schema', 'ops');
			}
		}, true);
		var timerOfUpdate = null;
		$scope.updWrap = function() {
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