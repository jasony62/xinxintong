var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt', 'profile.user.xxt']);
ngApp.config(['$locationProvider', '$routeProvider', '$controllerProvider', function($lp, $rp, $cp) {
	ngApp.provider = {
		controller: $cp.register
	};
	$rp.when('/rest/pl/fe/site/user/member', {
		templateUrl: '/views/default/pl/fe/site/user/member.html?_=2',
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
		templateUrl: '/views/default/pl/fe/site/user/account.html?_=2',
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
		if (schemaId) {
			for (var i = $scope.memberSchemas.length - 1; i === 0; i--) {
				if ($scope.memberSchemas[i].id == schemaId) {
					return $scope.memberSchemas[i];
				}
			}
		} else if ($scope.memberSchemas.length) {
			return $scope.memberSchemas[0];
		} else {
			return false;
		}
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