define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlRecommend', ['$scope', 'http2', function($scope, http2) {
		$scope.criteria = {
			category: 'template'
		};
	}]);
	ngApp.provider.controller('ctrlTemplate', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
		$scope.page = {
			at: 1,
			size: 30,
		};
		$scope.searchTemplate = function() {
			var url = '/rest/pl/fe/template/platform/list?matterType=enroll';
			http2.get(url, function(rsp) {
				$scope.templates = rsp.data.templates;
				$scope.page.total = rsp.data.total;
			});
		};
		$scope.preview = function(template) {
			$uibModal.open({
				templateUrl: 'previewTemplate.html',
				controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
					$scope.cancel = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close($scope.data);
					};
				}],
				backdrop: 'static'
			}).result.then(function(data) {
				var url = '/rest/pl/be/home/recommend/pushTemplate?template=' + template.id;
				http2.post(url, {}, function(rsp) {
					template.push_home = 'Y';
				});
			});
		};
		$scope.pullHome = function(template) {
			var url = '/rest/pl/be/home/recommend/pullTemplate?template=' + template.id;
			http2.post(url, {}, function(rsp) {
				template.push_home = 'N';
			});
		};
		$scope.searchTemplate();
	}]);
	ngApp.provider.controller('ctrlMatter', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
		$scope.page = {
			at: 1,
			size: 30,
		};
		$scope.searchMatter = function() {
			var url = '/rest/pl/be/home/recommend/listMatter?category=' + $scope.criteria.category;
			http2.get(url, function(rsp) {
				$scope.matters = rsp.data.matters;
				$scope.page.total = rsp.data.total;
			});
		};
		$scope.preview = function(application) {
			$uibModal.open({
				templateUrl: 'previewMatter.html',
				controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
					$scope.cancel = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close($scope.data);
					};
				}],
				backdrop: 'static'
			}).result.then(function(data) {
				var url = '/rest/pl/be/home/recommend/pushMatter?application=' + application.id;
				http2.post(url, {}, function(rsp) {
					application.approved = 'Y';
				});
			});
		};
		$scope.pullHome = function(application) {
			var url = '/rest/pl/be/home/recommend/pullMatter?application=' + application.id;
			http2.post(url, {}, function(rsp) {
				application.approved = 'N';
			});
		};
		$scope.searchMatter();
	}]);
	ngApp.provider.controller('ctrlSite', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
		$scope.page = {
			at: 1,
			size: 30,
		};
		$scope.searchApplication = function() {
			var url = '/rest/pl/be/home/recommend/listSite';
			http2.get(url, function(rsp) {
				$scope.sites = rsp.data.sites;
				$scope.page.total = rsp.data.total;
			});
		};
		$scope.open = function(application) {
			$uibModal.open({
				templateUrl: 'previewMatter.html',
				controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
					$scope.cancel = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close($scope.data);
					};
				}],
				backdrop: 'static'
			}).result.then(function(data) {
				var url = '/rest/pl/be/home/recommend/pushSite?application=' + application.id;
				http2.post(url, {}, function(rsp) {
					application.approved = 'Y';
				});
			});
		};
		$scope.pullHome = function(application) {
			var url = '/rest/pl/be/home/recommend/pullSite?application=' + application.id;
			http2.post(url, {}, function(rsp) {
				application.approved = 'N';
			});
		};
		$scope.searchApplication();
	}]);
});