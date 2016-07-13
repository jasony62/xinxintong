define(['require', 'ngSanitize', 'tmsUI'], function(require) {
	'use strict';
	var ngApp = angular.module('app', ['ngRoute', 'ui.tms']);
	ngApp.config(['$locationProvider', function($locationProvider) {
		$locationProvider.html5Mode(true);
	}]);
	ngApp.controller('ctrlApp', ['$scope', '$location', '$q', 'http2', 'noticebox', function($scope, $location, $q, http2, noticebox) {
		var siteId, missionId, inviteCode;
		siteId = $location.search().site;
		missionId = $location.search().id;
		inviteCode = '';
		$scope.accept = function() {
			var url = '/rest/pl/fe/matter/mission/coworker/accept?site=' + siteId + '&mission=' + missionId + '&code=' + inviteCode;
			http2.get(url, function(rsp) {
				location.href = '/rest/pl/fe/matter/mission?site=' + siteId + '&id=' + missionId;
			});
		};
	}]);
	/***/
	require(['domReady!'], function(document) {
		angular.bootstrap(document, ["app"]);
	});
	/***/
	return ngApp;
});