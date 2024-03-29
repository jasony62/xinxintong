define(['frame/RouteParam', 'frame/const', 'frame/templates'], function (
  RouteParam,
  CstApp,
  frameTemplates
) {
  'use strict'
  var ngApp = angular.module('app', [
    'ngRoute',
    'ui.tms',
    'ui.xxt',
    'http.ui.xxt',
    'notice.ui.xxt',
    'notice.ui.xxt',
    'schema.ui.xxt',
    'service.matter',
    'service.group',
    'protect.ui.xxt',
  ])
  ngApp.constant('cstApp', CstApp)
  ngApp.config([
    '$controllerProvider',
    '$routeProvider',
    '$locationProvider',
    '$compileProvider',
    '$uibTooltipProvider',
    'srvQuickEntryProvider',
    'srvSiteProvider',
    'srvGroupAppProvider',
    'srvGroupTeamProvider',
    'srvTagProvider',
    function (
      $controllerProvider,
      $routeProvider,
      $locationProvider,
      $compileProvider,
      $uibTooltipProvider,
      srvQuickEntryProvider,
      srvSiteProvider,
      srvGroupAppProvider,
      srvGroupTeamProvider,
      srvTagProvider
    ) {
      ngApp.provider = {
        controller: $controllerProvider.register,
        directive: $compileProvider.directive,
      }
      $routeProvider
        .when('/rest/pl/fe/matter/group/main', new RouteParam('main'))
        .when('/rest/pl/fe/matter/group/team', new RouteParam('team'))
        .when('/rest/pl/fe/matter/group/record', new RouteParam('record'))
        .when('/rest/pl/fe/matter/group/leave', new RouteParam('leave'))
        .when('/rest/pl/fe/matter/group/notice', new RouteParam('notice'))
        .otherwise(new RouteParam('record'))

      $locationProvider.html5Mode(true)
      $uibTooltipProvider.setTriggers({
        show: 'hide',
      })
      //设置服务参数
      ;(function () {
        var ls, siteId, appId
        ls = location.search
        siteId = ls.match(/[\?&]site=([^&]*)/)[1]
        appId = ls.match(/[\?&]id=([^&]*)/)[1]
        //
        srvSiteProvider.config(siteId)
        srvTagProvider.config(siteId)
        srvGroupAppProvider.config(siteId, appId)
        srvGroupTeamProvider.config(siteId, appId)
        srvQuickEntryProvider.setSiteId(siteId)
      })()
    },
  ])
  ngApp.factory('$exceptionHandler', function () {
    return function (exception, cause) {
      exception.message += ' (caused by "' + cause + '")'
      throw exception
    }
  })
  ngApp.controller('ctrlApp', [
    '$scope',
    'cstApp',
    'srvSite',
    'srvGroupApp',
    '$location',
    function ($scope, cstApp, srvSite, srvGroupApp, $location) {
      $scope.cstApp = cstApp
      $scope.frameTemplates = frameTemplates
      $scope.opened = ''
      $scope.$on('$locationChangeSuccess', function (event, currentRoute) {
        var subView = currentRoute.match(/([^\/]+?)\?/)
        $scope.subView = subView[1] === 'group' ? 'user' : subView[1]
        switch ($scope.subView) {
          case 'main':
          case 'team':
            $scope.opened = 'edit'
            break
          case 'record':
          case 'leave':
            $scope.opened = 'data'
            break
          case 'notice':
            $scope.opened = 'other'
            break
          default:
            $scope.opened = ''
        }
      })
      $scope.switchTo = function (subView) {
        var url = '/rest/pl/fe/matter/group/' + subView
        $location.path(url)
      }
      srvSite.get().then(function (oSite) {
        $scope.site = oSite
      })
      srvSite.tagList().then(function (oTag) {
        $scope.oTag = oTag
        srvGroupApp.get().then(function (oApp) {
          if (oApp.matter_mg_tag !== '') {
            oApp.matter_mg_tag.forEach(function (cTag, index) {
              $scope.oTag.forEach(function (oTag) {
                if (oTag.id === cTag) {
                  oApp.matter_mg_tag[index] = oTag
                }
              })
            })
          }
          $scope.app = oApp
        })
      })
      $scope.assocWithApp = function () {
        srvGroupApp.assocWithApp(cstApp.importSource).then(function () {})
      }
      $scope.cancelSourceApp = function () {
        srvGroupApp.cancelSourceApp()
      }
      $scope.gotoSourceApp = function () {
        var oSourceApp
        if ($scope.app.sourceApp) {
          oSourceApp = $scope.app.sourceApp
          switch (oSourceApp.type) {
            case 'enroll':
              location.href =
                '/rest/pl/fe/matter/enroll?site=' +
                oSourceApp.siteid +
                '&id=' +
                oSourceApp.id
              break
            case 'signin':
              location.href =
                '/rest/pl/fe/matter/signin?site=' +
                oSourceApp.siteid +
                '&id=' +
                oSourceApp.id
              break
            case 'mschema':
              location.href =
                '/rest/pl/fe/site/mschema?site=' +
                oSourceApp.siteid +
                '#' +
                oSourceApp.id
              break
          }
        }
      }
    },
  ])
  /***/
  require(['domReady!'], function (document) {
    angular.bootstrap(document, ['app'])
  })
  /***/
  return ngApp
})
