app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt', 'channel.fe.pl']);
app.config(['$controllerProvider', '$routeProvider', '$locationProvider', function($controllerProvider, $routeProvider, $locationProvider) {
	app.register = {
		controller: $controllerProvider.register
	};
	$routeProvider.when('/rest/pl/fe/matter/enroll/setting', {
		templateUrl: '/views/default/pl/fe/matter/enroll/setting.html?_=1',
		controller: 'ctrlSetting',
	}).when('/rest/pl/fe/matter/enroll/page', {
		templateUrl: '/views/default/pl/fe/matter/enroll/page.html?_=1',
		controller: 'ctrlPage',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/page.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/enroll/setting.html?_=1',
		controller: 'ctrlSetting'
	});
	$locationProvider.html5Mode(true);
}]);
app.factory('Mp', function($q, http2) {
	var Mp = function() {};
	Mp.prototype.getAuthapis = function(id) {
		var _this = this,
			deferred = $q.defer(),
			promise = deferred.promise;
		if (_this.authapis !== undefined) {
			deferred.resolve(_this.authapis);
		} else {
			http2.get('/rest/mp/authapi/get?valid=Y', function(rsp) {
				_this.authapis = rsp.data;
				deferred.resolve(rsp.data);
			});
		}
		return promise;
	};
	return Mp;
});
app.controller('ctrlArticle', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.mpid = ls.mpid;
	http2.get('/rest/mp/app/enroll/get?aid=' + $scope.id + '&mpid=' + $scope.mpid, function(rsp) {
		$scope.editing = rsp.data;
		$scope.editing.tags = (!$scope.editing.tags || $scope.editing.tags.length === 0) ? [] : $scope.editing.tags.split(',');
		$scope.editing.type = 'enroll';
	});
}]);
app.controller('ctrlSetting', ['$scope', 'http2', function($scope, http2) {
	$scope.pages4OutAcl = [];
	$scope.pages4Unauth = [];
	$scope.pages4Nonfan = [];
	$scope.$watch('editing.pages', function(nv) {
		var newPage;
		if (!nv) return;
		$scope.pages4OutAcl = $scope.editing.access_control === 'Y' ? [{
			name: '$authapi_outacl',
			title: '提示白名单'
		}] : [];
		$scope.pages4Unauth = $scope.editing.access_control === 'Y' ? [{
			name: '$authapi_auth',
			title: '提示认证'
		}] : [];
		$scope.pages4Nonfan = [{
			name: '$mp_follow',
			title: '提示关注'
		}];
		for (var p in nv) {
			newPage = {
				name: nv[p].name,
				title: nv[p].title
			};
			$scope.pages4OutAcl.push(newPage);
			$scope.pages4Unauth.push(newPage);
			$scope.pages4Nonfan.push(newPage);
		}
	}, true);
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
		http2.post('/rest/mp/app/enroll/update?aid=' + $scope.id, modifiedData, function(rsp) {
			$scope.modified = false;
			modifiedData = {};
		});
	};
	$scope.update = function(name) {
		if (name === 'entry_rule') {
			modifiedData.entry_rule = encodeURIComponent($scope.editing[name]);
		} else if (name === 'tags') {
			modifiedData.tags = $scope.editing.tags.join(',');
		} else {
			modifiedData[name] = $scope.editing[name];
		}
		$scope.modified = true;
	};
	$scope.setPic = function() {
		var options = {
			callback: function(url) {
				$scope.editing.pic = url + '?_=' + (new Date()) * 1;
				$scope.update('pic');
			}
		};
		$scope.$broadcast('mediagallery.open', options);
	};
	$scope.removePic = function() {
		var nv = {
			pic: ''
		};
		http2.post('/rest/mp/app/enroll/update?aid=' + $scope.aid, nv, function() {
			$scope.editing.pic = '';
		});
	};
	$scope.gotoPage = function(name) {
		location.href = '/rest/pl/fe/matter/enroll/page?id=' + $scope.id + '&mpid=' + $scope.mpid + '&page=' + name;
	};
}]);