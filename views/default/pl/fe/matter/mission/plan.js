define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter']);
    ngApp.config(['$locationProvider', 'srvSiteProvider', function($locationProvider, srvSiteProvider) {
        $locationProvider.html5Mode(true);
        var ls, siteId;
        ls = location.search;
        siteId = ls.match(/[\?&]site=([^&]*)/)[1];
        //
        srvSiteProvider.config(siteId);
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$location', 'srvSite', 'mediagallery', 'http2', function($scope, $location, srvSite, mediagallery, http2) {
        var oProto, oEntryRule;
        oEntryRule = { scope: 'none' };
        $scope.proto = oProto = {
            entryRule: oEntryRule,
            app: { group: { source: '' } },
            userApp: ''
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    oProto.pic = url + '?_=' + (new Date * 1);
                }
            };
            mediagallery.open($scope.site.id, options);
        };
        $scope.removePic = function() {
            oProto.pic = '';
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            var prop;
            if (data.state.indexOf('proto.') === 0) {
                prop = data.state.substr(8);
                oProto[prop] = data.value;
            }
        });
        $scope.chooseMschema = function() {
            srvSite.chooseMschema().then(function(result) {
                var chosen;
                if (result && result.chosen) {
                    chosen = result.chosen;
                    !oEntryRule.mschemas && (oEntryRule.mschemas = []);
                    oEntryRule.mschemas.push({ id: chosen.id, title: chosen.title });
                }
            });
        };
        $scope.removeMschema = function(oMschema) {
            oEntryRule.mschemas.splice(oEntryRule.mschemas.indexOf(oMschema), 1);
        };
        $scope.doCreate = function() {
            http2.post('/rest/pl/fe/matter/mission/create?site=' + $scope.site.id, oProto, function(rsp) {
                location.href = '/rest/pl/fe/matter/mission?site=' + $scope.site.id + '&id=' + rsp.data.id;
            });
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
            oProto.pic = oSite.heading_pic;
        });
        srvSite.getLoginUser().then(function(oUser) {
            $scope.loginUser = oUser;
            oProto.title = oUser.nickname + '的项目';
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsCount = Object.keys(oSns).length;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});