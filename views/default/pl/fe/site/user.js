var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt', 'profile.user.xxt']);
ngApp.config(['$locationProvider', '$routeProvider', '$controllerProvider', function($lp, $rp, $cp) {
	ngApp.provider = {
		controller: $cp.register
	};
	$rp.when('/rest/pl/fe/site/user/profile', {
		templateUrl: '/views/default/pl/fe/site/user/profile.html?_=1',
		controller: 'ctrlProfile',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/site/user/profile.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/site/user/member', {
		templateUrl: '/views/default/pl/fe/site/user/member.html?_=1',
		controller: 'ctrlMember',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/site/user/member.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).otherwise({
		templateUrl: '/views/default/pl/fe/site/user/account.html?_=1',
		controller: 'ctrlAccount',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/site/user/account.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	});
	$lp.html5Mode(true);
}]);
ngApp.controller('ctrlSite', ['$scope', '$location', 'http2', 'userProfile', function($scope, $location, http2, userProfile) {
	$scope.siteId = $location.search().site;
	$scope.currentMemberSchema = function(schemaId) {
		var i;
		for (i in $scope.memberSchemas) {
			if ($scope.memberSchemas[i].id == schemaId) {
				return $scope.memberSchemas[i];
			}
		}
		return false;
	};
	$scope.openProfile = function(userid) {
		userProfile.open($scope.siteId, userid, $scope.memberSchemas);
	};
	http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId, function(rsp) {
		$scope.site = rsp.data;
	});
	http2.get('/rest/pl/fe/site/member/schema/list?site=' + $scope.siteId, function(rsp) {
		$scope.memberSchemas = rsp.data;
	});
}]);