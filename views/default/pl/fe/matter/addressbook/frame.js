'use strict';
define(['require'], function() {
  var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'ui.xxt']);
  ngApp.config(['$routeProvider', '$locationProvider', '$controllerProvider', function($routeProvider, $locationProvider, $controllerProvider) {
    var RouteParam = function(name) {
      var baseURL = '/page/pl/fe/matter/addressbook/';
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
      .otherwise(new RouteParam('frame'));

    $locationProvider.html5Mode(true);
  }]);
  ngApp.controller('ctrlAddressbook', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    console.log(1);
  }]);
  /***/
  require(['domReady!'], function(document) {
    angular.bootstrap(document, ["app"]);
  });
  /***/
  return ngApp;
});