define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlLog', ['$scope', 'http2', 'srvEnrollLog', function($scope, http2, srvEnrollLog) {
		var read;
		$scope.read = read = {
			page: {},
			list: function() {
				var _this = this;
				srvEnrollLog.list($scope.app.id, this.page).then(function(logs) {
					_this.logs = logs;
				});
			}
		};
		read.list();
	}]);
});