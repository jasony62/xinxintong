xxtApp.config(['$locationProvider', '$controllerProvider', function ($locationProvider, $controllerProvider) {
    $locationProvider.html5Mode(true);
    xxtApp.register = { controller: $controllerProvider.register };
}]);
xxtApp.controller('mpCtrl', ['$rootScope', '$modal', '$q', 'http2', function ($rootScope, $modal, $q, http2) {
    $rootScope.$on('xxt.notice-box.timeout', function (event, name) {
        $rootScope.infomsg = $rootScope.errmsg = $rootScope.progmsg = '';
    });
    $rootScope.openShop = function () {
        $rootScope.$broadcast('xxt.float-toolbar.shop.open');
    };
}]); 
