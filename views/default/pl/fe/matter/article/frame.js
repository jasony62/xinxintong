define(['require'], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'http.ui.xxt', 'tinymce.ui.xxt', 'ui.xxt', 'member.xxt', 'service.matter', 'service.article', 'thumbnail.ui.xxt']);
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
    ngApp.config(['$routeProvider', '$locationProvider', '$controllerProvider', 'srvSiteProvider', 'srvAppProvider', 'srvTagProvider', function($routeProvider, $locationProvider, $controllerProvider, srvSiteProvider, srvAppProvider, srvTagProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/pl/fe/matter/article/');
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
            .when('/rest/pl/fe/matter/article/body', new RouteParam('body'))
            .when('/rest/pl/fe/matter/article/preview', new RouteParam('preview'))
            .when('/rest/pl/fe/matter/article/coin', new RouteParam('coin'))
            .when('/rest/pl/fe/matter/article/log', new RouteParam('log'))
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
            srvTagProvider.config(siteId);
            srvAppProvider.setSiteId(siteId);
            srvAppProvider.setAppId(articleId);
        })();
    }]);
    ngApp.controller('ctrlArticle', ['$scope', '$location', 'srvSite', 'srvApp', 'tmsThumbnail', function($scope, $location, srvSite, srvApp, tmsThumbnail) {
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'article' ? 'main' : subView[1];
            switch ($scope.subView) {
                case 'main':
                case 'body':
                    $scope.opened = 'edit';
                    break;
                case 'preview':
                    $scope.opened = 'publish';
                    break;
                case 'coin':
                case 'log':
                    $scope.opened = 'other';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function(subView) {
            var url = '/rest/pl/fe/matter/article/' + subView;
            $location.path(url);
        };
        $scope.update = function(names) {
            return srvApp.update(names);
        };
        $scope.editMschema = function(oMschema) {
            if (oMschema.matter_id) {
                if (oMschema.matter_type === 'mission') {
                    location.href = '/rest/pl/fe/matter/mission/mschema?id=' + oMschema.matter_id + '&site=' + $scope.editing.siteid + '#' + oMschema.id;
                } else {
                    location.href = '/rest/pl/fe/site/mschema?site=' + $scope.editing.siteid + '#' + oMschema.id;
                }
            } else {
                location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.editing.siteid + '&mschema=' + oMschema.id;
            }
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
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsNames = Object.keys(oSns);
            $scope.snsCount = Object.keys(oSns).length;
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
                srvSite.memberSchemaList($scope.editing).then(function(aMemberSchemas) {
                    $scope.memberSchemas = aMemberSchemas;
                    $scope.mschemasById = {};
                    $scope.memberSchemas.forEach(function(mschema) {
                        $scope.mschemasById[mschema.id] = mschema;
                    });
                });
            });
        });
        window.onbeforeunload = function(e) {
            if (!$scope.editing.pic && !$scope.editing.thumbnail) {
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