define(['require'], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter']);
    ngApp.constant('cstApp', {
        matterTypes: [{
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'enroll',
            title: '登记活动',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'signin',
            title: '签到活动',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'lottery',
            title: '抽奖活动',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'wall',
            title: '信息墙',
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
        }, {
            name: 'contribute',
            title: '投稿活动'
        }]
    });
    ngApp.config(['$routeProvider', '$locationProvider', '$controllerProvider', 'srvSiteProvider', 'srvTagProvider', function($routeProvider, $locationProvider, $controllerProvider, srvSiteProvider, srvTagProvider) {
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
            .otherwise(new RouteParam('main'));
        $locationProvider.html5Mode(true);
        (function() {
            var siteId = location.search.match(/[\?&]site=([^&]*)/)[1];
            srvSiteProvider.config(siteId);
            srvTagProvider.config(siteId);
            $locationProvider.html5Mode(true);
        })();
    }]);
    ngApp.controller('ctrlChannel', ['$scope', '$location', 'http2', 'srvSite', function($scope, $location, http2, srvSite) {
        var ls = $location.search();
        $scope.id = ls.id;
        $scope.siteId = ls.site;
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.tagList().then(function(oTag) {
            $scope.oTag = oTag;
        });
        http2.get('/rest/pl/fe/matter/channel/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
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