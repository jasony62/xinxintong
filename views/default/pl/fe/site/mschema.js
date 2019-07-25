define(['require', 'mschemaService'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'http.ui.xxt', 'tinymce.ui.xxt', 'notice.ui.xxt', 'schema.ui.xxt', 'service.matter', 'service.mschema', 'protect.ui.xxt']);
    ngApp.constant('CstApp', {
        alertMsg: {
            'schema.duplicated': '不允许重复添加登记项',
        },
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', 'srvSiteProvider', 'srvMschemaProvider', 'srvMschemaNoticeProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, srvSiteProvider, srvMschemaProvider, srvMschemaNoticeProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/pl/fe/site/mschema/');
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
        $locationProvider.html5Mode(true);
        ngApp.provider = {
            controller: $controllerProvider.register,
            directive: $compileProvider.directive
        };
        $routeProvider
            .when('/rest/pl/fe/site/mschema/main', new RouteParam('main'))
            .when('/rest/pl/fe/site/mschema/extattr', new RouteParam('extattr'))
            .when('/rest/pl/fe/site/mschema/notice', new RouteParam('notice'))
            .otherwise(new RouteParam('main'));

        var siteId = location.search.match(/site=([^&]*)/)[1];
        srvSiteProvider.config(siteId);
        srvMschemaProvider.config(siteId);
    }]);
    ngApp.controller('ctrlMschema', ['$scope', '$location', 'http2', 'srvSite', 'srvMschema', function($scope, $location, http2, srvSite, srvMschema) {
        function shiftAttr(oSchema) {
            oSchema.attrs = {
                mobile: oSchema.attr_mobile.split(''),
                email: oSchema.attr_email.split(''),
                name: oSchema.attr_name.split('')
            };
        }
        $scope.schemas = [];
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'mschema' ? 'main' : subView[1];
            switch ($scope.subView) {
                case 'main':
                    $scope.opened = 'edit';
                    break;
                case 'notice':
                    $scope.opened = 'other';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function(subView, hash) {
            var url = '/rest/pl/fe/site/mschema/' + subView;
            $location.path(url).hash(hash || '');
        };
        $scope.chooseSchema = function(oSchema) {
            $scope.choosedSchema = oSchema;
        };
        $scope.addSchema = function() {
            var url = '/rest/pl/fe/site/member/schema/create?site=' + $scope.site.id;
            http2.post(url, {}).then(function(rsp) {
                shiftAttr(rsp.data);
                $scope.schemas.push(rsp.data);
            });
        };
        $scope.delSchema = function() {
            var url, schema;
            if (window.confirm('确认删除通讯录？')) {
                schema = $scope.choosedSchema;
                url = '/rest/pl/fe/site/member/schema/delete?site=' + $scope.site.id + '&id=' + schema.id;
                http2.get(url).then(function(rsp) {
                    var i = $scope.schemas.indexOf(schema);
                    $scope.schemas.splice(i, 1);
                    $scope.choosedSchema = null;
                });
            }
        };
        srvSite.get().then(function(oSite) {
            var entryMschemaId;
            $scope.site = oSite;
            srvSite.snsList().then(function(data) {
                $scope.sns = data;
            });
            if (location.hash) {
                $scope.entryMschemaId = entryMschemaId = location.hash.substr(1);
                srvMschema.get(entryMschemaId).then(function(oMschema) {
                    shiftAttr(oMschema);
                    $scope.schemas = [oMschema];
                    $scope.chooseSchema(oMschema);
                });
                $scope.bOnlyone = true;
            } else {
                srvMschema.list('N').then(function(schemas) {
                    schemas.forEach(function(schema) {
                        shiftAttr(schema);
                        $scope.schemas.push(schema);
                    });
                    if ($scope.schemas.length === 0) {
                        $scope.schemas.push({
                            type: 'inner',
                            valid: 'N',
                            attrs: {
                                mobile: ['0', '0', '0', '0', '0', '0', '0'],
                                email: ['0', '0', '0', '0', '0', '0', '0'],
                                name: ['0', '0', '0', '0', '0', '0', '0']
                            }
                        });
                    }
                    $scope.chooseSchema(schemas[0]);
                });
                $scope.bOnlyone = false;
            }
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});