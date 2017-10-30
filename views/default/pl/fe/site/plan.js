define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt']);
    ngApp.config(['$locationProvider', function($locationProvider) {
        $locationProvider.html5Mode(true);
    }]);
    ngApp.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
        var _oProto, _oBeforeProto;
        $scope.proto = _oProto = {};
        _oBeforeProto = angular.copy(_oProto);
        $scope.doCreate = function() {
            http2.post('/rest/pl/fe/site/create', _oProto, function(rsp) {
                location.href = '/rest/pl/fe/site/setting?site=' + rsp.data.id;
            });
        };
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});