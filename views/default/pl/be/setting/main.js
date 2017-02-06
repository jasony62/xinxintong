define(['require'], function(require) {
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms']);
    ngApp.config(['$locationProvider', '$provide', '$controllerProvider', '$routeProvider', function($lp, $provide, $cp, $rp) {
        var RouteParam = function(name, loadjs) {
            var baseURL = '/views/default/pl/be/setting/';
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
        $rp.when('/rest/pl/fe/site/setting/notice', new RouteParam('notice', true))
            .otherwise(new RouteParam('notice', true));
    }]);
    ngApp.controller('ctrlSetting', ['$scope', 'http2', function($scope, http2) {}]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
