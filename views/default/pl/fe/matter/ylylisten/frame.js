define(['require'], function() {
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'http.ui.xxt', 'notice.ui.xxt', 'service.matter', 'member.xxt', 'channel.fe.pl']);
    ngApp.config(['$routeProvider', '$locationProvider', '$controllerProvider', 'srvSiteProvider', function($routeProvider, $locationProvider, $controllerProvider, srvSiteProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/pl/fe/matter/ylylisten/');
            this.templateUrl = baseURL + name + '.html?_=' + (new Date * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
            this.reloadOnSearch = false;
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
        ngApp.provider = {
            controller: $controllerProvider.register
        };
        $routeProvider
            .when('/rest/pl/fe/matter/ylylisten/preview', new RouteParam('preview'))
            .otherwise(new RouteParam('preview'));

        $locationProvider.html5Mode(true);

        //设置服务参数
        (function() {
            var siteId;
            siteId = location.search.match(/[\?&]site=([^&]*)/)[1];
            id = location.search.match(/[\?&]id=([^&]*)/)[1];
            srvSiteProvider.config(siteId);
        })();
    }]);
    ngApp.controller('ctrlYlylisten', ['$scope', '$location', 'http2', 'srvSite', function($scope, $location, http2, srvSite) {
        var ls = $location.search();
        $scope.id = ls.id;
        $scope.siteId = ls.site;
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'ylylisten' ? 'preview' : subView[1];
            switch ($scope.subView) {
                case 'preview':
                    $scope.opened = 'publish';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function(subView) {
            var url = '/rest/pl/fe/matter/ylylisten/' + subView;
            $location.path(url);
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});