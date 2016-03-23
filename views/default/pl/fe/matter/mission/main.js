app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
app.config(['$controllerProvider', '$locationProvider', function($controllerProvider, $locationProvider) {
	app.provider = {
		controller: $controllerProvider.register
	};
	$locationProvider.html5Mode(true);
}]);
app.controller('ctrlApp', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	$scope.id = $location.search().id;
	$scope.siteId = $location.search().site;
	$scope.back = function() {
		history.back();
	};
	$scope.$on('$routeChangeSuccess', function(evt, nextRoute, lastRoute) {
		if (nextRoute.loadedTemplateUrl.indexOf('/setting') !== -1) {
			$scope.subView = 'setting';
		} else if (nextRoute.loadedTemplateUrl.indexOf('/matter') !== -1) {
			$scope.subView = 'matter';
		}
	});
	http2.get('/rest/pl/fe/matter/mission/get?id=' + $scope.id, function(rsp) {
		$scope.editing = rsp.data;
		$scope.editing.type = 'mission';
	});
}]);
app.controller('ctrlSetting', ['$scope', 'http2', 'matterTypes', '$modal', 'mediagallery', function($scope, http2, matterTypes, $modal, mediagallery) {
	$scope.matterTypes = matterTypes;
	var modifiedData = {};
	$scope.modified = false;
	window.onbeforeunload = function(e) {
		var message;
		if ($scope.modified) {
			message = '修改还没有保存，是否要离开当前页面？',
				e = e || window.event;
			if (e) {
				e.returnValue = message;
			}
			return message;
		}
	};
	$scope.submit = function() {
		http2.post('/rest/pl/fe/matter/mission/setting/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function(rsp) {
			$scope.modified = false;
			modifiedData = {};
		});
	};
	$scope.update = function(name) {
		modifiedData[name] = $scope.editing[name];
		$scope.modified = true;
	};
	$scope.setPic = function() {
		var options = {
			callback: function(url) {
				$scope.editing.pic = url + '?_=' + (new Date()) * 1;
				$scope.update('pic');
			}
		};
		mediagallery.open($scope.siteid, options);
	};
	$scope.removePic = function() {
		var nv = {
			pic: ''
		};
		http2.post('/rest/pl/fe/matter/mission/setting/update?site=' + $scope.siteId + '&id=' + $scope.id, nv, function() {
			$scope.editing.pic = '';
		});
	};
}]);
app.controller('ctrlMatter', ['$scope', 'http2', function($scope, http2) {
	$scope.createArticle = function() {
		http2.get('/rest/pl/fe/matter/article/createByMission?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
			location.href = '/rest/pl/fe/matter/article?site=' + $scope.siteId + '&id=' + rsp.data.id;
		});
	};
	$scope.createEnroll = function() {
		http2.get('/rest/pl/fe/matter/enroll/createByMission?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
			location.href = '/rest/pl/fe/matter/enroll?site=' + $scope.siteId + '&id=' + rsp.data.id;
		});
	};
	$scope.open = function(matter) {
		if (matter.type === 'article') {
			location.href = '/rest/pl/fe/matter/article?site=' + $scope.siteId + '&id=' + matter.id;
		} else if (matter.type === 'enroll') {
			location.href = '/rest/pl/fe/matter/enroll?site=' + $scope.siteId + '&id=' + matter.id;
		}
	};
	$scope.fetch = function() {
		http2.get('/rest/pl/fe/matter/mission/matter/list?site=' + $scope.siteId + '&id=' + $scope.id + '&_=' + (new Date()).getTime(), function(rsp) {
			angular.forEach(rsp.data, function(matter) {
				matter._operator = matter.modifier_name || matter.creater_name;
				matter._operateAt = matter.modifiy_at || matter.create_at;
			});
			$scope.matters = rsp.data;
		});
	};
	$scope.fetch();
}]);