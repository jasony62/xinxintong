define(['require'], function(require) {
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'http.ui.xxt', 'notice.ui.xxt']);
    ngApp.config(['$locationProvider', '$controllerProvider', '$routeProvider', function($lp, $cp, $rp) {
        var RouteParam = function(name, loadjs) {
            var baseURL = '/views/default/pl/fe/site/message/';
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
            controller: $cp.register
        };
        $rp.when('/rest/pl/fe/site/message/received', new RouteParam('received', true))
            .otherwise(new RouteParam('received', true));
    }]);
    ngApp.controller('ctrlMessage', ['$scope', '$location', 'http2', function($scope, $location, http2) {
        $scope.siteId = $location.search().site;
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView ? (subView[1] === 'message' ? 'received' : subView[1]) : 'received';
        });
        http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId).then(function(rsp) {
            $scope.site = rsp.data;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});