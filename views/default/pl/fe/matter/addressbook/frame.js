define(['require'], function(require, pageLib) {
  'use strict';
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
      .when('/rest/pl/fe/matter/addressbook/setting', new RouteParam('setting'))
      .when('/rest/pl/fe/matter/addressbook/dept', new RouteParam('dept'))
      .when('/rest/pl/fe/matter/addressbook/roll', new RouteParam('roll'))
      .otherwise(new RouteParam('setting'));

    $locationProvider.html5Mode(true);

    $uibTooltipProvider.setTriggers({
      'show': 'hide'
    });
  }]);
  ngApp.controller('ctrlFrame', ['$scope', '$location', '$uibModal', '$q', 'http2', 'noticebox', function($scope, $location, $uibModal, $q, http2, noticebox) {
    var ls = $location.search(),
      modifiedData = {};

    $scope.id = ls.id;
    $scope.siteId = ls.site;
    $scope.modified = false;
    $scope.submit = function() {
      var defer = $q.defer();
      http2.post('/rest/pl/fe/matter/addressbook/update?site=' + $scope.siteId + '&app=' + $scope.id, modifiedData, function(rsp) {
        $scope.modified = false;
        modifiedData = {};
        noticebox.success('完成保存');
        defer.resolve(rsp.data);
      });
      return defer.promise;
    };
    $scope.update = function(names) {
      angular.isString(names) && (names = [names]);
      angular.forEach(names, function(name) {
        if (['entry_rule'].indexOf(name) !== -1) {
          modifiedData[name] = encodeURIComponent($scope.app[name]);
        } else if (name === 'tags') {
          modifiedData.tags = $scope.app.tags.join(',');
        } else {
          modifiedData[name] = $scope.app[name];
        }
      });
      $scope.modified = true;

      return $scope.submit();
    };
    $scope.remove = function() {
      if (window.confirm('确定删除活动？')) {
        http2.get('/rest/pl/fe/matter/addressbook/remove?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
          if ($scope.app.mission) {
            location = "/rest/pl/fe/matter/mission?site=" + $scope.siteId + "&id=" + $scope.app.mission.id;
          } else {
            location = '/rest/pl/fe/site/console?site=' + $scope.siteId;
          }
        });
      }
    };
    $scope.createPage = function() {
      var deferred = $q.defer();
      $uibModal.open({
        templateUrl: '/views/default/pl/fe/matter/addressbook/component/createPage.html?_=3',
        backdrop: 'static',
        controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
          $scope.options = {};
          $scope.ok = function() {
            $mi.close($scope.options);
          };
          $scope.cancel = function() {
            $mi.dismiss();
          };
        }],
      }).result.then(function(options) {
        http2.post('/rest/pl/fe/matter/addressbook/page/add?site=' + $scope.siteId + '&app=' + $scope.id, options, function(rsp) {
          var page = rsp.data;
          angular.extend(page, pageLib);
          page.arrange();
          $scope.app.pages.push(page);
          deferred.resolve(page);
        });
      });

      return deferred.promise;
    };
    $scope.getApp = function() {
      http2.get('/rest/pl/fe/site/snsList?site=' + $scope.siteId, function(rsp) {
        $scope.sns = rsp.data;
      });
      http2.get('/rest/pl/fe/site/member/schema/list?valid=Y&site=' + $scope.siteId, function(rsp) {
        $scope.memberSchemas = rsp.data;
      });
      http2.get('/rest/pl/fe/matter/addressbook/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
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