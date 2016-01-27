define(["require", "angular", "angular-sanitize"], function(require, angular) {
	'use strict';
	var app = angular.module('app', [
		'ngSanitize',
	]);
	app.config(['$controllerProvider', function($cp) {
		app.register = {
			controller: $cp.register
		};
	}]);
	app.controller('ctrl', ['$scope', '$timeout', function($scope, $timeout) {
		$scope.data = [];
		$timeout(function() {
			var i = 0;
			while (i < 100) {
				$scope.data.push('data:' + i);
				i++;
			}
			document.querySelector('.loading').style.display = 'none';
		}, 3000);
	}]);
	require(['domReady!'], function(document) {
		angular.bootstrap(document, ["app"]);
	});
});