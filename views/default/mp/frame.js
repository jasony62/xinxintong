xxtApp.config(['$locationProvider', '$controllerProvider', function($locationProvider, $controllerProvider) {
	$locationProvider.html5Mode(true);
	xxtApp.register = {
		controller: $controllerProvider.register
	};
}]);
xxtApp.controller('mpCtrl', ['$rootScope', '$q', 'http2', function($rootScope, $q, http2) {
	$rootScope.$on('xxt.notice-box.timeout', function(event, name) {
		$rootScope.infomsg = $rootScope.errmsg = $rootScope.progmsg = '';
	});
}]);