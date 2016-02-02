define(["angular"], function(angular) {
	'use strict';
	angular.module('app', []).controller('ctrl', ['$scope', '$timeout', function($scope, $timeout) {
		$scope.data = [];
		$timeout(function() {
			for (var i = 0; i < 100; i++) {
				$scope.data.push('data:' + i);
			}
			$timeout(function() {
				var link, head;
				link = document.createElement('link');
				link.href = "/test/loading/app.css?_=" + (new Date()).getTime();
				link.rel = 'stylesheet';
				link.onload = function() {
					var eleLoading, eleStyle;
					eleLoading = document.querySelector('.loading');
					eleLoading.parentNode.removeChild(eleLoading);
					eleStyle = document.querySelector('#loadingStyle');
					eleStyle.parentNode.removeChild(eleStyle);
				};
				head = document.querySelector('head');
				head.appendChild(link);
			}, 2000);
		}, 2000);
	}]);
});