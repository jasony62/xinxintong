define(['require'], function(require) {
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'http.ui.xxt', 'notice.ui.xxt', 'service.matter']);
    ngApp.config(['$locationProvider', '$controllerProvider', '$routeProvider', '$provide', 'srvSiteProvider', function($lp, $cp, $rp, $provide, srvSiteProvider) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/fe/site/sns/yx/';
            this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
            this.resolve = {
                load: function($q) {
                    var defer = $q.defer();
                    require([baseURL + name + '.js'], function() {
                        defer.resolve();
                    });
                    return defer.promise;
                }
            };
        };
        var siteId = location.search.match(/site=([^&]*)/)[1];
        srvSiteProvider.config(siteId);
        ngApp.provider = {
            controller: $cp.register,
            service: $provide.service
        };
        $lp.html5Mode(true);
        $rp.when('/rest/pl/fe/site/sns/yx/setting', new RouteParam('setting'))
            .when('/rest/pl/fe/site/sns/yx/massmsg', new RouteParam('massmsg'))
            .when('/rest/pl/fe/site/sns/yx/user', new RouteParam('user'))
            .when('/rest/pl/fe/site/sns/yx/text', new RouteParam('text'))
            .when('/rest/pl/fe/site/sns/yx/menu', new RouteParam('menu'))
            .when('/rest/pl/fe/site/sns/yx/qrcode', new RouteParam('qrcode'))
            .when('/rest/pl/fe/site/sns/yx/other', new RouteParam('other'))
            .when('/rest/pl/fe/site/sns/yx/relay', new RouteParam('relay'))
            .when('/rest/pl/fe/site/sns/yx/page', new RouteParam('page'))
            .otherwise(new RouteParam('setting'));
    }]);
    ngApp.controller('ctrlYx', ['$scope', '$location', 'http2', function($scope, $location, http2) {
        $scope.subView = '';
        $scope.siteId = $location.search().site;
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'yx' ? 'setting' : subView[1];
        });
        http2.get('/rest/pl/fe/site/sns/yx/get?site=' + $scope.siteId).then(function(rsp) {
            $scope.yx = rsp.data;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});