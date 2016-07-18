define(['require', 'ngSanitize', 'tmsUI'], function(require) {
	'use strict';
	var ngApp = angular.module('app', ['ngRoute', 'ui.tms']);
	ngApp.config(['$locationProvider', function($locationProvider) {
		$locationProvider.html5Mode(true);
	}]);
	ngApp.controller('ctrlApp', ['$scope', '$location', '$q', 'http2', 'noticebox', function($scope, $location, $q, http2, noticebox) {
		var inviteCode = $location.search().code;
		$scope.accept = function() {
			var url = '/rest/pl/fe/matter/mission/coworker/acceptInvite?code=' + inviteCode;
			http2.get(url, function(rsp) {
				var acl = rsp.data;
				location.href = '/rest/pl/fe/matter/mission?site=' + acl.siteid + '&id=' + acl.mission_id;
			});
		};
		http2.get('/rest/pl/fe/matter/mission/coworker/invite?code=' + inviteCode, function(rsp) {
			if (rsp.err_code != '0') {
				$scope.errmsg = rsp.err_msg;
			} else {
				$scope.task = rsp.data;
			}
		}, {
			autoBreak: false,
			autoNotice: false,
		});
	}]);
	/***/
	require(['domReady!'], function(document) {
		angular.bootstrap(document, ["app"]);
	});
	/***/
	return ngApp;
});