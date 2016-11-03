define(["angular", "xxt-page"], function(angular, codeAssembler) {
	'use strict';
	var ngApp = angular.module('home', ['ui.bootstrap']);
	ngApp.config(['$controllerProvider', function($cp) {
		ngApp.provider = {
			controller: $cp.register
		};
	}]);
	ngApp.controller('ctrlMain', ['$scope', '$http', function($scope, $http) {
		var ls = location.search,
			siteId = ls.match(/site=([^&]*)/)[1];

		$http.get('/rest/pl/fe/user/auth/isLogin').success(function(rsp) {
			$scope.isLogin = rsp.data;
		});
		$http.get('/rest/site/home/get?site=' + siteId).success(function(rsp) {
			if (rsp.err_code !== 0) {
				$scope.errmsg = rsp.err_msg;
				return;
			}
			codeAssembler.loadCode(ngApp, rsp.data.home_page).then(function() {
				$scope.site = rsp.data;
				$scope.page = rsp.data.home_page;
			});
		}).error(function(content, httpCode) {
			$scope.errmsg = content;
		});
	}]);

	/*bootstrap*/
	angular._lazyLoadModule('home');

	return ngApp;
});