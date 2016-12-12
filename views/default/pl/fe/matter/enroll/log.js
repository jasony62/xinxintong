define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlLog', ['$scope', 'http2', 'srvLog', function($scope, http2, srvLog) {
		var read;
		$scope.read = read = {
			page: {},
			list: function() {
				var _this = this;
				srvLog.list($scope.id, this.page).then(function(logs) {
					_this.logs = logs;
				});
			}
		};
		read.list();
	}]);
});