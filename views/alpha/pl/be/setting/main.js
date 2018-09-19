define(['require'], function(require) {
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms']);
    ngApp.config(['$locationProvider', '$provide', '$controllerProvider', '$routeProvider', function($lp, $provide, $cp, $rp) {
        var RouteParam = function(name, loadjs) {
            var baseURL;
            baseURL = name === 'notice' ? '/views/default/pl/be/setting/' : '/views/default/pl/fe/site/setting/';
            this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
            if (loadjs) {
                this.resolve = {
                    load: function($q) {
                        var defer = $q.defer();
                        require([baseURL + name + '.js'], function() {
                            defer.resolve();
                        });
                        return defer.promise;
                    }
                };
            }
        };
        $lp.html5Mode(true);
        ngApp.provider = {
            controller: $cp.register,
            service: $provide.service,
        };
        $rp
            .when('/rest/pl/be/setting/tmplmsg', new RouteParam('tmplmsg', true))
            .when('/rest/pl/be/setting/notice', new RouteParam('notice', true))
            .otherwise(new RouteParam('tmplmsg', true));
    }]);
    ngApp.controller('ctrlSetting', ['$scope', 'http2', function($scope, http2) {
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.split('/');
            $scope.subView = subView ? (subView[subView.length-1] === 'setting' ? 'tmplmsg' : subView[subView.length-1]) : 'tmplmsg';
        });
        $scope.siteId = 'platform';
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
