define(['require', 'mschemaService'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter', 'service.mschema']);
    ngApp.constant('CstApp', {
        alertMsg: {
            'schema.duplicated': '不允许重复添加登记项',
        },
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', 'srvSiteProvider', 'srvMschemaProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, srvSiteProvider, srvMschemaProvider) {
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
            .otherwise(new RouteParam('main'));

        var siteId = location.search.match(/site=([^&]*)/)[1];
        srvSiteProvider.config(siteId);
        srvMschemaProvider.config(siteId);
    }]);
    ngApp.factory('MemberSchema', function($q, http2) {
        var MemberSchema = function(siteId) {
            this.siteId = siteId;
            this.baseUrl = '/rest/pl/fe/site/member/schema/';
        };
        MemberSchema.prototype.get = function(mschemaId) {
            var deferred, url;
            deferred = $q.defer();
            url = this.baseUrl;
            url += 'get?site=' + this.siteId;
            url += '&mschema=' + mschemaId;
            http2.get(url, function(rsp) {
                var oMschema, bWhole;
                bWhole = true;
                oMschema = rsp.data;
                if (oMschema.matter_type) {
                    if (oMschema.matter_type === 'mission' && oMschema.matter_id) {
                        bWhole = false;
                        http2.get('/rest/pl/fe/matter/mission/get?id=' + oMschema.matter_id + '&cascaded=N', function(rsp) {
                            oMschema.mission = rsp.data;
                            deferred.resolve(oMschema);
                        });
                    }
                }
                bWhole && deferred.resolve(oMschema);
            });
            return deferred.promise;
        };
        MemberSchema.prototype.list = function(own) {
            var deferred, url;
            deferred = $q.defer();
            own === undefined && (own === 'N');
            url = this.baseUrl;
            url += 'list?site=' + this.siteId;
            url += '&own=' + own;
            http2.get(url, function(rsp) {
                deferred.resolve(rsp.data);
            });
            return deferred.promise;
        };
        MemberSchema.prototype.update = function(oSchema, updated) {
            var deferred, url;
            deferred = $q.defer();
            url = this.baseUrl;
            url += 'update?site=' + this.siteId;
            url += '&type=' + oSchema.type;
            if (oSchema.id) url += '&id=' + oSchema.id;
            http2.post(url, updated, function(rsp) {
                deferred.resolve(rsp.data);
            });
            return deferred.promise;
        };
        return MemberSchema;
    });
    ngApp.controller('ctrlMschema', ['$scope', function($scope) {

    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});