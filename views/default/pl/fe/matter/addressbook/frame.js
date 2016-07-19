define(['require'], function() {
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'ui.xxt', 'channel.fe.pl']);
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/fe/matter/addressbook/';
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
        ngApp.provider = {
            controller: $controllerProvider.register,
            directive: $compileProvider.directive
        };
        $routeProvider
            .when('/rest/pl/fe/matter/addressbook/edit/setting', new RouteParam('setting'))
            .when('/rest/pl/fe/matter/addressbook/edit/dept', new RouteParam('dept'))
            .when('/rest/pl/fe/matter/addressbook/edit/roll', new RouteParam('roll'))
            .otherwise(new RouteParam('setting'));

        $locationProvider.html5Mode(true);
        /*$uibTooltipProvider.setTriggers({
          'show': 'hide'
        });*/
    }]);
    ngApp.controller('ctrlFrame', ['$scope', '$location', '$uibModal', '$q', 'http2', 'noticebox', function($scope, $location, $uibModal, $q, http2, noticebox) {
        var ls = $location.search(),
            modifiedData = {};

        $scope.id = ls.id;
        $scope.siteId = ls.site;
        $scope.modified = false;

        $scope.getApp = function() {
            http2.get('/rest/pl/fe/site/snsList?site=' + $scope.siteId, function(rsp) {
                $scope.sns = rsp.data;
            });
            http2.get('/rest/pl/fe/site/member/schema/list?valid=Y&site=' + $scope.siteId, function(rsp) {
                $scope.memberSchemas = rsp.data;
            });
            http2.get('/rest/pl/fe/matter/addressbook/edit?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
                var app = rsp.data,
                    mapOfAppSchemas = {};
                app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
                app.type = 'addressbook';
                app.data_schemas = app.data_schemas && app.data_schemas.length ? JSON.parse(app.data_schemas) : [];
                angular.forEach(app.data_schemas, function(schema) {
                    mapOfAppSchemas[schema.id] = schema;
                });
                app.entry_rule.scope === undefined && (app.entry_rule.scope = 'none');
                angular.forEach(app.pages, function(page) {
                    angular.extend(page, pageLib);
                    page.arrange(mapOfAppSchemas);
                });
                $scope.app = app;
                $scope.url = 'http://' + location.host + '/rest/site/fe/matter/addressbook?site=' + $scope.siteId + '&app=' + $scope.id;
            });
        };
        $scope.getApp();
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
