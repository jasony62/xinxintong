'use strict';
define(["angular"], function(angular) {
	var onloads = [];
	var loadCss = function(url) {
		var link, head;
		link = document.createElement('link');
		link.href = url;
		link.rel = 'stylesheet';
		head = document.querySelector('head');
		head.appendChild(link);
	};
	loadCss('//cdn.bootcss.com/bootstrap/3.3.6/css/bootstrap.min.css');
	require.config({
		paths: {
			'ui-bootstrap-tpls': '//cdn.bootcss.com/angular-ui-bootstrap/1.1.2/ui-bootstrap-tpls.min'
		},
		shim: {
			"ui-bootstrap-tpls": {
				deps: ['angular']
			}
		}
	});
	require(['ui-bootstrap-tpls'], function() {
		var m1 = angular.module('module1', ['ui.bootstrap']);
		m1.config(['$controllerProvider', function($controllerProvider) {
			console.log('module1 - config begin');
		}]);
		m1.controller('ctrlModule1', ['$scope', '$uibModal', function($scope, $uibModal) {
			console.log('module1 - ctrl begin');
			var dlg = '<div class="modal-header">';
			dlg += '<h3 class="modal-title">I\'m a modal!</h3>';
			dlg += '</div>';
			dlg += '<div class="modal-body">content</div>';
			dlg += '<div class="modal-footer">';
			dlg += '<button class="btn btn-primary" type="button" ng-click="ok()">OK</button>';
			dlg += '<button class="btn btn-warning" type="button" ng-click="cancel()">Cancel</button>';
			dlg += '</div>';
			$scope.openDialog = function() {
				$uibModal.open({
					template: dlg,
					controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
						$scope.cancel = function() {
							$mi.dismiss();
						};
						$scope.ok = function() {
							$mi.close();
						};
					}],
					backdrop: 'static'
				});
			};
		}]);
		angular._lazyLoadModule('module1');
		console.log('module1 loaded');
		angular.forEach(onloads, function(onload) {
			angular.isFunction(onload) && onload();
		});
	});
	return {
		onload: function(callback) {
			onloads.push(callback);
		}
	};
});