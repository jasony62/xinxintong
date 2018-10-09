define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'http.ui.xxt', 'notice.ui.xxt', 'service.matter']);
    ngApp.config(['$locationProvider', '$controllerProvider', '$routeProvider', '$provide', 'srvSiteProvider', function($lp, $cp, $rp, $provide, srvSiteProvider) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/fe/site/sns/qy/';
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
        $rp.when('/rest/pl/fe/site/sns/qy/setting', new RouteParam('setting'))
            .when('/rest/pl/fe/site/sns/qy/text', new RouteParam('text'))
            .when('/rest/pl/fe/site/sns/qy/menu', new RouteParam('menu'))
            .when('/rest/pl/fe/site/sns/qy/other', new RouteParam('other'))
            .when('/rest/pl/fe/site/sns/qy/relay', new RouteParam('relay'))
            .when('/rest/pl/fe/site/sns/qy/page', new RouteParam('page'))
            .when('/rest/pl/fe/site/sns/qy/customapi', new RouteParam('customapi'))
            .otherwise(new RouteParam('setting'));
    }]);
    ngApp.controller('ctrlQy', ['$scope', '$location', 'http2', function($scope, $location, http2) {
        $scope.subView = '';
        $scope.siteId = $location.search().site;
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'qy' ? 'setting' : subView[1];
        });
        http2.get('/rest/pl/fe/site/sns/qy/get?site=' + $scope.siteId).then(function(rsp) {
            $scope.qy = rsp.data;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});