define(['require'], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'ui.xxt', 'member.xxt', 'service.matter', 'service.article', 'thumbnail.ui.xxt']);
    ngApp.constant('cstApp', {
        innerlink: [{
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'news',
            title: '多图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'channel',
            title: '频道',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'enroll',
            scenario: 'registration',
            title: '报名',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'enroll',
            scenario: 'voting',
            title: '投票',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'signin',
            title: '签到',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'wall',
            title: '信息墙',
            url: '/rest/pl/fe/matter'
        }],
    });
    ngApp.config(['$routeProvider', '$locationProvider', '$controllerProvider', 'srvSiteProvider', 'srvAppProvider', function($routeProvider, $locationProvider, $controllerProvider, srvSiteProvider, srvAppProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/pl/fe/matter/article/');
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
            controller: $controllerProvider.register
        };
        $routeProvider.when('/rest/pl/fe/matter/article/log', new RouteParam('log'))
            .when('/rest/pl/fe/matter/article/coin', new RouteParam('coin'))
            .when('/rest/pl/fe/matter/article/discuss', new RouteParam('discuss', '/views/default/pl/fe/_module/'))
            .otherwise(new RouteParam('main'));

        $locationProvider.html5Mode(true);
        //设置服务参数
        (function() {
            var ls, siteId, articleId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            articleId = ls.match(/[\?&]id=([^&]*)/)[1];
            //
            srvSiteProvider.config(siteId);
            srvAppProvider.setSiteId(siteId);
            srvAppProvider.setAppId(articleId);
        })();
    }]);
    ngApp.controller('ctrlArticle', ['$scope', 'srvSite', 'srvApp', 'tmsThumbnail', function($scope, srvSite, srvApp, tmsThumbnail) {
        $scope.viewNames = {
            'main': '发布预览',
            'coin': '积分规则',
            'log': '运行日志',
        };
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'article' ? 'main' : subView[1];
        });
        $scope.update = function(names) {
            return srvApp.update(names);
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.tagList().then(function(oTag) {
            $scope.oTag = oTag;
        });
        srvSite.tagList('C').then(function(oTag) {
            $scope.oTagC = oTag;
        });
        srvApp.get().then(function(editing) {
            $scope.editing = editing;
            !editing.attachments && (editing.attachments = []);
            $scope.entry = {
                url: editing.entryUrl,
                qrcode: '/rest/site/fe/matter/article/qrcode?site=' + $scope.editing.siteid + '&url=' + encodeURIComponent(editing.entryUrl),
            };
            if ($scope.editing.matter_cont_tag !== '') {
                $scope.editing.matter_cont_tag.forEach(function(cTag, index) {
                    $scope.oTagC.forEach(function(oTag) {
                        if (oTag.id === cTag) {
                            $scope.editing.matter_cont_tag[index] = oTag;
                        }
                    });
                });
            }
            if ($scope.editing.matter_mg_tag !== '') {
                $scope.editing.matter_mg_tag.forEach(function(cTag, index) {
                    $scope.oTag.forEach(function(oTag) {
                        if (oTag.id === cTag) {
                            $scope.editing.matter_mg_tag[index] = oTag;
                        }
                    });
                });
            }
        });
        window.onbeforeunload = function(e) {
            if (!editing.pic && !editing.thumbnail) {
                tmsThumbnail.thumbnail($scope.editing);
            }
        };
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});