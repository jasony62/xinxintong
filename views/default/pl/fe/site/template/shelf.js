define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlShelf', ['$scope', 'http2', function($scope, http2) {
		var criteria;
		$scope.criteria = criteria = {
			scope: 'S'
		};
		$scope.page = {
			size: 21,
			at: 1,
			total: 0
		};
		$scope.changeScope = function(scope) {
			criteria.scope = scope;
			if (scope === 'share2Me') {
				$scope.searchShare2Me()
			} else {
				$scope.searchTemplate();
			}
		};
		$scope.use = function(template) {
			var templateId, url;
			templateId = template.template_id || template.id;
			url = '/rest/pl/fe/template/purchase?template=' + templateId;
			url += '&site=' + $scope.siteId;
			http2.get(url, function(rsp) {
				http2.get('/rest/pl/fe/matter/enroll/createByOther?site=' + $scope.siteId + '&template=' + templateId, function(rsp) {
					location.href = '/rest/pl/fe/matter/enroll?id=' + rsp.data.id + '&site=' + $scope.siteId;
				});
			});
		};
		$scope.favor = function(template) {
			var url = '/rest/pl/fe/template/favor?template=' + template.id;
			url += '&site=' + $scope.siteId;
			http2.get(url, function(rsp) {
				template._favored = 'Y';
			});
		};
		$scope.unfavor = function(template, index) {
			var url = '/rest/pl/fe/template/unfavor?template=' + template.template_id;
			url += '&site=' + $scope.siteId;
			http2.get(url, function(rsp) {
				$scope.templates.splice(index, 1);
				$scope.page.total--;
			});
		};
		$scope.searchTemplate = function() {
			var url = '/rest/pl/fe/template/site/list?matterType=enroll&scope=' + criteria.scope;
			url += '&site=' + $scope.siteId;

			http2.get(url, function(rsp) {
				$scope.templates = rsp.data.templates;
				$scope.page.total = rsp.data.total;
			});
		};
		$scope.searchShare2Me = function() {
			var url = '/rest/pl/fe/template/platform/share2Me?matterType=enroll';
			url += '&site=' + $scope.siteId;

			http2.get(url, function(rsp) {
				$scope.templates = rsp.data.templates;
				$scope.page.total = rsp.data.total;
			});
		};
		$scope.searchTemplate();
	}]);
});