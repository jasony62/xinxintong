define(['require'], function() {
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter', 'member.xxt', 'channel.fe.pl']);
    ngApp.config(['$routeProvider', '$locationProvider', '$controllerProvider', 'srvSiteProvider', 'srvTagProvider', 'srvInviteProvider', function($routeProvider, $locationProvider, $controllerProvider, srvSiteProvider, srvTagProvider, srvInviteProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/pl/fe/matter/link/');
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
            .when('/rest/pl/fe/matter/link/preview', new RouteParam('preview'))
            .when('/rest/pl/fe/matter/link/invite', new RouteParam('invite', '/views/default/pl/fe/_module/'))
            .when('/rest/pl/fe/matter/link/log', new RouteParam('log'))
            .otherwise(new RouteParam('main'));

        $locationProvider.html5Mode(true);

        //设置服务参数
        (function() {
            var siteId;
            siteId = location.search.match(/[\?&]site=([^&]*)/)[1];
            id = location.search.match(/[\?&]id=([^&]*)/)[1];
            srvSiteProvider.config(siteId);
            srvTagProvider.config(siteId);
            srvInviteProvider.config('link', id);
        })();
    }]);
    ngApp.controller('ctrlLink', ['$scope', '$location', 'http2', 'srvSite', function($scope, $location, http2, srvSite) {
        var ls = $location.search();
        $scope.id = ls.id;
        $scope.siteId = ls.site;
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'link' ? 'main' : subView[1];
            switch ($scope.subView) {
                case 'main':
                    $scope.opened = 'edit';
                    break;
                case 'preview':
                    $scope.opened = 'publish';
                    $scope.opened = 'invite';
                    break;
                case 'log':
                    $scope.opened = 'other';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function(subView) {
            var url = '/rest/pl/fe/matter/link/' + subView;
            $location.path(url);
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.tagList().then(function(oTag) {
            $scope.oTag = oTag;
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
        });
        http2.get('/rest/pl/fe/matter/link/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
            $scope.editing = rsp.data;
            $scope.entryUrl = 'http://' + location.host + '/rest/site/fe/matter/link?site=' + $scope.siteId + '&id=' + $scope.id;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});