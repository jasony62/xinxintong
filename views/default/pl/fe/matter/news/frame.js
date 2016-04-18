ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
ngApp.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
	$routeProvider.when('/rest/pl/fe/matter/news', {
		templateUrl: '/views/default/pl/fe/matter/news/setting.html?_=2',
		controller: 'ctrlSetting',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/news/setting.html?_=2',
		controller: 'ctrlSetting'
	});
	$locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlNews', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.siteId = ls.site;
	http2.get('/rest/pl/fe/matter/news/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
		$scope.editing = rsp.data;
		$scope.entryUrl = 'http://' + location.host + '/rest/site/fe/matter?site=' + $scope.siteId + '&id=' + $scope.id + '&type=news';
	});
}]);
ngApp.controller('ctrlSetting', ['$scope', 'http2', 'mattersgallery', function($scope, http2, mattersgallery) {
	var modifiedData = {};
	$scope.modified = false;
	$scope.matterTypes = [{
		value: 'article',
		title: '单图文',
		url: '/rest/pl/fe/matter'
	}, {
		value: 'news',
		title: '多图文',
		url: '/rest/pl/fe/matter'
	}, {
		value: 'channel',
		title: '频道',
		url: '/rest/pl/fe/matter'
	}, {
		value: 'link',
		title: '链接',
		url: '/rest/pl/fe/matter'
	}, {
		value: 'enroll',
		title: '登记活动',
		url: '/rest/pl/fe/matter'
	}, {
		value: 'lottery',
		title: '抽奖活动',
		url: '/rest/pl/fe/matter'
	}];
	var updateMatters = function() {
		http2.post('/rest/pl/fe/matter/news/updateMatter?site=' + $scope.siteId + '&id=' + $scope.editing.id, $scope.editing.matters);
	};
	$scope.submit = function() {
		http2.post('/rest/pl/fe/matter/news/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function() {
			modifiedData = {};
			$scope.modified = false;
		});
	};
	$scope.update = function(name) {
		$scope.modified = true;
		modifiedData[name] = $scope.editing[name];
	};
	$scope.assign = function() {
		mattersgallery.open($scope.siteId, function(matters, type) {
			for (var i in matters) {
				matters[i].type = type;
			}
			$scope.editing.matters = $scope.editing.matters.concat(matters);
			updateMatters();
		}, {
			matterTypes: $scope.matterTypes,
			hasParent: false,
			singleMatter: false
		});
	};
	$scope.removeMatter = function(index) {
		$scope.editing.matters.splice(index, 1);
		updateMatters();
	};
	$scope.setEmptyReply = function() {
		mattersgallery.open($scope.siteId, function(matters, type) {
			if (matters.length === 1) {
				var p = {
					mt: type,
					mid: matters[0].id
				};
				http2.post('/rest/pl/fe/matter/news/setEmptyReply?site=' + $scope.siteId + '&id=' + $scope.editing.id, p, function(rsp) {
					$scope.editing.emptyReply = matters[0];
				});
			}
		}, {
			matterTypes: $scope.matterTypes,
			hasParent: false,
			singleMatter: true
		});
	};
	$scope.removeEmptyReply = function() {
		var p = {
			mt: '',
			mid: ''
		};
		http2.post('/rest/pl/fe/matter/news/setEmptyReply?site=' + $scope.siteId + '&id=' + $scope.editing.id, p, function(rsp) {
			$scope.editing.emptyReply = null;
		});
	};
	$scope.$on('my-sorted', function(ev, val) {
		// rearrange $scope.items
		$scope.editing.matters.splice(val.to, 0, $scope.editing.matters.splice(val.from, 1)[0]);
		for (var i = 0; i < $scope.editing.matters.length; i++) {
			$scope.editing.matters.seq = i;
		}
		updateMatters();
	});
}]);