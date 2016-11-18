define(["angular", "xxt-page"], function(angular, codeAssembler) {
	'use strict';
	var ngApp = angular.module('home', ['ui.bootstrap', 'ui.tms']);
	ngApp.config(['$controllerProvider', function($cp) {
		ngApp.provider = {
			controller: $cp.register
		};
	}]);
	ngApp.controller('ctrlMain', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
		var ls = location.search,
			siteId = ls.match(/site=([^&]*)/)[1];

		$scope.favorTemplate = function(template) {
			if ($scope.isLogin === 'N') {
				location.href = '/rest/pl/fe/user/login';
			} else {
				var url = '/rest/pl/fe/template/siteCanFavor?template=' + template.id + '&_=' + (new Date() * 1);
				http2.get(url, function(rsp) {
					var sites = rsp.data;
					$uibModal.open({
						templateUrl: 'favorTemplateSite.html',
						dropback: 'static',
						controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
							$scope2.mySites = sites;
							$scope2.ok = function() {
								var selected = [];
								sites.forEach(function(site) {
									site._selected === 'Y' && selected.push(site);
								});
								if (selected.length) {
									$mi.close(selected);
								} else {
									$mi.dismiss();
								}
							};
							$scope2.cancel = function() {
								$mi.dismiss();
							};
						}]
					}).result.then(function(selected) {
						var url = '/rest/pl/fe/template/favor?template=' + template.id,
							sites = [];

						selected.forEach(function(site) {
							sites.push(site.id);
						});
						url += '&site=' + sites.join(',');
						http2.get(url, function(rsp) {});
					});
				});
			}
		};
		$scope.useTemplate = function(template) {
			if ($scope.isLogin === 'N') {
				location.href = '/rest/pl/fe/user/login';
			} else {
				var url = '/rest/pl/fe/site/list?_=' + (new Date() * 1);
				http2.get(url, function(rsp) {
					var sites = rsp.data;
					$uibModal.open({
						templateUrl: 'useTemplateSite.html',
						dropback: 'static',
						controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
							var data;
							$scope2.mySites = sites;
							$scope2.data = data = {};
							$scope2.ok = function() {
								if (data.index !== undefined) {
									$mi.close(sites[data.index]);
								} else {
									$mi.dismiss();
								}
							};
							$scope2.cancel = function() {
								$mi.dismiss();
							};
						}]
					}).result.then(function(site) {
						var url = '/rest/pl/fe/template/purchase?template=' + template.id;
						url += '&site=' + site.id;
						http2.get(url, function(rsp) {
							http2.get('/rest/pl/fe/matter/enroll/createByOther?site=' + site.id + '&template=' + template.id, function(rsp) {
								location.href = '/rest/pl/fe/matter/enroll?id=' + rsp.data.id + '&site=' + site.id;
							});
						});
					});
				});
			}
		};
		$scope.subscribeSite = function() {
			if ($scope.isLogin === 'N') {
				location.href = '/rest/pl/fe/user/login';
			} else {
				var url = '/rest/pl/fe/site/siteCanSubscribe?site=' + $scope.site.id + '&_=' + (new Date() * 1);
				http2.get(url, function(rsp) {
					var sites = rsp.data;
					$uibModal.open({
						templateUrl: 'subscribeSite.html',
						dropback: 'static',
						controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
							$scope2.mySites = sites;
							$scope2.ok = function() {
								var selected = [];
								sites.forEach(function(site) {
									site._selected === 'Y' && selected.push(site);
								});
								if (selected.length) {
									$mi.close(selected);
								} else {
									$mi.dismiss();
								}
							};
							$scope2.cancel = function() {
								$mi.dismiss();
							};
						}]
					}).result.then(function(selected) {
						var url = '/rest/pl/fe/site/subscribe?site=' + $scope.site.id;
						sites = [];

						selected.forEach(function(mySite) {
							sites.push(mySite.id);
						});
						url += '&subscriber=' + sites.join(',');
						http2.get(url, function(rsp) {});
					});
				});
			}
		};
		http2.get('/rest/pl/fe/user/auth/isLogin', function(rsp) {
			$scope.isLogin = rsp.data;
		});
		http2.get('/rest/site/home/get?site=' + siteId, function(rsp) {
			codeAssembler.loadCode(ngApp, rsp.data.home_page).then(function() {
				$scope.site = rsp.data;
				$scope.page = rsp.data.home_page;
			});
		});
	}]);

	/*bootstrap*/
	angular._lazyLoadModule('home');

	return ngApp;
});