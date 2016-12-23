define(['frame'], function(ngApp) {
	'use strict';

	ngApp.provider.directive('tmsEditable', [function() {
		var isEditing, bar, activeNode, activeTarget, fixedContent;

		function deactivate($scope) {
			var phase;
			if (activeNode) {
				activeNode.classList.remove('tms-active');
				activeNode.removeAttribute('contenteditable');
				activeNode = null;
				activeTarget = null;
				phase = $scope.$root.$$phase;
				if (phase === '$digest' || phase === '$apply') {
					$scope.$emit('tms.editable.activate', null);
				} else {
					$scope.$apply(function() {
						$scope.$emit('tms.editable.activate', null);
					});
				}
			}
		}

		return {
			restrict: 'A',
			scope: {},
			controller: ['$scope', function($scope) {
				var _ctrl = this;

				function createBar() {
					var pos, btnSave, btnCancel;
					if (activeNode) {
						bar = document.createElement('div');
						btnSave = document.createElement('button');
						btnSave.innerHTML = '保存';
						btnSave.onclick = function(event) {
							_ctrl.acceptAndClose();
						};
						btnCancel = document.createElement('button');
						btnCancel.innerHTML = '取消';
						btnCancel.onclick = function(event) {
							_ctrl.cancelAncClose();
						};
						bar.appendChild(btnSave);
						bar.appendChild(btnCancel);
						bar.style.position = 'absolute';
						pos = activeNode.getBoundingClientRect();
						bar.style.top = (pos.bottom + 4 + window.pageYOffset) + 'px';
						bar.style.left = pos.left + 'px';
						document.body.appendChild(bar);
					}
				}

				function removeBar() {
					if (bar) {
						document.body.removeChild(bar);
						bar = null;
					}
				}

				this.isEditing = function() {
					return isEditing;
				};
				this.getActiveNode = function() {
					return activeNode;
				};
				this.activate = function(node, target) {
					var phase;
					if (activeNode) {
						activeNode.classList.remove('tms-active');
					}
					if (node) {
						node.classList.add('tms-active');
					}
					activeNode = node;
					activeTarget = target;
					phase = $scope.$root.$$phase;
					if (phase === '$digest' || phase === '$apply') {
						$scope.$emit('tms.editable.activate', activeTarget);
					} else {
						$scope.$apply(function() {
							$scope.$emit('tms.editable.activate', activeTarget);
						});
					}
				};
				this.beginEdit = function() {
					if (activeNode) {
						isEditing = true;
						fixedContent = activeNode.innerHTML;
						activeNode.setAttribute('contenteditable', true);
						activeNode.focus();
						createBar();
					}
				};
				this.stopEdit = function() {
					if (activeNode) {
						fixedContent = null;
						isEditing = false;
						activeNode.removeAttribute('contenteditable');
						removeBar();
					}
				};
				this.acceptAndClose = function() {
					var newContent;

					if (activeNode) {
						newContent = activeNode.innerHTML
						fixedContent = newContent;
						activeNode.removeAttribute('contenteditable');
					}
					if (activeTarget) {
						$scope.$apply(function() {
							activeTarget.obj[activeTarget.prop] = newContent;
							$scope.$emit('tms.editable.save', activeTarget);
						});
					}
				};
				this.cancelAncClose = function() {
					if (activeNode) {
						activeNode.removeAttribute('contenteditable');
						activeNode.innerHTML = fixedContent;
					}
				};
				this.enterKeyDown = function() {
					if (activeTarget) {
						$scope.$apply(function() {
							$scope.$emit('tms.editable.keydown.enter', activeTarget);
						});
					}
				};
				this.hasChanged = function() {
					if (activeNode) {
						return activeNode.innerHTML !== fixedContent;
					} else {
						return false;
					}
				};
			}],
			link: function($scope, elem, attrs) {
				elem.on('click', function(event) {
					deactivate($scope);
				});
			}
		};
	}]);

	ngApp.provider.directive('tmsEditableNode', [function() {
		return {
			restrict: 'A',
			scope: false,
			require: '^^tmsEditable',
			link: function($scope, elem, attrs, ctrl) {
				function getTarget(attrs) {
					var model, obj, prop;
					if (attrs.ngBind) {
						model = attrs.ngBind.split('.');
						obj = $scope;
						prop = model.pop();
						model.forEach(function(p) {
							obj = obj[p];
						});
						return {
							obj: obj,
							prop: prop
						};
					}

					return null;
				}
				elem.on('keydown', function(event) {
					if (event.keyCode === 13) {
						event.preventDefault();
						event.stopPropagation();
						ctrl.acceptAndClose();
						ctrl.enterKeyDown();
					}
				});
				elem.on('click', function(event) {
					event.preventDefault();
					event.stopPropagation();
					if (!ctrl.getActiveNode()) {
						ctrl.activate(this, getTarget(attrs));
					} else {
						if (!ctrl.isEditing()) {
							if (this === ctrl.getActiveNode()) {
								ctrl.beginEdit();
							} else {
								ctrl.activate(this, getTarget(attrs));
							}
						}
					}
				});
				elem.on('blur', function(event) {
					event.preventDefault();
					event.stopPropagation();
					if (ctrl.hasChanged()) {
						//内容已经修改，不允许丢失焦点
						this.focus();
					} else {
						ctrl.stopEdit();
					}
				});
				$scope.$on('tms.editable.edit', function(event, target) {
					var myTarget = getTarget(attrs);
					if (myTarget && target) {
						if (myTarget.obj === target.obj && myTarget.prop === target.prop) {
							ctrl.activate(elem[0], myTarget);
							ctrl.beginEdit();
						}
					}
				});
			}
		};
	}]);

	ngApp.provider.controller('ctrlPrepare', ['$scope', '$timeout', 'http2', function($scope, $timeout, http2) {
		$scope.data_schemas = [{
			"id": "c1",
			"title": "信息1",
			"type": "shorttext"
		}, {
			"id": "c2",
			"title": "信息2",
			"type": "longtext"
		}, {
			"id": "c1001",
			"title": "投票项1",
			"type": "single",
			"score": "Y",
			"ops": [{
				"v": "v1",
				"l": "非常同意",
				"score": 5
			}, {
				"v": "v2",
				"l": "同意",
				"score": 4
			}, {
				"v": "v3",
				"l": "一般",
				"score": 3
			}, {
				"v": "v4",
				"l": "有待提高",
				"score": 2
			}, {
				"v": "v5",
				"l": "不同意",
				"score": 1
			}]
		}];
		$scope.$on('tms.editable.save', function(event, target) {
			console.log(arguments);
		});
		$scope.$on('tms.editable.activate', function(event, target) {
			console.log(arguments);
			if (target) {
				$scope.activeObject = target.obj;
			} else {
				$scope.activeObject = null;
			}
		});
		$scope.append = function(beforeSchema) {
			var pos, newSchema;

			pos = beforeSchema ? $scope.data_schemas.indexOf(beforeSchema) + 1 : null;
			newSchema = {
				"id": "c" + (new Date() * 1),
				"title": "信息1",
				"type": "shorttext"
			};
			if (pos === null) {
				$scope.data_schemas.push(newSchema);
			} else {
				$scope.data_schemas.splice(pos, 0, newSchema);
			}
			$timeout(function() {
				$scope.$broadcast('tms.editable.edit', {
					obj: newSchema,
					prop: 'title'
				});
			});
		};
		$scope.insert = function(afterSchema) {
			var pos, newSchema;

			pos = afterSchema ? $scope.data_schemas.indexOf(afterSchema) : 0;
			newSchema = {
				"id": "c" + (new Date() * 1),
				"title": "信息1",
				"type": "shorttext"
			};
			$scope.data_schemas.splice(pos, 0, newSchema);
			$timeout(function() {
				$scope.$broadcast('tms.editable.edit', {
					obj: newSchema,
					prop: 'title'
				});
			});
		};
	}]);
});