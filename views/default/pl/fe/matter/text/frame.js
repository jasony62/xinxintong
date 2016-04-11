ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
ngApp.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
	$routeProvider.when('/rest/pl/fe/matter/news', {
		templateUrl: '/views/default/pl/fe/matter/text/setting.html?_=2',
		controller: 'ctrlSetting',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/text/setting.html?_=2',
		controller: 'ctrlSetting'
	});
	$locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlText', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.siteId = ls.site;
}]);
ngApp.controller('ctrlSetting', ['$scope', 'http2', function($scope, http2) {
	$scope.create = function() {
		var obj = {
			title: '新文本素材',
		};
		http2.post('/rest/pl/fe/matter/text/create?site=' + $scope.siteId, obj, function(rsp) {
			$scope.texts.splice(0, 0, rsp.data);
			$scope.selectOne(0);
		});
	};
	$scope.deleteOne = function(event) {
		event.preventDefault();
		event.stopPropagation();
		http2.get('/rest/mp/matter/text/delete?site=' + $scope.siteId + '&id=' + $scope.editing.id, function(rsp) {
			$scope.texts.splice($scope.selectedIndex, 1);
			if ($scope.texts.length == 0) {
				alert('empty');
			} else if ($scope.selectedIndex == $scope.texts.length) {
				$scope.selectOne($scope.selectedIndex - 1);
			} else {
				$scope.selectOne($scope.selectedIndex);
			}
		});
	};
	$scope.selectOne = function(index) {
		$scope.selectedIndex = index;
		$scope.editing = $scope.texts[index];
	};
	$scope.update = function(prop) {
		var p = {};
		p[prop] = $scope.editing[prop];
		http2.post('/rest/pl/fe/matter/text/update?site=' + $scope.siteId + '&id=' + $scope.editing.id, p);
	};
	$scope.doSearch = function() {
		var url = '/rest/pl/fe/matter/text/list?site=' + $scope.siteId,
			params = {};
		http2.get(url, function(rsp) {
			$scope.texts = rsp.data;
			if ($scope.texts.length > 0)
				$scope.selectOne(0);
		});
	};
	$scope.doSearch();
}]);
ngApp.filter("truncate", function() {
	return function(text, length) {
		if (text) {
			var ellipsis = text.length > length ? "..." : "";
			return text.slice(0, length) + ellipsis;
		};
		return text;
	}
});