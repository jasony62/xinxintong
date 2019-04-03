define(['require'], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'http.ui.xxt', 'notice.ui.xxt', 'service.matter']);
    ngApp.constant('cstApp', {
        matterTypes: [{
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'enroll',
            title: '记录活动',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'signin',
            title: '签到活动',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'link',
            title: '链接',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'mission',
            title: '项目',
            url: '/rest/pl/fe/matter'
        }],
        acceptMatterTypes: [{
            name: '',
            title: '任意'
        }, {
            name: 'article',
            title: '单图文'
        }]
    });
    ngApp.config(['$routeProvider', '$locationProvider', '$controllerProvider', 'srvSiteProvider', 'srvTagProvider', 'srvInviteProvider', function($routeProvider, $locationProvider, $controllerProvider, srvSiteProvider, srvTagProvider, srvInviteProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/pl/fe/matter/channel/');
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
            .when('/rest/pl/fe/matter/channel/matter', new RouteParam('matter'))
            .when('/rest/pl/fe/matter/channel/preview', new RouteParam('preview'))
            .when('/rest/pl/fe/matter/channel/invite', new RouteParam('invite', '/views/default/pl/fe/_module/'))
            .when('/rest/pl/fe/matter/channel/log', new RouteParam('log'))
            .otherwise(new RouteParam('main'));

        $locationProvider.html5Mode(true);
        (function() {
            var siteId = location.search.match(/[\?&]site=([^&]*)/)[1],
                id = location.search.match(/[\?&]id=([^&]*)/)[1];
            srvSiteProvider.config(siteId);
            srvTagProvider.config(siteId);
            srvInviteProvider.config('channel', id);
        })();
    }]);
    ngApp.directive('sortable', function() {
        return {
            link: function(scope, el, attrs) {
                el.sortable({
                    revert: 50
                });
                el.disableSelection();
                el.on("sortdeactivate", function(event, ui) {
                    var from = angular.element(ui.item).scope().$index;
                    var to = el.children('li').index(ui.item);
                    if (to >= 0) {
                        scope.$apply(function() {
                            if (from >= 0) {
                                scope.$emit('my-sorted', {
                                    from: from,
                                    to: to
                                });
                            }
                        });
                    }
                });
            }
        };
    });
    ngApp.controller('ctrlChannel', ['$scope', '$location', 'http2', 'srvSite', function($scope, $location, http2, srvSite) {
        var ls = $location.search();
        $scope.id = ls.id;
        $scope.siteId = ls.site;
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'channel' ? 'main' : subView[1];
            switch ($scope.subView) {
                case 'main':
                case 'matter':
                    $scope.opened = 'edit';
                    break;
                case 'preview':
                case 'invite':
                    $scope.opened = 'publish';
                    break;
                case 'log':
                    $scope.opened = 'other';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function(subView) {
            var url = '/rest/pl/fe/matter/channel/' + subView;
            $location.path(url);
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.tagList().then(function(oTag) {
            $scope.oTag = oTag;
        });
        http2.get('/rest/pl/fe/matter/channel/get?site=' + $scope.siteId + '&id=' + $scope.id).then(function(rsp) {
            if (rsp.data.matter_mg_tag !== '') {
                rsp.data.matter_mg_tag.forEach(function(cTag, index) {
                    $scope.oTag.forEach(function(oTag) {
                        if (oTag.id === cTag) {
                            rsp.data.matter_mg_tag[index] = oTag;
                        }
                    });
                });
            }
            $scope.editing = rsp.data;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});